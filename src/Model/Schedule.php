<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Http\TenantResolver;
use Salon\Support\Clock;

/** Opening hours, calendar overrides and bookable slots. */
final class Schedule
{
    private const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public static function byDay(): array
    {
        $tenantId = TenantResolver::getId();
        $rows = Database::all('SELECT * FROM opening_hours WHERE tenant_id = ?', [$tenantId]);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['day_of_week']] = $row;
        }
        $out = [];
        for ($d = 0; $d < 7; $d++) {
            $out[$d] = $map[$d] ?? ['day_of_week' => $d, 'opens' => '08:00:00', 'closes' => '20:00:00',
                'closed' => 0, 'note' => ''];
        }
        return $out;
    }

    /** Consecutive days with identical hours collapse into one display row. */
    public static function groupedWeek(): array
    {
        $byDay = self::byDay();
        $order = [1, 2, 3, 4, 5, 6, 0];
        $short = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
        $groups = [];
        foreach ($order as $day) {
            $row = $byDay[$day];
            $signature = $row['closed'] ? 'closed' : $row['opens'] . '|' . $row['closes'];
            $last = $groups ? array_key_last($groups) : null;
            if ($last !== null && $groups[$last]['signature'] === $signature
                && ((int) $groups[$last]['to'] + 1) % 7 === $day) {
                $groups[$last]['to'] = $day;
                continue;
            }
            $groups[] = ['from' => $day, 'to' => $day, 'signature' => $signature, 'row' => $row];
        }
        $out = [];
        $today = (int) date('w');
        foreach ($groups as $g) {
            $label = $g['from'] === $g['to']
                ? $short[$g['from']]
                : $short[$g['from']] . ' – ' . $short[$g['to']];
            $row = $g['row'];
            $days = [];
            for ($d = $g['from']; ; $d = ($d + 1) % 7) {
                $days[] = $d;
                if ($d === (int) $g['to']) {
                    break;
                }
            }
            $out[] = [
                'days' => $label,
                'is_today' => in_array($today, $days, true),
                'time' => (int) $row['closed'] === 1 ? 'Closed' : Clock::label(substr($row['opens'], 0, 5))
                    . ' – ' . Clock::label(substr($row['closes'], 0, 5)),
                'closed' => (int) $row['closed'] === 1,
            ];
        }
        return $out;
    }

    public static function forDate(string $date): array
    {
        $tenantId = TenantResolver::getId();
        $special = Database::one('SELECT * FROM special_days WHERE `date` = ? AND tenant_id = ?', [$date, $tenantId]);
        if ($special !== null) {
            return [
                'closed' => (int) $special['closed'] === 1,
                'opens' => (string) ($special['opens'] ?? '08:00:00'),
                'closes' => (string) ($special['closes'] ?? '20:00:00'),
                'label' => (string) $special['label'],
            ];
        }
        $row = self::byDay()[(int) date('w', (int) strtotime($date))];
        return [
            'closed' => (int) $row['closed'] === 1,
            'opens' => (string) $row['opens'],
            'closes' => (string) $row['closes'],
            'label' => '',
        ];
    }

    /** @return array<int, array{time:string,left:int}> */
    public static function slots(string $date, int $capacity = 1, int $leadMinutes = 0): array
    {
        $day = self::forDate($date);
        if ($day['closed']) {
            return [];
        }
        $fromMinute = 0;
        if ($date === date('Y-m-d')) {
            $fromMinute = (int) date('G') * 60 + (int) date('i') + max(0, $leadMinutes);
        }
        $step = max(5, Setting::int('slot_step', 30));
        $booked = BookingStatus::bookedIds();
        $out = [];
        foreach (Clock::slots($day['opens'], $day['closes'], $step, $fromMinute) as $label) {
            $used = Booking::slotCount($date, $label, $booked);
            $out[] = ['time' => $label, 'left' => max(0, $capacity - $used)];
        }
        return $out;
    }

    public static function openState(): array
    {
        $day = self::forDate(date('Y-m-d'));
        if ($day['closed']) {
            return ['open' => false, 'label' => 'Closed today', 'until' => ''];
        }
        $now = Clock::minutesOfDay(date('H:i'));
        $opens = Clock::minutesOfDay(substr($day['opens'], 0, 5));
        $closes = Clock::minutesOfDay(substr($day['closes'], 0, 5));
        if ($now < $opens) {
            return ['open' => false, 'label' => 'Opens ' . Clock::label(substr($day['opens'], 0, 5)),
                'until' => substr($day['opens'], 0, 5)];
        }
        if ($now >= $closes) {
            return ['open' => false, 'label' => 'Closed now', 'until' => ''];
        }
        return ['open' => true, 'label' => 'Open now', 'until' => substr($day['closes'], 0, 5)];
    }

    public static function maxDate(): string
    {
        return date('Y-m-d', strtotime('+' . max(1, Setting::int('max_days_ahead', 90)) . ' day'));
    }

    public static function dayName(int $dow): string
    {
        return self::DAY_NAMES[$dow] ?? '';
    }

    public static function save(int $dayOfWeek, array $data): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec(
            'INSERT INTO opening_hours (tenant_id, day_of_week, opens, closes, closed, note) VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE opens = VALUES(opens), closes = VALUES(closes),
                 closed = VALUES(closed), note = VALUES(note)',
            [
                $tenantId,
                $dayOfWeek,
                $data['opens'] ?? '08:00:00',
                $data['closes'] ?? '20:00:00',
                !empty($data['closed']) ? 1 : 0,
                mb_substr(trim($data['note'] ?? ''), 0, 120),
            ]
        );
    }

    public static function specials(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT * FROM special_days WHERE tenant_id = ? AND `date` >= CURDATE() ORDER BY `date`', [$tenantId]);
    }

    public static function saveSpecial(array $data, int $id = 0): void
    {
        $tenantId = TenantResolver::getId();
        $fields = [
            'tenant_id' => $tenantId,
            'date' => $data['date'] ?? date('Y-m-d'),
            'closed' => !empty($data['closed']) ? 1 : 0,
            'opens' => ($data['opens'] ?? '') === '' ? null : $data['opens'],
            'closes' => ($data['closes'] ?? '') === '' ? null : $data['closes'],
            'label' => mb_substr(trim($data['label'] ?? ''), 0, 80),
        ];
        if ($id > 0) {
            Database::exec('UPDATE special_days SET `date`=?, closed=?, opens=?, closes=?, label=? WHERE id=? AND tenant_id=?',
                [...array_values($fields), $id, $tenantId]);
            return;
        }
        Database::exec(
            'INSERT INTO special_days (tenant_id, `date`, closed, opens, closes, label) VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE closed = VALUES(closed), opens = VALUES(opens),
                 closes = VALUES(closes), label = VALUES(label)',
            array_values($fields)
        );
    }

    public static function deleteSpecial(int $id): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec('DELETE FROM special_days WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
    }
}
