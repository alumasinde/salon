<?php

declare(strict_types=1);

namespace Salon\Sms;

use Salon\Model\Setting;
use Salon\Support\Config;

/**
 * Outbound SMS gateway. Works with any HTTP provider that accepts a mobile
 * number, sender ID and message; defaults to sms.textsms.co.ke.
 *
 * Endpoint, method, sender and parameter names are editable in the admin panel
 * (stored in `settings`). Credentials come from .env, or from settings when the
 * .env value is empty.
 */
final class Gateway
{
    public const DEFAULT_PARAMS = 'apikey:apikey,partnerID:partnerID,message:message,shortcode:shortcode,mobile:mobile';

    public static function config(): array
    {
        $map = [];
        foreach (explode(',', Setting::get('sms_param_map', self::DEFAULT_PARAMS)) as $pair) {
            if (!str_contains($pair, ':')) {
                continue;
            }
            [$logical, $actual] = array_map('trim', explode(':', $pair, 2));
            if ($logical !== '' && $actual !== '') {
                $map[strtolower($logical)] = $actual;
            }
        }
        foreach (['apikey', 'partnerid', 'message', 'shortcode', 'mobile'] as $needed) {
            $map[$needed] ??= $needed;
        }
        $key = Setting::get('sms_key') !== '' ? Setting::get('sms_key') : Config::get('SMS_KEY');
        $partner = Setting::get('sms_partner_id') !== ''
            ? Setting::get('sms_partner_id')
            : Config::get('SMS_PARTNER_ID');
        return [
            'enabled' => Setting::bool('sms_enabled'),
            'endpoint' => rtrim(Setting::get('sms_endpoint', 'https://sms.textsms.co.ke/api/services/sendsms/'), " \t\n\r\0\x0B") ?: Config::get('SMS_ENDPOINT', 'https://sms.textsms.co.ke/api/services/sendsms/'),
            'method' => strtoupper(Setting::get('sms_method', Config::get('SMS_METHOD', 'GET'))) === 'POST' ? 'POST' : 'GET',
            'key' => $key,
            'partner_id' => $partner,
            'shortcode' => Setting::get('sms_shortcode', Config::get('SMS_SHORTCODE')),
            'params' => $map,
            'timeout' => max(2, min(30, Config::int('SMS_TIMEOUT', 8))),
        ];
    }

    /** True when the gateway has everything it needs to actually send. */
    public static function ready(array $config = []): bool
    {
        $config = $config ?: self::config();
        return $config['enabled'] && filter_var($config['endpoint'], FILTER_VALIDATE_URL) !== false
            && $config['key'] !== '' && $config['shortcode'] !== '';
    }

    public static function missing(array $config = []): array
    {
        $config = $config ?: self::config();
        $missing = [];
        if (!$config['enabled']) {
            $missing[] = 'sending is switched off';
        }
        if (filter_var($config['endpoint'], FILTER_VALIDATE_URL) === false) {
            $missing[] = 'endpoint URL';
        }
        if ($config['key'] === '') {
            $missing[] = 'API key';
        }
        if ($config['partner_id'] === '') {
            $missing[] = 'partner ID';
        }
        if ($config['shortcode'] === '') {
            $missing[] = 'sender ID or shortcode';
        }
        return $missing;
    }

    /**
     * @return array{ok:bool,status:int,response:string}
     */
    public static function send(string $to, string $message, array $config = []): array
    {
        $config = $config ?: self::config();
        $fields = [
            $config['params']['apikey'] => $config['key'],
            $config['params']['partnerid'] => $config['partner_id'],
            $config['params']['message'] => $message,
            $config['params']['shortcode'] => $config['shortcode'],
            $config['params']['mobile'] => $to,
        ];
        $query = http_build_query($fields, '', '&');
        $url = $config['endpoint'];
        if ($config['method'] === 'GET') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }
        $result = self::http($url, $config['method'], $config['method'] === 'POST' ? $query : null,
            $config['timeout']);
        $body = $result['body'];
        $looksFailed = preg_match('/\b(error|invalid|failed|not authorized|unauthorized|missing)\b/i', $body) === 1;
        $result['ok'] = $result['status'] >= 200 && $result['status'] < 300 && !$looksFailed;
        return ['ok' => $result['ok'], 'status' => $result['status'],
            'response' => mb_substr($body !== '' ? $body : (string) ($result['error'] ?? ''), 0, 255)];
    }

    /** @return array{status:int,body:string,error:string} */
    private static function http(string $url, string $method, ?string $body, int $timeout): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => min($timeout, 5),
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER => $body === null ? [] : ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => $body,
            ]);
            $response = (string) curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = (string) curl_error($ch);
            curl_close($ch);
            return ['status' => $status, 'body' => trim($response), 'error' => $error];
        }

        $context = stream_context_create(['http' => [
            'method' => $method,
            'timeout' => $timeout,
            'header' => $body === null ? '' : "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body ?? '',
            'ignore_errors' => true,
        ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $response = (string) @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
            }
        }
        return ['status' => $status, 'body' => trim($response),
            'error' => $response === '' ? 'No response from gateway' : ''];
    }
}
