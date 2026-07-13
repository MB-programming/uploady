<?php

/** Thin cURL wrapper — no external HTTP client dependency needed on shared hosting. */
class Http
{
    public static function request(string $method, string $url, array $options = []): array
    {
        $ch = curl_init();
        $headers = $options['headers'] ?? [];
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "$key: $value";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $options['timeout'] ?? 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (isset($options['json'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json'], JSON_UNESCAPED_UNICODE));
        } elseif (isset($options['form'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['form']));
        } elseif (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($header);
        });

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException("HTTP request to $url failed: $error");
        }

        return [
            'status' => $status,
            'body' => $body,
            'headers' => $responseHeaders,
            'json' => json_decode($body, true),
        ];
    }

    /** Streams a local file as the body of a PUT request (used for resumable video uploads). */
    public static function putFile(string $url, string $filePath, array $headers = [], ?int $rangeStart = null, ?int $rangeLength = null): array
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Cannot open $filePath for upload");
        }
        if ($rangeStart !== null) {
            fseek($handle, $rangeStart);
        }
        $length = $rangeLength ?? (filesize($filePath) - (int) $rangeStart);

        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "$key: $value";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $handle,
            CURLOPT_INFILESIZE => $length,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 280,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($header);
        });

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if ($body === false) {
            throw new RuntimeException("Upload PUT to $url failed: $error");
        }

        return [
            'status' => $status,
            'body' => $body,
            'headers' => $responseHeaders,
            'json' => json_decode($body, true),
        ];
    }
}
