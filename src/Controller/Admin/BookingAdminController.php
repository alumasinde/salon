<?php

declare(strict_types=1);

namespace Salon\Controller\Admin;

use Salon\Http\Activity;
use Salon\Http\AdminController;
use Salon\Http\Flash;
use Salon\Http\Request;
use Salon\Http\Validator;
use Salon\Model\Booking;
use Salon\Model\BookingStatus;
use Salon\Model\Schedule;
use Salon\Model\Service;
use Salon\Model\Setting;
use Salon\Sms\Gateway;
use Salon\Sms\SmsService;

final class BookingAdminController extends AdminController
{
    public function index(Request $request): string
    {
        $filters = [
            'view' => $request->query('view', 'upcoming'),
            'status_id' => $request->int('status_id'),
            'q' => $request->query('q'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'page' => max(1, $request->int('page', 1)),
            'dir' => $request->query('dir', 'asc'),
        ];
        return $this->render('admin/bookings', 'Bookings', [
            'filters' => $filters,
            'result' => Booking::search($filters),
        ], 'bookings');
    }

    public function create(Request $request): string
    {
        return $this->editor($request, null);
    }

    public function edit(Request $request, string $id = ''): string
    {
        $booking = $id === '' ? null : Booking::find((int) $id);
        if ($id !== '' && $booking === null) {
            Flash::push('That booking no longer exists.', 'err');
            redirect('admin/bookings');
        }
        return $this->editor($request, $booking);
    }

    public function store(Request $request, string $id = ''): string
    {
        $this->requirePost($request);
        $admin = $this->guard('bookings');
        $this->requireCsrf();

        $existing = $id === '' ? null : Booking::find((int) $id);
        if ($id !== '' && $existing === null) {
            Flash::push('That booking no longer exists.', 'err');
            redirect('admin/bookings');
        }

        $validator = Validator::make($_POST, [
            'client_name' => 'required|max:120',
            'phone' => 'required|phone',
            'service_name' => 'required|max:120',
            'booking_date' => 'required|date',
            'booking_time' => 'required|time',
            'notes' => 'max:500',
            'email' => 'email|max:160',
        ]);
        if ($validator->fails()) {
            Flash::push($validator->first(), 'err');
            redirect($id === '' ? 'admin/bookings/new' : 'admin/bookings/' . (int) $id);
        }

        $date = $validator->value('booking_date');
        $time = Booking::normalizeTime($validator->value('booking_time'));
        $statusId = $validator->int('status_id', BookingStatus::defaultId());
        if (!BookingStatus::validId($statusId)) {
            $statusId = BookingStatus::defaultId();
        }
        $capacity = max(1, Setting::int('capacity', 2));
        if (Booking::slotCount($date, $time, BookingStatus::bookedIds(), (int) ($existing['id'] ?? 0)) >= $capacity
            && (int) (BookingStatus::find($statusId)['counts_as_booked'] ?? 0) === 1) {
            Flash::push("That slot already holds $capacity bookings. Change the time or a status first.", 'err');
            redirect($id === '' ? 'admin/bookings/new' : 'admin/bookings/' . (int) $id);
        }

        $payload = [
            'client_name' => $validator->value('client_name'),
            'phone' => $validator->value('phone'),
            'email' => $validator->value('email'),
            'service_id' => $validator->value('service_id'),
            'service_name' => $validator->value('service_name'),
            'booking_date' => $date,
            'booking_time' => $time,
            'notes' => $validator->value('notes'),
            'status_id' => $statusId,
        ];

        if ($existing === null) {
            $created = Booking::create($payload + ['source' => 'walk-in', 'ip' => '']);
            Activity::log((int) $admin['id'], 'booking_create', $created['ref'] . ' ' . $payload['client_name'], $request->ip);
            Flash::push('Booking ' . $created['ref'] . ' added.');
        } else {
            Booking::update((int) $existing['id'], $payload);
            Activity::log((int) $admin['id'], 'booking_update', (string) $existing['ref_code'], $request->ip);
            Flash::push('Booking updated.');
        }
        redirect('admin/bookings');
    }

    public function status(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('bookings');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $statusId = (int) ($_POST['status_id'] ?? 0);
        $booking = Booking::find($id);
        if ($booking === null || !BookingStatus::validId($statusId)) {
            Flash::push('Could not change that booking.', 'err');
            redirect('admin/bookings');
        }
        Booking::setStatus($id, $statusId);
        Activity::log((int) $admin['id'], 'booking_status',
            $booking['ref_code'] . ' -> ' . (string) (BookingStatus::find($statusId)['name'] ?? $statusId), $request->ip);
        redirect('admin/bookings');
    }

    public function delete(Request $request): string
    {
        $this->requirePost($request);
        $admin = $this->guard('bookings');
        $this->requireCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $booking = Booking::find($id);
        if ($booking !== null) {
            Booking::delete($id);
            Activity::log((int) $admin['id'], 'booking_delete', (string) $booking['ref_code'], $request->ip);
            Flash::push('Deleted booking ' . $booking['ref_code'] . '.');
        }
        redirect('admin/bookings');
    }

    public function export(Request $request): string
    {
        $this->guard('bookings');
        $filters = ['view' => $request->query('view', 'upcoming'), 'per_page' => 5000, 'page' => 1,
            'q' => $request->query('q'), 'status_id' => $request->int('status_id'),
            'from' => $request->query('from'), 'to' => $request->query('to'), 'dir' => 'asc'];
        $rows = Booking::search($filters)['rows'];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookings-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Ref', 'Date', 'Time', 'Client', 'Phone', 'Service', 'Price', 'Status', 'Source', 'Notes']);
        foreach ($rows as $row) {
            fputcsv($out, [(string) $row['ref_code'], (string) $row['booking_date'], (string) $row['booking_time'],
                (string) $row['client_name'], (string) $row['phone'], (string) $row['service_name'],
                (string) $row['service_price'], (string) $row['status_name'], (string) $row['source'],
                (string) $row['notes']]);
        }
        fclose($out);
        Activity::log($this->actorId(), 'booking_export', (string) count($rows), $request->ip);
        return '';
    }

    private function editor(Request $request, ?array $booking): string
    {
        $date = (string) ($booking['booking_date'] ?? date('Y-m-d'));
        $capacity = max(1, Setting::int('capacity', 2));
        $slots = Schedule::slots($date, $capacity);
        if ($booking !== null) {
            $own = Booking::normalizeTime((string) $booking['booking_time']);
            $found = false;
            foreach ($slots as $index => $slot) {
                if ($slot['time'] === $own) {
                    $slots[$index]['left'] = $capacity;
                    $found = true;
                }
            }
            if (!$found) {
                array_unshift($slots, ['time' => $own, 'left' => $capacity]);
            }
        }
        return $this->render('admin/booking_edit',
            $booking === null ? 'New booking' : 'Booking ' . $booking['ref_code'], [
                'booking' => $booking,
                'services' => Service::active(),
                'slots' => $slots,
                'capacity' => $capacity,
                'maxDate' => Schedule::maxDate(),
                'smsLog' => $booking === null ? [] : SmsService::forBooking((int) $booking['id']),
                'smsReady' => Gateway::ready(),
                'recipients' => SmsService::recipients(),
            ], 'bookings');
    }
}
