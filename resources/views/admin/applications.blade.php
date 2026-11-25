@extends('layouts.app')

@section('title', 'Applications | Leave system')

@section('content')
@include('partials.admin-tabs', ['set' => 'dashboard'])

<div class="ph">
  <div>
    <h1>Leave applications</h1>
    <p>Open an application to review its details.</p>
  </div>
</div>

<div class="toolbar">
  <form method="GET" action="{{ route('admin.applications') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
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
  <span class="count">{{ $requests->total() }} {{ $requests->total() === 1 ? 'application' : 'applications' }}</span>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <th>Employee</th><th>Leave type</th><th>Dates</th><th class="num">Days</th>
        <th>Status</th><th class="muted">Submitted</th><th><span class="muted">Details</span></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($requests as $req)
        <tr>
          <td><b>{{ $req->user->name }}</b></td>
          <td>{{ $req->leaveType->name }}</td>
          <td>{{ $req->start_date->format('M d, Y') }} @if ($req->start_date->ne($req->end_date)) – {{ $req->end_date->format('M d, Y') }} @endif</td>
          <td class="num">{{ $req->days }}</td>
          <td><span class="pill {{ $req->status }}"><i></i>{{ ucfirst($req->status) }}</span></td>
          <td class="muted">{{ $req->created_at->format('M d, Y') }}</td>
          <td><a class="link" href="{{ route('requests.show', $req) }}">Open</a></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty"><h3>Nothing matches these filters</h3><p>Try a different employee, type, status or date range.</p></div></td></tr>
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
