<?php
require __DIR__ . '/../db.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
$csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$in = isset($_SESSION['uid']);
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['a'] ?? '';
    if ($a === 'login') {
        $u = q('SELECT * FROM admins WHERE username = ?', [trim($_POST['user'] ?? '')])->fetch();
        if ($u && password_verify($_POST['pass'] ?? '', $u['pass_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id'];
            header('Location: ./'); exit;
        }
        sleep(1); $err = 'Wrong username or password.';
    } elseif ($in && hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $id = (int)($_POST['id'] ?? 0);
        if ($a === 'status' && in_array($_POST['status'] ?? '', ['new', 'confirmed', 'done', 'cancelled'], true)) {
            q('UPDATE bookings SET status = ? WHERE id = ?', [$_POST['status'], $id]);
        } elseif ($a === 'svc_save' || $a === 'svc_add') {
            $v = [trim($_POST['category']), trim($_POST['name']), trim($_POST['duration']), max(0, (int)$_POST['price']), (int)$_POST['sort'], isset($_POST['active']) ? 1 : 0];
            if ($a === 'svc_add') q('INSERT INTO services (category, name, duration, price, sort, active) VALUES (?,?,?,?,?,?)', $v);
            else q('UPDATE services SET category=?, name=?, duration=?, price=?, sort=?, active=? WHERE id=?', [...$v, $id]);
        } elseif ($a === 'svc_del') {
            q('DELETE FROM services WHERE id = ?', [$id]);
        } elseif ($a === 'settings') {
            foreach (['name', 'whatsapp', 'phone', 'email', 'address', 'capacity'] as $k)
                q('REPLACE INTO settings (k, v) VALUES (?, ?)', [$k, trim($_POST[$k] ?? '')]);
            if (strlen($_POST['pass'] ?? '') >= 8)
                q('UPDATE admins SET pass_hash = ? WHERE id = ?', [password_hash($_POST['pass'], PASSWORD_DEFAULT), $_SESSION['uid']]);
        } elseif ($a === 'logout') {
            session_destroy(); header('Location: ./'); exit;
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    } else { $err = 'Session expired. Refresh and try again.'; }
}
$T = '<input type="hidden" name="csrf" value="' . $csrf . '">';
$p = $_GET['p'] ?? 'bookings';
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>Salon admin</title>
<style>
body{font:15px/1.5 system-ui,sans-serif;margin:0;background:#f6f1f4;color:#2a1626}
.w{max-width:1000px;margin:0 auto;padding:16px}
nav{background:#3a1233;color:#fff}nav .w{display:flex;gap:6px;align-items:center;flex-wrap:wrap;padding:10px 16px}
nav a,nav button{color:#fff;text-decoration:none;padding:8px 14px;border-radius:8px;background:none;border:0;font:inherit;cursor:pointer}
nav a.on{background:#d6336c}nav .sp{flex:1}
.card{background:#fff;border-radius:12px;padding:16px;margin-bottom:14px;overflow-x:auto}
table{width:100%;border-collapse:collapse}td,th{padding:8px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
input,select{padding:8px;border:1px solid #d9c9d3;border-radius:8px;font:inherit;max-width:100%}
button.b{background:#d6336c;color:#fff;border:0;border-radius:8px;padding:8px 14px;font:inherit;cursor:pointer}
button.d{background:#fff;color:#b3261e;border:1px solid #b3261e;border-radius:8px;padding:8px 12px;cursor:pointer}
.s{display:inline-block;padding:2px 10px;border-radius:99px;font-size:13px;background:#eee}
.new{background:#fde2ea}.confirmed{background:#d9f3e3}.cancelled{background:#eee;color:#777}
.row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px}
.err{color:#b3261e}label{display:grid;gap:4px;margin-bottom:10px;font-weight:600}
</style></head><body>
<?php if (!$in): ?>
<div class="w" style="max-width:360px;margin-top:60px"><div class="card"><h2>Admin login</h2>
<p class="err"><?= e($err) ?></p>
<form method="post" style="display:grid;gap:12px"><input type="hidden" name="a" value="login">
<input name="user" placeholder="Username" autocomplete="username" required>
<input name="pass" type="password" placeholder="Password" autocomplete="current-password" required>
<button class="b">Log in</button></form></div></div>
<?php else: ?>
<nav><div class="w">
<a href="?p=bookings" class="<?= $p === 'bookings' ? 'on' : '' ?>">Bookings</a>
<a href="?p=services" class="<?= $p === 'services' ? 'on' : '' ?>">Services</a>
<a href="?p=settings" class="<?= $p === 'settings' ? 'on' : '' ?>">Settings</a>
<a href="../" target="_blank">View site</a><span class="sp"></span>
<form method="post"><?= $T ?><input type="hidden" name="a" value="logout"><button>Log out</button></form>
</div></nav>
<div class="w"><p class="err"><?= e($err) ?></p>

<?php if ($p === 'services'):
    $rows = q('SELECT * FROM services ORDER BY sort, id')->fetchAll(); ?>
<div class="card"><h2>Services</h2><p>Lower sort numbers show first. Categories appear in the order of their first service.</p>
<?php foreach ($rows as $r): ?>
<form method="post" class="row"><?= $T ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
<input name="category" value="<?= e($r['category']) ?>" size="9" required placeholder="Category">
<input name="name" value="<?= e($r['name']) ?>" size="22" required placeholder="Name">
<input name="duration" value="<?= e($r['duration']) ?>" size="11" placeholder="Duration">
<input name="price" type="number" min="0" value="<?= $r['price'] ?>" style="width:84px" required>
<input name="sort" type="number" value="<?= $r['sort'] ?>" style="width:64px">
<label style="display:flex;gap:4px;margin:0;font-weight:400"><input type="checkbox" name="active" <?= $r['active'] ? 'checked' : '' ?>>Show</label>
<button class="b" name="a" value="svc_save">Save</button>
<button class="d" name="a" value="svc_del" onclick="return confirm('Delete this service?')">Delete</button></form>
<?php endforeach ?>
</div>
<div class="card"><h3>Add a service</h3>
<form method="post" class="row"><?= $T ?><input type="hidden" name="a" value="svc_add">
<input name="category" size="9" required placeholder="Category"><input name="name" size="22" required placeholder="Name">
<input name="duration" size="11" placeholder="Duration"><input name="price" type="number" min="0" style="width:84px" required placeholder="KES">
<input name="sort" type="number" value="100" style="width:64px"><input type="hidden" name="active" value="1">
<button class="b">Add</button></form></div>

<?php elseif ($p === 'settings'): $s = settings(); ?>
<div class="card" style="max-width:520px"><h2>Settings</h2>
<form method="post"><?= $T ?><input type="hidden" name="a" value="settings">
<?php foreach (['name' => 'Salon name', 'whatsapp' => 'WhatsApp number (254712345678)', 'phone' => 'Phone (shown on site)', 'email' => 'Email', 'address' => 'Address', 'capacity' => 'Bookings allowed per time slot'] as $k => $l): ?>
<label><?= e($l) ?><input name="<?= $k ?>" value="<?= e($s[$k] ?? '') ?>" <?= $k === 'capacity' ? 'type="number" min="1"' : '' ?> required></label>
<?php endforeach ?>
<label>New admin password (leave blank to keep)<input name="pass" type="password" minlength="8" autocomplete="new-password"></label>
<button class="b">Save settings</button></form></div>

<?php else:
    $f = $_GET['f'] ?? 'upcoming';
    $where = ['upcoming' => "date >= CURDATE() AND status IN ('new','confirmed')", 'new' => "status = 'new'", 'all' => '1'][$f] ?? '1';
    $dir = $f === 'all' ? 'DESC' : 'ASC';
    $rows = q("SELECT * FROM bookings WHERE $where ORDER BY date $dir, STR_TO_DATE(time, '%l:%i %p'), id LIMIT 200")->fetchAll();
    $nNew = q("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn(); ?>
<div class="card"><div class="row">
<a href="?f=upcoming">Upcoming</a> · <a href="?f=new">New (<?= (int)$nNew ?>)</a> · <a href="?f=all">All</a></div>
<table><tr><th>When</th><th>Client</th><th>Service</th><th>Status</th></tr>
<?php foreach ($rows as $r):
    $wa = preg_replace('/^0/', '254', ltrim($r['phone'], '+')); ?>
<tr><td><b><?= e(date('D j M', strtotime($r['date']))) ?></b><br><?= e($r['time']) ?></td>
<td><?= e($r['name']) ?><br><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= e($r['phone']) ?></a></td>
<td><?= e($r['service']) ?><?= $r['notes'] ? '<br><small>' . e($r['notes']) . '</small>' : '' ?></td>
<td><form method="post" class="row"><?= $T ?><input type="hidden" name="a" value="status"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<select name="status" onchange="this.form.submit()">
<?php foreach (['new', 'confirmed', 'done', 'cancelled'] as $st): ?><option <?= $r['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach ?>
</select></form></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="4">No bookings here yet.</td></tr>'; ?>
</table></div>
<?php endif ?>
</div>
<?php endif ?>
</body></html>
