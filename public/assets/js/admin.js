// Small helpers for the admin panel: no framework, nothing here is required to
// make a form work — the buttons and selects all still function without JS.
(function () {
  'use strict';

  // Status pickers save the booking as soon as they change.
  document.addEventListener('change', function (e) {
    const el = e.target.closest('[data-autosubmit]');
    if (el && el.form) el.form.submit();
  });

  // Destructive buttons ask first.
  document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-confirm]');
    if (el && !window.confirm(el.dataset.confirm)) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  // Colour squares that mirror a colour picker (booking statuses).
  document.querySelectorAll('input[type=color][data-preview]').forEach(function (input) {
    const box = document.querySelector(input.dataset.preview);
    if (!box) return;
    const paint = function () { box.style.background = input.value; };
    input.addEventListener('input', paint);
    paint();
  });

  // Live preview on the appearance page.
  const preview = document.getElementById('themePreview');
  if (!preview) return;

  function apply(vars) {
    Object.keys(vars).forEach(function (key) { preview.style.setProperty(key, vars[key]); });
  }

  document.querySelectorAll('input[name="preset"][data-scheme]').forEach(function (radio) {
    const show = function () {
      try { apply(JSON.parse(radio.dataset.scheme)); } catch (err) { /* bad JSON: keep current preview */ }
    };
    radio.addEventListener('change', show);
    if (radio.checked) show();
  });

  document.querySelectorAll('input[type=color][data-var]').forEach(function (input) {
    const code = document.querySelector('code[data-hex="' + input.name + '"]');
    const paint = function () {
      preview.style.setProperty(input.dataset.var, input.value);
      if (code) code.textContent = input.value;
    };
    input.addEventListener('input', paint);
    paint();
  });
})();
