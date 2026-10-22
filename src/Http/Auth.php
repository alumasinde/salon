<?php

declare(strict_types=1);

namespace Salon\Http;

use Salon\DB\Database;
use Salon\Model\Admin;
use Salon\Support\Config;

/** Session-backed admin authentication with throttling and idle timeouts. */
final class Auth
{
    private static ?array $user = null;
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        $secure = !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_name(Config::get('SESSION_NAME', 'salon_admin'));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => base_path() === '' ? '/' : rtrim(base_path(), '/') . '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;
        self::guardLifetime();
    }

    private static function guardLifetime(): void
    {
        if (!isset($_SESSION['uid'])) {
            return;
        }
        $idle = Config::int('SESSION_IDLE_SECONDS', 1800);
        $absolute = Config::int('SESSION_MAX_SECONDS', 28800);
        $now = time();
        $last = (int) ($_SESSION['last_seen'] ?? 0);
        $born = (int) ($_SESSION['login_at'] ?? 0);
        if (($idle > 0 && $last > 0 && $now - $last > $idle) || ($absolute > 0 && $born > 0 && $now - $born > $absolute)) {
            self::logout();
            session_destroy();
            $_SESSION = [];
            return;
        }
        $_SESSION['last_seen'] = $now;
    }

    public static function user(): ?array
    {
        self::start();
        if (self::$user !== null) {
            return self::$user;
        }
        $id = (int) ($_SESSION['uid'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $user = Admin::find($id);
        if ($user === null || (int) $user['active'] !== 1) {
            self::logout();
            return null;
        }
        return self::$user = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $username, string $password, string $ip): bool
    {
        self::start();
        $identifier = mb_substr(strtolower(trim($username)), 0, 120);
        if (self::throttled($identifier, $ip)) {
            Activity::log(null, 'login_blocked', $identifier, $ip);
            return false;
        }
        $user = Admin::findByUsername($identifier);
        $ok = $user !== null && password_verify($password, $user['pass_hash']);
        self::record($identifier, $ip, $ok);
        if (!$ok) {
            usleep(400000);
            return false;
        }
        if (password_needs_rehash($user['pass_hash'], defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')
            && in_array('argon2id', password_algos(), true) ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            Admin::setPassword((int) $user['id'], $password);
        }
        session_regenerate_id(true);
        $_SESSION = ['uid' => (int) $user['id'], 'login_at' => time(), 'last_seen' => time(),
            'csrf' => bin2hex(random_bytes(16))];
        self::$user = $user;
        Admin::touchLogin((int) $user['id']);
        Activity::log((int) $user['id'], 'login', $user['username'], $ip);
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $user = self::$user;
        if ($user !== null) {
            Activity::log((int) $user['id'], 'logout', $user['username'], (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function throttled(string $identifier, string $ip): bool
    {
        $window = 900;
        $failures = (int) (Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE successful = 0
             AND created_at > NOW() - INTERVAL ' . $window . ' SECOND AND (identifier = ? OR ip = ?)',
            [$identifier, $ip]) ?? 0);
        return $failures >= 8;
    }

    private static function record(string $identifier, string $ip, bool $ok): void
    {
        Database::exec('INSERT INTO login_attempts (identifier, ip, successful) VALUES (?,?,?)',
            [$identifier, $ip, $ok ? 1 : 0]);
        Database::exec('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY');
    }

    public static function can(string $ability): bool
    {
        return Admin::canManage(self::user(), $ability);
    }

    /** Session status used to tell the admin whether the DB is reachable. */
    public static function mustChangePassword(): bool
    {
        return (int) ((self::user()['force_password_change'] ?? 0)) === 1;
    }
}
