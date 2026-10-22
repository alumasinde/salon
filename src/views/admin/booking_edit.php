<?php

use Salon\Http\Csrf;
use Salon\Support\Phone;

$editing = $booking !== null;
$action = $editing ? 'admin/bookings/' . (int) $booking['id'] : 'admin/bookings/new';
$currentService = (string) ($booking['service_name'] ?? '');
$currentTime = (string) ($booking['booking_time'] ?? '');
?>
<div class="row">
  <h2><?= $editing ? 'Booking ' . e($booking['ref_code']) : 'Add a walk-in booking' ?></h2>
  <span class="sp"></span>
  <a class="btn small ghost" href="<?= e(url('admin/bookings')) ?>">Back to list</a>
</div>

<div class="split">
  <div class="card">
    <form method="post" action="<?= e(url($action)) ?>">
      <?= Csrf::field() ?>
      <div class="grid cols2">
        <label>Client name
          <input name="client_name" value="<?= e($booking['client_name'] ?? '') ?>" required maxlength="120"></label>
        <label>Phone
          <input name="phone" value="<?= e($booking['phone'] ?? '') ?>" required placeholder="0712 345 678"></label>
      </div>
      <label>Email <span class="hint">Optional</span>
        <input name="email" type="email" value="<?= e($booking['email'] ?? '') ?>" maxlength="160"></label>
      <label>Service
        <select name="service_name" required>
          <option value="">Choose a service</option>
          <?php foreach ($services as $service): ?>
            <option value="<?= e($service['name']) ?>" <?= $currentService === $service['name'] ? 'selected' : '' ?>>
              <?= e($service['name']) ?> · <?= number_format((float) $service['price']) ?> KES ·
              <?= e($service['duration']) ?>
            </option>
          <?php endforeach ?>
          <?php if ($editing && $currentService !== '' && !in_array($currentService, array_column($services, 'name'), true)): ?>
            <option value="<?= e($currentService) ?>" selected><?= e($currentService) ?> (no longer in catalogue)</option>
          <?php endif ?>
        </select></label>
      <div class="grid cols2">
        <label>Date
          <input type="date" name="booking_date" value="<?= e($booking['booking_date'] ?? date('Y-m-d')) ?>"
                 min="<?= e(date('Y-m-d')) ?>" max="<?= e($maxDate) ?>" required></label>
        <label>Time
          <select name="booking_time" required>
            <option value="">Choose a time</option>
            <?php foreach ($slots as $slot):
                $isCurrent = $slot['time'] === $currentTime;
                $full = (int) $slot['left'] <= 0 && !$isCurrent; ?>
              <option value="<?= e($slot['time']) ?>" <?= $isCurrent ? 'selected' : '' ?> <?= $full ? 'disabled' : '' ?>>
                <?= e(ucfirst($slot['time'])) ?><?= (int) $slot['left'] <= 0 ? ' (full)' : '' ?>
              </option>
            <?php endforeach ?>
          </select></label>
      </div>
      <label>Status
        <select name="status_id">
          <?php foreach ($statuses as $status): ?>
            <option value="<?= (int) $status['id'] ?>"
              <?= (int) ($booking['status_id'] ?? 0) === (int) $status['id'] ? 'selected' : '' ?>>
              <?= e($status['name']) ?><?= (int) $status['counts_as_booked'] === 1 ? ' (holds a slot)' : '' ?></option>
          <?php endforeach ?>
        </select></label>
      <label>Notes
        <textarea name="notes" maxlength="500"><?= e($booking['notes'] ?? '') ?></textarea></label>
      <p class="hint">This slot accepts <?= (int) $capacity ?> client(s). Full times are greyed out.</p>
      <div class="row">
        <button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Add booking' ?></button>
        <?php if ($editing): ?>
          <a class="btn ghost" href="<?= e(url('admin/bookings')) ?>">Cancel</a>
        <?php endif ?>
      </div>
    </form>
  </div>

  <div>
    <?php if ($editing): ?>
      <div class="card">
        <h3>Notify by SMS</h3>
        <p class="small muted">Sends to <?= count($recipients) ?> number(s):
          <?= e(implode(', ', array_map(static fn(array $r): string => $r['label'], $recipients)) ?: 'none set') ?>.</p>
        <?php if (!$smsReady): ?>
          <p class="small"><span class="badmark">Gateway not ready.</span>
            <a href="<?= e(url('admin/sms')) ?>">Fix SMS settings</a></p>
        <?php endif ?>
        <form method="post" action="<?= e(url('admin/bookings/' . (int) $booking['id'] . '/sms')) ?>">
          <?= Csrf::field() ?>
          <button class="btn small" type="submit">Send booking SMS now</button>
        </form>
        <?php if ($smsLog !== []): ?>
          <ul class="list-reset small">
            <?php foreach ($smsLog as $row): ?>
              <li><span class="mono"><?= e(Phone::display((string) $row['to_number'])) ?></span>
                <span class="<?= $row['status'] === 'sent' ? 'okmark' : 'badmark' ?>"><?= e($row['status']) ?></span></li>
            <?php endforeach ?>
          </ul>
        <?php else: ?>
          <p class="small muted">No SMS logged for this booking yet.</p>
        <?php endif ?>
      </div>

      <div class="card">
        <h3>Details</h3>
        <ul class="list-reset small">
          <li><span class="muted">Reference</span><span class="mono"><?= e($booking['ref_code']) ?></span></li>
          <li><span class="muted">Status</span>
            <span class="st st-<?= (int) $booking['status_id'] ?>"><?= e($booking['status_name'] ?? '') ?></span></li>
          <li><span class="muted">Source</span><span><?= e($booking['source']) ?></span></li>
          <li><span class="muted">Price held</span><span><?= number_format((float) $booking['service_price']) ?> KES</span></li>
          <li><span class="muted">Created</span><span><?= e((string) $booking['created_at']) ?></span></li>
          <li><span class="muted">Updated</span><span><?= e((string) $booking['updated_at']) ?></span></li>
          <li><span class="muted">Client IP</span><span class="mono"><?= e((string) $booking['ip']) ?></span></li>
        </ul>
        <p><a class="btn small subtle" target="_blank" rel="noopener"
              href="https://wa.me/<?= e((string) $booking['phone']) ?>">WhatsApp the client</a></p>
        <form method="post" action="<?= e(url('admin/bookings/delete')) ?>"
              data-confirm="Delete booking <?= e($booking['ref_code']) ?> for <?= e($booking['client_name']) ?>?">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
          <button class="btn small danger" type="submit">Delete booking</button>
        </form>
      </div>
    <?php else: ?>
      <div class="card">
        <h3>Walk-in bookings</h3>
        <p class="small muted">Use this for phone and walk-in clients so the calendar stays honest.
          Bookings added here do not trigger the SMS notification.</p>
      </div>
    <?php endif ?>
  </div>
</div>
