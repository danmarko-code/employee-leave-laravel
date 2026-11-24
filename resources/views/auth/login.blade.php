<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Request, approve and track employee leave.">
  <meta name="theme-color" content="#12303a">
  <title>Sign in | Leave system</title>

  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='11' fill='%230B5F70'/%3E%3Crect x='10' y='11' width='20' height='19' rx='4' fill='none' stroke='white' stroke-width='2.4'/%3E%3Cpath d='M10 17h20M16 8.5v5M24 8.5v5' stroke='white' stroke-width='2.4' stroke-linecap='round'/%3E%3Cpath d='m16 24 3 3 6-6' fill='none' stroke='%23F6B93B' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-body">
  <div class="login-page">
    <div class="login-bg" aria-hidden="true"></div>

    <main class="login-card {{ $errors->any() ? 'has-error' : '' }}" id="loginCard">
      <img class="login-logo" src="{{ asset('assets/auth/logo.svg') }}" alt="Leave system" width="56" height="56">
      <h1 class="login-title">Welcome back!</h1>
      <p class="login-subtitle">Sign in to manage your leave and approvals.</p>

      @if (session('status'))
        <div class="login-notice" role="status">
          <img src="{{ asset('assets/auth/icon-check.svg') }}" alt="" aria-hidden="true">
          <span>{{ session('status') }}</span>
        </div>
      @endif

      @if ($errors->any())
        <div class="login-error" id="loginAlert" role="alert">
          <img src="{{ asset('assets/auth/icon-alert.svg') }}" alt="" aria-hidden="true">
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form class="login-form" id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="login-field {{ $errors->has('email') ? 'is-invalid' : '' }}">
          <label for="email">Work email</label>
          <div class="login-input-wrap">
            <img class="login-input-icon" src="{{ asset('assets/auth/icon-mail.svg') }}" alt="" aria-hidden="true">
            <input class="login-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="name@company.com" spellcheck="false" required autofocus>
          </div>
          @error('email')
            <div class="login-field-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="login-field {{ $errors->has('password') ? 'is-invalid' : '' }}">
          <label for="password">Password</label>
          <div class="login-input-wrap">
            <img class="login-input-icon" src="{{ asset('assets/auth/icon-lock.svg') }}" alt="" aria-hidden="true">
            <input class="login-input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
            <button class="login-eye" id="passwordToggle" type="button" aria-label="Show password" aria-pressed="false"
              data-icon-on="{{ asset('assets/auth/icon-eye.svg') }}"
              data-icon-off="{{ asset('assets/auth/icon-eye-off.svg') }}">
              <img src="{{ asset('assets/auth/icon-eye.svg') }}" alt="" aria-hidden="true">
            </button>
          </div>
          @error('password')
            <div class="login-field-error">{{ $message }}</div>
          @enderror
        </div>

        <div class="login-options">
          <label class="login-check">
            <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span class="login-check-box" aria-hidden="true">
              <img src="{{ asset('assets/auth/icon-check.svg') }}" alt="">
            </span>
            <span>Remember me</span>
          </label>
          <a class="login-forgot" href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button class="login-submit" id="loginBtn" type="submit">
          <span class="login-btn-label">Log in</span>
          <span class="login-spinner" aria-hidden="true"></span>
        </button>
      </form>
    </main>
  </div>

  <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
