<?php use Salon\Http\Csrf; ?>
<h2>Services</h2>

<div class="card">
  <h3>Categories</h3>
  <p class="hint">Categories group the price list on the website. Lower sort numbers appear first.</p>
  <div class="head-col">
    <div class="formrow header">
      <span>Name</span><span>Blurb</span><span>Sort</span><span>Show</span><span>Save</span>
    </div>
    <?php foreach ($categories as $category): ?>
      <form method="post" action="<?= e(url('admin/categories/save')) ?>" class="formrow">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
        <input name="name" value="<?= e($category['name']) ?>" required maxlength="60">
        <input name="blurb" value="<?= e($category['blurb']) ?>" maxlength="255" placeholder="Short description">
        <input name="sort" type="number" value="<?= (int) $category['sort'] ?>" class="num">
        <span class="center"><input type="checkbox" name="active" value="1" <?= (int) $category['active'] === 1 ? 'checked' : '' ?>></span>
        <span class="center row">
          <button class="btn small" type="submit">Save</button>
          <button class="btn small danger" type="submit" formaction="<?= e(url('admin/categories/delete')) ?>"
                  data-confirm="Delete this category? Services inside it move to No category.">Delete</button>
        </span>
      </form>
    <?php endforeach ?>
    <form method="post" action="<?= e(url('admin/categories/save')) ?>" class="formrow">
      <?= Csrf::field() ?>
      <input name="name" placeholder="New category" required maxlength="60">
      <input name="blurb" placeholder="Short description" maxlength="255">
      <input name="sort" type="number" value="100" class="num">
      <span class="center"><input type="checkbox" name="active" value="1" checked></span>
      <span class="center"><button class="btn small" type="submit">Add</button></span>
    </form>
  </div>
</div>

<div class="card">
  <h3>Service list</h3>
  <div class="head-col">
    <div class="formrow header wide">
      <span>Category</span><span>Name</span><span>Duration</span><span>Price</span><span>Deposit</span>
      <span>Sort</span><span>Show</span><span>Booked</span><span>Save</span>
    </div>
    <?php foreach ($services as $service): ?>
      <form method="post" action="<?= e(url('admin/services/save')) ?>" class="formrow wide">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
        <select name="category_id">
          <option value="">None</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>"
              <?= (int) ($service['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>>
              <?= e($category['name']) ?></option>
          <?php endforeach ?>
        </select>
        <span class="stack">
          <input name="name" value="<?= e($service['name']) ?>" required maxlength="120">
          <input name="description" value="<?= e($service['description']) ?>" maxlength="500" placeholder="Optional description">
        </span>
        <input name="duration" value="<?= e($service['duration']) ?>" maxlength="40" placeholder="2 hours" class="short">
        <input name="price" type="number" min="0" step="50" value="<?= (int) $service['price'] ?>" class="num">
        <input name="deposit" type="number" min="0" step="50" value="<?= (int) $service['deposit'] ?>" class="num">
        <input name="sort" type="number" value="<?= (int) $service['sort'] ?>" class="num">
        <span class="center"><input type="checkbox" name="active" value="1" <?= (int) $service['active'] === 1 ? 'checked' : '' ?>></span>
        <span class="center muted"><?= (int) $service['booking_count'] ?></span>
        <span class="center row">
          <button class="btn small" type="submit">Save</button>
          <button class="btn small danger" type="submit" formaction="<?= e(url('admin/services/delete')) ?>"
                  data-confirm="Delete <?= e($service['name']) ?>?">Delete</button>
        </span>
      </form>
    <?php endforeach ?>
    <form method="post" action="<?= e(url('admin/services/save')) ?>" class="formrow wide">
      <?= Csrf::field() ?>
      <select name="category_id">
        <option value="">No category</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
        <?php endforeach ?>
      </select>
      <span class="stack">
        <input name="name" placeholder="Service name" required maxlength="120">
        <input name="description" placeholder="Optional description" maxlength="500">
      </span>
      <input name="duration" placeholder="2 hours" class="short" maxlength="40">
      <input name="price" type="number" min="0" step="50" placeholder="KES" class="num" required>
      <input name="deposit" type="number" min="0" step="50" value="0" class="num">
      <input name="sort" type="number" value="100" class="num">
      <span class="center"><input type="checkbox" name="active" value="1" checked></span>
      <span class="center">-</span>
      <span class="center"><button class="btn small" type="submit">Add service</button></span>
    </form>
  </div>
  <p class="hint">Duration text is used to estimate how long a service takes. Prices are in KES.</p>
</div>
