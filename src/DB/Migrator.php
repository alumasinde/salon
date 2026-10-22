<?php

declare(strict_types=1);

namespace Salon\DB;

use RuntimeException;

/**
 * Runs ordered .sql migration files from /migrations.
 *
 * Each file may contain an "up" section and a section after the marker
 * "-- @down" used by rollback. Statements are separated on semicolons that
 * sit outside quoted strings and comments.
 */
final class Migrator
{
    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? SALON_ROOT . '/migrations';
    }

    public function files(): array
    {
        $files = glob($this->dir . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);
        return $files;
    }

    public function ensureTable(): void
    {
        Database::pdo()->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(190) NOT NULL UNIQUE,
            batch INT NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB');
    }

    public function appliedFiles(): array
    {
        return array_column($this->appliedRecords(), 'filename');
    }

    public function status(): array
    {
        $applied = [];
        foreach ($this->appliedRecords() as $r) {
            $applied[$r['filename']] = $r;
        }
        $rows = [];
        foreach ($this->files() as $path) {
            $name = basename($path);
            $rows[] = ['file' => $name, 'applied' => isset($applied[$name]),
                'batch' => $applied[$name]['batch'] ?? null, 'at' => $applied[$name]['applied_at'] ?? null];
        }
        return $rows;
    }

    public function appliedRecords(): array
    {
        $this->ensureTable();
        return Database::all('SELECT filename, batch, applied_at FROM schema_migrations ORDER BY filename');
    }

    /** Apply every pending migration. Returns the list of applied filenames. */
    public function up(?string $until = null): array
    {
        $this->ensureTable();
        $done = $this->appliedFiles();
        $batch = (int) (Database::value('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations') ?? 0) + 1;
        $ran = [];
        foreach ($this->files() as $path) {
            $name = basename($path);
            if (in_array($name, $done, true)) {
                continue;
            }
            $this->run($this->statements(file_get_contents($path), 'up'), $name);
            Database::exec('INSERT INTO schema_migrations (filename, batch) VALUES (?, ?)', [$name, $batch]);
            $ran[] = $name;
            if ($until !== null && $name === $until) {
                break;
            }
        }
        return $ran;
    }

    /** Roll back the last $steps applied migrations, newest first. */
    public function down(int $steps = 1): array
    {
        $this->ensureTable();
        $undone = [];
        for ($i = 0; $i < $steps; $i++) {
            $row = Database::one('SELECT filename FROM schema_migrations ORDER BY id DESC LIMIT 1');
            if ($row === null) {
                break;
            }
            $path = $this->dir . '/' . $row['filename'];
            if (!is_readable($path)) {
                throw new RuntimeException('Migration file missing for rollback: ' . $row['filename']);
            }
            $statements = $this->statements(file_get_contents($path), 'down');
            if ($statements === []) {
                throw new RuntimeException('Migration has no down section: ' . $row['filename']);
            }
            $this->run($statements, $row['filename']);
            Database::exec('DELETE FROM schema_migrations WHERE filename = ?', [$row['filename']]);
            $undone[] = $row['filename'];
        }
        return $undone;
    }

    private function run(array $statements, string $name): void
    {
        foreach ($statements as $sql) {
            try {
                Database::pdo()->exec($sql);
            } catch (\Throwable $t) {
                throw new RuntimeException('In ' . $name . ': ' . $t->getMessage() . "\n  SQL: "
                    . substr(preg_replace('/\s+/', ' ', $sql), 0, 300));
            }
        }
    }

    /** @return string[] */
    public function statements(string $sql, string $section = 'up'): array
    {
        if ($section === 'down') {
            $pos = strpos($sql, '-- @down');
            $sql = $pos === false ? '' : substr($sql, $pos + 8);
        } else {
            $pos = strpos($sql, '-- @down');
            if ($pos !== false) {
                $sql = substr($sql, 0, $pos);
            }
        }
        $out = [];
        $buffer = '';
        $quote = null;
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($quote === null && $c === '-' && ($sql[$i + 1] ?? '') === '-') {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                continue;
            }
            if ($quote === null && $c === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                continue;
            }
            if ($quote !== null) {
                $buffer .= $c;
                if ($c === '\\' && $quote !== '`') {
                    $buffer .= $sql[++$i] ?? '';
                    continue;
                }
                if ($c === $quote && ($sql[$i + 1] ?? '') !== $quote) {
                    $quote = null;
                } elseif ($c === $quote && ($sql[$i + 1] ?? '') === $quote) {
                    $buffer .= $sql[++$i];
                }
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') {
                $quote = $c;
                $buffer .= $c;
                continue;
            }
            if ($c === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $out[] = $trimmed;
                }
                $buffer = '';
                continue;
            }
            $buffer .= $c;
        }
        if (trim($buffer) !== '' && !preg_match('/^(\s|--)/', trim($buffer))) {
            $out[] = trim($buffer);
        }
        return array_values(array_filter($out, static fn(string $s): bool => trim($s) !== ''));
    }
}
