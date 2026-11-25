<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#ffffff">
  <title>Set new password | Leave system</title>

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

      <h1>Set new password</h1>
      <p class="sub">Minimum 12 characters with upper, lower, number, and symbol.</p>

      @if ($errors->any())
        <div class="alert info" id="loginAlert" role="alert" style="background:var(--bad-bg);color:var(--bad);border:1px solid rgba(179,38,30,.25);">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--bad)"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form id="resetForm" method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field {{ $errors->has('email') ? 'err' : '' }}">
          <label for="email">Work email</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
            <input class="input" id="email" name="email" type="email" value="{{ old('email', $email) }}" required readonly style="background:#eef4ef;">
          </div>
          <div class="ferr" id="emailErr">@error('email'){{ $message }}@enderror</div>
        </div>

        <div class="field {{ $errors->has('password') ? 'err' : '' }}">
          <label for="password">New password</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input has-eye" id="password" name="password" type="password" autocomplete="new-password" placeholder="Enter new strong password" required autofocus>
            <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="ferr" id="passwordErr">@error('password'){{ $message }}@enderror</div>
        </div>

        <div class="field {{ $errors->has('password_confirmation') ? 'err' : '' }}">
          <label for="password_confirmation">Confirm new password</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input has-eye" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm new password" required>
            <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button class="btn primary lg block" id="submitBtn" type="submit">
          <span class="btn-t">Update password <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
          <span class="spin"></span>
        </button>
      </form>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
