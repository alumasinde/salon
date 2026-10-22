(function () {
  'use strict';
  const data = JSON.parse(document.getElementById('siteData').textContent);
  const SERVICES = data.services || {};
  const CATEGORIES = data.categories || [];
  const $ = (id) => document.getElementById(id);
  const kes = (n) => 'KES ' + Number(n).toLocaleString('en-KE');

  // ----- service tabs -------------------------------------------------------
  const keys = Object.keys(SERVICES);
  let active = keys[0] || '';
  const blurbFor = (name) => (CATEGORIES.find((c) => c.name === name) || {}).blurb || '';

  function renderTabs() {
    $('tabs').innerHTML = keys.map((k) =>
      '<button type="button" class="tab" role="tab" aria-selected="' + (k === active) + '" data-k="' +
      esc(k) + '">' + esc(k) + '</button>').join('');
    $('list').innerHTML = (SERVICES[active] || []).map((s) =>
      '<div class="item"><div><h3>' + esc(s[0]) + '</h3><small>' + esc(s[1]) + '</small><br>' +
      '<button type="button" class="booklink" data-s="' + esc(s[0]) + '">Book this</button></div>' +
      '<span class="price">' + kes(s[2]) + '</span></div>').join('');
    const note = blurbFor(active);
    $('categoryBlurb').textContent = note;
    $('categoryBlurb').hidden = note === '';
  }

  function esc(value) {
    return String(value).replace(/[&<>"']/g, (c) =>
      ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  renderTabs();
  $('tabs').addEventListener('click', (e) => {
    const tab = e.target.closest('.tab');
    if (!tab) return;
    active = tab.dataset.k;
    renderTabs();
  });
  $('list').addEventListener('click', (e) => {
    const button = e.target.closest('button[data-s]');
    if (!button) return;
    $('service').value = button.dataset.s;
    $('book').scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => $('date').focus({ preventScroll: true }), 400);
  });

  // ----- booking form -------------------------------------------------------
  const service = $('service');
  service.innerHTML = '<option value="">Choose a service</option>' + keys.map((k) =>
    '<optgroup label="' + esc(k) + '">' + SERVICES[k].map((s) =>
      '<option>' + esc(s[0]) + '</option>').join('') + '</optgroup>').join('');

  const time = $('time');
  const slotNote = $('slotNote');
  let slotCache = {};

  function fillSlots(date, slots, closed, label) {
    if (closed) {
      time.innerHTML = '<option value="">Closed that day</option>';
      time.disabled = true;
      slotNote.textContent = label || 'We are closed on that date. Please pick another.';
      return;
    }
    if (!slots || !slots.length) {
      time.innerHTML = '<option value="">No times left</option>';
      time.disabled = true;
      slotNote.textContent = 'Nothing bookable on that date. Call us and we will fit you in.';
      return;
    }
    time.disabled = false;
    time.innerHTML = '<option value="">Choose a time</option>' + slots.map((s) =>
      '<option value="' + esc(s.time) + '"' + (s.full ? ' disabled' : '') + '>' + esc(s.time) +
      (s.left > 0 && s.left <= 2 ? ' (' + s.left + ' left)' : '') + '</option>').join('');
    const open = slots.filter((s) => !s.full).length;
    slotNote.textContent = open === slots.length
      ? slots.length + ' times available (' + data.capacity + ' clients per time).'
      : open + ' of ' + slots.length + ' times still open.';
  }

  function loadSlots(date) {
    if (!date) return;
    if (slotCache[date]) {
      fillSlots(date, slotCache[date].slots, slotCache[date].closed, slotCache[date].label);
      return;
    }
    slotNote.textContent = 'Checking availability...';
    fetch(data.slotsUrl + '?date=' + encodeURIComponent(date), {
      headers: { Accept: 'application/json' },
    }).then((r) => r.json()).then((json) => {
      slotCache[date] = json;
      if ($('date').value === date) fillSlots(date, json.slots, json.closed, json.label);
    }).catch(() => {
      slotNote.textContent = 'Could not check availability. Pick a time and we will confirm.';
      time.disabled = false;
      time.innerHTML = '<option value="">Choose a time</option>' + defaultTimes().map((t) =>
        '<option>' + t + '</option>').join('');
    });
  }

  function defaultTimes() {
    const out = [];
    for (let m = 8 * 60; m < 20 * 60; m += 30) {
      const h = Math.floor(m / 60);
      out.push(((h % 12) || 12) + ':' + String(m % 60).padStart(2, '0') + ' ' + (h >= 12 ? 'pm' : 'am'));
    }
    return out;
  }

  $('date').addEventListener('change', () => {
    slotCache = {};
    loadSlots($('date').value);
  });

  function setErr(field, message) {
    const target = document.querySelector('[data-for=' + field + ']');
    if (target) target.textContent = message;
    return message === '';
  }

  function toast(message) {
    const el = $('toast');
    el.textContent = message;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 5000);
  }

  $('form').addEventListener('submit', (e) => {
    e.preventDefault();
    const phone = $('phone').value.replace(/[\s-]/g, '');
    let ok = true;
    ok = setErr('name', $('name').value.trim().length < 2 ? 'Enter your name.' : '') && ok;
    ok = setErr('phone', /^(\+?254|0)[17]\d{8}$/.test(phone) ? '' : 'Enter a Kenyan number, like 0712 345 678.') && ok;
    ok = setErr('service', service.value ? '' : 'Choose a service.') && ok;
    ok = setErr('date', $('date').value ? '' : 'Choose a date.') && ok;
    ok = setErr('time', time.value ? '' : 'Choose a time.') && ok;
    if (!ok) {
      const first = document.querySelector('.err:not(:empty)');
      if (first && first.previousElementSibling) first.previousElementSibling.focus();
      return;
    }
    const submit = $('form').querySelector('button[type=submit]');
    submit.disabled = true;
    const body = new FormData();
    [['name', 'name'], ['phone', 'phone'], ['service', 'service'], ['date', 'date'], ['time', 'time'],
      ['notes', 'notes'], ['email', 'email'], ['token', 'token'], ['website', 'website']].forEach((pair) => {
      const el = $(pair[1]);
      body.set(pair[0], el ? el.value : '');
    });
    fetch(data.bookUrl, { method: 'POST', body, headers: { Accept: 'application/json' } })
      .then((r) => r.json().then((json) => ({ status: r.status, json })))
      .then(({ status, json }) => {
        toast(json.msg || 'Could not send. Please call or WhatsApp us.');
        if (json.ok) {
          $('form').reset();
          slotCache = {};
          time.innerHTML = '<option value="">Pick a date first</option>';
          time.disabled = true;
          slotNote.textContent = '';
        } else if (status === 409 || status === 422) {
          delete slotCache[$('date').value];
          loadSlots($('date').value);
        }
      })
      .catch(() => toast('Could not send. Please call or WhatsApp us.'))
      .finally(() => { submit.disabled = false; });
  });

  document.querySelectorAll('#form input, #form select').forEach((el) => {
    el.addEventListener('input', () => setErr(el.id, ''));
  });

  if (window.location.hash === '#book') {
    $('date').focus({ preventScroll: true });
  }
})();
