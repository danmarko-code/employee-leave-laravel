@extends('layouts.app')

@section('title', $user->name . ' | Leave system')

@section('content')
<div class="ph">
  <div><h1>{{ $user->name }}</h1><p>{{ $user->position->name ?? 'Not assigned' }} · {{ $user->department->name ?? 'No department' }}</p></div>
  <a class="btn" href="{{ route('users') }}">← Back to employees</a>
</div>

@if ($temporaryPassword)
  <section class="card" data-temp-password-card style="margin-bottom:20px;">
    <h2 class="dr-h">Account created</h2>
    <p class="muted">Copy the one-time password now. It will not be shown again.</p>
    <div class="field">
      <label for="temporary_password">Temporary password</label>
      <div style="display:flex;gap:10px;align-items:center;">
        <input id="temporary_password" class="input mono" type="text" value="{{ $temporaryPassword }}" readonly autocomplete="off" spellcheck="false" data-temp-password-value>
        <button type="button" class="btn" data-copy-target="temporary_password" data-copy-status="temporary-password-status">Copy</button>
      </div>
      <span id="temporary-password-status" class="muted" role="status" aria-live="polite"></span>
    </div>
    <button type="button" class="btn primary" data-temp-password-saved>I've saved it</button>
  </section>
@endif

@if (session('status'))
  <div class="alert info" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ session('status') }}</span></div>
@endif

<div class="card" style="max-width:720px;">
  <dl class="dl">
    <dt>Email</dt><dd>{{ $user->email }}</dd>
    <dt>Phone</dt><dd>{{ $user->phone ?: 'Not set' }}</dd>
    <dt>Role</dt><dd>{{ $user->roleRelation->display_name ?? $user->role }}</dd>
    <dt>Status</dt><dd><span class="pill {{ $user->is_active ? 'approved' : 'cancelled' }}"><i></i>{{ $user->is_active ? 'Active' : 'Inactive' }}</span></dd>
    <dt>Password</dt><dd>{{ $user->must_change_password ? 'Temporary, change required at first login' : 'Changed by user' }}</dd>
  </dl>

  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a class="btn primary" href="{{ route('admin.users.edit', $user) }}">Edit account</a>
    @can('resetTemporaryPassword', $user)
      <form method="POST" action="{{ route('admin.users.temporary-password.reset', $user) }}" data-confirm="Generate a new temporary password? The old one will stop working and this user will be signed out.">
        @csrf
        <button type="submit" class="btn">Reset temporary password</button>
      </form>
    @endcan
    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete this account?">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn danger">Delete account</button>
    </form>
  </div>

  <h3 class="dr-h" style="margin-top:26px;">Leave balances</h3>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Type</th><th>Year</th><th class="num">Allocated</th><th class="num">Used</th><th class="num">Pending</th></tr></thead>
      <tbody>
        @forelse ($balances as $balance)
          <tr>
            <td>{{ $balance->leaveType->name }}</td><td>{{ $balance->year }}</td>
            <td class="num">{{ $balance->allocated_days }}</td><td class="num">{{ $balance->used_days }}</td><td class="num">{{ $balance->pending_days }}</td>
          </tr>
        @empty
          <tr><td colspan="5"><span class="muted">No balance records yet.</span></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <h3 class="dr-h" style="margin-top:26px;">Recent leave history</h3>
  <ul class="tl">
    @forelse ($history as $req)
      <li class="{{ $req->status === 'approved' ? 'done' : ($req->status === 'pending' ? 'wait' : 'bad') }}">
        <i></i><b>{{ $req->leaveType->name }}</b>
        <small>{{ $req->start_date->format('M d, Y') }} – {{ $req->end_date->format('M d, Y') }}, {{ $req->days }} days</small>
        <span class="pill {{ $req->status }}"><i></i>{{ ucfirst($req->status) }}</span>
      </li>
    @empty
      <li class="off"><i></i><b>No leave applications yet.</b></li>
    @endforelse
  </ul>
</div>
@endsection
