@extends('layouts.app')

@section('title', 'My profile | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>My profile</h1>
    <p>Contact the administrator to change official details.</p>
  </div>
</div>

@if (session('status'))
  <div class="alert info" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; max-width: 1000px;">
  <!-- Profile Information & Phone -->
  <div class="card">
    <div class="dr-who">
      <span class="av lg" style="background: {{ $user->color ?: '#10405A' }};" aria-hidden="true">
        {{ strtoupper(substr($user->name, 0, 2)) }}
      </span>
      <div>
        <b>{{ $user->name }}</b>
        <small>{{ $user->position->name ?? 'Staff Member' }}</small>
      </div>
    </div>

    <dl class="dl" style="margin-top: 20px;">
      <dt>Full name</dt><dd>{{ $user->name }}</dd>
      <dt>Work email</dt><dd>{{ $user->email }}</dd>
      <dt>Position</dt><dd>{{ $user->position->name ?? 'Not assigned' }}</dd>
      <dt>Department</dt><dd>{{ $user->department ? $user->department->name : 'General' }}</dd>
      <dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd>
    </dl>

    <form method="POST" action="{{ route('profile.update') }}" style="margin-top: 20px;">
      @csrf
      <div class="field {{ $errors->has('phone') ? 'err' : '' }}">
        <label for="pf-phone">Phone number</label>
        <div class="inp">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <input class="input" id="pf-phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel" placeholder="+1 (555) 000-0000">
        </div>
        <div class="ferr">@error('phone'){{ $message }}@enderror</div>
      </div>
      <button class="btn primary" type="submit">Save phone number</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card">
    <h3>Security & Password</h3>
    <p class="muted" style="margin-bottom: 20px;">Update your password regularly to ensure your account stays secure.</p>

    <form method="POST" action="{{ route('password.change.update') }}">
      @csrf

      <div class="field {{ $errors->has('current_password') ? 'err' : '' }}">
        <label for="current_password">Current password</label>
        <div class="inp">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
          <input class="input has-eye" id="current_password" name="current_password" type="password" placeholder="Enter current password" required>
          <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="ferr">@error('current_password'){{ $message }}@enderror</div>
      </div>

      <div class="field {{ $errors->has('new_password') ? 'err' : '' }}">
        <label for="new_password">New password</label>
        <div class="inp">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
          <input class="input has-eye" id="new_password" name="new_password" type="password" placeholder="Min 12 chars, upper, lower, number, symbol" required>
          <button type="button" class="eye" data-act="togglePw" aria-label="Show password">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="ferr">@error('new_password'){{ $message }}@enderror</div>
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

      <button class="btn primary" type="submit">Update password</button>
    </form>
  </div>
</div>
@endsection
