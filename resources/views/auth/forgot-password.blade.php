<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#ffffff">
  <title>Forgot password | Leave system</title>

  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='11' fill='%230B5F70'/%3E%3Crect x='10' y='11' width='20' height='19' rx='4' fill='none' stroke='white' stroke-width='2.4'/%3E%3Cpath d='M10 17h20M16 8.5v5M24 8.5v5' stroke='white' stroke-width='2.4' stroke-linecap='round'/%3E%3Cpath d='m16 24 3 3 6-6' fill='none' stroke='%23F6B93B' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="background:#fff">
  @include('partials.icons')

  <div class="auth">
    <div class="auth-card {{ $errors->any() ? 'shake' : '' }}">
      <div class="auth-brand">
        <div class="wordmark">
          <svg class="logo" viewBox="0 0 40 40" aria-hidden="true"><use href="#logo-mark"/></svg>
          <b>Leave system</b>
        </div>
      </div>

      <h1>Reset password</h1>
      <p class="sub">Enter your work email address to receive reset instructions.</p>

      @if (session('status'))
        <div class="alert info" role="alert">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert info" id="loginAlert" role="alert" style="background:var(--bad-bg);color:var(--bad);border:1px solid rgba(179,52,46,.25);">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--bad)"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form id="forgotForm" method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="field {{ $errors->has('email') ? 'err' : '' }}">
          <label for="email">Work email</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="name@company.com" spellcheck="false" required autofocus>
          </div>
          <div class="ferr" id="emailErr">@error('email'){{ $message }}@enderror</div>
        </div>

        <button class="btn primary lg block" id="submitBtn" type="submit">
          <span class="btn-t">Send reset instructions <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
          <span class="spin"></span>
        </button>

        <div style="margin-top:20px;text-align:center;">
          <a href="{{ route('login') }}" class="link" style="color:var(--muted);text-decoration:none;font-size:13.5px;font-weight:500;">
            ← Back to sign in
          </a>
        </div>
      </form>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
