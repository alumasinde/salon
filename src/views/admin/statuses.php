<?php use Salon\Http\Csrf; ?>
<h2>Booking statuses</h2>

<div class="card">
  <p class="hint">Statuses drive the workflow. A status that “holds a slot” counts against the capacity in Settings,
    so a cancelled booking frees the time automatically.</p>
  <div class="head-col">
    <div class="formrow header">
      <span>Code</span><span>Label</span><span>Colour</span><span>Text</span><span>Sort</span>
      <span>Holds slot</span><span>Default</span><span>Show</span><span>Used</span><span>Save</span>
    </div>
    <?php foreach ($rows as $row): ?>
      <form method="post" action="<?= e(url('admin/statuses/save')) ?>" class="formrow">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <input name="code" value="<?= e($row['code']) ?>" class="short" required maxlength="30">
        <input name="name" value="<?= e($row['name']) ?>" required maxlength="60">
        <span class="center"><input type="color" name="color" value="<?= e($row['color']) ?>" data-preview="#sw-<?= (int) $row['id'] ?>"></span>
        <span class="swatch" id="sw-<?= (int) $row['id'] ?>"></span>
        <span class="center"><input type="color" name="text_color" value="<?= e($row['text_color']) ?>"></span>
        <input name="sort" type="number" value="<?= (int) $row['sort'] ?>" class="num">
        <span class="center"><input type="checkbox" name="counts_as_booked" value="1" <?= (int) $row['counts_as_booked'] === 1 ? 'checked' : '' ?>></span>
        <span class="center"><input type="checkbox" name="is_default" value="1" <?= (int) $row['is_default'] === 1 ? 'checked' : '' ?>></span>
        <span class="center"><input type="checkbox" name="active" value="1" <?= (int) $row['active'] === 1 ? 'checked' : '' ?>></span>
        <span class="center muted"><?= (int) $row['total'] ?></span>
        <span class="center row">
          <button class="btn small" type="submit">Save</button>
          <button class="btn small danger" type="submit" formaction="<?= e(url('admin/statuses/delete')) ?>"
                  data-confirm="Delete the status <?= e($row['name']) ?>?">Delete</button>
        </span>
      </form>
    <?php endforeach ?>
    <form method="post" action="<?= e(url('admin/statuses/save')) ?>" class="formrow">
      <?= Csrf::field() ?>
      <input name="code" placeholder="waiting_sms" class="short" required maxlength="30">
      <input name="name" placeholder="Waiting for SMS reply" required maxlength="60">
      <span class="center"><input type="color" name="color" value="#6f5d6b"></span>
      <span class="swatch"></span>
      <span class="center"><input type="color" name="text_color" value="#ffffff"></span>
      <input name="sort" type="number" value="60" class="num">
      <span class="center"><input type="checkbox" name="counts_as_booked" value="1"></span>
      <span class="center"><input type="checkbox" name="is_default" value="1"></span>
      <span class="center"><input type="checkbox" name="active" value="1" checked></span>
      <span class="center">-</span>
      <span class="center"><button class="btn small" type="submit">Add status</button></span>
    </form>
  </div>
</div>
