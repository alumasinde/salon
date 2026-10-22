<?php

use Salon\Http\Csrf;
use Salon\Model\Tenant;
?>
<h2>Tenants</h2>

<div class="card">
  <p class="hint">Each salon on the platform is found by the domain its visitors use, so a domain
    must point here before its site answers. Suspended and pending tenants are closed to visitors.</p>
  <p><a class="btn" href="<?= e(url('admin/tenants/new')) ?>">Add tenant</a></p>
</div>

<?php if ($tenants === []): ?>
  <div class="card"><p class="hint">No tenants yet. Add the first one to get started.</p></div>
<?php else: ?>
  <div class="card">
    <div class="tablewrap">
      <table>
        <tr><th>Tenant</th><th>Domain</th><th>Status</th><th>Plan</th><th>Admins</th><th>Bookings this month</th><th>&nbsp;</th></tr>
        <?php foreach ($tenants as $tenant): ?>
          <tr>
            <td><a href="<?= e(url('admin/tenants/' . (int) $tenant['id'])) ?>"><?= e($tenant['name']) ?></a>
                <div class="muted small mono"><?= e($tenant['slug']) ?></div></td>
            <td class="mono small"><?= e($tenant['domain']) ?></td>
            <td><span class="pill"><?= e(ucfirst((string) $tenant['status'])) ?></span></td>
            <td><?= e(ucfirst((string) $tenant['plan'])) ?></td>
            <td><?= Tenant::countAdmins((int) $tenant['id']) ?> / <?= (int) $tenant['max_admins'] ?></td>
            <td><?= Tenant::countBookingsThisMonth((int) $tenant['id']) ?> / <?= (int) $tenant['max_bookings_per_month'] ?>
                <div class="muted small"><a href="<?= e(url('admin/tenants/' . (int) $tenant['id'] . '/stats')) ?>">usage</a></div></td>
            <td>
              <form method="post" action="<?= e(url('admin/tenants/delete')) ?>"
                  data-confirm="Delete &quot;<?= e($tenant['name']) ?>&quot;? Its settings and services go with it.">
                <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $tenant['id'] ?>">
                <button class="btn" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
      </table>
    </div>
  </div>
<?php endif ?>
