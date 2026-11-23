@extends('layouts.app')

@section('title', 'Leave balances | Leave system')

@section('content')
<div class="ph">
  <div><h1>Leave balances</h1><p>Adjust yearly entitlement without touching used or pending days.</p></div>
</div>

@if (session('status'))
  <div class="alert info" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ session('status') }}</span></div>
@endif

<div class="toolbar">
  <form method="GET" action="{{ route('admin.balances.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <select class="input" name="user_id" aria-label="Employee" style="width:auto;min-width:170px;">
      <option value="">All employees</option>
      @foreach ($users as $person)
        <option value="{{ $person->id }}" {{ (string) data_get($filters, 'user_id') === (string) $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
      @endforeach
    </select>
    <select class="input" name="leave_type_id" aria-label="Leave type" style="width:auto;min-width:170px;">
      <option value="">All leave types</option>
      @foreach ($leaveTypes as $type)
        <option value="{{ $type->id }}" {{ (string) data_get($filters, 'leave_type_id') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
      @endforeach
    </select>
    <input class="input" type="number" name="year" value="{{ data_get($filters, 'year', '') }}" placeholder="Year" min="2000" max="2100" style="width:120px;">
    <button type="submit" class="btn">Apply filters</button>
  </form>
  <span class="count">{{ $balances->total() }} {{ $balances->total() === 1 ? 'record' : 'records' }}</span>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Employee</th><th>Leave type</th><th>Year</th><th class="num">Allocated</th><th class="num">Used</th><th class="num">Pending</th><th></th></tr></thead>
    <tbody>
      @forelse ($balances as $balance)
        <tr>
          <td><b>{{ $balance->user->name }}</b></td>
          <td>{{ $balance->leaveType->name }}</td>
          <td>{{ $balance->year }}</td>
          <td class="num">{{ $balance->allocated_days }}</td>
          <td class="num">{{ $balance->used_days }}</td>
          <td class="num">{{ $balance->pending_days }}</td>
          <td><a class="link" href="{{ route('admin.balances.edit', $balance) }}">Edit</a></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty"><h3>No balances match</h3><p>Try a different employee, type or year.</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($balances->hasPages())
  <div style="margin-top: 20px; display: flex; gap: 8px;">
    @if ($balances->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $balances->previousPageUrl() }}">Previous</a> @endif
    @if ($balances->hasMorePages()) <a class="btn" href="{{ $balances->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
@endsection
