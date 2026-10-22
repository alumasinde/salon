<?php

$actionLabels = [
    'login' => 'Signed in', 'login_blocked' => 'Blocked sign-in', 'login_throttled' => 'Too many wrong passwords',
    'login_unlock' => 'Login attempts cleared', 'logout' => 'Signed out', 'password_change' => 'Password changed',
    'booking_create' => 'Booking received', 'booking_update' => 'Booking edited', 'booking_status' => 'Status changed',
    'booking_delete' => 'Booking deleted', 'booking_export' => 'Bookings exported',
    'service_create' => 'Service added', 'service_update' => 'Service changed', 'service_delete' => 'Service deleted',
    'status_create' => 'Status added', 'status_update' => 'Status changed', 'status_delete' => 'Status deleted',
    'hours_update' => 'Opening hours saved',
    'content_text' => 'Website text saved', 'review_create' => 'Review added', 'review_update' => 'Review updated',
    'settings_update' => 'Settings saved',
    'sms_settings' => 'SMS settings saved', 'sms_test' => 'Test SMS sent', 'sms_retry' => 'Failed SMS retried',
    'sms_resend' => 'Booking SMS re-sent',
    'admin_create' => 'Account added', 'admin_update' => 'Account changed', 'admin_delete' => 'Account deleted',
    'theme_save' => 'Colours saved',
];
?>
<h2>Activity</h2>

<div class="card">
  <p class="hint">Every change made in the panel, newest first. Kept so a strange booking or setting can be traced back to who did it.</p>
  <?php if ($rows === []): ?>
    <p class="hint">Nothing logged yet.</p>
  <?php else: ?>
    <div class="tablewrap">
      <table>
        <tr><th>When</th><th>Who</th><th>What</th><th>Detail</th><th>IP</th></tr>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= e(date('j M Y H:i', strtotime((string) $row['created_at']))) ?></td>
            <td><?= e((string) ($row['username'] ?? 'Deleted account')) ?></td>
            <td><?= e($actionLabels[$row['action']] ?? $row['action']) ?></td>
            <td class="small muted"><?= e((string) $row['detail']) ?></td>
            <td class="mono small"><?= e((string) $row['ip']) ?></td>
          </tr>
        <?php endforeach ?>
      </table>
    </div>
  <?php endif ?>
</div>
