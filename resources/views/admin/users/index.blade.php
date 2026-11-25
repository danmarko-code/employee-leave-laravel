@extends('layouts.app')

@section('title', 'Employees | Leave system')

@section('content')
@include('partials.admin-tabs', ['set' => 'employees'])

<div class="ph">
  <div>
    <h1>Employees</h1>
    <p>Create and manage accounts. Open an account to see its leave history.</p>
  </div>
  <a class="btn primary" href="{{ route('admin.users.create') }}">Add employee</a>
</div>

@if (session('status'))
  <div class="alert info" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ session('status') }}</span></div>
@endif

<div class="toolbar">
  <form method="GET" action="{{ route('users') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <input class="input" type="search" name="q" value="{{ $q }}" placeholder="Search people" aria-label="Search people" style="width:260px;">
    <select class="input" name="role" aria-label="Filter by role" style="width:auto;min-width:170px;">
      <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All roles</option>
      @foreach ($roles as $item)
        <option value="{{ $item->name }}" {{ $role === $item->name ? 'selected' : '' }}>{{ $item->display_name }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn">Search</button>
  </form>
  <span class="count">{{ $users->total() }} {{ $users->total() === 1 ? 'person' : 'people' }}</span>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Name</th><th>Position</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($users as $person)
        <tr>
          <td><b>{{ $person->name }}</b><br><span class="muted">{{ $person->email }}</span></td>
          <td>{{ $person->position->name ?? 'Not assigned' }}<br><span class="muted">{{ $person->department->name ?? '—' }}</span></td>
          <td><span class="tag">{{ $person->roleRelation->display_name ?? $person->role }}</span></td>
          <td><span class="pill {{ $person->is_active ? 'approved' : 'cancelled' }}"><i></i>{{ $person->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td>
            <a class="link" href="{{ route('admin.users.show', $person) }}">Open</a>
            <a class="link" href="{{ route('admin.users.edit', $person) }}" style="margin-left:10px;">Edit</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h3>No one matches</h3><p>Try a different search or role.</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($users->hasPages())
  <div style="margin-top: 20px; display: flex; gap: 8px;">
    @if ($users->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $users->previousPageUrl() }}">Previous</a> @endif
    @if ($users->hasMorePages()) <a class="btn" href="{{ $users->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
@endsection
