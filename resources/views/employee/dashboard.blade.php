@extends('layouts.app')

@section('title', 'My leave | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>My leave</h1>
    <p>Track your leave balances and upcoming time off.</p>
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

<div class="bal-grid">
  @foreach ($balances as $bal)
    @include('partials.balance-ring', [
      'name' => $bal->leaveType->name,
      'allocated' => $bal->allocated_days,
      'avail' => $bal->available_days,
      'pending' => $bal->pending_days,
      'used' => $bal->used_days,
    ])
  @endforeach

  @if ($nextLeave)
    <article class="card next" tabindex="0" onclick="window.location.href='{{ route('requests.show', $nextLeave) }}'">
      <h3>Next time off</h3>
      <div class="next-row">
        <div class="datebox">
          <b>{{ $nextLeave->start_date->format('j') }}</b>
          <span>{{ $nextLeave->start_date->format('M') }}</span>
        </div>
        <div>
          <div class="next-t">{{ $nextLeave->leaveType->name }}</div>
          <div class="muted">
            {{ $nextLeave->start_date->format('M d, Y') }}
            @if ($nextLeave->start_date->ne($nextLeave->end_date))
              – {{ $nextLeave->end_date->format('M d, Y') }}
            @endif
          </div>
        </div>
      </div>
      <div class="next-foot">
        <span class="pill {{ $nextLeave->status }}"><i></i>{{ ucfirst($nextLeave->status) }}</span>
        <span class="muted">{{ $nextLeave->days }} {{ Str::plural('working day', $nextLeave->days) }}</span>
      </div>
    </article>
  @else
    <article class="card next empty-next">
      <h3>Next time off</h3>
      <p class="muted">Nothing planned yet. Book your next break while your balance is healthy.</p>
      <div class="next-foot">
        <a class="btn primary sm" href="{{ route('apply') }}">Apply for leave</a>
      </div>
    </article>
  @endif
</div>

<div class="sh">
  <div>
    <h2>Recent requests</h2>
    <p>Your latest leave applications.</p>
  </div>
  <a class="link" href="{{ route('requests') }}" style="color:var(--lagoon-700);text-decoration:none;font-weight:600;font-size:13.5px;">View all requests →</a>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <th>Type</th>
        <th>Dates</th>
        <th class="num">Days</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($recentRequests as $req)
        <tr class="click" tabindex="0" onclick="window.location.href='{{ route('requests.show', $req) }}'">
          <td><b>{{ $req->leaveType->name }}</b></td>
          <td>
            {{ $req->start_date->format('M d, Y') }}
            @if ($req->start_date->ne($req->end_date))
              – {{ $req->end_date->format('M d, Y') }}
            @endif
          </td>
          <td class="num">{{ $req->days }}</td>
          <td><span class="pill {{ $req->status }}"><i></i>{{ ucfirst($req->status) }}</span></td>
        </tr>
      @empty
        <tr>
          <td colspan="4">
            <div class="empty">
              <h3>No leave requests yet</h3>
              <p>When you submit a leave request, it will appear here.</p>
            </div>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
