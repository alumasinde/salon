<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;

/** Service catalogue and categories. */
final class Service
{
    public static function categories(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM service_categories';
        $sql .= $activeOnly ? ' WHERE active = 1' : '';
        return Database::all($sql . ' ORDER BY sort, id');
    }

    public static function category(int $id): ?array
    {
        return Database::one('SELECT * FROM service_categories WHERE id = ?', [$id]);
    }

    public static function active(): array
    {
        return Database::all(
            'SELECT s.*, c.name AS category FROM services s
             LEFT JOIN service_categories c ON c.id = s.category_id
             WHERE s.active = 1 ORDER BY c.sort, s.sort, s.id'
        );
    }

    public static function all(): array
    {
        return Database::all(
            'SELECT s.*, c.name AS category, COUNT(b.id) AS booking_count FROM services s
             LEFT JOIN service_categories c ON c.id = s.category_id
             LEFT JOIN bookings b ON b.service_id = s.id
             GROUP BY s.id ORDER BY c.sort, s.sort, s.id'
        );
    }

    /** [category => [[name, duration, price], ...]] for the public price list. */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::active() as $row) {
            $key = $row['category'] !== null && $row['category'] !== '' ? $row['category'] : 'Other';
            $out[$key][] = [$row['name'], $row['duration'], (int) $row['price'], (int) $row['id']];
        }
        return $out;
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM services WHERE id = ?', [$id]);
    }

    public static function findByName(string $name): ?array
    {
        return Database::one('SELECT * FROM services WHERE name = ? AND active = 1 ORDER BY id LIMIT 1', [$name]);
    }

    public static function save(array $d, int $id = 0): int
    {
        $name = mb_substr(trim($d['name'] ?? ''), 0, 120);
        $duration = mb_substr(trim($d['duration'] ?? ''), 0, 40);
        $fields = [
            'category_id' => ($d['category_id'] ?? '') === '' ? null : max(0, (int) $d['category_id']),
            'name' => $name,
            'description' => mb_substr(trim($d['description'] ?? ''), 0, 500),
            'duration' => $duration,
            'duration_min' => (int) ($d['duration_min'] ?? 0) > 0 ? (int) $d['duration_min'] : self::parseDuration($duration),
            'price' => max(0, (int) ($d['price'] ?? 0)),
            'deposit' => max(0, (int) ($d['deposit'] ?? 0)),
            'popular' => !empty($d['popular']) ? 1 : 0,
            'sort' => (int) ($d['sort'] ?? 0),
            'active' => !empty($d['active']) ? 1 : 0,
        ];
        if ($name === '') {
            return $id;
        }
        if ($id > 0) {
            Database::exec(
                'UPDATE services SET category_id=?, name=?, description=?, duration=?, duration_min=?, price=?,
                 deposit=?, popular=?, sort=?, active=? WHERE id=?',
                [...array_values($fields), $id]
            );
            return $id;
        }
        $columns = implode(', ', array_map(static fn(string $k): string => "`$k`", array_keys($fields)));
        return Database::insert(
            'INSERT INTO services (' . $columns . ') VALUES (' . rtrim(str_repeat('?, ', count($fields)), ', ') . ')',
            array_values($fields)
        );
    }

    public static function delete(int $id): void
    {
        Database::exec('DELETE FROM services WHERE id = ?', [$id]);
    }

    public static function saveCategory(array $d, int $id = 0): int
    {
        $fields = [
            'name' => mb_substr(trim($d['name'] ?? ''), 0, 60),
            'blurb' => mb_substr(trim($d['blurb'] ?? ''), 0, 255),
            'sort' => (int) ($d['sort'] ?? 0),
            'active' => !empty($d['active']) ? 1 : 0,
        ];
        if ($fields['name'] === '') {
            return $id;
        }
        if ($id > 0) {
            Database::exec('UPDATE service_categories SET name=?, blurb=?, sort=?, active=? WHERE id=?',
                [...array_values($fields), $id]);
            return $id;
        }
        return Database::insert(
            'INSERT INTO service_categories (name, blurb, sort, active) VALUES (?,?,?,?)',
            array_values($fields)
        );
    }

    /** Categories in use cannot be dropped; services fall back to NULL category. */
    public static function deleteCategory(int $id): bool
    {
        if ((int) (Database::value('SELECT COUNT(*) FROM services WHERE category_id = ?', [$id]) ?? 0) > 0) {
            return false;
        }
        return Database::exec('DELETE FROM service_categories WHERE id = ?', [$id]) > 0;
    }

    /** "4 to 6 hours" => 360, "45 min" => 45, "1.5 hours" => 90. */
    public static function parseDuration(string $text): int
    {
        $text = strtolower($text);
        if (str_contains($text, 'hour') || str_contains($text, 'hr')) {
            preg_match_all('/(\d+(?:\.\d+)?)/', $text, $matches);
            $values = array_map('floatval', $matches[1] ?? []);
            if ($values === []) {
                return 60;
            }
            $minutes = (int) round(max($values) * 60);
            return $minutes > 0 ? $minutes : 60;
        }
        if (preg_match('/(\d+)/', $text, $m)) {
            return max(10, (int) $m[1]);
        }
        return 60;
    }
}
