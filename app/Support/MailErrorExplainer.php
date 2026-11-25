<?php

namespace App\Support;

use Throwable;

/**
 * Turns a mail server error into a plain reason and the steps that fix it.
 *
 * Symfony Mailer reports problems as raw SMTP or socket messages ("Expected response
 * code 250 but got code 535…", "stream_socket_client(): Unable to connect…"). A shop
 * owner can't act on those, so each common cause is named in plain words instead.
 */
class MailErrorExplainer
{
    /**
     * @return array{reason: string, fixes: list<string>}
     */
    public static function explain(Throwable|string $error, ?string $host = null, int|string|null $port = null, ?string $encryption = null): array
    {
        $raw = $error instanceof Throwable ? $error->getMessage() : $error;
        $m = mb_strtolower($raw);
        $host = trim((string) $host) ?: '?';
        $port = (int) $port ?: 587;
        $encryption = mb_strtolower((string) $encryption);
        $has = fn (string ...$needles) => array_filter($needles, fn ($n) => str_contains($m, $n)) !== [];
        $t = fn (string $key, array $replace = []) => __("admin_mail.errors.$key", ['host' => $host, 'port' => $port] + $replace);
        $blockedFixes = [__('admin_mail.fixes.whm'), __('admin_mail.fixes.csf'), __('admin_mail.fixes.localhost')];

        // Host name typed wrong, or no DNS.
        if ($has('getaddrinfo', 'name or service not known', 'nodename nor servname', 'no address associated', 'could not resolve', 'no such host', 'temporary failure in name resolution')) {
            return ['reason' => $t('host_not_found'), 'fixes' => [__('admin_mail.fixes.check_host')]];
        }

        // Blocked by a firewall, or nothing listening on that port.
        if ($has('connection refused')) {
            return ['reason' => $t('refused'), 'fixes' => $blockedFixes];
        }
        if ($has('timed out', 'timeout', 'network is unreachable', 'no route to host')) {
            return ['reason' => $t('timeout'), 'fixes' => $blockedFixes];
        }

        // The server wants encryption the settings don't use.
        if ($has('must issue a starttls', 'starttls first', 'requires tls', 'tls required')) {
            return ['reason' => $t('needs_tls'), 'fixes' => [__('admin_mail.fixes.use_587_tls')]];
        }

        // Wrong user name or password.
        if ($has('535', '534', 'authentication failed', 'username and password not accepted', 'invalid login', 'authentication unsuccessful', 'failed to authenticate', 'authentication credentials invalid', 'incorrect authentication data', 'auth failed')) {
            if (str_contains(mb_strtolower($host), 'gmail') || str_contains(mb_strtolower($host), 'google')) {
                return ['reason' => $t('login_gmail'), 'fixes' => [__('admin_mail.fixes.gmail_app_password')]];
            }
            if (preg_match('/office365|outlook|hotmail|live\.com/', mb_strtolower($host))) {
                return ['reason' => $t('login_microsoft'), 'fixes' => [__('admin_mail.fixes.microsoft_smtp_auth')]];
            }

            return ['reason' => $t('login'), 'fixes' => [__('admin_mail.fixes.full_email_username')]];
        }

        // Certificates and SSL/TLS mix-ups.
        if ($has('certificate', 'peer name', 'cn match', 'self signed', 'self-signed')) {
            return ['reason' => $t('certificate'), 'fixes' => [__('admin_mail.fixes.hosting_host')]];
        }
        if ($has('ssl', 'tls', 'wrong version number', 'crypto', 'handshake', 'unexpected eof')) {
            if ($port === 465 && $encryption !== 'ssl') {
                return ['reason' => $t('use_ssl_465'), 'fixes' => [__('admin_mail.fixes.match_port')]];
            }
            if ($port === 587 && $encryption === 'ssl') {
                return ['reason' => $t('use_tls_587'), 'fixes' => [__('admin_mail.fixes.match_port')]];
            }

            return ['reason' => $t('encryption'), 'fixes' => [__('admin_mail.fixes.match_port')]];
        }

        // Logged in, but the server won't send for this address.
        if ($has('sender address rejected', 'not owned by user', 'sender verify', 'relay', 'not permitted', 'from address', 'sender is not allowed', '553')) {
            return ['reason' => $t('sender'), 'fixes' => [__('admin_mail.fixes.from_mailbox')]];
        }
        if ($has('recipient', 'user unknown', 'mailbox unavailable', 'no such user', 'does not exist')) {
            return ['reason' => $t('recipient'), 'fixes' => [__('admin_mail.fixes.check_recipient')]];
        }

        $said = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? $raw);

        return ['reason' => $t('other', ['message' => mb_strimwidth($said, 0, 240, '…')]), 'fixes' => []];
    }
}
