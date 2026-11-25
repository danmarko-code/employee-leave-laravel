<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#ffffff">
  <title>Change password | Leave system</title>

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

      <h1>Update password</h1>
      <p class="sub">Set a strong personal password to activate your account.</p>

      @if (session('notice'))
        <div class="alert info" role="alert">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
          <span>{{ session('notice') }}</span>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert info" id="loginAlert" role="alert" style="background:var(--bad-bg);color:var(--bad);border:1px solid rgba(179,38,30,.25);">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--bad)"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form id="changePasswordForm" method="POST" action="{{ route('password.change.update') }}" novalidate>
        @csrf

        <div class="field {{ $errors->has('current_password') ? 'err' : '' }}">
          <label for="current_password">Current / Temporary password</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input has-eye" id="current_password" name="current_password" type="password" placeholder="Enter current temporary password" required autofocus>
            <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="ferr" id="current_passwordErr">@error('current_password'){{ $message }}@enderror</div>
        </div>

        <div class="field {{ $errors->has('new_password') ? 'err' : '' }}">
          <label for="new_password">New password</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input has-eye" id="new_password" name="new_password" type="password" placeholder="Min 12 chars: A-Z, a-z, 0-9, symbol" required>
            <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="ferr" id="new_passwordErr">@error('new_password'){{ $message }}@enderror</div>
        </div>

        <div class="field {{ $errors->has('new_password_confirmation') ? 'err' : '' }}">
          <label for="new_password_confirmation">Confirm new password</label>
          <div class="inp">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            <input class="input has-eye" id="new_password_confirmation" name="new_password_confirmation" type="password" placeholder="Re-enter new password" required>
            <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button class="btn primary lg block" id="submitBtn" type="submit">
          <span class="btn-t">Save and continue <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
          <span class="spin"></span>
        </button>
      </form>

      <div style="margin-top:20px;text-align:center;">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="link" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:13.5px;font-weight:500;">
            Sign out instead
          </button>
        </form>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
