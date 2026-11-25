<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#ffffff">
  <title>Forgot password | Leave system</title>

  <link rel="icon" href="{{ asset('assets/auth/cvsu-favicon.png') }}">
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
          <img class="logo" src="{{ asset('assets/auth/cvsu-favicon.png') }}" alt="" aria-hidden="true">
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
        <div class="alert info" id="loginAlert" role="alert" style="background:var(--bad-bg);color:var(--bad);border:1px solid rgba(179,38,30,.25);">
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
