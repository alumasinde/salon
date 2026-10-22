<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\DB\Database;

/** Audit trail for admin actions and login events. */
final class Activity
{
    public static function log(?int $adminId, string $action, string $detail = '', string $ip = ''): void
    {
        try {
            Database::exec('INSERT INTO activity_log (admin_id, action, detail, ip) VALUES (?,?,?,?)',
                [$adminId, mb_substr($action, 0, 60), mb_substr($detail, 0, 255), mb_substr($ip, 0, 45)]);
        } catch (\Throwable) {
            // Auditing must never break the action being audited.
        }
    }

    public static function recent(int $limit = 20): array
    {
        return Database::all(
            'SELECT a.*, ad.username FROM activity_log a LEFT JOIN admins ad ON ad.id = a.admin_id
             ORDER BY a.id DESC LIMIT ' . max(1, min(100, $limit))
        );
    }
}
