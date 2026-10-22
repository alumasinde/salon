<?php

use Salon\Http\Csrf;

$action = $tenant === null ? 'admin/tenants/new' : 'admin/tenants/' . (int) $tenant['id'];
?>
<h2><?= $tenant === null ? 'New tenant' : 'Edit tenant' ?></h2>

<form method="post" action="<?= e(url($action)) ?>">
  <div class="card">
    <?= Csrf::field() ?>
    <label>Business name
      <input name="name" value="<?= e($tenant['name'] ?? '') ?>" required maxlength="120"></label>
    <label>Domain
      <input name="domain" value="<?= e($tenant['domain'] ?? '') ?>" required maxlength="255"
        placeholder="salon-name.co.ke">
      <span class="hint">The address visitors reach this salon on. Point the DNS at this server first.</span></label>

    <div class="grid cols2">
      <label>Status
        <select name="status">
          <?php foreach (['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended'] as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($tenant['status'] ?? 'pending') === $value ? 'selected' : '' ?>>
              <?= e($label) ?></option>
          <?php endforeach ?>
        </select>
        <span class="hint">Only active tenants serve pages.</span></label>
      <label>Plan
        <select name="plan">
          <?php foreach (['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium'] as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($tenant['plan'] ?? 'basic') === $value ? 'selected' : '' ?>>
              <?= e($label) ?></option>
          <?php endforeach ?>
        </select></label>
    </div>

    <div class="grid cols2">
      <label>Admin accounts
        <input type="number" name="max_admins" min="1" max="100"
          value="<?= e((string) ($tenant['max_admins'] ?? '5')) ?>" required>
        <span class="hint">Team page stops adding accounts at this number.</span></label>
      <label>Bookings per month
        <input type="number" name="max_bookings_per_month" min="1" max="100000"
          value="<?= e((string) ($tenant['max_bookings_per_month'] ?? '500')) ?>" required>
        <span class="hint">Online and walk-in booking both stop once the month reaches this.</span></label>
    </div>

    <div class="grid cols2">
      <label>Primary colour
        <input type="color" name="primary_color" value="<?= e($tenant['primary_color'] ?? '#3a1233') ?>"></label>
      <label>Accent colour
        <input type="color" name="accent_color" value="<?= e($tenant['accent_color'] ?? '#d6336c') ?>"></label>
    </div>

    <p class="hint">These two colours drive the public site of this salon. Each admin can still pick their
      own panel colours under Colours.</p>
  </div>

  <div class="card">
    <h3>Branding</h3>
    <label>Logo URL
      <input name="logo_url" value="<?= e($tenant['logo_url'] ?? '') ?>" maxlength="255"
        placeholder="https://example.co.ke/logo.png"></label>
    <label>Favicon URL
      <input name="favicon_url" value="<?= e($tenant['favicon_url'] ?? '') ?>" maxlength="255"
        placeholder="https://example.co.ke/favicon.ico"></label>
    <label>Extra CSS
      <textarea name="custom_css" rows="6"
        placeholder=".hero{padding:40px 0}"><?= e($tenant['custom_css'] ?? '') ?></textarea>
      <span class="hint">Added to this salon's public page. Angle brackets are stripped.</span></label>
  </div>

  <div class="card">
    <button class="btn" type="submit"><?= $tenant === null ? 'Add tenant' : 'Save changes' ?></button>
    <a class="btn" href="<?= e(url('admin/tenants')) ?>">Cancel</a>
    <?php if ($tenant !== null): ?>
      <p class="hint">A new tenant starts with no hours, services or statuses of its own. Open its domain
        and fill in the panel before telling customers about it.</p>
    <?php endif ?>
  </div>
</form>
