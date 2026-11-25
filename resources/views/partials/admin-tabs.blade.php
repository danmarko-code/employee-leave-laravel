@php
  $set = $set ?? 'dashboard';
  $sets = [
    'dashboard' => [
      'label' => 'Dashboard sections',
      'tabs' => [
        ['route' => 'dashboard', 'label' => 'Overview'],
        ['route' => 'approvals.index', 'label' => 'Approvals'],
        ['route' => 'admin.applications', 'label' => 'Applications'],
        ['route' => 'activity', 'label' => 'Activity'],
      ],
    ],
    'employees' => [
      'label' => 'Employee sections',
      'tabs' => [
        ['route' => 'users', 'label' => 'People'],
        ['route' => 'admin.balances.index', 'label' => 'Balances'],
      ],
    ],
  ];
  $config = $sets[$set] ?? $sets['dashboard'];

  if ($set === 'employees') {
    $activeRoute = request()->routeIs('admin.balances.*') ? 'admin.balances.index' : 'users';
  } elseif (request()->routeIs('approvals.*')) {
    $activeRoute = 'approvals.index';
  } elseif (request()->routeIs('admin.applications')) {
    $activeRoute = 'admin.applications';
  } elseif (request()->routeIs('activity')) {
    $activeRoute = 'activity';
  } else {
    $activeRoute = 'dashboard';
  }
@endphp

<nav class="settings-tabs" aria-label="{{ $config['label'] }}">
  @foreach ($config['tabs'] as $tab)
    @php $isActive = $activeRoute === $tab['route']; @endphp
    <a class="settings-tab {{ $isActive ? 'active' : '' }}" href="{{ route($tab['route']) }}" @if ($isActive) aria-current="page" @endif>{{ $tab['label'] }}</a>
  @endforeach
</nav>
