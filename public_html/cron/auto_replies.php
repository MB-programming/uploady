<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

// How long we keep waiting for a prompted commenter to follow before giving up. Meta's
// private-reply window is 7 days from the comment, so there's no point waiting longer.
const AWAITING_FOLLOW_TTL_DAYS = 7;

// Same dual entry point as cron/publish.php: PHP CLI directly, or via URL with the cron_secret.
if (PHP_SAPI !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    if (!hash_equals(App::config('cron_secret'), (string) $secret)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$lockHandle = fopen(App::storagePath('logs/auto_replies.lock'), 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Another auto-reply run is already in progress.\n";
    exit;
}

$accountCache = [];
$loadAccount = function (int $accountId) use (&$accountCache): ?array {
    return $accountCache[$accountId] ??= SocialAccount::find($accountId);
};

// ---- Phase 1: poll comments on watched videos and answer new matching ones -----------------

$rules = AutoReplyRule::activeWithTargets();
echo count($rules) . " active auto-reply rule(s).\n";

foreach ($rules as $rule) {
    $account = $loadAccount((int) $rule['social_account_id']);
    if (!$account) {
        continue; // account disconnected; rule is effectively dormant
    }

    try {
        $comments = InstagramMessagingService::fetchComments((string) $rule['media_id'], $account);
    } catch (Throwable $e) {
        error_log("[cron/auto_replies] rule={$rule['id']} comments fetch: " . $e->getMessage());
        continue;
    }

    $handled = array_flip(AutoReplyEvent::handledCommentIds((int) $rule['id']));

    foreach ($comments as $comment) {
        $commentId = (string) ($comment['id'] ?? '');
        if ($commentId === '' || isset($handled[$commentId])) {
            continue;
        }

        $commenterId = $comment['from']['id'] ?? null;
        $commenterUsername = $comment['from']['username'] ?? ($comment['username'] ?? null);

        // Never auto-reply to the account's own comments (e.g. the owner pinning a comment).
        if ($commenterId !== null && (string) $commenterId === (string) $account['platform_account_id']) {
            continue;
        }
        if (!AutoReplyRule::commentMatches($rule, (string) ($comment['text'] ?? ''))) {
            continue;
        }

        try {
            if (!$rule['require_follow']) {
                InstagramMessagingService::sendPrivateReply($account, $commentId, $rule['dm_message']);
                AutoReplyEvent::create((int) $rule['id'], $commentId, $commenterId, $commenterUsername, 'completed');
                echo "Rule {$rule['id']}: DM sent to @{$commenterUsername} for comment $commentId.\n";
                continue;
            }

            // Follow-gated: if Meta already knows they follow, skip the prompt and deliver directly.
            $following = $commenterId !== null
                ? InstagramMessagingService::isUserFollowing($account, (string) $commenterId)
                : null;

            if ($following === true) {
                InstagramMessagingService::sendPrivateReply($account, $commentId, $rule['dm_message']);
                AutoReplyEvent::create((int) $rule['id'], $commentId, $commenterId, $commenterUsername, 'completed');
                echo "Rule {$rule['id']}: follower @{$commenterUsername} got the message directly.\n";
            } else {
                InstagramMessagingService::sendPrivateReply($account, $commentId, $rule['follow_prompt'] ?: $rule['dm_message']);
                AutoReplyEvent::create((int) $rule['id'], $commentId, $commenterId, $commenterUsername, 'awaiting_follow');
                echo "Rule {$rule['id']}: follow prompt sent to @{$commenterUsername}.\n";
            }
        } catch (Throwable $e) {
            error_log("[cron/auto_replies] rule={$rule['id']} comment=$commentId: " . $e->getMessage());
            AutoReplyEvent::create((int) $rule['id'], $commentId, $commenterId, $commenterUsername, 'failed', $e->getMessage());
        }
    }
}

// ---- Phase 2: re-check prompted commenters — did they DM back, and do they follow now? -----

foreach (AutoReplyEvent::awaitingFollow() as $event) {
    if (strtotime($event['created_at']) < time() - AWAITING_FOLLOW_TTL_DAYS * 86400) {
        AutoReplyEvent::markExpired((int) $event['id']);
        continue;
    }
    if (empty($event['commenter_id'])) {
        continue; // can't address a DM without the commenter's IGSID; wait until expiry
    }

    $account = $loadAccount((int) $event['social_account_id']);
    if (!$account) {
        continue;
    }

    try {
        // Only act when the user sent us something new since our last prompt — that both
        // signals "check me now" and (re)opens the 24h window we need to DM them.
        $lastInbound = InstagramMessagingService::lastInboundMessageAt($account, (string) $event['commenter_id']);
        if ($lastInbound === null || $lastInbound <= strtotime((string) $event['prompt_sent_at'])) {
            continue;
        }

        $following = InstagramMessagingService::isUserFollowing($account, (string) $event['commenter_id']);

        if ($following === false) {
            // Still not following: nudge once per inbound message, never twice in a row.
            InstagramMessagingService::sendDirectMessage($account, (string) $event['commenter_id'], $event['follow_prompt'] ?: $event['dm_message']);
            AutoReplyEvent::touchPromptSent((int) $event['id']);
            echo "Event {$event['id']}: @{$event['commenter_username']} replied but still doesn't follow — re-prompted.\n";
            continue;
        }

        // Following (or Meta won't say — give them the benefit of the doubt rather than loop forever).
        InstagramMessagingService::sendDirectMessage($account, (string) $event['commenter_id'], $event['dm_message']);
        AutoReplyEvent::markCompleted((int) $event['id']);
        echo "Event {$event['id']}: @{$event['commenter_username']} qualified — message delivered.\n";
    } catch (Throwable $e) {
        error_log("[cron/auto_replies] event={$event['id']}: " . $e->getMessage());
        AutoReplyEvent::markFailed((int) $event['id'], $e->getMessage());
    }
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);
