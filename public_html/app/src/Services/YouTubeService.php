<?php

/**
 * Uploads to YouTube via the resumable upload protocol (YouTube Data API v3).
 * "YouTube Shorts" isn't a separate API — a vertical video under 60s uploaded normally
 * is automatically surfaced as a Short by YouTube, so both targets use this same class.
 *
 * The upload is chunked and resumes across cron ticks: shared-hosting cron wraps our
 * process in `timeout`, so a single-shot upload of a large file gets killed mid-flight.
 * If we then started a fresh session on the next tick, every tick would create ANOTHER
 * metadata-only video on the channel (this actually happened in production). Instead the
 * resumable session URI is persisted in post_targets.upload_session before any bytes move,
 * and each tick first asks YouTube "how much did you get?" — including detecting the case
 * where the upload actually finished but our process died before recording it.
 */
class YouTubeService
{
    private const CHUNK_BYTES = 64 * 1024 * 1024; // must stay a multiple of 256 KiB per YouTube spec
    private const TICK_UPLOAD_BUDGET_SECONDS = 150; // stop starting new chunks near Hostinger's cron kill window

    public static function publish(array $target, array $account): void
    {
        $accessToken = self::ensureFreshToken($account);
        $session = PostTarget::uploadSession($target);

        if (empty($session['upload_url'])) {
            $session = self::initResumableSession($target, $accessToken);
        } else {
            $probe = self::probeSession($session['upload_url'], (int) $session['file_size']);

            if (isset($probe['video_id'])) {
                // Finished on a previous tick but we died before saving — do NOT re-upload.
                self::finishPublish($target, $accessToken, $probe['video_id']);
                return;
            }
            if (!empty($probe['expired'])) {
                // Session URIs die after ~a day. An incomplete session never became a video,
                // so restarting is safe (no duplicate exists on the channel).
                $session = self::initResumableSession($target, $accessToken);
            } else {
                $session['bytes_sent'] = $probe['bytes_received'];
            }
        }

        self::uploadChunks($target, $accessToken, $session);
    }

    private static function initResumableSession(array $target, string $accessToken): array
    {
        $tags = array_values(array_filter(array_map('trim', explode(',', (string) $target['tags']))));

        $metadata = [
            'snippet' => [
                'title' => mb_substr($target['title'], 0, 100),
                'description' => (string) $target['description'],
                'tags' => $tags,
                'categoryId' => '22', // People & Blogs — reasonable generic default
            ],
            'status' => [
                'privacyStatus' => $target['visibility'],
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $fileSize = filesize($target['video_path']);

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

        // Persist BEFORE the first byte moves — this is the whole anti-duplication guarantee.
        $session = ['upload_url' => $uploadUrl, 'file_size' => $fileSize, 'bytes_sent' => 0];
        PostTarget::saveUploadSession($target['id'], $session);
        return $session;
    }

    /**
     * Asks YouTube how much of the session it already has (a "Content-Range: bytes star/SIZE"
     * probe request). Returns one of: ['video_id' => ...] when the upload actually completed,
     * ['bytes_received' => N] to resume from N, or ['expired' => true, 'bytes_received' => 0].
     */
    private static function probeSession(string $uploadUrl, int $fileSize): array
    {
        $response = Http::request('PUT', $uploadUrl, [
            'headers' => ['Content-Range' => "bytes */$fileSize", 'Content-Length' => '0'],
            'timeout' => 30,
        ]);

        if ($response['status'] < 300 && !empty($response['json']['id'])) {
            return ['video_id' => $response['json']['id']];
        }
        if ($response['status'] === 308) {
            return ['bytes_received' => self::bytesReceivedFromHeaders($response['headers'])];
        }
        return ['expired' => true, 'bytes_received' => 0];
    }

    private static function uploadChunks(array $target, string $accessToken, array $session): void
    {
        $videoPath = $target['video_path'];
        $fileSize = (int) $session['file_size'];
        $offset = (int) ($session['bytes_sent'] ?? 0);
        $deadline = time() + self::TICK_UPLOAD_BUDGET_SECONDS;

        while ($offset < $fileSize) {
            $length = min(self::CHUNK_BYTES, $fileSize - $offset);
            $end = $offset + $length - 1;

            $response = Http::putFile($session['upload_url'], $videoPath, [
                'Content-Type' => 'video/mp4',
                'Content-Range' => "bytes $offset-$end/$fileSize",
            ], $offset, $length);

            if ($response['status'] === 308) {
                $offset = self::bytesReceivedFromHeaders($response['headers'], $end + 1);
                $session['bytes_sent'] = $offset;
                PostTarget::saveUploadSession($target['id'], $session);
                if (time() >= $deadline) {
                    return; // out of safe time this tick — the next tick resumes from $offset
                }
                continue;
            }

            if ($response['status'] < 300 && !empty($response['json']['id'])) {
                self::finishPublish($target, $accessToken, $response['json']['id']);
                return;
            }

            throw new RuntimeException('YouTube chunk upload failed: ' . $response['body']);
        }
    }

    /** Parses the 308 "Range: bytes=0-N" header; the next byte to send is N+1. */
    private static function bytesReceivedFromHeaders(array $headers, int $fallback = 0): int
    {
        if (preg_match('/bytes=\d+-(\d+)/', $headers['range'] ?? '', $m)) {
            return ((int) $m[1]) + 1;
        }
        return $fallback;
    }

    private static function finishPublish(array $target, string $accessToken, string $videoId): void
    {
        if (!empty($target['thumbnail_path']) && is_file($target['thumbnail_path'])) {
            self::trySetThumbnail($accessToken, $videoId, $target['thumbnail_path']);
        }
        PostTarget::markPublished($target['id'], $videoId, "https://youtu.be/$videoId");
    }

    /**
     * Custom thumbnails require the channel to be phone-verified; a channel that isn't just
     * gets a 403 from this endpoint. That's not worth failing an otherwise-successful upload
     * over, so this is best-effort and only logged if it doesn't work.
     */
    private static function trySetThumbnail(string $accessToken, string $videoId, string $thumbnailPath): void
    {
        try {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($thumbnailPath) ?: 'image/jpeg';
            $response = Http::request('POST', "https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId=$videoId&uploadType=media", [
                'headers' => ['Authorization' => "Bearer $accessToken", 'Content-Type' => $mime],
                'body' => file_get_contents($thumbnailPath),
            ]);
            if ($response['status'] >= 300) {
                error_log("[YouTubeService] thumbnail set failed for $videoId: " . $response['body']);
            }
        } catch (Throwable $e) {
            error_log("[YouTubeService] thumbnail set exception for $videoId: " . $e->getMessage());
        }
    }

    /** Public because KeywordService reuses the same OAuth token for search-based competition stats. */
    public static function ensureFreshToken(array $account): string
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
