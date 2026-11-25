<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') | Leave system</title>

  <link rel="icon" href="{{ asset('assets/auth/cvsu-favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <style>
    .err-wrap { display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 24px; }
    .err-card { max-width: 440px; width: 100%; text-align: center; }
    .err-code { font-size: 60px; font-weight: 700; letter-spacing: -0.04em; color: var(--lagoon-700); line-height: 1; margin: 18px 0 8px; }
    .err-card h1 { font-size: 20px; font-weight: 600; margin: 0 0 8px; color: var(--ink); }
    .err-card p { color: var(--muted); margin: 0 0 24px; font-size: 14.5px; }
  </style>
</head>
<body>
  @include('partials.icons')

  <div class="err-wrap">
    <div class="card err-card">
      <div style="display:flex;justify-content:center;margin-bottom:14px;">
        <div class="wordmark">
          <img class="logo" src="{{ asset('assets/auth/cvsu-favicon.png') }}" alt="" aria-hidden="true">
          <b>Leave system</b>
        </div>
      </div>
      <div class="err-code">@yield('code')</div>
      <h1>@yield('message')</h1>
      <p>@yield('description')</p>
      <a href="{{ url('/') }}" class="btn primary" style="text-decoration:none;display:inline-flex;">
        Return to application
      </a>
    </div>
  </div>
</body>
</html>
