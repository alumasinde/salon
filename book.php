<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json');

function out(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'msg' => $msg]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request.', 405);
if (!empty($_POST['website'])) out(true, 'Booking received.'); // honeypot: bots fill this in

$name    = trim($_POST['name'] ?? '');
$phone   = preg_replace('/[\s-]/', '', $_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$date    = $_POST['date'] ?? '';
$time    = trim($_POST['time'] ?? '');
$notes   = mb_substr(trim($_POST['notes'] ?? ''), 0, 300);

if (mb_strlen($name) < 2 || mb_strlen($name) > 80)              out(false, 'Enter your name.', 422);
if (!preg_match('/^(\+?254|0)[17]\d{8}$/', $phone))             out(false, 'Enter a Kenyan number, like 0712 345 678.', 422);
$d = DateTime::createFromFormat('!Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d')) out(false, 'Choose a date from today onwards.', 422);
if (!preg_match('/^\d{1,2}:(00|30) (am|pm)$/', $time))          out(false, 'Choose a time.', 422);
if (!q('SELECT 1 FROM services WHERE name = ? AND active = 1', [$service])->fetch()) out(false, 'Choose a service.', 422);

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (q('SELECT COUNT(*) FROM bookings WHERE ip = ? AND created_at > NOW() - INTERVAL 1 HOUR', [$ip])->fetchColumn() >= 5)
    out(false, 'Too many requests. Please call us instead.', 429);

$cap = (int)(settings()['capacity'] ?? 2);
if (q("SELECT COUNT(*) FROM bookings WHERE date = ? AND time = ? AND status IN ('new','confirmed')", [$date, $time])->fetchColumn() >= $cap)
    out(false, 'That time is full. Please pick another.', 409);

q('INSERT INTO bookings (name, phone, service, date, time, notes, ip) VALUES (?,?,?,?,?,?,?)',
  [$name, $phone, $service, $date, $time, $notes, $ip]);
out(true, 'Booking received. We will confirm on WhatsApp shortly.');
