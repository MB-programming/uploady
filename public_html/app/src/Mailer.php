<?php

/**
 * Minimal SMTP client (no external dependencies) using the credentials configured
 * in admin_settings.php. Supports AUTH LOGIN and both STARTTLS (587) and implicit
 * TLS (465), which covers Hostinger/Gmail-app-password/SendGrid/Mailgun-style relays.
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $bodyHtml): void
    {
        $host = Settings::get('smtp_host');
        $port = (int) (Settings::get('smtp_port') ?: 587);
        $username = Settings::get('smtp_username');
        $encryptedPassword = Settings::get('smtp_password');
        $encryption = Settings::get('smtp_encryption', 'tls');
        $fromEmail = Settings::get('smtp_from_email') ?: $username;
        $fromName = Settings::get('smtp_from_name') ?: t('common.brand');

        if (!$host || !$username || !$encryptedPassword) {
            throw new RuntimeException('SMTP is not configured yet — fill in the settings first.');
        }
        $password = Crypto::decrypt($encryptedPassword);

        $transportPrefix = $encryption === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($transportPrefix . $host . ':' . $port, $errno, $errstr, 15);
        if (!$socket) {
            throw new RuntimeException("Could not connect to $host:$port — $errstr");
        }

        try {
            $ehloName = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost') ?: 'localhost';

            self::expect($socket, '220');
            self::command($socket, "EHLO $ehloName", '250');

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', '220');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Failed to negotiate STARTTLS with the SMTP server.');
                }
                self::command($socket, "EHLO $ehloName", '250');
            }

            self::command($socket, 'AUTH LOGIN', '334');
            self::command($socket, base64_encode($username), '334');
            self::command($socket, base64_encode($password), '235');

            self::command($socket, "MAIL FROM:<$fromEmail>", '250');
            self::command($socket, "RCPT TO:<$toEmail>", '250');
            self::command($socket, 'DATA', '354');

            $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n"
                . "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$toEmail>\r\n"
                . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n";

            // Dot-stuff any line that starts with a lone "." so it isn't read as the end-of-DATA marker.
            $stuffedBody = preg_replace('/^\./m', '..', $bodyHtml);

            fwrite($socket, $headers . "\r\n" . $stuffedBody . "\r\n.\r\n");
            self::expect($socket, '250');

            fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private static function command($socket, string $line, string $expectCode): string
    {
        fwrite($socket, $line . "\r\n");
        return self::expect($socket, $expectCode);
    }

    /** @param resource $socket */
    private static function expect($socket, string $expectCode): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if (strpos($response, $expectCode) !== 0) {
            throw new RuntimeException("Unexpected SMTP response (expected $expectCode): " . trim($response));
        }
        return $response;
    }
}
