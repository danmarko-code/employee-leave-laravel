<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') | Leave system</title>

  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='11' fill='%230B5F70'/%3E%3Crect x='10' y='11' width='20' height='19' rx='4' fill='none' stroke='white' stroke-width='2.4'/%3E%3Cpath d='M10 17h20M16 8.5v5M24 8.5v5' stroke='white' stroke-width='2.4' stroke-linecap='round'/%3E%3Cpath d='m16 24 3 3 6-6' fill='none' stroke='%23F6B93B' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
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
          <svg class="logo" viewBox="0 0 40 40" aria-hidden="true"><use href="#logo-mark"/></svg>
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
