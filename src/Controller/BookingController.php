<?php

declare(strict_types=1);

namespace Salon\Controller;

use Salon\DB\Database;
use Salon\Http\Controller;
use Salon\Http\Csrf;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Booking;
use Salon\Model\BookingStatus;
use Salon\Model\Schedule;
use Salon\Model\Service;
use Salon\Model\Setting;
use Salon\Sms\SmsService;
use Salon\Support\Phone;

/** Public booking endpoint. Returns JSON; never exposes internal failures to the client. */
final class BookingController extends Controller
{
    public function store(Request $request): string
    {
        $this->requirePost($request);

        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            return $this->json(['ok' => true, 'msg' => Setting::get('booking_success_text', 'Booking received.')]);
        }
        if (!Csrf::verifyToken((string) ($_POST['token'] ?? ''), 'booking')) {
            return $this->json(['ok' => false, 'msg' => 'This form expired. Please reload the page and try again.'], 419);
        }

        $validator = Validator::make($_POST, [
            'name' => 'required|max:120',
            'phone' => 'required|phone',
            'service' => 'required|max:120',
            'date' => 'required|date',
            'time' => 'required|time',
            'notes' => 'max:500',
            'email' => 'email|max:160',
        ]);
        if ($validator->fails()) {
            return $this->json(['ok' => false, 'msg' => $validator->first()], 422);
        }

        $name = $validator->value('name');
        $phone = Phone::normalize($validator->value('phone')) ?? '';
        $date = $validator->value('date');
        $time = Booking::normalizeTime($validator->value('time'));
        $service = Service::findByName($validator->value('service'))
            ?? ($this->numericService($validator->value('service')));

        if ($service === null) {
            return $this->json(['ok' => false, 'msg' => 'Choose one of our services.'], 422);
        }
        if ($phone === '') {
            return $this->json(['ok' => false, 'msg' => 'Enter a Kenyan number, like 0712 345 678.'], 422);
        }
        $today = date('Y-m-d');
        if ($date < $today || $date > Schedule::maxDate()) {
            return $this->json(['ok' => false, 'msg' => 'Choose a date between today and '
                . date('j M Y', strtotime(Schedule::maxDate())) . '.'], 422);
        }
        if (Schedule::forDate($date)['closed']) {
            return $this->json(['ok' => false, 'msg' => 'We are closed on that date. Please pick another.'], 409);
        }
        $lead = max(0, Setting::int('lead_time_minutes', 60));
        $slotMinute = strtotime($date . ' ' . $time);
        if ($slotMinute === false) {
            return $this->json(['ok' => false, 'msg' => 'Choose a time.'], 422);
        }
        if ($slotMinute < time() + ($lead * 60)) {
            return $this->json(['ok' => false, 'msg' => 'We need at least ' . $lead . ' minutes notice. Pick a later time.'], 422);
        }

        $ip = $request->ip;
        if ($this->tooMany($ip, $phone)) {
            return $this->json(['ok' => false, 'msg' => 'Too many requests. Please call or WhatsApp us instead.'], 429);
        }

        $capacity = max(1, Setting::int('capacity', 2));
        $bookedIds = BookingStatus::bookedIds();
        $lock = 'salon_slot_' . substr(md5($date . '|' . $time), 0, 24);
        $locked = (string) (Database::value('SELECT GET_LOCK(?, 4)', [$lock]) ?? '0') === '1';

        try {
            $created = Database::transaction(function () use ($date, $time, $capacity, $bookedIds, $service, $validator, $phone, $ip): array {
                if (Booking::slotCount($date, $time, $bookedIds) >= $capacity) {
                    throw new \RuntimeException('full');
                }
                return Booking::create([
                    'client_name' => $validator->value('name'),
                    'phone' => $phone,
                    'email' => $validator->value('email'),
                    'service_id' => (int) $service['id'],
                    'service_name' => (string) $service['name'],
                    'booking_date' => $date,
                    'booking_time' => $time,
                    'notes' => $validator->value('notes'),
                    'source' => 'website',
                    'ip' => $ip,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'full') {
                return $this->json(['ok' => false, 'msg' => 'That time just filled up. Please pick another.'], 409);
            }
            error_log('booking insert failed: ' . $e->getMessage());
            return $this->json(['ok' => false, 'msg' => 'We could not save your booking. Please call us.'], 500);
        } finally {
            if ($locked) {
                Database::value('SELECT RELEASE_LOCK(?)', [$lock]);
            }
        }

        $booking = Booking::find((int) $created['id']) ?? [];
        try {
            SmsService::notifyBooking($booking);
        } catch (\Throwable $t) {
            error_log('booking SMS failed: ' . $t->getMessage());
        }

        return $this->json([
            'ok' => true,
            'ref' => $created['ref'],
            'msg' => Setting::get('booking_success_text', 'Booking received.') . ' Your reference is ' . $created['ref'] . '.',
        ]);
    }

    private function numericService(string $value): ?array
    {
        return is_numeric($value) ? Service::find((int) $value) : null;
    }

    private function tooMany(string $ip, string $phone): bool
    {
        $byIp = (int) (Database::value(
            'SELECT COUNT(*) FROM bookings WHERE ip = ? AND created_at > NOW() - INTERVAL 1 HOUR', [$ip]) ?? 0);
        $byPhone = (int) (Database::value(
            'SELECT COUNT(*) FROM bookings WHERE phone = ? AND booking_date = CURDATE()', [$phone]) ?? 0);
        return $byIp >= 5 || $byPhone >= 3;
    }
}
