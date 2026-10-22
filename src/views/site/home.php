<?php

use Salon\Support\Phone;

$name = $settings['salon_name'] ?? 'Salon';
$logoWord = explode(' ', $name)[0];
$whatsapp = preg_replace('/\D/', '', (string) ($settings['whatsapp'] ?? ''));
$tell = Phone::display((string) ($settings['phone'] ?? ''));
$line = static fn(array $text, string $key, string $default = ''): string => trim((string) ($text[$key] ?? '')) !== ''
    ? (string) $text[$key] : $default;
?>
<header>
  <div class="wrap nav">
    <a class="logo" href="#top"><?= e($logoWord) ?><span>.</span></a>
    <ul>
      <li><a href="#services">Services</a></li>
      <li><a href="#why">Why <?= e($logoWord) ?></a></li>
      <li><a href="#reviews">Reviews</a></li>
      <li><a href="#visit">Find us</a></li>
    </ul>
    <a class="btn btn-primary" href="#book">Book now</a>
  </div>
</header>

<main id="top">
  <div class="hero">
    <div class="wrap hero-grid">
      <div>
        <h1><?= e($line($text, 'hero_title', 'Hair and beauty that fits your day, not the other way round.')) ?></h1>
        <p><?= e($line($text, 'hero_text', 'Pick a service, choose a time, and we will confirm on WhatsApp.')) ?></p>
        <div class="cta">
          <a class="btn btn-primary" href="#book"><?= e($line($text, 'hero_cta_primary', 'Book an appointment')) ?></a>
          <a class="btn btn-ghost" href="#services"><?= e($line($text, 'hero_cta_secondary', 'See prices')) ?></a>
        </div>
        <?php $trust = array_filter([
            $line($text, 'trust_line_1', ''), $line($text, 'trust_line_2', ''), $line($text, 'trust_line_3', ''),
        ]); ?>
        <?php if ($trust !== []): ?>
          <div class="trust">
            <?php foreach ($trust as $item): ?><span><?= e($item) ?></span><?php endforeach ?>
          </div>
        <?php endif ?>
      </div>
      <aside class="today" aria-label="Opening hours">
        <span class="open <?= $open['open'] ? '' : 'closed' ?>" id="openState"><?= e($open['label']) ?></span>
        <h2>Opening hours</h2>
        <ul>
          <?php foreach ($hours as $row): ?>
            <li><span class="<?= $row['is_today'] ? 'on' : '' ?>"><?= e($row['days']) ?></span>
                <span class="<?= $row['is_today'] ? 'on' : '' ?>"><?= e($row['time']) ?></span></li>
          <?php endforeach ?>
        </ul>
      </aside>
    </div>
  </div>

  <section id="services">
    <div class="wrap">
      <div class="head">
        <h2>Services and prices</h2>
        <p><?= e($line($text, 'services_intro', 'Prices are in Kenya shillings.')) ?></p>
      </div>
      <div class="tabs" role="tablist" id="tabs"></div>
      <p class="note" id="categoryBlurb"></p>
      <div class="list" id="list" role="tabpanel"></div>
      <p class="note"><?= e($line($text, 'services_note', '')) ?></p>
    </div>
  </section>

  <?php if ($why !== []): ?>
  <section class="why" id="why">
    <div class="wrap">
      <div class="head"><h2><?= e($line($text, 'why_heading', 'Why clients come back')) ?></h2></div>
      <div class="why-grid">
        <?php foreach ($why as $block): ?>
          <div><h3><?= e($block['title']) ?></h3><p><?= e($block['body']) ?></p></div>
        <?php endforeach ?>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php if ($reviews !== []): ?>
  <section id="reviews">
    <div class="wrap">
      <div class="head"><h2><?= e($line($text, 'reviews_heading', 'What our clients say')) ?></h2></div>
      <div class="reviews">
        <?php foreach ($reviews as $review): ?>
          <div class="review">
            <span class="stars" aria-label="<?= (int) $review['rating'] ?> stars"><?= str_repeat('★', max(0, (int) $review['rating'])) ?></span>
            <p><?= e($review['body']) ?></p>
            <cite><?= e($review['author']) ?><?= $review['location'] ? ', ' . e($review['location']) : '' ?></cite>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </section>
  <?php endif ?>

  <section class="book" id="book">
    <div class="wrap book-grid">
      <div>
        <div class="head tight">
          <h2><?= e($line($text, 'book_heading', 'Book your appointment')) ?></h2>
          <p><?= e($line($text, 'book_text', 'We will confirm by WhatsApp or phone.')) ?></p>
        </div>
        <?php if ($rules !== []): ?>
          <ul class="rules">
            <?php foreach ($rules as $rule): ?>
              <li><b><?= e($rule['title']) ?>:</b> <?= e($rule['body']) ?></li>
            <?php endforeach ?>
          </ul>
        <?php endif ?>
      </div>
      <form id="form" novalidate>
        <input id="website" class="hpot" tabindex="-1" autocomplete="off" aria-hidden="true" name="website">
        <input type="hidden" id="token" name="token" value="<?= e($token) ?>">
        <label>Full name
          <input id="name" name="name" autocomplete="name" required maxlength="120">
          <span class="err" data-for="name"></span>
        </label>
        <label>Phone number <span class="hint">Used only to confirm your booking</span>
          <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0712 345 678" required>
          <span class="err" data-for="phone"></span>
        </label>
        <label>Service
          <select id="service" name="service" required></select>
          <span class="err" data-for="service"></span>
        </label>
        <div class="row">
          <label>Date
            <input id="date" name="date" type="date" min="<?= e($today) ?>" max="<?= e($maxDate) ?>" required>
            <span class="err" data-for="date"></span>
          </label>
          <label>Preferred time
            <select id="time" name="time" required disabled>
              <option value="">Pick a date first</option>
            </select>
            <span class="err" data-for="time"></span>
          </label>
        </div>
        <p class="slotnote" id="slotNote" aria-live="polite"></p>
        <label>Email <span class="hint">Optional, for your confirmation</span>
          <input id="email" name="email" type="email" autocomplete="email" maxlength="160">
          <span class="err" data-for="email"></span>
        </label>
        <label>Anything we should know? <span class="hint">Optional</span>
          <textarea id="notes" name="notes" maxlength="500" placeholder="Hair length, style inspiration, allergies"></textarea>
        </label>
        <button class="btn btn-primary" type="submit">Request booking</button>
      </form>
    </div>
  </section>

  <section class="visit" id="visit">
    <div class="wrap visit-grid">
      <div>
        <div class="head tight"><h2><?= e($line($text, 'visit_heading', 'Find us')) ?></h2></div>
        <dl>
          <dt>Address</dt><dd><?= e($settings['address'] ?? '') ?></dd>
          <dt>Phone</dt><dd><a href="tel:+<?= e($whatsapp) ?>"><?= e($tell) ?></a></dd>
          <dt>WhatsApp</dt><dd><a href="https://wa.me/<?= e($whatsapp) ?>">Message us</a></dd>
          <dt>Email</dt><dd><a href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a></dd>
          <?php if (trim((string) ($text['visit_extra'] ?? '')) !== ''): ?>
            <dt>Parking</dt><dd><?= e($text['visit_extra']) ?></dd>
          <?php endif ?>
        </dl>
        <p class="actions"><a class="btn btn-primary" target="_blank" rel="noopener"
          href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode((string) ($settings['map_query'] ?? '')) ?>">Get directions</a></p>
      </div>
      <div class="map"><p><b><?= e($settings['address'] ?? '') ?></b><br>
        <span class="sub"><?= e($line($text, 'map_caption', '')) ?></span></p></div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <span>&copy; <?= date('Y') ?> <?= e($name) ?>. All rights reserved.</span>
    <span><?= e($settings['social_instagram'] ?? '') ?> · <?= e($settings['social_tiktok'] ?? '') ?></span>
  </div>
</footer>

<a class="wa" href="https://wa.me/<?= e($whatsapp) ?>?text=<?= urlencode('Hi ' . $logoWord . ', I would like to book') ?>">Chat on WhatsApp</a>
<div id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="siteData" nonce="<?= e($nonce ?? '') ?>"><?= json_encode([
    'services' => $services,
    'categories' => $categories,
    'capacity' => $capacity,
    'slotsUrl' => url('slots'),
    'bookUrl' => url('book'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
