<?php

/**
 * Comment-to-DM auto replies for Instagram Business/Creator accounts, via the Meta Graph API.
 *
 * Uses the same Page access token the publishing flow stores on the social account. Extra
 * permissions are required beyond publishing: instagram_manage_comments (read comments with
 * their `from` user), instagram_manage_messages + pages_manage_metadata (private replies and
 * DMs). Existing clients must reconnect their Instagram account after these scopes are added.
 *
 * Platform constraints that shape the flow here:
 *  - A comment can receive exactly ONE private reply, within 7 days of the comment.
 *  - Free-form DMs are only allowed inside the 24h window after the user messages the account.
 * So for follow-gated rules, the single private reply is the "follow me, then send me any
 * message" prompt; once the user DMs back (opening the 24h window) we verify the follow via
 * the User Profile API and send the real message as a normal DM.
 */
class InstagramMessagingService
{
    private const API = 'https://graph.facebook.com/v19.0';

    /** Latest comments on a media, oldest first: id, text, username, from{id,username}, timestamp. */
    public static function fetchComments(string $mediaId, array $account, int $limit = 50): array
    {
        $token = SocialAccount::accessToken($account);
        $response = Http::request('GET', self::API . "/$mediaId/comments?" . http_build_query([
            'fields' => 'id,text,timestamp,username,from',
            'limit' => $limit,
            'access_token' => $token,
        ]));

        if ($response['status'] !== 200 || !isset($response['json']['data'])) {
            throw new RuntimeException('Instagram comments fetch failed: ' . $response['body']);
        }

        $comments = $response['json']['data'];
        usort($comments, fn ($a, $b) => strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? ''));
        return $comments;
    }

    /** The one-shot private reply to a comment (opens a DM thread with the commenter). */
    public static function sendPrivateReply(array $account, string $commentId, string $text): void
    {
        self::sendMessage($account, ['comment_id' => $commentId], $text);
    }

    /** Free-form DM to a user — only valid inside the 24h window after they message us. */
    public static function sendDirectMessage(array $account, string $igsid, string $text): void
    {
        self::sendMessage($account, ['id' => $igsid], $text);
    }

    private static function sendMessage(array $account, array $recipient, string $text): void
    {
        $pageId = SocialAccount::meta($account)['facebook_page_id'] ?? null;
        if (!$pageId) {
            throw new RuntimeException('Instagram account is missing its linked Facebook Page id — reconnect the account.');
        }

        $response = Http::request('POST', self::API . "/$pageId/messages", [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'recipient' => $recipient,
                'message' => ['text' => mb_substr($text, 0, 1000)],
                'access_token' => SocialAccount::accessToken($account),
            ],
        ]);

        if ($response['status'] !== 200) {
            throw new RuntimeException('Instagram message send failed: ' . $response['body']);
        }
    }

    /**
     * Whether the user follows the connected account, via the Messenger User Profile API's
     * is_user_follow_business field. Returns null when Meta won't tell us (e.g. the user has
     * never messaged the account yet) so callers can fall back to the follow-prompt flow.
     */
    public static function isUserFollowing(array $account, string $igsid): ?bool
    {
        $response = Http::request('GET', self::API . "/$igsid?" . http_build_query([
            'fields' => 'is_user_follow_business',
            'access_token' => SocialAccount::accessToken($account),
        ]));

        if ($response['status'] !== 200 || !array_key_exists('is_user_follow_business', $response['json'] ?? [])) {
            return null;
        }
        return (bool) $response['json']['is_user_follow_business'];
    }

    /**
     * Timestamp (unix) of the newest DM the user sent us, or null if none/unreadable.
     * Used to detect "the commenter messaged back after our follow prompt".
     */
    public static function lastInboundMessageAt(array $account, string $igsid): ?int
    {
        $pageId = SocialAccount::meta($account)['facebook_page_id'] ?? null;
        if (!$pageId) {
            return null;
        }
        $token = SocialAccount::accessToken($account);

        $response = Http::request('GET', self::API . "/$pageId/conversations?" . http_build_query([
            'platform' => 'instagram',
            'user_id' => $igsid,
            'fields' => 'messages.limit(10){from,created_time}',
            'access_token' => $token,
        ]));

        $messages = $response['json']['data'][0]['messages']['data'] ?? [];
        foreach ($messages as $message) { // newest first per Graph API default ordering
            if (($message['from']['id'] ?? '') === $igsid) {
                $ts = strtotime($message['created_time'] ?? '');
                return $ts !== false ? $ts : null;
            }
        }
        return null;
    }
}
