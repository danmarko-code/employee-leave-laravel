@php
  $user = Auth::user();
  $role = $user->role ?? 'employee';
  $pendingApprovals = $pendingApprovals ?? 0;

  $isActivityPage = request()->routeIs('activity') || request()->is('activity');

  // Grouped admin pages still highlight the Dashboard item. Request detail
  // pages reached from the queue also belong to this group.
  $isDashboardGroup = $isActivityPage
    || request()->is('overview') || request()->routeIs('overview')
    || request()->is('dashboard')
    || request()->is('approvals*') || request()->routeIs('approvals.*')
    || request()->is('applications*') || request()->routeIs('admin.applications')
    || ($role === 'admin' && request()->routeIs('requests.show'));

  $navItems = $role === 'admin' ? [
    ['route' => 'dashboard', 'url' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['route' => 'users', 'url' => '/users', 'label' => 'Employees', 'icon' => 'users'],
    ['route' => 'reports', 'url' => '/reports', 'label' => 'Reports', 'icon' => 'file'],
    ['route' => 'requests', 'url' => '/requests', 'label' => 'My leave', 'icon' => 'my-leave'],
    ['route' => 'settings.departments', 'url' => '/settings', 'label' => 'Settings', 'icon' => 'settings'],
  ] : [
    ['route' => 'dashboard', 'url' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['route' => 'requests', 'url' => '/requests', 'label' => 'My requests', 'icon' => 'file'],
    ['route' => 'profile', 'url' => '/profile', 'label' => 'Profile', 'icon' => 'user'],
  ];

  $icons = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
    'file' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
    'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'wallet' => '<path d="M20 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-2"/><path d="M16 12h6v5h-6a2.5 2.5 0 0 1 0-5z"/><path d="M3 7h16"/>',
    'check' => '<path d="M20 6 9 17l-5-5"/>',
    'my-leave' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M9 15l2 2 4-4"/>',
    'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21h-4v-.08A1.7 1.7 0 0 0 8.96 19.36a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3v-4h.08A1.7 1.7 0 0 0 4.64 8.96a1.7 1.7 0 0 0-.34-1.87l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1.04-1.56V3h4v.08A1.7 1.7 0 0 0 15.04 4.64a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9v.04A1.7 1.7 0 0 0 20.96 10.08H21v4h-.08A1.7 1.7 0 0 0 19.4 15z"/>',
  ];
@endphp

<aside class="side" id="side">
  <div class="wordmark">
    <img class="logo" src="{{ asset('assets/auth/cvsu-favicon.png') }}" alt="" aria-hidden="true">
    <b>Leave system</b>
  </div>

  <nav class="nav" id="nav" aria-label="Main">
    <span class="nav-ind" id="navInd"></span>
    @foreach ($navItems as $item)
      @php
        $isActive = match ($item['route']) {
          'settings.departments' => request()->is('settings*') || request()->routeIs('settings.*'),
          'requests' => $role === 'admin'
            ? (request()->is('requests') || request()->routeIs('apply*'))
            : (request()->routeIs('requests*', 'apply*')),
          'dashboard' => $role === 'admin' ? $isDashboardGroup : request()->is('dashboard') || request()->routeIs('dashboard'),
          'users' => request()->is('users*') || request()->is('balances*')
            || request()->routeIs('users', 'admin.users.*', 'admin.balances.*'),
          'reports' => request()->is('reports*') || request()->routeIs('reports', 'admin.reports.*'),
          default => request()->is(trim($item['url'], '/')) || request()->routeIs($item['route']),
        };
        $itemUrl = Route::has($item['route']) ? route($item['route']) : url($item['url']);
      @endphp
      <a class="nav-item {{ $isActive ? 'active' : '' }}" href="{{ $itemUrl }}" data-view="{{ $item['route'] }}" {!! $isActive ? 'aria-current="page"' : '' !!}>
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$item['icon']] ?? '' !!}</svg>
        <span>{{ $item['label'] }}</span>
        @if ($item['route'] === 'dashboard' && $role === 'admin')
          <span class="badge" id="qBadge" {!! $pendingApprovals === 0 ? 'hidden' : '' !!}>
            <span class="sr-only">{{ $pendingApprovals }} pending {{ $pendingApprovals === 1 ? 'approval' : 'approvals' }}</span>
            <span aria-hidden="true">{{ $pendingApprovals }}</span>
          </span>
        @endif
      </a>
    @endforeach
  </nav>

  <div class="side-foot">
    <div class="umenu" id="umenu">
      <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}">
        @csrf
        <button type="submit" class="out">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          Sign out
        </button>
      </form>
    </div>
    <button type="button" class="ubtn" data-act="umenu" aria-haspopup="menu" aria-expanded="false">
      <span class="av" style="background: {{ $user->color ?? '#0a4a12' }}" aria-hidden="true">
        {{ strtoupper(substr($user->name ?? 'User', 0, 2)) }}
      </span>
      <span class="meta">
        <b>{{ $user->name ?? 'Employee' }}</b>
        <small>{{ $role === 'admin' ? 'Administrator' : 'Employee' }}</small>
      </span>
      <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
  </div>
</aside>
