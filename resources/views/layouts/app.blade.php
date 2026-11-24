<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#E8F0F3">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', 'Leave system')</title>

  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='11' fill='%230B5F70'/%3E%3Crect x='10' y='11' width='20' height='19' rx='4' fill='none' stroke='white' stroke-width='2.4'/%3E%3Cpath d='M10 17h20M16 8.5v5M24 8.5v5' stroke='white' stroke-width='2.4' stroke-linecap='round'/%3E%3Cpath d='m16 24 3 3 6-6' fill='none' stroke='%23F6B93B' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
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
