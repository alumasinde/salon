<?php

use Salon\Support\Phone;

$maxDay = max(1, array_reduce($nextDays, static fn(int $m, array $d): int => max($m, (int) $d['total']), 1));
?>
<h2>Dashboard</h2>
<div class="grid cols4">
  <div class="stat"><span>Today</span><b><?= (int) $stats['today'] ?></b>bookings on the calendar</div>
  <div class="stat"><span>Awaiting reply</span><b><?= (int) $stats['awaiting'] ?></b>still marked new</div>
  <div class="stat"><span>Next 7 days</span><b><?= (int) $stats['this_week'] ?></b>requests in</div>
  <div class="stat"><span>Done this month</span><b><?= number_format((float) $stats['month_value']) ?></b>KES at completed bookings</div>
</div>

<div class="split">
  <div>
    <div class="card">
      <div class="row">
        <h2>Today</h2><span class="sp"></span>
        <span class="tag"><?= e($open['label']) ?></span>
        <a class="btn small" href="<?= e(url('admin/bookings/new')) ?>">Add walk-in</a>
        <a class="btn small subtle" href="<?= e(url('admin/bookings')) ?>">All bookings</a>
      </div>
      <div class="tablewrap">
        <table>
          <tr><th>Time</th><th>Client</th><th>Service</th><th>Status</th></tr>
          <?php if ($todayList === []): ?>
            <tr><td colspan="4" class="muted">Nothing booked for today yet.</td></tr>
          <?php endif ?>
          <?php foreach ($todayList as $row): ?>
            <tr>
              <td><b><?= e(ucfirst((string) $row['booking_time'])) ?></b></td>
              <td><a href="<?= e(url('admin/bookings/' . (int) $row['id'])) ?>"><?= e($row['client_name']) ?></a>
                  <div class="muted small"><?= e(Phone::display((string) $row['phone'])) ?></div></td>
              <td><?= e($row['service_name']) ?></td>
              <td><span class="st st-<?= (int) $row['status_id'] ?>"><?= e($row['status_name']) ?></span></td>
            </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

    <div class="card">
      <h3>Next 7 days</h3>
      <div class="bars">
        <?php foreach ($nextDays as $day):
            $bucket = max(1, min(8, (int) ceil(($day['total'] / $maxDay) * 8))); ?>
          <div class="barcol" title="<?= e($day['date']) ?>: <?= (int) $day['total'] ?> booking(s)">
            <div class="bar b<?= $bucket ?>"><em><?= (int) $day['total'] ?></em></div>
            <span class="barlabel"><?= e(date('D', (int) strtotime($day['date']))) ?></span>
          </div>
        <?php endforeach ?>
      </div>
      <p class="muted small">Capacity is <?= (int) $capacity ?> client(s) per time slot across
        <?= (int) $serviceCount ?> active services.</p>
    </div>

    <div class="card">
      <h3>Latest requests</h3>
      <ul class="list-reset">
        <?php foreach ($recent as $row): ?>
          <li>
            <span><a href="<?= e(url('admin/bookings/' . (int) $row['id'])) ?>"><?= e($row['client_name']) ?></a>
                <span class="muted small"><?= e($row['service_name']) ?> ·
                  <?= e(date('D j M', (int) strtotime((string) $row['booking_date']))) ?>
                  <?= e($row['booking_time']) ?></span></span>
            <span class="st st-<?= (int) $row['status_id'] ?>"><?= e($row['status_name']) ?></span>
          </li>
        <?php endforeach ?>
        <?php if ($recent === []): ?><li class="muted">No bookings yet. Share the site link to get the first one.</li><?php endif ?>
      </ul>
    </div>
  </div>

  <div>
    <div class="card">
      <h3>Booking SMS</h3>
      <?php if ($smsReady): ?>
        <p class="small"><span class="okmark">Ready.</span> New bookings notify <?= count($recipients) ?>
          number(s): <?= e(implode(', ', array_map(static fn(array $r): string => $r['label'], $recipients))) ?: 'none' ?>.</p>
      <?php else: ?>
        <p class="small"><span class="badmark">Not sending.</span> Missing: <?= e(implode(', ', $smsMissing)) ?>.</p>
      <?php endif ?>
      <p class="small muted">Today: <?= (int) $smsToday['sent'] ?> sent, <?= (int) $smsToday['failed'] ?> failed,
        <?= (int) $smsToday['skipped'] ?> skipped.</p>
      <p><a class="btn small" href="<?= e(url('admin/sms')) ?>">SMS settings</a></p>
    </div>

    <div class="card">
      <h3>Opening hours</h3>
      <ul class="list-reset">
        <?php foreach ($hours as $row): ?>
          <li><span><?= e($row['days']) ?></span><span class="<?= $row['is_today'] ? 'tag' : 'muted' ?>"><?= e($row['time']) ?></span></li>
        <?php endforeach ?>
      </ul>
      <p><a class="btn small subtle" href="<?= e(url('admin/schedule')) ?>">Change hours</a></p>
    </div>

    <div class="card">
      <h3>Popular services</h3>
      <ul class="list-reset">
        <?php foreach ($topServices as $row): ?>
          <li><span class="small"><?= e($row['service_name']) ?></span><span class="tag"><?= (int) $row['total'] ?></span></li>
        <?php endforeach ?>
        <?php if ($topServices === []): ?><li class="muted small">Nothing booked yet.</li><?php endif ?>
      </ul>
    </div>
  </div>
</div>
