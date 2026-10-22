<?php

declare(strict_types=1);

/** @var array<string,mixed> $tenant */
/** @var array<string,mixed> $stats */
?>
<h2><?= e($tenant['name']) ?> · usage</h2>

<div class="grid cols4">
  <div class="stat"><span>Admin accounts</span><b><?= (int) $stats['admin_count'] ?></b>
    of <?= (int) $tenant['max_admins'] ?> allowed</div>
  <div class="stat"><span>Bookings this month</span><b><?= (int) $stats['booking_count'] ?></b>
    of <?= (int) $tenant['max_bookings_per_month'] ?> allowed</div>
  <div class="stat"><span>Plan</span><b class="small"><?= e(ucfirst((string) $tenant['plan'])) ?></b>
    <?= e(ucfirst((string) $tenant['status'])) ?></div>
  <div class="stat"><span>Domain</span><b class="small mono"><?= e($tenant['domain']) ?></b>
    <?= $stats['can_add_admin'] ? 'room for another account' : 'account limit reached' ?></div>
</div>

<div class="card">
  <p class="hint">Bookings count from the first of this month. When the monthly limit is reached,
    this salon's website and its walk-in booking form both refuse new bookings until next month.</p>
  <p>
    <a class="btn" href="<?= e(url('admin/tenants/' . (int) $tenant['id'])) ?>">Edit tenant</a>
    <a class="btn" href="<?= e(url('admin/tenants')) ?>">All tenants</a>
  </p>
</div>
