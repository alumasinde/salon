<?php

declare(strict_types=1);

namespace Salon\DB;

use PDO;
use PDOStatement;
use Salon\Support\Config;
use RuntimeException;

/** Thin PDO wrapper. All application SQL goes through here as prepared statements. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::int('DB_PORT', 3306),
            Config::get('DB_NAME', 'salon'),
            Config::get('DB_CHARSET', 'utf8mb4')
        );
        try {
            self::$pdo = new PDO($dsn, Config::get('DB_USER', 'root'), Config::get('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (\Throwable $t) {
            throw new RuntimeException(
                'Cannot connect to database "' . Config::get('DB_NAME', 'salon') . '" on '
                . Config::get('DB_HOST', '127.0.0.1') . ':' . Config::int('DB_PORT', 3306)
                . '. Check .env, then run: php bin/migrate.php up --create-database ('
                . $t->getMessage() . ')'
            );
        }
        return self::$pdo;
    }

    public static function serverDsn(): string
    {
        return sprintf('mysql:host=%s;port=%d;charset=%s', Config::get('DB_HOST', '127.0.0.1'),
            Config::int('DB_PORT', 3306), Config::get('DB_CHARSET', 'utf8mb4'));
    }

    public static function q(string $sql, array $args = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($args);
        return $st;
    }

    public static function all(string $sql, array $args = []): array
    {
        return self::q($sql, $args)->fetchAll();
    }

    public static function one(string $sql, array $args = []): ?array
    {
        $row = self::q($sql, $args)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $args = []): mixed
    {
        $value = self::q($sql, $args)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $sql, array $args = []): int
    {
        self::q($sql, $args);
        return (int) self::pdo()->lastInsertId();
    }

    public static function exec(string $sql, array $args = []): int
    {
        $st = self::q($sql, $args);
        return $st->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $t) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $t;
        }
    }
}
