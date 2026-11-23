@extends('layouts.app')

@section('title', 'Approvals | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>Approvals</h1>
    <p>Leave applications from your team and department waiting for review.</p>
  </div>
</div>

@if (session('status'))
  <div class="alert info" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

<div class="stats" style="margin-bottom: 22px;">
  <div class="card stat hl">
    <div class="n">{{ $pendingCount }}</div>
    <div class="l">Pending review</div>
  </div>
  <div class="card stat">
    <div class="n">{{ $decidedCount }}</div>
    <div class="l">Decided requests</div>
  </div>
</div>

<div class="toolbar">
  <form method="GET" action="{{ route('approvals.index') }}" id="approvalFilter">
    <select class="input" name="status" onchange="this.form.submit()" style="width: auto; min-width: 190px;" aria-label="Filter by status">
      <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending review</option>
      <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
      <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
      <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
    </select>
  </form>
  <span class="count">{{ $requests->total() }} {{ $requests->total() === 1 ? 'application' : 'applications' }}</span>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <th>Employee</th>
        <th>Leave type</th>
        <th>Dates</th>
        <th class="num">Days</th>
        <th>Status</th>
        <th class="muted">Submitted</th>
        <th><span class="muted">Review</span></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($requests as $req)
        <tr>
          <td><b>{{ $req->user->name }}</b></td>
          <td>{{ $req->leaveType->name }}</td>
          <td>
            {{ $req->start_date->format('M d, Y') }}
            @if ($req->start_date->ne($req->end_date))
              – {{ $req->end_date->format('M d, Y') }}
            @endif
          </td>
          <td class="num">{{ $req->days }}</td>
          <td><span class="pill {{ $req->status }}"><i></i>{{ ucfirst($req->status) }}</span></td>
          <td class="muted">{{ $req->created_at->format('M d, Y') }}</td>
          <td><a class="link" href="{{ route('approvals.show', $req) }}">Open</a></td>
        </tr>
      @empty
        <tr>
          <td colspan="7">
            <div class="empty">
              <h3>Nothing matches this filter</h3>
              <p>Try another status. New applications will show up here.</p>
            </div>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($requests->hasPages())
  <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div class="muted" style="font-size: 13.5px;">
      Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of {{ $requests->total() }} applications
    </div>
    <div style="display: flex; gap: 8px;">
      @if ($requests->onFirstPage())
        <button class="btn" disabled style="opacity: 0.5; cursor: not-allowed;">Previous</button>
      @else
        <a class="btn" href="{{ $requests->previousPageUrl() }}">Previous</a>
      @endif

      @if ($requests->hasMorePages())
        <a class="btn" href="{{ $requests->nextPageUrl() }}">Next</a>
      @else
        <button class="btn" disabled style="opacity: 0.5; cursor: not-allowed;">Next</button>
      @endif
    </div>
  </div>
@endif
@endsection
