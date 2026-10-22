<?php

use Salon\Http\Csrf;

$longKeys = ['hero_text', 'services_intro', 'services_note', 'book_text', 'visit_extra', 'map_caption'];
$types = ['why' => '“Why clients come back” cards', 'rule' => 'Booking rules list'];
$byType = [];
foreach ($blocks as $block) {
    $byType[(string) $block['block_type']][] = $block;
}
?>
<h2>Website text</h2>

<div class="card">
  <p class="hint">These are the words clients read on the landing page. Leave a field empty and the built-in wording is used instead.</p>
  <div class="head-col">
    <div class="formrow header wide"><span>Field</span><span>Text</span><span>Save</span></div>
    <?php foreach ($texts as $row): ?>
      <?php $key = (string) $row['key']; $value = (string) $row['value']; $isLong = in_array($key, $longKeys, true) || mb_strlen($value) > 70; ?>
      <form method="post" action="<?= e(url('admin/content/text')) ?>" class="formrow wide">
        <?= Csrf::field() ?><input type="hidden" name="key" value="<?= e($key) ?>">
        <span class="stack">
          <b><?= e($row['label'] !== '' ? $row['label'] : $key) ?></b>
          <small class="mono muted"><?= e($key) ?></small>
        </span>
        <?php if ($isLong): ?>
          <span class="stack"><textarea name="value" rows="2" maxlength="4000"><?= e($value) ?></textarea></span>
        <?php else: ?>
          <span class="stack"><input name="value" value="<?= e($value) ?>" maxlength="4000"></span>
        <?php endif ?>
        <span class="row center"><button class="btn small" type="submit">Save</button></span>
      </form>
    <?php endforeach ?>
  </div>
</div>

<h2>Cards</h2>

<?php foreach ($types as $type => $heading): ?>
  <div class="card">
    <h3><?= e($heading) ?></h3>
    <div class="head-col">
      <div class="formrow header wide"><span>Title</span><span>Body</span><span>Sort</span><span>Show</span><span>Save</span></div>
      <?php foreach ($byType[$type] ?? [] as $block): ?>
        <form method="post" action="<?= e(url('admin/content/block')) ?>" class="formrow wide">
          <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $block['id'] ?>">
          <input type="hidden" name="block_type" value="<?= e($type) ?>">
          <input name="title" value="<?= e($block['title']) ?>" required maxlength="120">
          <span class="stack"><textarea name="body" rows="2" maxlength="500"><?= e($block['body']) ?></textarea></span>
          <input name="sort" type="number" value="<?= (int) $block['sort'] ?>" class="num">
          <span class="center"><input type="checkbox" name="active" value="1" <?= (int) $block['active'] === 1 ? 'checked' : '' ?>></span>
          <span class="row center">
            <button class="btn small" type="submit">Save</button>
            <button class="btn small danger" type="submit" formaction="<?= e(url('admin/content/block-delete')) ?>"
                    data-confirm="Remove this card from the site?">Delete</button>
          </span>
        </form>
      <?php endforeach ?>
      <form method="post" action="<?= e(url('admin/content/block')) ?>" class="formrow wide">
        <?= Csrf::field() ?><input type="hidden" name="block_type" value="<?= e($type) ?>">
        <input name="title" placeholder="New card title" required maxlength="120">
        <span class="stack"><textarea name="body" rows="2" placeholder="Short supporting sentence" maxlength="500"></textarea></span>
        <input name="sort" type="number" value="40" class="num">
        <span class="center"><input type="checkbox" name="active" value="1" checked></span>
        <span class="row center"><button class="btn small" type="submit">Add card</button></span>
      </form>
    </div>
  </div>
<?php endforeach ?>

<h2>Reviews</h2>

<div class="card">
  <p class="hint">The landing page shows the first few reviews marked “Show”, ordered by sort.</p>
  <div class="head-col">
    <div class="formrow header wide"><span>Author</span><span>Review</span><span>Where</span><span>Stars</span><span>Sort</span><span>Show</span><span>Save</span></div>
    <?php foreach ($reviews as $review): ?>
      <form method="post" action="<?= e(url('admin/content/review')) ?>" class="formrow wide">
        <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int) $review['id'] ?>">
        <input name="author" value="<?= e($review['author']) ?>" required maxlength="80">
        <span class="stack"><textarea name="body" rows="2" required maxlength="600"><?= e($review['body']) ?></textarea></span>
        <input name="location" value="<?= e($review['location']) ?>" maxlength="80" placeholder="Neighbourhood">
        <span class="center"><input name="rating" type="number" min="1" max="5" value="<?= (int) $review['rating'] ?>" class="num"></span>
        <input name="sort" type="number" value="<?= (int) $review['sort'] ?>" class="num">
        <span class="center"><input type="checkbox" name="active" value="1" <?= (int) $review['active'] === 1 ? 'checked' : '' ?>></span>
        <span class="row center">
          <button class="btn small" type="submit">Save</button>
          <button class="btn small danger" type="submit" formaction="<?= e(url('admin/content/review-delete')) ?>"
                  data-confirm="Delete this review?">Delete</button>
        </span>
      </form>
    <?php endforeach ?>
    <form method="post" action="<?= e(url('admin/content/review')) ?>" class="formrow wide">
      <?= Csrf::field() ?>
      <input name="author" placeholder="Name" required maxlength="80">
      <span class="stack"><textarea name="body" rows="2" placeholder="What the client said" required maxlength="600"></textarea></span>
      <input name="location" placeholder="Neighbourhood" maxlength="80">
      <span class="center"><input name="rating" type="number" min="1" max="5" value="5" class="num"></span>
      <input name="sort" type="number" value="30" class="num">
      <span class="center"><input type="checkbox" name="active" value="1" checked></span>
      <span class="row center"><button class="btn small" type="submit">Add review</button></span>
    </form>
  </div>
</div>
