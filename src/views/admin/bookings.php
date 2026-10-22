<?php

use Salon\Http\Csrf;
use Salon\Support\Phone;

$qs = static function (array $overrides) use ($filters): string {
    return http_build_query($filters + $overrides);
};
$views = ['upcoming' => 'Upcoming', 'today' => 'Today', 'new' => 'Awaiting reply', 'past' => 'Past', 'all' => 'All'];
?>
<div class="row">
  <h2>Bookings</h2><span class="sp"></span>
  <a class="btn small" href="<?= e(url('admin/bookings/new')) ?>">Add walk-in</a>
  <a class="btn small subtle" href="<?= e(url('admin/bookings/export?' . $qs([]))) ?>">Export CSV</a>
</div>

<div class="card">
  <div class="row">
    <?php foreach ($views as $key => $label): ?>
      <a class="btn small <?= $filters['view'] === $key ? '' : 'subtle' ?>"
         href="<?= e(url('admin/bookings?' . $qs(['view' => $key, 'page' => 1]))) ?>"><?= e($label) ?></a>
    <?php endforeach ?>
  </div>

  <form method="get" action="<?= e(url('admin/bookings')) ?>" class="filterbar">
    <input type="hidden" name="view" value="<?= e($filters['view']) ?>">
    <label>Search
      <input name="q" value="<?= e($filters['q']) ?>" placeholder="name, phone, ref, service"></label>
    <label>From
      <input type="date" name="from" value="<?= e($filters['from']) ?>"></label>
    <label>To
      <input type="date" name="to" value="<?= e($filters['to']) ?>"></label>
    <label>Status
      <select name="status_id">
        <option value="">Any</option>
        <?php foreach ($statuses as $status): ?>
          <option value="<?= (int) $status['id'] ?>" <?= (int) $filters['status_id'] === (int) $status['id'] ? 'selected' : '' ?>>
            <?= e($status['name']) ?></option>
        <?php endforeach ?>
      </select></label>
    <button class="btn small" type="submit">Filter</button>
    <a class="btn small ghost" href="<?= e(url('admin/bookings?view=' . $filters['view'])) ?>">Clear</a>
  </form>

  <div class="tablewrap">
    <table>
      <tr><th>When</th><th>Client</th><th>Service</th><th>Status</th><th>&nbsp;</th></tr>
      <?php foreach ($result['rows'] as $row): ?>
        <tr>
          <td><b><?= e(date('D j M', (int) strtotime((string) $row['booking_date']))) ?></b>
              <div class="muted small"><?= e(ucfirst((string) $row['booking_time'])) ?></div>
              <div class="mono muted"><?= e($row['ref_code']) ?></div></td>
          <td><a href="<?= e(url('admin/bookings/' . (int) $row['id'])) ?>"><?= e($row['client_name']) ?></a>
              <div class="muted small"><a href="https://wa.me/<?= e(preg_replace('/^254/', '254', (string) $row['phone'])) ?>"
                    target="_blank" rel="noopener"><?= e(Phone::display((string) $row['phone'])) ?></a></div>
              <?php if ($row['notes'] !== ''): ?><div class="muted small"><?= e($row['notes']) ?></div><?php endif ?></td>
          <td><?= e($row['service_name']) ?>
              <div class="muted small"><?= number_format((float) $row['service_price']) ?> KES</div></td>
          <td>
            <form method="post" action="<?= e(url('admin/bookings/status')) ?>">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <select name="status_id" data-autosubmit>
                <?php foreach ($statuses as $status): ?>
                  <option value="<?= (int) $status['id'] ?>" <?= (int) $row['status_id'] === (int) $status['id'] ? 'selected' : '' ?>>
                    <?= e($status['name']) ?></option>
                <?php endforeach ?>
              </select>
            </form>
          </td>
          <td class="right"><a class="btn small ghost" href="<?= e(url('admin/bookings/' . (int) $row['id'])) ?>">Open</a></td>
        </tr>
      <?php endforeach ?>
      <?php if ($result['rows'] === []): ?>
        <tr><td colspan="5" class="muted">No bookings match this filter.</td></tr>
      <?php endif ?>
    </table>
  </div>

  <div class="pager">
    <span class="muted small"><?= (int) $result['total'] ?> booking(s) · page <?= (int) $result['page'] ?>
      of <?= (int) $result['pages'] ?></span>
    <span class="row">
      <?php if ($result['page'] > 1): ?>
        <a class="btn small subtle" href="<?= e(url('admin/bookings?' . $qs(['page' => $result['page'] - 1]))) ?>">Previous</a>
      <?php endif ?>
      <?php if ($result['page'] < $result['pages']): ?>
        <a class="btn small subtle" href="<?= e(url('admin/bookings?' . $qs(['page' => $result['page'] + 1]))) ?>">Next</a>
      <?php endif ?>
    </span>
  </div>
</div>
