@extends('layouts.app')

@section('title', 'Reports | Leave system')

@section('content')
<div class="ph">
  <div><h1>Leave report</h1><p>Filter the records, then print or export them.</p></div>
  @php
    $exportQuery = http_build_query(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
  @endphp
  <a class="btn primary" href="{{ route('admin.reports.export') }}{{ $exportQuery !== '' ? '?' . $exportQuery : '' }}">Export CSV</a>
</div>

<div class="toolbar">
  <form method="GET" action="{{ route('reports') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <select class="input" name="employee_id" aria-label="Employee" style="width:auto;min-width:170px;">
      <option value="">All employees</option>
      @foreach ($users as $person)
        <option value="{{ $person->id }}" {{ (string) data_get($filters, 'employee_id') === (string) $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
      @endforeach
    </select>
    <select class="input" name="leave_type_id" aria-label="Leave type" style="width:auto;min-width:170px;">
      <option value="">All leave types</option>
      @foreach ($leaveTypes as $type)
        <option value="{{ $type->id }}" {{ (string) data_get($filters, 'leave_type_id') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
      @endforeach
    </select>
    <select class="input" name="status" aria-label="Status" style="width:auto;min-width:170px;">
      <option value="">All statuses</option>
      @foreach (['pending', 'approved', 'rejected', 'cancelled'] as $value)
        <option value="{{ $value }}" {{ data_get($filters, 'status') === $value ? 'selected' : '' }}>{{ ucfirst($value) }}</option>
      @endforeach
    </select>
    <input class="input" type="date" name="from" value="{{ data_get($filters, 'from', '') }}" aria-label="From date" style="width:170px;">
    <input class="input" type="date" name="to" value="{{ data_get($filters, 'to', '') }}" aria-label="To date" style="width:170px;">
    <button type="submit" class="btn">Apply filters</button>
  </form>
</div>

<div class="stats" style="margin-bottom:22px;">
  <div class="card stat"><div class="n">{{ $counts['all'] }}</div><div class="l">Applications</div></div>
  <div class="card stat"><div class="n">{{ $counts['approved'] }}</div><div class="l">Approved</div></div>
  <div class="card stat"><div class="n">{{ $counts['pending'] }}</div><div class="l">Pending</div></div>
  <div class="card stat"><div class="n">{{ $counts['rejected'] }}</div><div class="l">Rejected</div></div>
  <div class="card stat hl"><div class="n">{{ $counts['days'] }}</div><div class="l">Approved leave days</div></div>
</div>

<div class="sh"><h2>Records</h2></div>
<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Employee</th><th>Leave type</th><th>Dates</th><th class="num">Days</th><th>Status</th><th class="muted">Submitted</th></tr></thead>
    <tbody>
      @forelse ($requests as $req)
        <tr>
          <td><b>{{ $req->user->name }}</b></td>
          <td>{{ $req->leaveType->name }}</td>
          <td>{{ $req->start_date->format('M d, Y') }} @if ($req->start_date->ne($req->end_date)) – {{ $req->end_date->format('M d, Y') }} @endif</td>
          <td class="num">{{ $req->days }}</td>
          <td><span class="pill {{ $req->status }}"><i></i>{{ ucfirst($req->status) }}</span></td>
          <td class="muted">{{ $req->created_at->format('M d, Y') }}</td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty"><h3>No records match</h3><p>Widen the dates or pick another filter.</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($requests->hasPages())
  <div style="margin-top: 20px; display: flex; gap: 8px;">
    @if ($requests->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $requests->previousPageUrl() }}">Previous</a> @endif
    @if ($requests->hasMorePages()) <a class="btn" href="{{ $requests->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
@endsection
