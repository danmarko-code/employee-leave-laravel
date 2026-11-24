@if (session('splash'))
  <style>
    .app-splash {
      position: fixed;
      inset: 0;
      z-index: 9999;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #12303a, #0f766e);
      color: #fff;
      font-family: "IBM Plex Sans", "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
      opacity: 1;
      transition: opacity 500ms cubic-bezier(.22, 1, .36, 1);
    }

    .app-splash.is-leaving {
      opacity: 0;
      pointer-events: none;
    }

    .app-splash__inner {
      max-width: 320px;
      padding: 24px;
      text-align: center;
    }

    .app-splash__mark {
      position: relative;
      width: 132px;
      height: 132px;
      margin: 0 auto 18px;
    }

    .app-splash__logo {
      position: absolute;
      left: 50%;
      top: 50%;
      width: 88px;
      height: 88px;
      transform: translate(-50%, -50%);
    }

    .app-splash__ring {
      position: absolute;
      inset: 0;
      width: 132px;
      height: 132px;
    }

    .app-splash__ring .ring {
      stroke-dasharray: 1;
      stroke-dashoffset: 1;
    }

    .app-splash__title {
      font-size: 22px;
      font-weight: 600;
      letter-spacing: -.02em;
    }

    .app-splash__welcome {
      margin-top: 6px;
      color: rgba(255, 255, 255, .78);
      font-size: 14px;
    }

    .app-splash__progress {
      width: 180px;
      height: 2px;
      margin: 22px auto 0;
      border-radius: 999px;
      background: rgba(255, 255, 255, .18);
      overflow: hidden;
    }

    .app-splash__progress span {
      display: block;
      width: 0;
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(90deg, #2dd4bf, #5eead4);
    }

    @media (prefers-reduced-motion: no-preference) {
      .app-splash__logo { animation: splash-logo-in 700ms cubic-bezier(.22, 1, .36, 1) both; }
      .app-splash__ring .ring { animation: splash-ring-draw 1.2s cubic-bezier(.22, 1, .36, 1) .1s forwards; }
      .app-splash__title { animation: splash-rise 600ms cubic-bezier(.22, 1, .36, 1) .35s both; }
      .app-splash__welcome { animation: splash-rise 600ms cubic-bezier(.22, 1, .36, 1) .48s both; }
      .app-splash__progress span { animation: splash-progress 1.8s cubic-bezier(.22, 1, .36, 1) .15s forwards; }

      @keyframes splash-logo-in {
        from { opacity: 0; transform: translate(-50%, -50%) scale(.72); }
        to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
      }

      @keyframes splash-ring-draw {
        to { stroke-dashoffset: 0; }
      }

      @keyframes splash-rise {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: none; }
      }

      @keyframes splash-progress {
        to { width: 100%; }
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .app-splash { transition-duration: 600ms; }
      .app-splash__progress span { width: 100%; }
    }
  </style>

  <div class="app-splash" id="appSplash" role="status" aria-live="polite">
    <div class="app-splash__inner">
      <div class="app-splash__mark">
        <img class="app-splash__logo" src="{{ asset('assets/auth/logo.svg') }}" alt="" aria-hidden="true" width="88" height="88">
        <svg class="app-splash__ring" viewBox="0 0 100 100" fill="none" aria-hidden="true">
          <circle cx="50" cy="50" r="46" stroke="#fff" stroke-opacity=".12" stroke-width="2"></circle>
          <circle class="ring" cx="50" cy="50" r="46" stroke="#5eead4" stroke-width="2.5" stroke-linecap="round" pathLength="1" transform="rotate(-90 50 50)"></circle>
        </svg>
      </div>
      <div class="app-splash__title">Leave system</div>
      <div class="app-splash__welcome">Welcome, {{ auth()->user()->name }}</div>
      <div class="app-splash__progress" aria-hidden="true"><span></span></div>
    </div>
  </div>

  <script>
    (function () {
      var splash = document.getElementById('appSplash');
      if (!splash) return;

      var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var fadeDelay = reduceMotion ? 600 : 2000;
      var fadeTime = reduceMotion ? 600 : 500;

      window.setTimeout(function () {
        splash.classList.add('is-leaving');
      }, fadeDelay);

      window.setTimeout(function () {
        splash.remove();
      }, fadeDelay + fadeTime);
    })();
  </script>
@endif
