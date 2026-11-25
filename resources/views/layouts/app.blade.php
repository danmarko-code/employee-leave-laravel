<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#f3f6f2">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', 'Leave system')</title>

  <link rel="icon" href="{{ asset('assets/auth/cvsu-favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
  @include('partials.splash')
  @include('partials.icons')

  <a class="skip" href="#page">Skip to content</a>

  <div class="shell" id="shell">
    @include('partials.sidebar')
    <div class="nav-scrim" data-act="navClose"></div>

    <div class="main-wrap">
      @include('partials.topbar')
      <main class="page view-in" id="page" tabindex="-1">
        @yield('content')
      </main>
    </div>
  </div>

  <div class="overlay-root" id="drawerRoot"></div>
  <div class="overlay-root" id="modalRoot"></div>
  <div id="toasts" role="status" aria-live="polite"></div>

  <script src="{{ asset('js/date-utils.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  @stack('scripts')

  <script>
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) location.reload();
    });
  </script>
</body>
</html>
