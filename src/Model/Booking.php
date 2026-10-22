<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Support\Phone;

/** Booking records. */
final class Booking
{
    private const REF_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generateRef(): string
    {
        do {
            $ref = '';
            for ($i = 0; $i < 6; $i++) {
                $ref .= self::REF_ALPHABET[random_int(0, strlen(self::REF_ALPHABET) - 1)];
            }
            $clash = Database::value('SELECT id FROM bookings WHERE ref_code = ?', [$ref]);
        } while ($clash !== null);
        return $ref;
    }

    public static function create(array $d): array
    {
        $service = null;
        if (!empty($d['service_id'])) {
            $service = Service::find((int) $d['service_id']);
        } elseif (!empty($d['service_name'])) {
            $service = Service::findByName((string) $d['service_name']);
        }
        $ref = self::generateRef();
        $id = Database::insert(
            'INSERT INTO bookings (ref_code, client_name, phone, email, service_id, service_name, service_price,
                booking_date, booking_time, notes, status_id, source, ip)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $ref,
                mb_substr(trim($d['client_name'] ?? ''), 0, 120),
                Phone::normalize((string) ($d['phone'] ?? '')) ?? preg_replace('/\D/', '', (string) $d['phone']),
                mb_substr(trim($d['email'] ?? ''), 0, 160),
                $service['id'] ?? null,
                mb_substr((string) ($d['service_name'] ?? $service['name'] ?? 'Unspecified'), 0, 120),
                max(0, (int) ($service['price'] ?? 0)),
                $d['booking_date'] ?? date('Y-m-d'),
                self::normalizeTime((string) ($d['booking_time'] ?? '')),
                mb_substr(trim($d['notes'] ?? ''), 0, 500),
                (int) ($d['status_id'] ?? BookingStatus::defaultId()),
                $d['source'] ?? 'website',
                $d['ip'] ?? '',
            ]
        );
        return ['id' => $id, 'ref' => $ref, 'service' => $service];
    }

    public static function normalizeTime(string $label): string
    {
        $label = strtolower(str_replace('.', '', trim($label)));
        $ts = strtotime($label);
        return $ts === false ? $label : date('g:i a', $ts);
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT b.*, s.name AS status_name, s.code AS status_code, s.color AS status_color,
                s.text_color AS status_text_color, s.counts_as_booked, sv.duration, sv.duration_min, sv.deposit
             FROM bookings b
             LEFT JOIN booking_statuses s ON s.id = b.status_id
             LEFT JOIN services sv ON sv.id = b.service_id
             WHERE b.id = ?',
            [$id]
        );
    }

    public static function findByRef(string $ref): ?array
    {
        return Database::one('SELECT * FROM bookings WHERE ref_code = ?', [strtoupper(trim($ref))]);
    }

    /** How many slot-holding bookings exist for one date+time. */
    public static function slotCount(string $date, string $time, array $bookedIds = [], int $excludeId = 0): int
    {
        $bookedIds = $bookedIds ?: BookingStatus::bookedIds();
        if ($bookedIds === []) {
            return 0;
        }
        $in = rtrim(str_repeat('?, ', count($bookedIds)), ', ');
        return (int) Database::value(
            "SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND booking_time = ?
             AND status_id IN ($in)" . ($excludeId > 0 ? ' AND id <> ?' : ''),
            array_merge([$date, self::normalizeTime($time)], $bookedIds, $excludeId > 0 ? [$excludeId] : [])
        );
    }

    public static function isSlotOpen(string $date, string $time, int $capacity): bool
    {
        return self::slotCount($date, $time) < max(1, $capacity);
    }

    public static function setStatus(int $id, int $statusId): void
    {
        Database::exec('UPDATE bookings SET status_id = ? WHERE id = ?', [$statusId, $id]);
    }

    public static function update(int $id, array $d): void
    {
        $service = !empty($d['service_id']) ? Service::find((int) $d['service_id']) : null;
        Database::exec(
            'UPDATE bookings SET client_name=?, phone=?, email=?, service_id=?, service_name=?, service_price=?,
                booking_date=?, booking_time=?, notes=?, status_id=? WHERE id=?',
            [
                mb_substr(trim($d['client_name'] ?? ''), 0, 120),
                Phone::normalize((string) ($d['phone'] ?? '')) ?? (string) $d['phone'],
                mb_substr(trim($d['email'] ?? ''), 0, 160),
                $service['id'] ?? null,
                mb_substr((string) ($d['service_name'] ?? $service['name'] ?? ''), 0, 120),
                max(0, (int) ($service['price'] ?? ($d['service_price'] ?? 0))),
                $d['booking_date'] ?? date('Y-m-d'),
                self::normalizeTime((string) ($d['booking_time'] ?? '')),
                mb_substr(trim($d['notes'] ?? ''), 0, 500),
                (int) ($d['status_id'] ?? BookingStatus::defaultId()),
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::exec('DELETE FROM bookings WHERE id = ?', [$id]);
    }

    /** Filtered, paginated list for the admin panel. */
    public static function search(array $f): array
    {
        $where = ['1=1'];
        $args = [];
        $view = $f['view'] ?? 'upcoming';
        if ($view === 'upcoming') {
            $where[] = 'b.booking_date >= CURDATE()';
        } elseif ($view === 'today') {
            $where[] = 'b.booking_date = CURDATE()';
        } elseif ($view === 'past') {
            $where[] = 'b.booking_date < CURDATE()';
        } elseif ($view === 'new') {
            $id = BookingStatus::idForCode('new');
            $where[] = 'b.status_id = ?';
            $args[] = $id ?? BookingStatus::defaultId();
        }
        if (!empty($f['status_id'])) {
            $where[] = 'b.status_id = ?';
            $args[] = (int) $f['status_id'];
        }
        if (!empty($f['from'])) {
            $where[] = 'b.booking_date >= ?';
            $args[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 'b.booking_date <= ?';
            $args[] = $f['to'];
        }
        if (!empty($f['q'])) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $f['q']) . '%';
            $where[] = '(b.client_name LIKE ? OR b.phone LIKE ? OR b.ref_code LIKE ? OR b.service_name LIKE ?)';
            array_push($args, $like, $like, strtoupper($f['q']), $like);
        }
        $clause = implode(' AND ', $where);
        $total = (int) (Database::value("SELECT COUNT(*) FROM bookings b WHERE $clause", $args) ?? 0);
        $perPage = max(10, min(200, (int) ($f['per_page'] ?? 25)));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $dir = ($f['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $rows = Database::all(
            "SELECT b.*, s.name AS status_name, s.code AS status_code, s.color AS status_color,
                s.text_color AS status_text_color
             FROM bookings b LEFT JOIN booking_statuses s ON s.id = b.status_id
             WHERE $clause
             ORDER BY b.booking_date $dir, STR_TO_DATE(CONCAT('2000-01-01 ', UPPER(b.booking_time)), '%Y-%m-%d %h:%i %p') $dir,
                 b.id $dir
             LIMIT $perPage OFFSET " . (($page - 1) * $perPage),
            $args
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** Numbers for the admin dashboard. */
    public static function stats(): array
    {
        $newId = BookingStatus::idForCode('new');
        $doneId = BookingStatus::idForCode('done');
        $monthStart = date('Y-m-01');
        return [
            'today' => (int) (Database::value(
                'SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()') ?? 0),
            'upcoming' => (int) (Database::value(
                'SELECT COUNT(*) FROM bookings WHERE booking_date >= CURDATE() AND status_id IN
                 (SELECT id FROM booking_statuses WHERE counts_as_booked = 1)') ?? 0),
            'awaiting' => (int) (Database::value(
                'SELECT COUNT(*) FROM bookings WHERE status_id = ? AND booking_date >= CURDATE()',
                [$newId ?? 0]) ?? 0),
            'this_week' => (int) (Database::value(
                'SELECT COUNT(*) FROM bookings WHERE booking_date BETWEEN CURDATE()
                 AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)') ?? 0),
            'month_total' => (int) (Database::value(
                'SELECT COUNT(*) FROM bookings WHERE booking_date >= ?', [$monthStart]) ?? 0),
            'month_value' => (int) (Database::value(
                'SELECT COALESCE(SUM(service_price), 0) FROM bookings WHERE status_id = ? AND booking_date >= ?',
                [$doneId ?? 0, $monthStart]) ?? 0),
            'busiest_slots' => Database::all(
                'SELECT booking_time, COUNT(*) AS total FROM bookings WHERE booking_date >= CURDATE()
                 GROUP BY booking_time ORDER BY total DESC LIMIT 5'),
            'top_services' => Database::all(
                'SELECT service_name, COUNT(*) AS total FROM bookings
                 GROUP BY service_name ORDER BY total DESC LIMIT 5'),
        ];
    }

    public static function recent(int $limit = 8): array
    {
        return Database::all(
            'SELECT b.*, s.name AS status_name, s.color AS status_color FROM bookings b
             LEFT JOIN booking_statuses s ON s.id = b.status_id
             ORDER BY b.created_at DESC, b.id DESC LIMIT ' . max(1, min(50, $limit))
        );
    }

    /** Day-by-day counts used by the dashboard strip. */
    public static function nextDays(int $days = 7): array
    {
        $days = max(1, min(30, $days));
        $rows = Database::all(
            'SELECT booking_date, COUNT(*) AS total FROM bookings
             WHERE booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . $days . ' DAY)
             GROUP BY booking_date'
        );
        $map = array_column($rows, 'total', 'booking_date');
        $out = [];
        for ($i = 0; $i <= $days; $i++) {
            $date = date('Y-m-d', strtotime("+$i day"));
            $out[] = ['date' => $date, 'total' => (int) ($map[$date] ?? 0)];
        }
        return $out;
    }
}
