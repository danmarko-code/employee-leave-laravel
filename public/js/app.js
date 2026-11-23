/**
 * Leave System Frontend Interaction Script
 * Preserves prototype animations, easings, and elastic sidebar indicator.
 */

(function () {
  'use strict';

  let navTimer = null;

  // Move elastic navigation indicator
  function moveNavInd(ind, a, immediate) {
    if (!ind || !a) return;
    const to = a.offsetTop;
    const h = a.offsetHeight;

    if (ind.dataset.to === String(to) && ind.style.height && !immediate) return;
    ind.dataset.to = to;
    clearTimeout(navTimer);

    const set = (y, ht, sx, ms, ease) => {
      ind.style.transition = ms ? 'transform ' + ms + 'ms ' + ease + ', height ' + ms + 'ms ' + ease : 'none';
      ind.style.height = ht + 'px';
      ind.style.transform = 'translateY(' + y + 'px) scaleX(' + sx + ')';
    };

    if (immediate || !ind.classList.contains('ready') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      set(to, h, 1, 0);
      ind.classList.add('ready');
      return;
    }

    const navTop = ind.parentElement.getBoundingClientRect().top;
    const r = ind.getBoundingClientRect();
    const curTop = r.top - navTop;
    const curBot = curTop + r.height;
    const top = Math.min(curTop, to);
    const bot = Math.max(curBot, to + h);

    set(top, bot - top, 0.94, 190, 'cubic-bezier(.3,.8,.4,1)');
    navTimer = setTimeout(() => {
      set(to, h, 1, 540, 'cubic-bezier(.34,1.35,.64,1)');
    }, 130);
  }

  // Toast notification
  window.showToast = function (msg, kind) {
    const toasts = document.getElementById('toasts');
    if (!toasts) return;

    const el = document.createElement('div');
    el.className = 'toast ' + (kind || '');
    const isErr = kind === 'err' || kind === 'error';
    const ic = isErr
      ? '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>'
      : '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

    el.innerHTML = ic + '<span>' + msg + '</span><i class="toast-bar"></i>';
    toasts.appendChild(el);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => el.classList.add('in'));
    });

    const timer = setTimeout(() => {
      el.classList.remove('in');
      setTimeout(() => el.remove(), 260);
    }, 3800);

    el.addEventListener('click', () => {
      clearTimeout(timer);
      el.classList.remove('in');
      setTimeout(() => el.remove(), 260);
    });
  };

  document.addEventListener('DOMContentLoaded', () => {
    let warnOnTemporaryPasswordUnload = false;

    window.addEventListener('beforeunload', (event) => {
      if (!warnOnTemporaryPasswordUnload) return;
      event.preventDefault();
      event.returnValue = '';
    });

    // Nav indicator init
    const ind = document.getElementById('navInd');
    const nav = document.getElementById('nav');
    if (ind && nav) {
      const getActive = () => nav.querySelector('.nav-item[aria-current="page"]') || nav.querySelector('.nav-item.active') || nav.querySelector('.nav-item');

      const snapToActive = () => {
        const active = getActive();
        if (active) {
          ind.style.transition = 'none';
          ind.style.height = active.offsetHeight + 'px';
          ind.style.transform = 'translateY(' + active.offsetTop + 'px) scaleX(1)';
          ind.dataset.to = String(active.offsetTop);
          ind.classList.add('ready');
        }
      };

      // Place indicator immediately without animation from wrong position on load
      snapToActive();
      window.addEventListener('load', snapToActive);

      // Elastic indicator triggers only on click
      nav.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
          nav.querySelectorAll('.nav-item').forEach(el => {
            el.removeAttribute('aria-current');
            el.classList.remove('active');
          });
          item.setAttribute('aria-current', 'page');
          item.classList.add('active');
          moveNavInd(ind, item, false);
        });
      });
    }

    // Delegated click handlers
    document.addEventListener('click', (e) => {
      const target = e.target;

      // Copy the one-time password without an inline handler.
      const copyBtn = target.closest('[data-copy-target]');
      if (copyBtn) {
        const input = document.getElementById(copyBtn.dataset.copyTarget);
        const status = document.getElementById(copyBtn.dataset.copyStatus);
        const showStatus = (text) => {
          if (!status) return;
          status.textContent = text;
          if (text === 'Copied') {
            setTimeout(() => { status.textContent = ''; }, 1400);
          }
        };

        if (input) {
          const fallbackCopy = () => {
            input.focus();
            input.select();
            if (!document.execCommand('copy')) throw new Error('Copy failed');
          };

          const copy = navigator.clipboard && window.isSecureContext
            ? navigator.clipboard.writeText(input.value).catch(() => fallbackCopy())
            : Promise.resolve().then(fallbackCopy);

          copy.then(() => showStatus('Copied')).catch(() => showStatus('Copy failed'));
        }
        return;
      }

      // Acknowledge the one-time password and stop warning on navigation.
      const savedBtn = target.closest('[data-temp-password-saved]');
      if (savedBtn) {
        warnOnTemporaryPasswordUnload = false;
        const card = savedBtn.closest('[data-temp-password-card]');
        if (card) card.remove();
        return;
      }

      // Mobile nav open
      if (target.closest('[data-act="navOpen"]')) {
        const shell = document.getElementById('shell');
        if (shell) shell.classList.add('nav-open');
        return;
      }

      // Mobile nav close
      if (target.closest('[data-act="navClose"]') || target.classList.contains('nav-scrim')) {
        const shell = document.getElementById('shell');
        if (shell) shell.classList.remove('nav-open');
        return;
      }

      // User menu toggle
      const ubtn = target.closest('[data-act="umenu"]');
      const umenu = document.getElementById('umenu');
      if (ubtn && umenu) {
        const isOpen = umenu.classList.toggle('open');
        ubtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        return;
      }

      // Click outside user menu closes it
      if (umenu && umenu.classList.contains('open') && !target.closest('#umenu') && !target.closest('.ubtn')) {
        umenu.classList.remove('open');
        const btn = document.querySelector('.ubtn');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }

      // Toggle password visibility
      const eyeBtn = target.closest('[data-act="togglePw"]');
      if (eyeBtn) {
        const inp = eyeBtn.parentElement.querySelector('input');
        if (inp) {
          const isPw = inp.type === 'password';
          inp.type = isPw ? 'text' : 'password';
          eyeBtn.setAttribute('aria-label', isPw ? 'Hide password' : 'Show password');
          eyeBtn.innerHTML = isPw
            ? '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 8 10 8a13.2 13.2 0 0 1-1.67 2.68M6.61 6.61A13.5 13.5 0 0 0 2 12s3.5 8 10 8a9.7 9.7 0 0 0 5.39-1.61M2 2l20 20"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>'
            : '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
        }
        return;
      }
    });

    // Form submit button loading spinner animation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
      loginForm.addEventListener('submit', () => {
        const btn = document.getElementById('loginBtn');
        if (btn && loginForm.checkValidity()) {
          btn.classList.add('loading');
        }
      });
    }

    // Add/Edit employee: department-driven position list.
    document.querySelectorAll('[data-user-form]').forEach((form) => {
      let config;
      try {
        config = JSON.parse(form.dataset.formConfig || '{}');
      } catch (error) {
        return;
      }

      const department = form.querySelector('#department_id');
      const position = form.querySelector('#position_id');
      if (!department || !position) return;

      const departments = config.departments || {};

      const fillPositions = (departmentId, preferred) => {
        const data = departments[String(departmentId)] || null;
        const items = data ? data.positions : [];

        position.replaceChildren();
        const first = document.createElement('option');
        first.value = '';
        first.textContent = data ? 'Select a Position' : 'Select a department first';
        position.appendChild(first);

        items.forEach((item) => {
          const option = document.createElement('option');
          option.value = String(item.id);
          option.textContent = item.name;
          position.appendChild(option);
        });

        position.disabled = items.length === 0;
        const wanted = preferred !== '' ? String(preferred) : '';
        const matches = Array.from(position.options).some((option) => option.value === wanted);
        position.value = matches ? wanted : '';
      };

      department.addEventListener('change', () => fillPositions(department.value, ''));
      fillPositions(form.dataset.departmentId, form.dataset.positionId);
    });

    document.querySelectorAll('[data-temp-password-card]').forEach(() => {
      warnOnTemporaryPasswordUnload = true;
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        const message = form.dataset.confirm || 'Are you sure?';
        if (!window.confirm(message)) event.preventDefault();
      });
    });
  });
})();
