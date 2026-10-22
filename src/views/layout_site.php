<?php use Salon\Http\Csrf; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($settings['salon_name'] ?? 'Salon') ?> | Hair, Nails &amp; Beauty in Nairobi</title>
<meta name="description" content="<?= e($settings['meta_description'] ?? '') ?>">
<meta name="theme-color" content="#3a1233">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>
<?= $content ?>
<script nonce="<?= e($nonce ?? '') ?>" src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>
