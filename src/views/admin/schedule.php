<?php

use Salon\Http\Csrf;
use Salon\Model\Schedule;

$dayNames = [];
for ($day = 1; $day <= 6; $day++) {
    $dayNames[$day] = Schedule::dayName($day);
}
$dayNames[0] = Schedule::dayName(0);
?>
<h2>Opening hours</h2>
<p class="hint">These hours decide which times clients can pick on the website. Bookable slots stop at closing time.</p>

<div class="split">
  <div class="card">
    <h3>Weekly hours</h3>
    <form method="post" action="<?= e(url('admin/schedule/hours')) ?>">
      <?= Csrf::field() ?>
      <?php foreach ($rows as $row): $day = (int) $row['day_of_week']; ?>
        <div class="formrow">
          <span class="daylabel"><?= e($dayNames[$day]) ?></span>
          <label class="inlinecheck">Open
            <input type="time" name="day[<?= $day ?>][opens]" value="<?= e(substr((string) $row['opens'], 0, 5)) ?>"></label>
          <label class="inlinecheck">Close
            <input type="time" name="day[<?= $day ?>][closes]" value="<?= e(substr((string) $row['closes'], 0, 5)) ?>"></label>
          <label class="inlinecheck">Closed
            <input type="checkbox" name="day[<?= $day ?>][closed]" value="1" <?= (int) $row['closed'] === 1 ? 'checked' : '' ?>></label>
          <input name="day[<?= $day ?>][note]" value="<?= e((string) $row['note']) ?>" placeholder="Note (optional)" class="grow">
        </div>
      <?php endforeach ?>
      <div class="row end"><button class="btn" type="submit">Save hours</button></div>
    </form>
  </div>

  <div>
    <div class="card">
      <h3>What clients see</h3>
      <ul class="list-reset small">
        <?php foreach ($grouped as $group): ?>
          <li><span class="<?= $group['is_today'] ? 'tag' : '' ?>"><?= e($group['days']) ?></span>
              <span class="muted"><?= e($group['time']) ?></span></li>
        <?php endforeach ?>
      </ul>
      <p class="small">Right now: <span class="tag"><?= e($open['label']) ?></span></p>
    </div>
  </div>
</div>

<div class="card">
  <h3>Closed days and special hours</h3>
  <p class="hint">Overrides beat the weekly pattern: public holidays, one-day closures, or extended festive hours.</p>
  <div class="tablewrap">
    <table>
      <tr><th>Date</th><th>Reason</th><th>Status</th><th>&nbsp;</th></tr>
      <?php foreach ($specials as $special): ?>
        <tr>
          <td><?= e(date('D j M Y', (int) strtotime((string) $special['date']))) ?></td>
          <td><?= e($special['label']) ?></td>
          <td><?= (int) $special['closed'] === 1
              ? 'Closed all day'
              : e(substr((string) $special['opens'], 0, 5)) . ' – ' . e(substr((string) $special['closes'], 0, 5)) ?></td>
          <td class="right">
            <form method="post" action="<?= e(url('admin/schedule/special-delete')) ?>"
                  data-confirm="Remove this override?">
              <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $special['id'] ?>">
              <button class="btn small danger" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach ?>
      <?php if ($specials === []): ?><tr><td colspan="4" class="muted">No overrides yet.</td></tr><?php endif ?>
    </table>
  </div>
  <form method="post" action="<?= e(url('admin/schedule/special')) ?>" class="formrow">
    <?= Csrf::field() ?>
    <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required class="short">
    <input name="label" placeholder="Kudi celebration - closed" maxlength="80" class="grow">
    <label class="inlinecheck">Closed<input type="checkbox" name="closed" value="1" checked></label>
    <label class="inlinecheck">Open<input type="time" name="opens" value="08:00"></label>
    <label class="inlinecheck">Close<input type="time" name="closes" value="20:00"></label>
    <button class="btn small" type="submit">Save override</button>
  </form>
</div>
