@extends('layouts.app')

@section('title', 'Settings | Leave system')

@section('content')
<div class="ph">
  <div><h1>Settings</h1><p>Keep the leave system organized and up to date.</p></div>
</div>

@if (session('status'))
  <div class="alert info" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ session('status') }}</span></div>
@endif

@if ($errors->any())
  <div class="alert err" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg><span>{{ $errors->first() }}</span></div>
@endif

<nav class="settings-tabs" aria-label="Settings sections">
  <a class="settings-tab {{ request()->routeIs('settings.departments') ? 'active' : '' }}" href="{{ route('settings.departments') }}" {{ request()->routeIs('settings.departments') ? 'aria-current="page"' : '' }}>Departments</a>
  <a class="settings-tab {{ request()->routeIs('settings.positions') ? 'active' : '' }}" href="{{ route('settings.positions') }}" {{ request()->routeIs('settings.positions') ? 'aria-current="page"' : '' }}>Positions</a>
  <a class="settings-tab {{ request()->routeIs('settings.leave-types') ? 'active' : '' }}" href="{{ route('settings.leave-types') }}" {{ request()->routeIs('settings.leave-types') ? 'aria-current="page"' : '' }}>Leave types</a>
</nav>

@yield('settings-content')

<div class="card settings-roles">
  <h3 class="dr-h">Roles</h3>
  <p class="muted">Roles are fixed and cannot be changed here.</p>
  <ul class="role-list">
    @foreach ($roles as $role)
      <li><b>{{ $role->display_name }}</b><span>{{ $role->description }}</span></li>
    @endforeach
  </ul>
</div>
@endsection
