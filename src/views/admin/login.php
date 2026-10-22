<?php use Salon\Http\Csrf; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Sign in · <?= e($siteName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<style nonce="<?= e($nonce) ?>">
:root{--primary:<?= e($theme['primary']) ?>;--accent:<?= e($theme['accent']) ?>;--bg:<?= e($theme['bg']) ?>;
--surface:<?= e($theme['surface']) ?>;--text:<?= e($theme['text']) ?>;--line:<?= e($theme['line']) ?>;
--muted:<?= e($theme['muted']) ?>;--on-primary:<?= e($theme['on_primary']) ?>;--on-accent:<?= e($theme['on_accent']) ?>;
--accent-hover:<?= e($theme['accent_hover']) ?>;--primary-soft:<?= e($theme['primary_soft']) ?>;}
body{display:grid;place-items:center;min-height:100vh}
</style>
</head>
<body>
<div class="narrow">
  <div class="card">
    <h2><?= e($siteName) ?></h2>
    <p class="muted small">Admin panel. Sign in with the account your owner created.</p>
    <?php foreach ($flashes as $flash): ?>
      <div class="flash <?= e($flash['type'] === 'err' ? 'err' : 'ok') ?>"><?= e($flash['message']) ?></div>
    <?php endforeach ?>
    <?php if ($error !== ''): ?><div class="flash err"><?= e($error) ?></div><?php endif ?>
    <form method="post" action="<?= e(url('admin/login')) ?>" autocomplete="on">
      <?= Csrf::field() ?>
      <label>Username
        <input name="user" autocomplete="username" required autofocus></label>
      <label>Password
        <input name="pass" type="password" autocomplete="current-password" required></label>
      <button class="btn" type="submit">Log in</button>
    </form>
  </div>
</div>
</body>
</html>
