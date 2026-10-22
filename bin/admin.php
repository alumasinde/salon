<?php

/**
 * Admin account CLI — replaces the old web installer.
 *
 *   php bin/admin.php list
 *   php bin/admin.php create alice --first=Alice --role=owner --sms=0712345678
 *   php bin/admin.php password alice
 *   php bin/admin.php activate alice
 *
 * The password is asked for once and is never taken from the command line,
 * so it does not end up in the shell history.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Salon\DB\Database;
use Salon\Model\Admin;
use Salon\Support\Phone;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script only runs from the command line.\n");
}

$args = array_slice($argv, 1);
$command = array_shift($args) ?: 'help';
$name = null;
$opts = [];
foreach ($args as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/i', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? '1';
    } elseif ($name === null) {
        $name = strtolower(trim($arg));
    }
}

/** @return array<string,string> */
$opt = static function (string $key, string $default = '') use ($opts): string {
    $value = $opts[$key] ?? null;
    return $value === null || $value === '' ? $default : (string) $value;
};

$ask = static function (string $question): string {
    echo $question;
    $line = fgets(STDIN);
    if ($line === false) {
        fwrite(STDERR, "Cannot read from standard input.\n");
        exit(1);
    }
    return rtrim($line, "\r\n");
};

$readPassword = static function (string $username) use ($ask): string {
    echo "Password for {$username} (typing is visible, at least 10 characters):\n";
    $pass = $ask('> ');
    if (strlen($pass) < 10) {
        fwrite(STDERR, "Password is too short.\n");
        exit(1);
    }
    $again = $ask('Repeat > ');
    if ($again !== $pass) {
        fwrite(STDERR, "The two passwords did not match.\n");
        exit(1);
    }
    return $pass;
};

$byName = static function (string $username): array {
    $row = Database::one('SELECT * FROM admins WHERE username = ?', [$username]);
    if ($row === null) {
        fwrite(STDERR, "No account called \"{$username}\". Try: php bin/admin.php list\n");
        exit(1);
    }
    return $row;
};

try {
    switch ($command) {
        case 'list':
            $rows = Admin::all();
            if ($rows === []) {
                echo "No admin accounts yet.\n";
                break;
            }
            printf("%-4s %-18s %-8s %-7s %-8s %s\n", 'ID', 'USERNAME', 'ROLE', 'ACTIVE', 'SMS', 'LAST LOGIN');
            foreach ($rows as $row) {
                printf("%-4d %-18s %-8s %-7s %-8s %s\n", (int) $row['id'], $row['username'], $row['role'],
                    (int) $row['active'] === 1 ? 'yes' : 'no',
                    (int) $row['sms_opt_in'] === 1 ? 'yes' : 'no',
                    $row['last_login_at'] === null ? 'never' : (string) $row['last_login_at']);
            }
            break;

        case 'create':
            if ($name === null || $name === '') {
                fwrite(STDERR, "Usage: php bin/admin.php create <username> [--first=Name] [--last=Name] [--email=] [--role=owner|manager|staff] [--sms=0712345678]\n");
                exit(1);
            }
            if (Admin::findByUsername($name) !== null || Database::one('SELECT id FROM admins WHERE username = ?', [$name]) !== null) {
                fwrite(STDERR, "That username already exists.\n");
                exit(1);
            }
            $role = $opt('role', 'owner');
            if (!array_key_exists($role, Admin::ROLES)) {
                fwrite(STDERR, "Role must be owner, manager or staff.\n");
                exit(1);
            }
            $phone = $opt('sms');
            if ($phone !== '' && Phone::normalize($phone) === null) {
                fwrite(STDERR, "That phone number does not look Kenyan.\n");
                exit(1);
            }
            $id = Admin::create([
                'first_name' => $opt('first', $name),
                'last_name' => $opt('last'),
                'username' => $name,
                'email' => $opt('email'),
                'phone' => $phone === '' ? '' : (string) Phone::normalize($phone),
                'role' => $role,
                'active' => 1,
                'sms_opt_in' => $phone === '' ? 0 : 1,
                'password' => $readPassword($name),
            ]);
            echo "Created #{$id} {$name} ({$role}). Sign in at /admin\n";
            break;

        case 'password':
            if ($name === null) {
                fwrite(STDERR, "Usage: php bin/admin.php password <username>\n");
                exit(1);
            }
            $admin = $byName($name);
            Admin::setPassword((int) $admin['id'], $readPassword($name));
            echo "Password updated for {$name}.\n";
            break;

        case 'activate':
            if ($name === null) {
                fwrite(STDERR, "Usage: php bin/admin.php activate <username>\n");
                exit(1);
            }
            $admin = $byName($name);
            Database::exec('UPDATE admins SET active = 1 WHERE id = ?', [(int) $admin['id']]);
            echo "{$name} can sign in again.\n";
            break;

        case 'help':
        default:
            echo "Usage: php bin/admin.php list|create|password|activate\n\n";
            echo "  list                         show every account\n";
            echo "  create <user> [options]      add an admin (asks for the password)\n";
            echo "  password <user>              reset a password\n";
            echo "  activate <user>              re-enable a disabled account\n\n";
            echo "Options for create: --first --last --email --role --sms\n";
    }
} catch (Throwable $t) {
    fwrite(STDERR, 'FAILED: ' . $t->getMessage() . "\n");
    exit(1);
}
