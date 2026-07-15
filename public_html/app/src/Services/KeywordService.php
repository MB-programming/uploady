<?php

/**
 * Keyword & hashtag research for the keywords.php tool — no paid API needed on shared hosting.
 *
 * Demand signals come from Google's public autocomplete endpoint (ds=yt narrows it to what
 * people actually type into YouTube search). Competition numbers come from YouTube Data API
 * search.list total-result counts, authenticated with one of the user's connected YouTube
 * channels — so real numbers are only available when a channel is connected (each lookup
 * costs 100 quota units, which is why competition is limited to the top keywords only).
 * TikTok/Instagram expose no comparable free lookup, so for those platforms the tool shows
 * the relative demand score and clearly labels competition as unavailable.
 */
class KeywordService
{
    private const MAX_KEYWORDS = 20;
    public const MAX_COMPETITION_LOOKUPS = 10;

    // Question/intent prefixes typed before a topic — expanding the seed through these pulls
    // long-tail suggestions out of autocomplete instead of just the topic's own completions.
    private const MODIFIERS = [
        'ar' => ['ازاي', 'أفضل', 'طريقة', 'شرح', 'ليه', 'سعر', 'مجانا'],
        'en' => ['how to', 'best', 'tutorial', 'why', 'price', 'free'],
    ];

    /**
     * Keyword ideas for a seed topic, as [keyword => demand score 1-100] sorted best-first.
     * The score is relative popularity inferred from autocomplete rank and how many intent
     * variations surface the same phrase — not an absolute search-volume figure.
     */
    public static function suggestions(string $seed, string $platform, string $locale): array
    {
        $useYouTubeSource = in_array($platform, ['youtube', 'youtube_shorts'], true);
        $modifiers = self::MODIFIERS[$locale === 'ar' ? 'ar' : 'en'];

        $queries = [$seed];
        foreach ($modifiers as $modifier) {
            $queries[] = $modifier . ' ' . $seed;
        }

        $scores = [];
        foreach ($queries as $index => $query) {
            foreach (self::fetchAutocomplete($query, $useYouTubeSource, $locale) as $position => $suggestion) {
                $keyword = trim(mb_strtolower($suggestion));
                if ($keyword === '' || $keyword === trim(mb_strtolower($seed))) {
                    continue;
                }
                // Earlier positions = more typed by real users; the plain seed's own
                // completions ($index 0) matter more than modifier expansions.
                $scores[$keyword] = ($scores[$keyword] ?? 0) + max(1, 10 - $position) + ($index === 0 ? 5 : 0);
            }
        }

        arsort($scores);
        $scores = array_slice($scores, 0, self::MAX_KEYWORDS, true);

        $max = $scores === [] ? 1 : max($scores);
        return array_map(fn ($s) => max(1, (int) round($s / $max * 100)), $scores);
    }

    private static function fetchAutocomplete(string $query, bool $youtube, string $locale): array
    {
        // ie/oe force UTF-8 — without them Google answers Arabic in a legacy codepage (mojibake).
        $params = ['client' => 'firefox', 'q' => $query, 'hl' => $locale === 'ar' ? 'ar' : 'en', 'ie' => 'utf-8', 'oe' => 'utf-8'];
        if ($youtube) {
            $params['ds'] = 'yt';
        }

        try {
            $response = Http::request('GET', 'https://suggestqueries.google.com/complete/search?' . http_build_query($params), [
                'timeout' => 8,
            ]);
            return is_array($response['json'][1] ?? null) ? $response['json'][1] : [];
        } catch (Throwable $e) {
            error_log('[KeywordService] autocomplete failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Approximate number of competing YouTube videos per keyword, via search.list.
     * Returns [keyword => count]; a keyword maps to -1 when the lookup failed.
     */
    public static function youtubeCompetition(array $keywords, array $account): array
    {
        $token = YouTubeService::ensureFreshToken($account);
        $results = [];

        foreach (array_slice($keywords, 0, self::MAX_COMPETITION_LOOKUPS) as $keyword) {
            try {
                $response = Http::request('GET', 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
                    'part' => 'id',
                    'q' => $keyword,
                    'type' => 'video',
                    'maxResults' => 1,
                ]), [
                    'headers' => ['Authorization' => "Bearer $token"],
                    'timeout' => 10,
                ]);
                $results[$keyword] = isset($response['json']['pageInfo']['totalResults'])
                    ? (int) $response['json']['pageInfo']['totalResults']
                    : -1;
            } catch (Throwable $e) {
                error_log('[KeywordService] competition lookup failed: ' . $e->getMessage());
                $results[$keyword] = -1;
            }
        }

        return $results;
    }

    /** Buckets a raw competing-video count into low/medium/high (YouTube caps counts at ~1M). */
    public static function competitionLevel(int $totalResults): string
    {
        if ($totalResults < 0) {
            return 'unknown';
        }
        if ($totalResults < 50000) {
            return 'low';
        }
        if ($totalResults < 500000) {
            return 'medium';
        }
        return 'high';
    }

    /** "كلمة مفتاحية" → "#كلمة_مفتاحية" (hashtags can't contain spaces; underscores keep them readable). */
    public static function toHashtag(string $keyword): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $keyword);
        return '#' . preg_replace('/\s+/u', '_', trim($clean));
    }
}
