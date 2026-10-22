<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Booking;
use Salon\Model\Setting;
use Salon\Sms\Gateway;
use Salon\Sms\SmsService;

/** Booking SMS: gateway settings, recipients, test sends and the delivery log. */
final class SmsController extends AdminController
{
    public function index(Request $request): string
    {
        $config = Gateway::config();
        $sample = [
            'ref_code' => 'DEMO01',
            'client_name' => 'Amina W.',
            'service_name' => (Booking::recent(1)[0]['service_name'] ?? 'Knotless braids'),
            'booking_date' => date('Y-m-d', strtotime('+1 day')),
            'booking_time' => '10:00 am',
            'phone' => '254712345678',
            'notes' => 'Wants shoulder-length knotless.',
        ];
        return $this->render('admin/sms', 'Booking SMS', [
            'config' => $config,
            'missing' => Gateway::missing($config),
            'recipients' => SmsService::recipients(),
            'stats' => SmsService::todayStats(),
            'queue' => SmsService::recent(40),
            'preview' => SmsService::bookingText($sample),
            'sample' => $sample,
        ], 'sms');
    }

    public function save(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('sms');
        $this->requireCsrf();
        $validator = Validator::make($_POST, [
            'sms_notify_number' => 'phone',
            'sms_shortcode' => 'max:20',
            'sms_method' => 'in:GET,POST',
            'sms_param_map' => 'max:255',
            'sms_template' => 'max:400',
            'sms_partner_id' => 'max:60',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect('admin/sms');
        }
        $endpoint = trim((string) ($_POST['sms_endpoint'] ?? ''));
        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            Flash::push('The gateway endpoint must be a full URL starting with https://', 'err');
            redirect('admin/sms');
        }
        $pairs = [
            'sms_enabled' => empty($_POST['sms_enabled']) ? '0' : '1',
            'sms_admin_notify' => empty($_POST['sms_admin_notify']) ? '0' : '1',
            'sms_notify_number' => $validator->value('sms_notify_number'),
            'sms_shortcode' => $validator->value('sms_shortcode'),
            'sms_endpoint' => $endpoint,
            'sms_method' => $validator->value('sms_method', 'GET'),
            'sms_param_map' => $validator->value('sms_param_map'),
            'sms_template' => $validator->value('sms_template'),
        ];
        Setting::setMany($pairs);
        // Credentials left blank keep whatever is already stored.
        foreach (['sms_key' => 190, 'sms_partner_id' => 60] as $secret => $limit) {
            $value = trim((string) ($_POST[$secret] ?? ''));
            if ($value !== '' && $value !== '********') {
                Setting::set($secret, mb_substr($value, 0, $limit));
            }
        }
        Activity::log((int) $admin['id'], 'sms_settings', $pairs['sms_enabled'] === '1' ? 'enabled' : 'disabled', $request->ip);
        Flash::push('SMS settings saved.');
        redirect('admin/sms');
    }

    public function test(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('sms');
        $this->requireCsrf();
        $result = SmsService::sendNow((string) ($_POST['test_number'] ?? ''),
            (string) ($_POST['test_message'] ?? 'Salon test message'));
        Activity::log((int) $admin['id'], 'sms_test', $result['ok'] ? 'sent' : 'failed', $request->ip);
        Flash::push($result['message'], $result['ok'] ? 'ok' : 'err');
        redirect('admin/sms');
    }

    public function retry(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('sms');
        $this->requireCsrf();
        $counts = SmsService::retryFailed(25);
        Activity::log((int) $admin['id'], 'sms_retry', (string) $counts['retried'], $request->ip);
        Flash::push($counts['retried'] === 0
            ? ($counts['reason'] !== '' ? 'Nothing retried: ' . $counts['reason'] . '.' : 'No failed messages to retry.')
            : 'Retried ' . $counts['retried'] . ': ' . $counts['sent'] . ' sent, ' . $counts['failed'] . ' still failing.',
            $counts['sent'] > 0 ? 'ok' : 'err');
        redirect('admin/sms');
    }

    /** Re-send the notification for one booking. */
    public function resend(Request $request, string $id = ''): string
    {
        $this->requirePost($request);
        $admin = $this->guard('sms');
        $this->requireCsrf();
        $booking = Booking::find((int) $id);
        if ($booking === null) {
            Flash::push('That booking is gone.', 'err');
            redirect('admin/bookings');
        }
        $summary = SmsService::notifyBooking($booking);
        Activity::log((int) $admin['id'], 'sms_resend', (string) $booking['ref_code'], $request->ip);
        $message = $summary['sent'] . ' sent, ' . $summary['failed'] . ' failed, ' . $summary['skipped'] . ' skipped.';
        Flash::push(trim($message . ($summary['reason'] !== '' ? ' (' . $summary['reason'] . ')' : ' ')),
            $summary['sent'] > 0 ? 'ok' : 'err');
        redirect('admin/bookings/edit/' . (int) $id);
    }
}
