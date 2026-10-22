<?php

use Salon\Http\Csrf;

$masked = static fn(string $v): string => $v === '' ? '' : '********';
$statusCounts = array_filter($stats);
?>
<h2>Booking SMS</h2>

<div class="card">
  <?php if ($config['enabled'] && $statusCounts !== []): ?>
    <div class="row">
      <?php foreach ($statusCounts as $status => $total): ?>
        <span class="tag"><?= e((string) $status) ?>: <?= (int) $total ?></span>
      <?php endforeach ?>
      <span class="muted small">today</span>
    </div>
  <?php elseif (!$config['enabled']): ?>
    <p class="hint">Sending is switched off, so bookings are saved but no text goes out. Tick “Send booking SMS” below to turn it on.</p>
  <?php else: ?>
    <p class="hint">No messages sent today yet.</p>
  <?php endif ?>
  <?php if ($missing !== []): ?>
    <p class="badmark">Not ready: <?= e(implode(', ', $missing)) ?>.</p>
  <?php else: ?>
    <p class="okmark">Gateway is configured and ready to send.</p>
  <?php endif ?>
</div>

<div class="split">
  <div>
    <div class="card">
      <h3>Gateway</h3>
      <form method="post" action="<?= e(url('admin/sms/save')) ?>">
        <?= Csrf::field() ?>
        <label class="check"><input type="checkbox" name="sms_enabled" value="1" <?= $config['enabled'] ? 'checked' : '' ?>>
          Send booking SMS</label>
        <label>Endpoint URL
          <input name="sms_endpoint" value="<?= e($config['endpoint']) ?>" required maxlength="255">
          <span class="hint">Full address, e.g. https://sms.textsms.co.ke/api/services/sendsms/</span>
        </label>
        <div class="grid cols2">
          <label>Method
            <select name="sms_method">
              <?php foreach (['GET', 'POST'] as $method): ?>
                <option <?= $config['method'] === $method ? 'selected' : '' ?>><?= e($method) ?></option>
              <?php endforeach ?>
            </select>
          </label>
          <label>Sender ID / shortcode
            <input name="sms_shortcode" value="<?= e($config['shortcode']) ?>" maxlength="20" placeholder="ZURI">
          </label>
        </div>
        <div class="grid cols2">
          <label>API key
            <input type="password" name="sms_key" value="<?= e($masked((string) $config['key'])) ?>" maxlength="190" autocomplete="off">
            <span class="hint">Stored in the database. Leave the dots to keep the current key, or put it in .env instead.</span>
          </label>
          <label>Partner ID
            <input type="password" name="sms_partner_id" value="<?= e($masked((string) $config['partner_id'])) ?>" maxlength="60" autocomplete="off">
          </label>
        </div>
        <label>Parameter names
          <input name="sms_param_map" value="<?= e(implode(',', array_map(
              static fn(string $logical, string $actual): string => $logical . ':' . $actual,
              array_keys($config['params']), array_values($config['params'])))) ?>" maxlength="255">
          <span class="hint">logical:provider &mdash; apikey, partnerid, message, shortcode, mobile</span>
        </label>
        <label>Message text
          <textarea name="sms_template" rows="3" maxlength="400"><?= e(Salon\Model\Setting::get('sms_template')) ?></textarea>
          <span class="hint">Placeholders: {ref} {name} {service} {date} {time} {phone} {salon} {notes}</span>
        </label>
        <div class="grid cols2">
          <label>Salon number that receives bookings
            <input name="sms_notify_number" value="<?= e(Salon\Model\Setting::get('sms_notify_number')) ?>" maxlength="20" placeholder="0712 345 678">
          </label>
          <label class="check"><input type="checkbox" name="sms_admin_notify" value="1"
              <?= Salon\Model\Setting::bool('sms_admin_notify', true) ? 'checked' : '' ?>>
          Also text admins who opted in (Team page)</label>
        </div>
        <button class="btn" type="submit">Save SMS settings</button>
      </form>
    </div>

    <div class="card">
      <h3>Recipients</h3>
      <?php if ($recipients === []): ?>
        <p class="hint">No numbers yet. Set the salon number above, or switch on “Also text admins” and give an admin a phone number.</p>
      <?php else: ?>
        <ul class="list-reset">
          <?php foreach ($recipients as $to): ?>
            <li><span><?= e($to['label']) ?></span><span class="mono"><?= e($to['number']) ?></span></li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </div>

    <div class="card">
      <h3>Send a test</h3>
      <form method="post" action="<?= e(url('admin/sms/test')) ?>">
        <?= Csrf::field() ?>
        <label>Test number
          <input name="test_number" required maxlength="20" placeholder="0712 345 678">
        </label>
        <label>Message
          <textarea name="test_message" rows="2" maxlength="480"><?= e($preview) ?></textarea>
        </label>
        <button class="btn" type="submit">Send test SMS</button>
      </form>
    </div>
  </div>

  <div>
    <div class="card">
      <h3>What goes out</h3>
      <p class="mono"><?= e($preview) ?></p>
      <p class="hint">Built from a sample booking: <?= e($sample['client_name']) ?>, <?= e($sample['service_name']) ?>,
        <?= e($sample['booking_date']) ?> at <?= e($sample['booking_time']) ?>.</p>
    </div>
    <div class="card">
      <h3>Failed messages</h3>
      <p class="hint">Retries every queued or failed message, up to 4 attempts each.</p>
      <form method="post" action="<?= e(url('admin/sms/retry')) ?>">
        <?= Csrf::field() ?><button class="btn subtle" type="submit">Retry now</button>
      </form>
    </div>
  </div>
</div>

<div class="card">
  <div class="row">
    <h3>Delivery log</h3>
    <span class="sp"></span>
    <span class="muted small">last <?= count($queue) ?> sends</span>
  </div>
  <?php if ($queue === []): ?>
    <p class="hint">Nothing sent yet.</p>
  <?php else: ?>
    <div class="tablewrap">
      <table>
        <tr><th>When</th><th>To</th><th>Message</th><th>Status</th><th>Tries</th><th>Gateway said</th></tr>
        <?php foreach ($queue as $row): ?>
          <tr>
            <td><?= e(date('j M H:i', strtotime((string) $row['created_at']))) ?></td>
            <td><span class="mono"><?= e($row['to_number']) ?></span><br><small class="muted"><?= e($row['recipient_label']) ?></small></td>
            <td><?= e($row['message']) ?>
              <?php if (!empty($row['booking_id'])): ?>
                <br><a class="small" href="<?= e(url('admin/bookings/' . (int) $row['booking_id'])) ?>">#<?= (int) $row['booking_id'] ?></a>
              <?php endif ?>
            </td>
            <td><span class="tag"><?= e($row['status']) ?></span></td>
            <td><?= (int) $row['attempts'] ?></td>
            <td><small class="muted"><?= e((string) ($row['error'] !== '' ? $row['error'] : $row['provider_response'])) ?></small></td>
          </tr>
        <?php endforeach ?>
      </table>
    </div>
  <?php endif ?>
</div>
