<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

const MAX_ATTEMPTS = 20; // ~ generous retry budget across cron ticks before giving up on a target

// Hostinger's Cron Jobs UI can run this two ways: directly via PHP CLI (preferred — set it up as
// "php /home/USER/domains/your-domain.com/cron/publish.php"), or by hitting a URL if only that's
// available on your plan. The URL path must present the shared cron_secret so randoms can't spam it.
if (PHP_SAPI !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    if (!hash_equals(App::config('cron_secret'), (string) $secret)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// Prevent two overlapping runs if a previous invocation is still working through a slow upload.
$lockHandle = fopen(App::storagePath('logs/publish.lock'), 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Another publish run is already in progress.\n";
    exit;
}

function platformServiceClass(string $platform): string
{
    return match ($platform) {
        'youtube', 'youtube_shorts' => YouTubeService::class,
        'tiktok' => TikTokService::class,
        'instagram' => InstagramService::class,
        default => throw new RuntimeException("Unknown platform: $platform"),
    };
}

function finalizePostIfDone(int $postId): void
{
    $remaining = PostTarget::pendingForPost($postId);
    if ($remaining !== []) {
        return;
    }

    $post = Post::find($postId);
    $targets = PostTarget::forPost($postId);
    $publishedCount = count(array_filter($targets, fn ($t) => $t['status'] === 'published'));
    $failedCount = count(array_filter($targets, fn ($t) => $t['status'] === 'failed'));

    if ($publishedCount > 0 && $failedCount === 0) {
        $overallStatus = 'published';
    } elseif ($publishedCount > 0 && $failedCount > 0) {
        $overallStatus = 'partially_published';
    } else {
        $overallStatus = 'failed';
    }
    Post::updateStatus($postId, $overallStatus);

    // The whole point of doing this on shared hosting: don't let uploaded videos pile up on disk.
    if ($post['video_path'] && is_file($post['video_path'])) {
        @unlink($post['video_path']);
    }
    Post::markVideoDeleted($postId);
    echo "Post $postId: finalized as $overallStatus, video file removed.\n";
}

$dueTargets = PostTarget::due();
echo count($dueTargets) . " due target(s) found.\n";

$touchedPostIds = [];
$startedPosts = [];

foreach ($dueTargets as $target) {
    $postId = (int) $target['post_id'];
    $touchedPostIds[$postId] = true;

    if (!isset($startedPosts[$postId])) {
        Post::updateStatus($postId, 'processing');
        $startedPosts[$postId] = true;
    }

    if ($target['attempts'] >= MAX_ATTEMPTS) {
        PostTarget::markFailed($target['id'], 'Exceeded maximum retry attempts');
        continue;
    }

    PostTarget::markUploading($target['id']);

    try {
        $account = SocialAccount::find((int) $target['social_account_id']);
        if (!$account) {
            throw new RuntimeException('Connected social account no longer exists.');
        }

        $serviceClass = platformServiceClass($target['platform']);
        $serviceClass::publish($target, $account);

        echo "Post $postId / target {$target['id']} ({$target['platform']}): step completed.\n";
    } catch (Throwable $e) {
        error_log("[cron/publish] post=$postId target={$target['id']} platform={$target['platform']}: " . $e->getMessage());
        $target['attempts']++; // markUploading already incremented in DB; mirror it locally for this check
        if ($target['attempts'] >= MAX_ATTEMPTS) {
            PostTarget::markFailed($target['id'], $e->getMessage());
        } else {
            PostTarget::recordError($target['id'], $e->getMessage());
        }
        echo "Post $postId / target {$target['id']}: error — " . $e->getMessage() . "\n";
    }
}

foreach (array_keys($touchedPostIds) as $postId) {
    finalizePostIfDone($postId);
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);
