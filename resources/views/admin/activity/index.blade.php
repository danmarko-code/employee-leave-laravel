@extends('layouts.app')

@section('title', 'Activity | Leave system')

@section('content')
@include('partials.admin-tabs', ['set' => 'dashboard'])

<div class="ph">
  <div><h1>Activity</h1><p>Recent actions recorded by the system. This log is read-only.</p></div>
</div>

<div class="toolbar">
  <form method="GET" action="{{ route('activity') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input class="input" type="search" name="action" value="{{ data_get($filters, 'action', '') }}" placeholder="Action, e.g. leave.approved" aria-label="Action" style="width:240px;">
    <select class="input" name="user_id" aria-label="User" style="width:auto;min-width:170px;">
      <option value="">All users</option>
      @foreach ($users as $person)
        <option value="{{ $person->id }}" {{ (string) data_get($filters, 'user_id') === (string) $person->id ? 'selected' : '' }}>{{ $person->name }}</option>
      @endforeach
    </select>
    <input class="input" type="date" name="from" value="{{ data_get($filters, 'from', '') }}" aria-label="From date" style="width:170px;">
    <input class="input" type="date" name="to" value="{{ data_get($filters, 'to', '') }}" aria-label="To date" style="width:170px;">
    <button type="submit" class="btn">Apply filters</button>
  </form>
  <span class="count">{{ $logs->total() }} {{ $logs->total() === 1 ? 'entry' : 'entries' }}</span>
</div>

<div class="card">
  <ul class="feed">
    @forelse ($logs as $log)
      <li>
        <i></i>
        <span class="when">{{ $log->created_at->format('M d, Y') }}</span>
        <span>
          <b>{{ $log->user->name ?? 'System' }}</b>
          <span class="tag" style="margin-left:8px;">{{ $log->action }}</span><br>
          {{ $log->description }}
        </span>
      </li>
    @empty
      <li><span class="muted">No activity matches these filters.</span></li>
    @endforelse
  </ul>
</div>

@if ($logs->hasPages())
  <div style="margin-top: 20px; display: flex; gap: 8px;">
    @if ($logs->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $logs->previousPageUrl() }}">Previous</a> @endif
    @if ($logs->hasMorePages()) <a class="btn" href="{{ $logs->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
@endsection
