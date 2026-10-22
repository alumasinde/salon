<?php
use Salon\Http\Csrf;
use Salon\Http\TenantResolver;
use Salon\Model\Tenant;

$tenant = TenantResolver::current();
$tenantTheme = Tenant::getTheme($tenant);
$primaryColor = $tenantTheme['primary'] ?? '#3a1233';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($settings['salon_name'] ?? 'Salon') ?> | Hair, Nails &amp; Beauty in Nairobi</title>
<meta name="description" content="<?= e($settings['meta_description'] ?? '') ?>">
<meta name="theme-color" content="<?= e($primaryColor) ?>">
<?php if ($tenant !== null && $tenant['favicon_url'] ?? '' !== ''): ?>
<link rel="icon" href="<?= e($tenant['favicon_url']) ?>">
<?php endif ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<style nonce="<?= e($nonce ?? '') ?>">
:root {
  --primary: <?= e($primaryColor) ?>;
  --accent: <?= e($tenantTheme['accent'] ?? '#d6336c') ?>;
  --primary-soft: <?= e($tenantTheme['primary_soft'] ?? '#f6f1f4') ?>;
  --accent-soft: <?= e($tenantTheme['accent_soft'] ?? '#fce4ec') ?>;
}
<?php if ($tenant !== null && ($tenant['custom_css'] ?? '') !== ''): ?>
<?= $tenant['custom_css'] ?>
<?php endif ?>
</style>
</head>
<body>
<?= $content ?>
<script nonce="<?= e($nonce ?? '') ?>" src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
