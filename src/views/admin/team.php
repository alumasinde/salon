<?php

use Salon\Http\Csrf;
use Salon\Model\Admin;

$currentId = (int) $admin['id'];
?>
<h2>Team</h2>

<div class="card">
  <p class="hint">Owners manage everything, including settings and this page. Managers handle bookings, services, hours,
    content and SMS. Staff only see bookings and their own appearance. Changing a password here sends that person to the
    password-change screen on their next login.</p>
  <div class="head-col">
    <div class="formrow header wide">
      <span>First</span><span>Last</span><span>Username</span><span>Email</span><span>Phone</span>
      <span>Role</span><span>SMS</span><span>Active</span><span>New password</span><span>Save</span>
    </div>
    <?php foreach ($rows as $row): ?>
      <form method="post" action="<?= e(url('admin/team/save')) ?>" class="formrow wide">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <input name="first_name" value="<?= e($row['first_name']) ?>" required maxlength="60">
        <input name="last_name" value="<?= e($row['last_name']) ?>" maxlength="60">
        <input name="username" value="<?= e($row['username']) ?>" required maxlength="60" readonly>
        <input name="email" type="email" value="<?= e($row['email']) ?>" maxlength="160" placeholder="optional">
        <input name="phone" value="<?= e($row['phone']) ?>" maxlength="20" placeholder="0712 345 678">
        <select name="role">
          <?php foreach ($roles as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $row['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach ?>
        </select>
        <span class="center"><input type="checkbox" name="sms_opt_in" value="1" <?= (int) $row['sms_opt_in'] === 1 ? 'checked' : '' ?>></span>
        <span class="center"><input type="checkbox" name="active" value="1" <?= (int) $row['active'] === 1 ? 'checked' : '' ?>></span>
        <input type="password" name="password" placeholder="keep current" maxlength="72" autocomplete="new-password">
        <span class="row center">
          <button class="btn small" type="submit">Save</button>
          <?php if ((int) $row['id'] !== $currentId): ?>
            <button class="btn small danger" type="submit" formaction="<?= e(url('admin/team/delete')) ?>"
                    data-confirm="Delete the account <?= e($row['username']) ?>?">Delete</button>
          <?php endif ?>
        </span>
      </form>
      <p class="hint">
        Colours: <?= e(Admin::PRESETS[$row['theme_preset']]['label'] ?? 'Custom') ?>
        · last signed in <?= e($row['last_login_at'] === null ? 'never' : date('j M Y H:i', strtotime((string) $row['last_login_at']))) ?>
      </p>
    <?php endforeach ?>

    <form method="post" action="<?= e(url('admin/team/save')) ?>" class="formrow wide">
      <?= Csrf::field() ?><input type="hidden" name="id" value="0">
      <input name="first_name" placeholder="First name" required maxlength="60">
      <input name="last_name" placeholder="Last name" maxlength="60">
      <input name="username" placeholder="username" required maxlength="60">
      <input name="email" type="email" placeholder="email" maxlength="160">
      <input name="phone" placeholder="0712 345 678" maxlength="20">
      <select name="role">
        <?php foreach ($roles as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $value === 'staff' ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
      </select>
      <span class="center"><input type="checkbox" name="sms_opt_in" value="1"></span>
      <span class="center"><input type="checkbox" name="active" value="1" checked></span>
      <input type="password" name="password" placeholder="min 10 characters" minlength="10" required autocomplete="new-password">
      <span class="row center"><button class="btn small" type="submit">Add account</button></span>
    </form>
  </div>
</div>

<div class="card">
  <h3>Locked out?</h3>
  <p class="hint">Sign-in is throttled after too many wrong passwords. Clear the attempts for a username, or for an IP address.</p>
  <form method="post" action="<?= e(url('admin/team/unlock')) ?>" class="row">
    <?= Csrf::field() ?>
    <input name="username" placeholder="username" maxlength="60">
    <input name="ip" placeholder="IP address" maxlength="45">
    <button class="btn subtle" type="submit">Clear attempts</button>
  </form>
</div>
