<?php

/**
 * Database migration CLI.
 *
 *   php bin/migrate.php status
 *   php bin/migrate.php up [--create-database] [--force]
 *   php bin/migrate.php up --until=20261022_0300_seed_content.sql
 *   php bin/migrate.php rollback --steps=1 --force
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Salon\DB\Database;
use Salon\DB\Migrator;
use Salon\Support\Config;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script only runs from the command line.\n");
}

$args = array_slice($argv, 1);
$command = array_shift($args) ?: 'status';
$flags = [];
foreach ($args as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/i', $arg, $m)) {
        $flags[$m[1]] = $m[2] ?? true;
    }
}

$confirm = static function (string $question) use ($flags): void {
    if (!empty($flags['force'])) {
        return;
    }
    echo $question . ' [y/N] ';
    $answer = strtolower(trim((string) fgets(STDIN)));
    if ($answer !== 'y' && $answer !== 'yes') {
        echo "Aborted.\n";
        exit(1);
    }
};

try {
    if ($command === 'up' || $command === 'status') {
        if (!empty($flags['create-database'])) {
            $name = Config::get('DB_NAME', 'salon');
            if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                throw new RuntimeException('DB_NAME contains unsafe characters.');
            }
            $root = new PDO(Database::serverDsn(), Config::get('DB_USER', 'root'), Config::get('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $root->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` '
                . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            echo "Database `{$name}` is ready.\n";
        }
    }

    $m = new Migrator();

    switch ($command) {
        case 'status':
            printf("%-4s %-44s %-7s %s\n", '', 'MIGRATION', 'STATUS', 'APPLIED AT');
            foreach ($m->status() as $row) {
                printf("%-4s %-44s %-7s %s\n", '', $row['file'], $row['applied'] ? 'done' : 'pending',
                    $row['at'] ?? '-');
            }
            $pending = count(array_filter($m->status(), static fn(array $r): bool => !$r['applied']));
            echo "\n{$pending} pending.\n";
            echo $pending > 0 ? "Run: php bin/migrate.php up\n" : "Database is up to date.\n";
            break;

        case 'up':
            $ran = $m->up(!empty($flags['until']) ? (string) $flags['until'] : null);
            if ($ran === []) {
                echo "Nothing to apply.\n";
            }
            foreach ($ran as $file) {
                echo "applied  {$file}\n";
            }
            echo count($ran) . " migration(s) applied.\n";
            break;

        case 'rollback':
            $steps = (int) ($flags['steps'] ?? 1);
            $confirm("Roll back the last {$steps} migration(s)? This changes schema and data.");
            $undone = $m->down($steps);
            foreach ($undone as $file) {
                echo "reverted {$file}\n";
            }
            echo count($undone) . " migration(s) rolled back.\n";
            break;

        case 'fresh':
            $confirm('Dropping every table in "' . Config::get('DB_NAME', 'salon') . '" and rebuilding. Continue?');
            foreach (Database::all('SHOW TABLES') as $row) {
                $table = array_values($row)[0];
                if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                    Database::pdo()->exec('DROP TABLE `' . $table . '`');
                }
            }
            $ran = $m->up();
            echo count($ran) . " migration(s) applied on a clean database.\n";
            break;

        default:
            echo "Unknown command: {$command}\n\n";
            echo "Usage: php bin/migrate.php status|up|rollback|fresh [options]\n";
            exit(1);
    }
} catch (Throwable $t) {
    fwrite(STDERR, 'MIGRATION FAILED: ' . $t->getMessage() . "\n");
    exit(1);
}
