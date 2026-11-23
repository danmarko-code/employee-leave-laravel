@extends('layouts.app')

@section('title', 'My requests | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>My requests</h1>
    <p>Every leave request you have submitted.</p>
  </div>
  <a class="btn primary" href="{{ route('apply') }}">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/></svg>
    Apply for leave
  </a>
</div>

@if (session('status'))
  <div class="alert info" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

<div class="toolbar">
  <form method="GET" action="{{ route('requests') }}" id="filterForm">
    <select class="input" name="status" onchange="this.form.submit()" style="width: auto; min-width: 170px;">
      <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
      <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending review</option>
      <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
      <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
      <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
    </select>
  </form>
  <span class="count">{{ $requests->total() }} {{ Str::plural('request', $requests->total()) }}</span>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <th>Type</th>
        <th>Dates</th>
        <th class="num">Days</th>
        <th>Status</th>
        <th class="muted">Submitted</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($requests as $req)
        <tr class="click" tabindex="0" data-id="{{ $req->id }}" onclick="openRequestDrawer({{ $req->id }})">
          <td><b>{{ $req->leaveType->name }}</b></td>
          <td>
            {{ $req->start_date->format('M d, Y') }}
            @if ($req->start_date->ne($req->end_date))
              – {{ $req->end_date->format('M d, Y') }}
            @endif
          </td>
          <td class="num">{{ $req->days }}</td>
          <td><span class="pill {{ $req->status }}"><i></i>{{ ucfirst($req->status) }}</span></td>
          <td class="muted">{{ $req->created_at->format('M d, Y') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5">
            <div class="empty">
              <h3>Nothing matches this filter</h3>
              <p>Try selecting another status or submit a new leave application.</p>
              <a class="btn primary" href="{{ route('apply') }}" style="margin-top: 14px;">Apply for leave</a>
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
      Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of {{ $requests->total() }} requests
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

@push('scripts')
<script>
function closeDrawer() {
  const root = document.getElementById('drawerRoot');
  if (root) {
    root.classList.remove('open');
    setTimeout(() => { root.innerHTML = ''; }, 320);
  }
}

function openRequestDrawer(id) {
  const root = document.getElementById('drawerRoot');
  if (!root) return;

  fetch('/requests/' + id, {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(res => {
    if (res.status === 403 || res.status === 404) {
      alert('Unauthorized: You do not have permission to view this request.');
      throw new Error('Access denied');
    }
    return res.json();
  })
  .then(data => {
    let historyItems = '';
    if (data.histories && data.histories.length) {
      data.histories.forEach(h => {
        historyItems += '<li class="done"><i></i><b>' + h.status_to + '</b><small>' + h.date + ' by ' + h.actor_name + '</small>' + (h.note ? '<div class="quote">' + h.note + '</div>' : '') + '</li>';
      });
    }

    let cancelBtn = '';
    if (data.can_cancel) {
      cancelBtn = '<form method="POST" action="/requests/' + data.id + '/cancel" onsubmit="return confirm(\'Are you sure you want to cancel this request?\')">' +
        '<input type="hidden" name="_token" value="' + document.querySelector('meta[name="csrf-token"]').content + '">' +
        '<button type="submit" class="btn danger sm">Cancel request</button>' +
        '</form>';
    }

    let docRow = '<dd class="muted">Not attached</dd>';
    if (data.has_attachment) {
      docRow = '<dd><a class="link" href="' + data.download_url + '" style="color:var(--lagoon-700);font-weight:600;">Download ' + (data.attachment_name || 'Document') + '</a></dd>';
    }

    root.innerHTML = '<div class="scrim" onclick="closeDrawer()"></div>' +
      '<aside class="drawer" tabindex="-1">' +
      '<div class="dr-head">' +
        '<h2>Request details</h2>' +
        '<button type="button" class="x" onclick="closeDrawer()" aria-label="Close">' +
          '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
        '</button>' +
      '</div>' +
      '<div class="dr-body">' +
        '<dl class="dl">' +
          '<dt>Status</dt><dd><span class="pill ' + data.status + '"><i></i>' + data.status.charAt(0).toUpperCase() + data.status.slice(1) + '</span></dd>' +
          '<dt>Leave type</dt><dd><b>' + data.type_name + '</b></dd>' +
          '<dt>Dates</dt><dd>' + data.start_date + ' – ' + data.end_date + '</dd>' +
          '<dt>Working days</dt><dd>' + data.days + '</dd>' +
          '<dt>Reason</dt><dd>' + data.reason + '</dd>' +
          '<dt>Attachment</dt>' + docRow +
        '</dl>' +
        '<h3 class="dr-h">Timeline</h3>' +
        '<ul class="tl">' + historyItems + '</ul>' +
      '</div>' +
      '<div class="dr-foot" style="display:flex;justify-content:space-between;align-items:center;">' +
        '<button type="button" class="btn" onclick="closeDrawer()">Close</button>' +
        cancelBtn +
      '</div>' +
      '</aside>';

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        root.classList.add('open');
      });
    });
  })
  .catch(err => console.error(err));
}
</script>
@endpush
@endsection
