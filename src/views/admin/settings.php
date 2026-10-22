<?php

use Salon\Controller\Admin\SettingController;
use Salon\Http\Csrf;

$values = array_column($rows, 'value', 'key');
$labels = array_column($rows, 'label', 'key');

$groups = [
    'Salon details' => ['salon_name', 'phone', 'whatsapp', 'email', 'address', 'map_query', 'social_instagram', 'social_tiktok', 'meta_description'],
    'Booking rules' => ['capacity', 'slot_step', 'lead_time_minutes', 'max_days_ahead', 'deposit_note', 'booking_success_text'],
];

$numbers = [
    'capacity' => [1, 200],
    'slot_step' => [5, 240],
    'lead_time_minutes' => [0, 1440],
    'max_days_ahead' => [1, 365],
];
$textareas = ['address', 'meta_description', 'deposit_note', 'booking_success_text'];
$types = ['email' => 'email', 'whatsapp' => 'tel', 'phone' => 'tel'];
?>
<h2>Settings</h2>

<div class="card">
  <p class="hint">Booking SMS lives on its own page so the gateway credentials stay together.
    Migrations applied: <?= (int) $migrationCount ?> · bookings: <?= (int) $counts['bookings'] ?> ·
    services: <?= (int) $counts['services'] ?> · admin accounts: <?= (int) $counts['admins'] ?>.</p>
</div>

<?php $first = true; foreach ($groups as $heading => $keys): ?>
  <?php if ($first) { $first = false; echo '<div class="grid cols2">'; } ?>
  <div class="card">
    <h3><?= e($heading) ?></h3>
    <form method="post" action="<?= e(url('admin/settings/save')) ?>">
      <?= Csrf::field() ?>
      <?php foreach ($keys as $key): ?>
        <?php if (!array_key_exists($key, SettingController::RULES)) {
            continue;
        } ?>
        <?php $value = (string) ($values[$key] ?? ''); $label = $labels[$key] ?? $key; ?>
        <label><?= e($label) ?>
          <?php if (in_array($key, $textareas, true)): ?>
            <textarea name="<?= e($key) ?>" rows="2" maxlength="255"><?= e($value) ?></textarea>
          <?php elseif (isset($numbers[$key])): ?>
            <input name="<?= e($key) ?>" type="number" min="<?= (int) $numbers[$key][0] ?>"
                   max="<?= (int) $numbers[$key][1] ?>" value="<?= e($value) ?>">
          <?php else: ?>
            <input name="<?= e($key) ?>" type="<?= e($types[$key] ?? 'text') ?>" value="<?= e($value) ?>" maxlength="255">
          <?php endif ?>
        </label>
      <?php endforeach ?>
      <button class="btn" type="submit">Save <?= e(strtolower($heading)) ?></button>
    </form>
  </div>
<?php endforeach ?>
