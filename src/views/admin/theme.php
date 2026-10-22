<?php

use Salon\Http\Csrf;
use Salon\Model\Admin;

$keys = ['primary', 'accent', 'bg', 'surface', 'text'];
$labels = ['primary' => 'Header', 'accent' => 'Accent', 'bg' => 'Background', 'surface' => 'Cards', 'text' => 'Text'];

/** Derive the full variable set for one palette the same way the layout does. */
$schemeOf = static function (array $palette) use ($keys): array {
    $theme = Admin::theme([
        'theme_preset' => 'custom',
        'theme_primary' => $palette['primary'],
        'theme_accent' => $palette['accent'],
        'theme_bg' => $palette['bg'],
        'theme_surface' => $palette['surface'],
        'theme_text' => $palette['text'],
    ]);
    $out = [];
    foreach ($keys as $key) {
        $out['--' . $key] = $theme[$key];
    }
    foreach (['line', 'muted', 'on_primary', 'on_accent', 'accent_hover', 'accent_soft', 'primary_soft', 'gold'] as $key) {
        $out['--' . str_replace('_', '-', $key)] = $theme[$key];
    }
    return $out;
};
?>
<style nonce="<?= e($nonce) ?>">
<?php foreach ($presets as $key => $preset): ?>
.pr-<?= e($key) ?> i.a{background:<?= e($preset['primary']) ?>}
.pr-<?= e($key) ?> i.b{background:<?= e($preset['accent']) ?>}
.pr-<?= e($key) ?> i.c{background:<?= e($preset['bg']) ?>}
.pr-<?= e($key) ?> i.d{background:<?= e($preset['surface']) ?>}
<?php endforeach ?>
</style>

<h2>My account</h2>
<p class="hint">Signed in as <b><?= e($admin['first_name'] !== '' ? $admin['first_name'] : $admin['username']) ?></b>
  (<?= e(Admin::ROLES[$admin['role']] ?? $admin['role']) ?>). Colours chosen here change only your own panel.</p>

<div class="split">
  <div>
    <div class="card">
      <h3>Colour scheme</h3>
      <form method="post" action="<?= e(url('admin/theme/save')) ?>">
        <?= Csrf::field() ?>
        <div class="presetgrid">
          <?php foreach ($presets as $key => $preset): ?>
            <label class="pset">
              <input type="radio" name="preset" value="<?= e($key) ?>"
                     data-scheme="<?= e(json_encode($schemeOf($preset), JSON_HEX_QUOT | JSON_HEX_APOS)) ?>"
                     <?= $current['preset'] === $key ? 'checked' : '' ?>>
              <span><?= e($preset['label']) ?>
                <span class="sws"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i></span>
              </span>
            </label>
          <?php endforeach ?>
        </div>
        <p><button class="btn" type="submit">Use this scheme</button></p>
      </form>
    </div>

    <div class="card">
      <h3>Custom colours</h3>
      <form method="post" action="<?= e(url('admin/theme/save')) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="preset" value="custom">
        <div class="grid cols2">
          <?php foreach ($keys as $key): ?>
            <label><?= e($labels[$key]) ?>
              <span class="row">
                <input type="color" name="<?= e($key) ?>" value="<?= e($current[$key]) ?>" data-var="--<?= e($key) ?>">
                <code class="mono" data-hex="<?= e($key) ?>"><?= e($current[$key]) ?></code>
              </span>
            </label>
          <?php endforeach ?>
        </div>
        <div class="row">
          <button class="btn" type="submit">Save custom colours</button>
          <span class="hint">Hover shades, borders and button text are worked out from these five.</span>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Password</h3>
      <form method="post" action="<?= e(url('admin/theme/password')) ?>" class="narrow">
        <?= Csrf::field() ?>
        <label>Current password
          <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <div class="grid cols2">
          <label>New password
            <input type="password" name="new_password" required minlength="10" autocomplete="new-password">
            <span class="hint">At least 10 characters.</span>
          </label>
          <label>Repeat new password
            <input type="password" name="confirm_password" required minlength="10" autocomplete="new-password">
          </label>
        </div>
        <button class="btn" type="submit">Change password</button>
      </form>
    </div>
  </div>

  <div class="card">
    <h3>Preview</h3>
    <div class="preview" id="themePreview">
      <h3>Today</h3>
      <p class="muted">Knotless braids, 2 slots left at 10:00 am.</p>
      <p><span class="btn">Confirm</span> <a href="<?= e(url('admin/bookings')) ?>">Open bookings</a></p>
      <p><span class="tag">Cancelled</span></p>
    </div>
    <p class="hint">Pick a scheme or move a colour slider to see it here before saving.</p>
  </div>
</div>
