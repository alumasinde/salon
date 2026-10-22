<?php

declare(strict_types=1);

namespace Salon\Sms;

use Salon\DB\Database;
use Salon\Http\TenantResolver;
use Salon\Model\Admin;
use Salon\Model\Setting;
use Salon\Support\Phone;

/** Builds booking notifications, queues them and pushes them through the gateway. */
final class SmsService
{
    private const MAX_LENGTH = 480;

    public static function bookingText(array $booking): string
    {
        $template = Setting::get('sms_template', 'New booking {ref}: {name}, {service}, {date} {time}. Call {phone}');
        $ts = strtotime((string) ($booking['booking_date'] ?? ''));
        $replacements = [
            '{ref}' => (string) ($booking['ref_code'] ?? ''),
            '{name}' => (string) ($booking['client_name'] ?? ''),
            '{service}' => (string) ($booking['service_name'] ?? ''),
            '{date}' => $ts === false ? (string) ($booking['booking_date'] ?? '') : date('D j M', $ts),
            '{time}' => (string) ($booking['booking_time'] ?? ''),
            '{phone}' => Phone::display((string) ($booking['phone'] ?? '')),
            '{salon}' => Setting::get('salon_name', 'Salon'),
            '{notes}' => mb_substr((string) ($booking['notes'] ?? ''), 0, 80),
        ];
        return self::gsm7(strtr($template, $replacements));
    }

    /** @return array<int, array{number:string,label:string}> */
    public static function recipients(): array
    {
        $list = [];
        $salon = Phone::normalize(Setting::get('sms_notify_number'));
        if ($salon !== null) {
            $list[] = ['number' => $salon, 'label' => 'Salon'];
        }
        if (Setting::bool('sms_admin_notify', true)) {
            foreach (Admin::smsRecipients() as $admin) {
                $number = Phone::normalize((string) $admin['phone']);
                if ($number !== null) {
                    $list[] = ['number' => $number, 'label' => $admin['first_name'] ?: 'Admin'];
                }
            }
        }
        $unique = [];
        foreach ($list as $row) {
            $unique[$row['number']] ??= $row;
        }
        return array_values($unique);
    }

    /**
     * Notify about a new booking. Never throws: SMS failure must not lose a booking.
     * @return array{queued:int,sent:int,failed:int,skipped:int,reason:string}
     */
    public static function notifyBooking(array $booking): array
    {
        $summary = ['queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => ''];
        $recipients = self::recipients();
        if ($recipients === []) {
            $summary['reason'] = 'no recipient numbers configured';
            return $summary;
        }
        $message = self::bookingText($booking);
        $config = Gateway::config();
        if (!Gateway::ready($config)) {
            foreach ($recipients as $to) {
                self::record($to, $message, (int) ($booking['id'] ?? 0), 'skipped',
                    'not sent: ' . implode(', ', Gateway::missing($config)), null);
                $summary['skipped']++;
            }
            $summary['reason'] = implode(', ', Gateway::missing($config));
            return $summary;
        }
        foreach ($recipients as $to) {
            $id = self::record($to, $message, (int) ($booking['id'] ?? 0), 'queued', '', null);
            $summary['queued']++;
            $result = Gateway::send($to['number'], $message, $config);
            self::record($to, $message, (int) ($booking['id'] ?? 0),
                $result['ok'] ? 'sent' : 'failed', mb_substr($result['response'], 0, 255), $id);
            $summary[$result['ok'] ? 'sent' : 'failed']++;
        }
        return $summary;
    }

    /** Manual send from the admin panel (test message). */
    public static function sendNow(string $to, string $message): array
    {
        $number = Phone::normalize($to);
        if ($number === null) {
            return ['ok' => false, 'message' => 'Enter a Kenyan number, like 0712 345 678.'];
        }
        $config = Gateway::config();
        if ($config['key'] === '' || $config['shortcode'] === '') {
            return ['ok' => false, 'message' => 'Add the API key and sender ID first.'];
        }
        $message = self::gsm7($message);
        $id = self::record(['number' => $number, 'label' => 'Test'], $message, 0, 'queued', '', null);
        $result = Gateway::send($number, $message, $config);
        self::record(['number' => $number, 'label' => 'Test'], $message, 0,
            $result['ok'] ? 'sent' : 'failed', mb_substr($result['response'], 0, 255), $id);
        return ['ok' => $result['ok'], 'message' => $result['ok']
            ? 'Sent. Gateway said: ' . mb_substr($result['response'], 0, 120)
            : 'Rejected (HTTP ' . $result['status'] . '): ' . mb_substr($result['response'], 0, 120)];
    }

    /** @return int Number of messages that were retried. */
    public static function retryFailed(int $limit = 20): array
    {
        $rows = Database::all(
            'SELECT * FROM sms_queue WHERE status = ? AND tenant_id = ? AND attempts < 4 ORDER BY id DESC LIMIT '
            . max(1, min(100, $limit)), ['failed', TenantResolver::idOrNull()]
        );
        $config = Gateway::config();
        $counts = ['retried' => 0, 'sent' => 0, 'failed' => 0];
        if (!Gateway::ready($config)) {
            return $counts + ['reason' => 'gateway not configured or switched off'];
        }
        foreach ($rows as $row) {
            $result = Gateway::send((string) $row['to_number'], (string) $row['message'], $config);
            Database::exec(
                'UPDATE sms_queue SET status = ?, provider_response = ?, error = ?, attempts = attempts + 1,
                 sent_at = ? WHERE id = ?',
                [$result['ok'] ? 'sent' : 'failed', mb_substr($result['response'], 0, 255),
                    $result['ok'] ? '' : mb_substr($result['response'], 0, 255),
                    $result['ok'] ? date('Y-m-d H:i:s') : null, (int) $row['id']]
            );
            $counts['retried']++;
            $counts[$result['ok'] ? 'sent' : 'failed']++;
        }
        return $counts + ['reason' => ''];
    }

    public static function recent(int $limit = 40): array
    {
        return Database::all('SELECT * FROM sms_queue WHERE tenant_id = ? ORDER BY id DESC LIMIT '
            . max(1, min(200, $limit)), [TenantResolver::idOrNull()]);
    }

    public static function forBooking(int $bookingId): array
    {
        return Database::all('SELECT * FROM sms_queue WHERE booking_id = ? ORDER BY id DESC', [$bookingId]);
    }

    public static function todayStats(): array
    {
        $rows = Database::all(
            'SELECT status, COUNT(*) AS total FROM sms_queue
             WHERE created_at >= CURDATE() AND tenant_id = ? GROUP BY status',
            [TenantResolver::idOrNull()]
        );
        $out = ['sent' => 0, 'failed' => 0, 'queued' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $out[$row['status']] = (int) $row['total'];
        }
        return $out;
    }

    private static function record(array $to, string $message, int $bookingId, string $status, string $response, ?int $updateId): int
    {
        $tenantId = TenantResolver::idOrNull();
        if ($updateId !== null && $updateId > 0) {
            Database::exec(
                'UPDATE sms_queue SET status = ?, provider_response = ?, error = ?, attempts = attempts + 1,
                 sent_at = ? WHERE id = ? AND tenant_id = ?',
                [$status, mb_substr($response, 0, 255), $status === 'sent' ? '' : mb_substr($response, 0, 255),
                    $status === 'sent' ? date('Y-m-d H:i:s') : null, $updateId, $tenantId]
            );
            return $updateId;
        }
        return Database::insert(
            'INSERT INTO sms_queue (tenant_id, booking_id, to_number, recipient_label, message, status, provider_response, error)
             VALUES (?,?,?,?,?,?,?,?)',
            [$tenantId, $bookingId > 0 ? $bookingId : null, $to['number'], $to['label'], $message, $status,
                mb_substr($response, 0, 255), $status === 'sent' ? '' : mb_substr($response, 0, 255)]
        );
    }

    /** Strip characters outside the GSM 7-bit alphabet so the gateway does not reject the text. */
    public static function gsm7(string $text): string
    {
        $text = str_replace(
            ['‘', '’', '“', '”', '–', '—', '…', '€', '£', 'é', 'è', 'à', 'ù'],
            ["'", "'", '"', '"', '-', '-', '...', 'EUR', 'GBP', 'e', 'e', 'a', 'u'],
            $text
        );
        $text = preg_replace('/[^\x20-\x7E\n]/u', '?', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return mb_substr(trim($text), 0, self::MAX_LENGTH);
    }
}
