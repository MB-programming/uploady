<?php

/**
 * Publishes via the TikTok Content Posting API (v2). TikTok's flow is async: we init the
 * upload, hand over the file, then poll a status endpoint — which fits naturally with a
 * cron worker that ticks every few minutes instead of holding one long HTTP request open.
 */
class TikTokService
{
    public static function publish(array $target, array $account): void
    {
        $session = PostTarget::uploadSession($target);
        $accessToken = self::ensureFreshToken($account);

        if (empty($session['phase'])) {
            self::initAndUpload($target, $accessToken);
            return;
        }

        // A previous tick initialized the session but the process was killed (shared-hosting
        // cron `timeout`) before the file transfer finished. Retry the SAME publish_id/upload
        // URL instead of init-ing a new one — re-initializing on every tick is what creates
        // duplicate metadata-only drafts on the platform.
        if ($session['phase'] === 'uploading') {
            self::resumeUpload($target, $session);
            return;
        }

        if ($session['phase'] === 'processing') {
            self::pollStatus($target, $session, $accessToken);
        }
    }

    private static function initAndUpload(array $target, string $accessToken): void
    {
        $privacyLevel = self::pickPrivacyLevel($target, $accessToken);
        $videoPath = $target['video_path'];
        $fileSize = filesize($videoPath);

        $initResponse = Http::request('POST', 'https://open.tiktokapis.com/v2/post/publish/video/init/', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'json' => [
                'post_info' => [
                    'title' => mb_substr($target['title'] . ' ' . self::hashtags($target['tags']), 0, 2200),
                    'privacy_level' => $privacyLevel,
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                ],
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $fileSize,
                    'chunk_size' => $fileSize, // uploaded as a single chunk
                    'total_chunk_count' => 1,
                ],
            ],
        ]);

        $data = $initResponse['json']['data'] ?? null;
        if ($initResponse['status'] !== 200 || !$data || empty($data['publish_id']) || empty($data['upload_url'])) {
            throw new RuntimeException('TikTok publish init failed: ' . $initResponse['body']);
        }

        // Persist the session BEFORE moving any bytes, so a process killed mid-upload resumes
        // this publish_id on the next tick instead of creating a duplicate one.
        PostTarget::saveUploadSession($target['id'], [
            'phase' => 'uploading',
            'publish_id' => $data['publish_id'],
            'upload_url' => $data['upload_url'],
        ]);

        self::uploadFile($target['id'], $videoPath, $data['upload_url'], (string) $data['publish_id']);
    }

    private static function resumeUpload(array $target, array $session): void
    {
        try {
            self::uploadFile((int) $target['id'], $target['video_path'], $session['upload_url'], (string) $session['publish_id']);
        } catch (Throwable $e) {
            // TikTok upload URLs expire after ~1 hour. An expired never-completed upload leaves
            // nothing visible on TikTok, so clearing the session and re-initializing next tick
            // is safe — unlike blindly re-initializing on EVERY tick, which piles up drafts.
            PostTarget::saveUploadSession((int) $target['id'], []);
            throw $e;
        }
    }

    private static function uploadFile(int $targetId, string $videoPath, string $uploadUrl, string $publishId): void
    {
        $fileSize = filesize($videoPath);
        $uploadResponse = Http::putFile($uploadUrl, $videoPath, [
            'Content-Type' => 'video/mp4',
            'Content-Range' => "bytes 0-" . ($fileSize - 1) . "/$fileSize",
        ]);

        if ($uploadResponse['status'] >= 300) {
            throw new RuntimeException('TikTok video upload failed: ' . $uploadResponse['body']);
        }

        PostTarget::saveUploadSession($targetId, [
            'phase' => 'processing',
            'publish_id' => $publishId,
        ]);
    }

    private static function pollStatus(array $target, array $session, string $accessToken): void
    {
        $response = Http::request('POST', 'https://open.tiktokapis.com/v2/post/publish/status/fetch/', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'json' => ['publish_id' => $session['publish_id']],
        ]);

        $status = $response['json']['data']['status'] ?? null;

        if ($status === 'PUBLISH_COMPLETE') {
            $publiclyAvailablePostId = $response['json']['data']['publicaly_available_post_id'][0]
                ?? $response['json']['data']['publicly_available_post_id'][0]
                ?? null;
            PostTarget::markPublished(
                $target['id'],
                (string) $session['publish_id'],
                $publiclyAvailablePostId ? "https://www.tiktok.com/@/video/$publiclyAvailablePostId" : null
            );
            return;
        }

        if ($status === 'FAILED') {
            $reason = $response['json']['data']['fail_reason'] ?? 'unknown';
            throw new RuntimeException("TikTok publish failed: $reason");
        }

        // Still PROCESSING_UPLOAD / PROCESSING_DOWNLOAD — leave status as "uploading" and
        // check again on the next cron tick.
    }

    private static function pickPrivacyLevel(array $target, string $accessToken): string
    {
        $response = Http::request('POST', 'https://open.tiktokapis.com/v2/post/publish/creator_info/query/', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'json' => (object) [],
        ]);

        $options = $response['json']['data']['privacy_level_options'] ?? [];

        if ($target['visibility'] === 'public' && in_array('PUBLIC_TO_EVERYONE', $options, true)) {
            return 'PUBLIC_TO_EVERYONE';
        }
        // Unaudited apps only ever get SELF_ONLY back from creator_info — this is TikTok's
        // policy, not a bug: the client needs the app audited before videos go fully public.
        return in_array('SELF_ONLY', $options, true) ? 'SELF_ONLY' : ($options[0] ?? 'SELF_ONLY');
    }

    private static function hashtags(string $tags): string
    {
        $parts = array_filter(array_map('trim', explode(',', $tags)));
        return implode(' ', array_map(fn ($t) => '#' . preg_replace('/\s+/', '', $t), $parts));
    }

    private static function ensureFreshToken(array $account): string
    {
        $expiresAt = $account['token_expires_at'] ? strtotime($account['token_expires_at']) : 0;
        if ($expiresAt > time() + 60) {
            return SocialAccount::accessToken($account);
        }

        $config = App::config('tiktok');
        $refreshToken = SocialAccount::refreshToken($account);
        if (!$refreshToken) {
            throw new RuntimeException('No TikTok refresh token stored — the client needs to reconnect the account.');
        }

        $response = Http::request('POST', 'https://open.tiktokapis.com/v2/oauth/token/', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form' => [
                'client_key' => $config['client_key'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        if ($response['status'] !== 200 || empty($response['json']['access_token'])) {
            throw new RuntimeException('Failed to refresh TikTok access token: ' . $response['body']);
        }

        $newAccessToken = $response['json']['access_token'];
        $newRefreshToken = $response['json']['refresh_token'] ?? $refreshToken;
        $expiresAt = date('Y-m-d H:i:s', time() + (int) ($response['json']['expires_in'] ?? 86400));
        SocialAccount::updateTokens($account['id'], $newAccessToken, $newRefreshToken, $expiresAt);

        return $newAccessToken;
    }
}
