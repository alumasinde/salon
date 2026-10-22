<?php

use Salon\Http\Csrf;
use Salon\Model\Setting;

$salonName = Setting::get('salon_name', 'Salon');
$roleLabels = Salon\Model\Admin::ROLES;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title ?? 'Admin') ?> · <?= e($salonName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<style nonce="<?= e($nonce) ?>">
:root{
  --primary:<?= e($theme['primary']) ?>;--accent:<?= e($theme['accent']) ?>;--bg:<?= e($theme['bg']) ?>;
  --surface:<?= e($theme['surface']) ?>;--text:<?= e($theme['text']) ?>;--line:<?= e($theme['line']) ?>;
  --muted:<?= e($theme['muted']) ?>;--on-primary:<?= e($theme['on_primary']) ?>;--on-accent:<?= e($theme['on_accent']) ?>;
  --accent-hover:<?= e($theme['accent_hover']) ?>;--accent-soft:<?= e($theme['accent_soft']) ?>;
  --primary-soft:<?= e($theme['primary_soft']) ?>;--gold:<?= e($theme['gold']) ?>;
}
<?php foreach ($statuses as $status): ?>
.st-<?= (int) $status['id'] ?>{background:<?= e($status['color']) ?>;color:<?= e($status['text_color']) ?>}
<?php endforeach ?>
</style>
</head>
<body>
<div class="topbar">
  <div class="wrap">
    <a class="brand" href="<?= e(url('admin')) ?>"><?= e($salonName) ?></a>
    <?php foreach ($nav as $item): ?>
      <a class="link <?= ($current ?? '') === $item['key'] ? 'on' : '' ?>" href="<?= e(url($item['path'])) ?>">
        <?= e($item['label']) ?><?php if (!empty($item['badge'])): ?><span class="pill"><?= (int) $item['badge'] ?></span><?php endif ?>
      </a>
    <?php endforeach ?>
    <a class="link <?= ($current ?? '') === 'admin/theme' ? 'on' : '' ?>" href="<?= e(url('admin/theme')) ?>">Appearance</a>
    <span class="sp"></span>
    <span class="userbox"><?= e($admin['first_name'] ?: $admin['username']) ?>
      · <?= e($roleLabels[$admin['role']] ?? $admin['role']) ?></span>
    <a class="link" href="<?= e(url('')) ?>" target="_blank" rel="noopener">View site</a>
    <form method="post" action="<?= e(url('admin/logout')) ?>">
      <?= Csrf::field() ?><button class="link">Log out</button>
    </form>
  </div>
</div>
<main>
  <div class="wrap">
    <?php foreach ($flashes as $flash): ?>
      <div class="flash <?= e($flash['type'] === 'err' ? 'err' : 'ok') ?>"><?= e($flash['message']) ?></div>
    <?php endforeach ?>
    <?= $content ?>
  </div>
</main>
<script nonce="<?= e($nonce) ?>" src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
