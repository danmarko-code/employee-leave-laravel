<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Cavite State University leave system. Request, approve and track employee leave.">
  <meta name="theme-color" content="#052e0b">
  <title>Sign in | Cavite State University</title>

  <link rel="icon" href="{{ asset('assets/auth/cvsu-favicon.png') }}">
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body class="login-body">
  <main class="cv-login">
    <aside class="cv-brand" aria-labelledby="cv-brand-heading">
      <img class="cv-seal" src="{{ asset('assets/auth/cvsu-seal.webp') }}" alt="Cavite State University seal" width="220" height="191">
      <p class="cv-kicker">Republic of the Philippines</p>
      <div class="cv-brand-text">
        <p class="cv-uni" id="cv-brand-heading">Cavite State University</p>
        <p class="cv-sys">Leave system</p>
      </div>
      <span class="cv-rule" aria-hidden="true"></span>
      <p class="cv-since">Established 1906</p>
    </aside>

    <section class="cv-panel" aria-label="Sign in">
      <div class="cv-card login-card {{ $errors->any() ? 'has-error' : '' }}" id="loginCard">
        <img class="cv-app-logo" src="{{ asset('assets/auth/cvsu-logo.webp') }}" alt="Cavite State University" width="72" height="63">
        <h1 class="cv-title">Sign in</h1>
        <p class="cv-sub">Use your work email to continue.</p>

        @if (session('status'))
          <div class="cv-notice" role="status">
            <img src="{{ asset('assets/auth/cv-check.svg') }}" alt="" aria-hidden="true" width="18" height="18">
            <span>{{ session('status') }}</span>
          </div>
        @endif

        @if ($errors->any())
          <div class="cv-alert" id="loginAlert" role="alert">
            <img src="{{ asset('assets/auth/cv-alert.svg') }}" alt="" aria-hidden="true" width="18" height="18">
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        <form class="cv-form login-form" id="loginForm" method="POST" action="{{ route('login') }}" novalidate>
          @csrf

          <div class="cv-field login-field {{ $errors->has('email') ? 'is-invalid' : '' }}">
            <label class="cv-label" for="email">Work email</label>
            <div class="cv-input login-input-wrap">
              <img class="cv-ico login-input-icon" src="{{ asset('assets/auth/cv-mail.svg') }}" alt="" aria-hidden="true" width="20" height="20">
              <input class="cv-control login-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="name@company.com" spellcheck="false" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" required autofocus>
            </div>
            @error('email')
              <div class="cv-field-error login-field-error" id="email-error">{{ $message }}</div>
            @enderror
          </div>

          <div class="cv-field login-field {{ $errors->has('password') ? 'is-invalid' : '' }}">
            <div class="cv-label cv-label-row">
              <label for="password">Password</label>
              <a class="cv-link login-forgot" href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <div class="cv-input login-input-wrap">
              <img class="cv-ico login-input-icon" src="{{ asset('assets/auth/cv-lock.svg') }}" alt="" aria-hidden="true" width="20" height="20">
              <input class="cv-control login-input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" required>
              <button class="cv-toggle login-eye" id="passwordToggle" type="button" aria-label="Show password" aria-pressed="false"
                data-icon-on="{{ asset('assets/auth/cv-eye.svg') }}"
                data-icon-off="{{ asset('assets/auth/cv-eye-off.svg') }}">
                <img src="{{ asset('assets/auth/cv-eye.svg') }}" alt="" aria-hidden="true" width="20" height="20">
              </button>
            </div>
            @error('password')
              <div class="cv-field-error login-field-error" id="password-error">{{ $message }}</div>
            @enderror
          </div>

          <label class="cv-check login-check">
            <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span class="cv-check-box" aria-hidden="true">
              <img src="{{ asset('assets/auth/cv-check.svg') }}" alt="" width="12" height="12">
            </span>
            <span>Keep me signed in on this device</span>
          </label>

          <button class="cv-btn login-submit" id="loginBtn" type="submit">
            <span class="login-btn-label">Sign in</span>
            <span class="login-spinner" aria-hidden="true"></span>
          </button>
        </form>
      </div>

      <p class="cv-foot">&copy; {{ date('Y') }} Cavite State University</p>
    </section>
  </main>

  <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
