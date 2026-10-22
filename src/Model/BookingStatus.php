<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Support\Color;

/** Configurable booking workflow states (the admin panel edits these, code reads `code`). */
final class BookingStatus
{
    public static function all(): array
    {
        return Database::all('SELECT * FROM booking_statuses ORDER BY sort, id');
    }

    public static function active(): array
    {
        return Database::all('SELECT * FROM booking_statuses WHERE active = 1 ORDER BY sort, id');
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM booking_statuses WHERE id = ?', [$id]);
    }

    public static function byCode(string $code): ?array
    {
        return Database::one('SELECT * FROM booking_statuses WHERE code = ?', [$code]);
    }

    public static function defaultId(): int
    {
        $id = Database::value('SELECT id FROM booking_statuses WHERE is_default = 1 AND active = 1 LIMIT 1')
            ?? Database::value('SELECT id FROM booking_statuses WHERE code = ? AND active = 1 LIMIT 1', ['new'])
            ?? Database::value('SELECT id FROM booking_statuses ORDER BY sort, id LIMIT 1');
        return (int) $id;
    }

    /** Status ids that occupy a time slot for capacity checks. */
    public static function bookedIds(): array
    {
        $ids = Database::all('SELECT id FROM booking_statuses WHERE counts_as_booked = 1 AND active = 1');
        return array_map('intval', array_column($ids, 'id'));
    }

    public static function idForCode(string $code): ?int
    {
        $row = self::byCode($code);
        return $row === null ? null : (int) $row['id'];
    }

    public static function validId(int $id): bool
    {
        return self::find($id) !== null;
    }

    public static function save(array $data, int $id = 0): int
    {
        $fields = [
            'code' => strtolower(preg_replace('/[^a-z0-9_]/i', '', $data['code'] ?? '') ?? ''),
            'name' => mb_substr(trim($data['name'] ?? ''), 0, 60),
            'color' => Color::normalize($data['color'] ?? '#6f5d6b', '#6f5d6b'),
            'text_color' => Color::normalize($data['text_color'] ?? '#ffffff', '#ffffff'),
            'sort' => (int) ($data['sort'] ?? 0),
            'counts_as_booked' => !empty($data['counts_as_booked']) ? 1 : 0,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
        ];
        if ($fields['code'] === '' || $fields['name'] === '') {
            return $id;
        }
        if ($id > 0) {
            Database::exec(
                'UPDATE booking_statuses SET code=?, name=?, color=?, text_color=?, sort=?,
                 counts_as_booked=?, is_default=?, active=? WHERE id=?',
                [...array_values($fields), $id]
            );
            return $id;
        }
        $columns = implode(', ', array_map(static fn(string $k): string => "`$k`", array_keys($fields)));
        $placeholders = rtrim(str_repeat('?, ', count($fields)), ', ');
        return Database::insert("INSERT INTO booking_statuses ($columns) VALUES ($placeholders)", array_values($fields));
    }

    public static function delete(int $id): bool
    {
        if ((int) (Database::value('SELECT COUNT(*) FROM booking_statuses') ?? 1) <= 1) {
            return false;
        }
        if ((int) (Database::value('SELECT COUNT(*) FROM bookings WHERE status_id = ?', [$id]) ?? 0) > 0) {
            return false;
        }
        return Database::exec('DELETE FROM booking_statuses WHERE id = ?', [$id]) > 0;
    }
}
