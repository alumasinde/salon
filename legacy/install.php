<?php
require __DIR__ . '/db.php';
try {
    $installed = db()->query("SHOW TABLES LIKE 'admins'")->fetch() && q('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
} catch (Throwable $t) { exit('Cannot connect. Check config.php and create the database first.'); }
if ($installed) { http_response_code(403); exit('Already installed. Delete install.php from the server.'); }

$err = ''; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['user'] ?? ''); $p = $_POST['pass'] ?? '';
    if (strlen($u) < 3 || strlen($p) < 8) { $err = 'Username needs 3+ characters and password 8+.'; }
    else {
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/schema.sql')))) as $sql) db()->exec($sql);
        q('INSERT INTO admins (username, pass_hash) VALUES (?, ?)', [$u, password_hash($p, PASSWORD_DEFAULT)]);
        foreach (['name' => 'Zuri Beauty Lounge', 'whatsapp' => '254700000000', 'phone' => '0700 000 000',
                  'email' => 'hello@zuribeauty.co.ke', 'address' => '2nd Floor, Sarit Centre Road, Westlands, Nairobi',
                  'capacity' => '2'] as $k => $v) q('REPLACE INTO settings (k, v) VALUES (?, ?)', [$k, $v]);
        $seed = "Hair|Knotless braids|4 to 6 hours|3500\nHair|Box braids|4 to 6 hours|3000\nHair|Cornrows|1 to 3 hours|800\nHair|Silk press|2 hours|2500\nHair|Wash, blow-dry and style|1 hour|1200\nHair|Loc retwist|2 hours|1500\nHair|Hair treatment|1 hour|1800\nNails|Classic manicure|45 min|800\nNails|Gel polish|1 hour|1500\nNails|Acrylic full set|2 hours|2500\nNails|Pedicure|1 hour|1200\nMakeup|Day makeup|1 hour|2500\nMakeup|Evening glam|1.5 hours|3500\nMakeup|Bridal makeup and trial|3 hours|12000\nSpa|Express facial|45 min|2000\nSpa|Back and shoulder massage|30 min|1800\nSpa|Eyebrow shaping|20 min|500";
        foreach (explode("\n", $seed) as $i => $line) {
            [$c, $n, $d, $pr] = explode('|', $line);
            q('INSERT INTO services (category, name, duration, price, sort) VALUES (?,?,?,?,?)', [$c, $n, $d, $pr, ($i + 1) * 10]);
        }
        $done = true;
    }
}
?><!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install</title>
<body style="font:16px system-ui;max-width:360px;margin:60px auto;padding:0 16px">
<?php if ($done): ?>
  <h2>Done</h2><p>Delete <b>install.php</b> now, then <a href="admin/">open the admin panel</a>.</p>
<?php else: ?>
  <h2>Create admin account</h2><p style="color:#b3261e"><?= e($err) ?></p>
  <form method="post" style="display:grid;gap:12px">
    <input name="user" placeholder="Username" required style="padding:10px">
    <input name="pass" type="password" placeholder="Password (8+ characters)" required style="padding:10px">
    <button style="padding:12px">Install</button>
  </form>
<?php endif ?>
