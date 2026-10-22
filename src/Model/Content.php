<?php

declare(strict_types=1);

namespace Salon\Model;

use Salon\DB\Database;
use Salon\Http\TenantResolver;

/** Editable copy, feature blocks and testimonials. */
final class Content
{
    private static ?array $textCache = null;

    public static function text(string $key, string $default = ''): string
    {
        if (self::$textCache === null) {
            $tenantId = TenantResolver::getId();
            self::$textCache = array_column(
                Database::all('SELECT `key`, `value` FROM site_content WHERE tenant_id = ?', [$tenantId]),
                'value',
                'key'
            );
        }
        $value = trim((string) (self::$textCache[$key] ?? ''));
        return $value === '' ? $default : $value;
    }

    public static function rows(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT * FROM site_content WHERE tenant_id = ? ORDER BY sort, `key`', [$tenantId]);
    }

    public static function setText(string $key, string $value, string $label = ''): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec(
            'INSERT INTO site_content (`key`, `value`, label, tenant_id) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, mb_substr(trim($value), 0, 4000), $label, $tenantId]
        );
        self::$textCache = null;
    }

    public static function blocks(string $type): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all(
            'SELECT * FROM site_blocks WHERE block_type = ? AND active = 1 AND tenant_id = ? ORDER BY sort, id',
            [$type, $tenantId]
        );
    }

    public static function allBlocks(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT * FROM site_blocks WHERE tenant_id = ? ORDER BY block_type, sort, id', [$tenantId]);
    }

    public static function saveBlock(array $d, int $id = 0): int
    {
        $tenantId = TenantResolver::getId();
        $fields = [
            'tenant_id' => $tenantId,
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
            Database::exec('UPDATE site_blocks SET block_type=?, title=?, body=?, sort=?, active=? WHERE id=? AND tenant_id=?',
                [...array_values($fields), $id, $tenantId]);
            return $id;
        }
        return Database::insert(
            'INSERT INTO site_blocks (tenant_id, block_type, title, body, sort, active) VALUES (?,?,?,?,?,?)',
            array_values($fields)
        );
    }

    public static function deleteBlock(int $id): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec('DELETE FROM site_blocks WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
    }

    public static function reviews(int $limit = 0): array
    {
        $tenantId = TenantResolver::getId();
        $sql = 'SELECT * FROM reviews WHERE active = 1 AND tenant_id = ? ORDER BY sort, id';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, min(20, $limit));
        }
        return Database::all($sql, [$tenantId]);
    }

    public static function allReviews(): array
    {
        $tenantId = TenantResolver::getId();
        return Database::all('SELECT * FROM reviews WHERE tenant_id = ? ORDER BY sort, id', [$tenantId]);
    }

    public static function saveReview(array $d, int $id = 0): int
    {
        $tenantId = TenantResolver::getId();
        $fields = [
            'tenant_id' => $tenantId,
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
            Database::exec('UPDATE reviews SET author=?, location=?, rating=?, body=?, sort=?, active=? WHERE id=? AND tenant_id=?',
                [...array_values($fields), $id, $tenantId]);
            return $id;
        }
        return Database::insert(
            'INSERT INTO reviews (tenant_id, author, location, rating, body, sort, active) VALUES (?,?,?,?,?,?,?)',
            array_values($fields)
        );
    }

    public static function deleteReview(int $id): void
    {
        $tenantId = TenantResolver::getId();
        Database::exec('DELETE FROM reviews WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
    }
}
