<?php
$name = $settings['salon_name'] ?? 'Salon';
?>
<main id="top">
  <div class="hero">
    <div class="wrap">
      <h1>That page is not here.</h1>
      <p><?= e($message !== '' ? $message : 'The link you followed may be old or mistyped.') ?></p>
      <div class="cta">
        <a class="btn btn-primary" href="<?= e(url('')) ?>#book">Book an appointment</a>
        <a class="btn btn-ghost" href="<?= e(url('')) ?>">Back to <?= e($name) ?></a>
      </div>
    </div>
  </div>
</main>
