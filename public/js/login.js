(function () {
  'use strict';

  const form = document.getElementById('loginForm');
  const button = document.getElementById('loginBtn');
  const card = document.getElementById('loginCard');
  const toggle = document.getElementById('passwordToggle');
  const password = document.getElementById('password');

  if (card) {
    card.classList.remove('is-submitting');
  }

  if (button) {
    button.disabled = false;
    button.classList.remove('is-loading');
  }

  window.addEventListener('pageshow', function (e) {
    if (e.persisted) location.reload();
  });

  if (toggle && password) {
    toggle.addEventListener('click', function () {
      const show = password.type === 'password';
      password.type = show ? 'text' : 'password';
      toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', String(show));

      const icon = toggle.querySelector('img');
      if (icon) {
        icon.src = show ? toggle.dataset.iconOff : toggle.dataset.iconOn;
      }
    });
  }

  if (form && button && card) {
    form.addEventListener('submit', function () {
      if (!form.checkValidity()) {
        return;
      }

      button.disabled = true;
      button.classList.add('is-loading');
      card.classList.add('is-submitting');
    });
  }
})();
