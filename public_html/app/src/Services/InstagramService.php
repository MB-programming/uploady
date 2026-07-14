<?php

/**
 * Publishes as an Instagram Reel via the Meta Graph API. Unlike YouTube/TikTok, Instagram's
 * container API does not accept a direct file upload — it fetches the video itself from a
 * public URL we hand it, so we point it at media/serve.php (gated by the post's public_token)
 * for the short window between container creation and Instagram finishing the download.
 */
class InstagramService
{
    public static function publish(array $target, array $account): void
    {
        $session = PostTarget::uploadSession($target);
        $pageAccessToken = SocialAccount::accessToken($account); // Page tokens don't expire via refresh_token

        if (empty($session['phase'])) {
            self::createContainer($target, $account, $pageAccessToken);
            return;
        }

        if ($session['phase'] === 'container_processing') {
            self::pollContainer($target, $account, $session, $pageAccessToken);
        }
    }

    private static function createContainer(array $target, array $account, string $pageAccessToken): void
    {
        $igUserId = $account['platform_account_id'];
        $publicVideoUrl = App::url('media/serve.php?token=' . $target['public_token']);
        $caption = mb_substr(trim($target['title'] . "\n\n" . $target['description'] . "\n" . self::hashtags($target['tags'])), 0, 2200);

        $response = Http::request('POST', "https://graph.facebook.com/v19.0/$igUserId/media", [
            'form' => [
                'media_type' => 'REELS',
                'video_url' => $publicVideoUrl,
                'caption' => $caption,
                'share_to_feed' => 'true',
                'access_token' => $pageAccessToken,
            ],
        ]);

        if ($response['status'] !== 200 || empty($response['json']['id'])) {
            throw new RuntimeException('Instagram container creation failed: ' . $response['body']);
        }

        PostTarget::saveUploadSession($target['id'], [
            'phase' => 'container_processing',
            'container_id' => $response['json']['id'],
        ]);
    }

    private static function pollContainer(array $target, array $account, array $session, string $pageAccessToken): void
    {
        $containerId = $session['container_id'];

        $status = Http::request('GET', "https://graph.facebook.com/v19.0/$containerId?" . http_build_query([
            'fields' => 'status_code,status',
            'access_token' => $pageAccessToken,
        ]));

        $statusCode = $status['json']['status_code'] ?? null;

        if ($statusCode === 'ERROR' || $statusCode === 'EXPIRED') {
            $detail = $status['json']['status'] ?? 'unknown error';
            throw new RuntimeException("Instagram container failed: $detail");
        }

        if ($statusCode !== 'FINISHED') {
            return; // still IN_PROGRESS — check again on the next cron tick
        }

        $igUserId = $account['platform_account_id'];
        $publishResponse = Http::request('POST', "https://graph.facebook.com/v19.0/$igUserId/media_publish", [
            'form' => [
                'creation_id' => $containerId,
                'access_token' => $pageAccessToken,
            ],
        ]);

        if ($publishResponse['status'] !== 200 || empty($publishResponse['json']['id'])) {
            throw new RuntimeException('Instagram media_publish failed: ' . $publishResponse['body']);
        }

        $mediaId = $publishResponse['json']['id'];

        $permalinkResponse = Http::request('GET', "https://graph.facebook.com/v19.0/$mediaId?" . http_build_query([
            'fields' => 'permalink',
            'access_token' => $pageAccessToken,
        ]));
        $permalink = $permalinkResponse['json']['permalink'] ?? null;

        PostTarget::markPublished($target['id'], $mediaId, $permalink);
    }

    private static function hashtags(string $tags): string
    {
        $parts = array_filter(array_map('trim', explode(',', $tags)));
        return implode(' ', array_map(fn ($t) => '#' . preg_replace('/\s+/', '', $t), $parts));
    }
}
