<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;

/** Editable copy, feature blocks and testimonials. */
final class Content
{
    private static ?array $textCache = null;

    public static function text(string $key, string $default = ''): string
    {
        if (self::$textCache === null) {
            self::$textCache = array_column(
                Database::all('SELECT `key`, `value` FROM site_content'), 'value', 'key'
            );
        }
        $value = trim((string) (self::$textCache[$key] ?? ''));
        return $value === '' ? $default : $value;
    }

    public static function rows(): array
    {
        return Database::all('SELECT * FROM site_content ORDER BY sort, `key`');
    }

    public static function setText(string $key, string $value, string $label = ''): void
    {
        Database::exec(
            'INSERT INTO site_content (`key`, `value`, label) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, mb_substr(trim($value), 0, 4000), $label]
        );
        self::$textCache = null;
    }

    public static function blocks(string $type): array
    {
        return Database::all(
            'SELECT * FROM site_blocks WHERE block_type = ? AND active = 1 ORDER BY sort, id', [$type]
        );
    }

    public static function allBlocks(): array
    {
        return Database::all('SELECT * FROM site_blocks ORDER BY block_type, sort, id');
    }

    public static function saveBlock(array $d, int $id = 0): int
    {
        $fields = [
            'block_type' => mb_substr(trim($d['block_type'] ?? 'why'), 0, 30),
            'title' => mb_substr(trim($d['title'] ?? ''), 0, 120),
            'body' => mb_substr(trim($d['body'] ?? ''), 0, 500),
            'sort' => (int) ($d['sort'] ?? 0),
            'active' => !empty($d['active']) ? 1 : 0,
        ];
        if ($fields['title'] === '') {
            return $id;
        }
        if ($id > 0) {
            Database::exec('UPDATE site_blocks SET block_type=?, title=?, body=?, sort=?, active=? WHERE id=?',
                [...array_values($fields), $id]);
            return $id;
        }
        return Database::insert(
            'INSERT INTO site_blocks (block_type, title, body, sort, active) VALUES (?,?,?,?,?)',
            array_values($fields)
        );
    }

    public static function deleteBlock(int $id): void
    {
        Database::exec('DELETE FROM site_blocks WHERE id = ?', [$id]);
    }

    public static function reviews(int $limit = 0): array
    {
        $sql = 'SELECT * FROM reviews WHERE active = 1 ORDER BY sort, id';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, min(20, $limit));
        }
        return Database::all($sql);
    }

    public static function allReviews(): array
    {
        return Database::all('SELECT * FROM reviews ORDER BY sort, id');
    }

    public static function saveReview(array $d, int $id = 0): int
    {
        $fields = [
            'author' => mb_substr(trim($d['author'] ?? ''), 0, 80),
            'location' => mb_substr(trim($d['location'] ?? ''), 0, 80),
            'rating' => max(1, min(5, (int) ($d['rating'] ?? 5))),
            'body' => mb_substr(trim($d['body'] ?? ''), 0, 600),
            'sort' => (int) ($d['sort'] ?? 0),
            'active' => !empty($d['active']) ? 1 : 0,
        ];
        if ($fields['author'] === '' || $fields['body'] === '') {
            return $id;
        }
        if ($id > 0) {
            Database::exec('UPDATE reviews SET author=?, location=?, rating=?, body=?, sort=?, active=? WHERE id=?',
                [...array_values($fields), $id]);
            return $id;
        }
        return Database::insert(
            'INSERT INTO reviews (author, location, rating, body, sort, active) VALUES (?,?,?,?,?,?)',
            array_values($fields)
        );
    }

    public static function deleteReview(int $id): void
    {
        Database::exec('DELETE FROM reviews WHERE id = ?', [$id]);
    }
}
