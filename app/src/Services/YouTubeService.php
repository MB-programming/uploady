<?php

/**
 * Uploads to YouTube via the resumable upload protocol (YouTube Data API v3).
 * "YouTube Shorts" isn't a separate API — a vertical video under 60s uploaded normally
 * is automatically surfaced as a Short by YouTube, so both targets use this same class.
 */
class YouTubeService
{
    public static function publish(array $target, array $post, array $account): void
    {
        $accessToken = self::ensureFreshToken($account);

        $categoryId = '22'; // People & Blogs — reasonable generic default
        $tags = array_values(array_filter(array_map('trim', explode(',', (string) $post['tags']))));

        $metadata = [
            'snippet' => [
                'title' => mb_substr($post['title'], 0, 100),
                'description' => (string) $post['description'],
                'tags' => $tags,
                'categoryId' => $categoryId,
            ],
            'status' => [
                'privacyStatus' => $post['visibility'],
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $videoPath = $post['video_path'];
        $fileSize = filesize($videoPath);

        $initResponse = Http::request('POST', 'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type' => 'video/mp4',
                'X-Upload-Content-Length' => (string) $fileSize,
            ],
            'json' => $metadata,
        ]);

        $uploadUrl = $initResponse['headers']['location'] ?? null;
        if (!$uploadUrl) {
            throw new RuntimeException('YouTube resumable session init failed: ' . $initResponse['body']);
        }

        $uploadResponse = Http::putFile($uploadUrl, $videoPath, [
            'Content-Type' => 'video/mp4',
        ]);

        if ($uploadResponse['status'] >= 300 || empty($uploadResponse['json']['id'])) {
            throw new RuntimeException('YouTube video upload failed: ' . $uploadResponse['body']);
        }

        $videoId = $uploadResponse['json']['id'];
        PostTarget::markPublished($target['id'], $videoId, "https://youtu.be/$videoId");
    }

    private static function ensureFreshToken(array $account): string
    {
        $expiresAt = $account['token_expires_at'] ? strtotime($account['token_expires_at']) : 0;
        if ($expiresAt > time() + 60) {
            return SocialAccount::accessToken($account);
        }

        $config = App::config('youtube');
        $refreshToken = SocialAccount::refreshToken($account);
        if (!$refreshToken) {
            throw new RuntimeException('No YouTube refresh token stored — the client needs to reconnect the channel.');
        }

        $response = Http::request('POST', 'https://oauth2.googleapis.com/token', [
            'form' => [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        if ($response['status'] !== 200 || empty($response['json']['access_token'])) {
            throw new RuntimeException('Failed to refresh YouTube access token: ' . $response['body']);
        }

        $newAccessToken = $response['json']['access_token'];
        $expiresAt = date('Y-m-d H:i:s', time() + (int) ($response['json']['expires_in'] ?? 3600));
        SocialAccount::updateTokens($account['id'], $newAccessToken, null, $expiresAt);

        return $newAccessToken;
    }
}
