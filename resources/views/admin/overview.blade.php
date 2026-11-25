@extends('layouts.app')

@section('title', 'Overview | Leave system')

@section('content')
@include('partials.admin-tabs', ['set' => 'dashboard'])

<div class="dashboard">
  <div class="ph">
    <div>
      <h1>{{ Auth::user()->name }}'s overview</h1>
      <p>Employees and leave applications at a glance.</p>
    </div>
    <a class="btn primary" href="{{ route('admin.users.create') }}">Add employee</a>
  </div>

  <div class="stats dashboard-stats">
    <div class="card stat"><div class="n">{{ $employeeCount }}</div><div class="l">Employees</div></div>
    <div class="card stat"><div class="n">{{ $allCount }}</div><div class="l">Leave applications</div></div>
    <div class="card stat hl"><div class="n">{{ $pendingCount }}</div><div class="l">Pending review</div></div>
    <div class="card stat"><div class="n">{{ $approvedCount }}</div><div class="l">Approved</div></div>
    <div class="card stat"><div class="n">{{ $rejectedCount }}</div><div class="l">Rejected</div></div>
  </div>

  <div class="dashboard-panels">
    <section class="review-panel" aria-labelledby="review-heading">
      <div class="sh">
        <h2 id="review-heading">Needs review</h2>
        <a class="link" href="{{ route('approvals.index') }}">View all approvals</a>
      </div>

      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr><th>Employee</th><th>Leave type</th><th>Dates</th><th class="num">Days</th><th>Status</th><th><span class="muted">Review</span></th></tr>
          </thead>
          <tbody>
            @forelse ($pending as $req)
              <tr>
                <td><b>{{ $req->user->name }}</b></td>
                <td>{{ $req->leaveType->name }}</td>
                <td>{{ $req->start_date->format('M d, Y') }} – {{ $req->end_date->format('M d, Y') }}</td>
                <td class="num">{{ $req->days }}</td>
                <td><span class="pill pending"><i></i>Pending</span></td>
                <td><a class="link" href="{{ route('approvals.show', $req) }}">Open</a></td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="empty"><h3>You are all caught up</h3><p>New applications will show up here.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <section class="dashboard-context" aria-label="Leave balances and upcoming leave">
    <div class="bal-grid">
      @php $lastRingIndex = count($balanceRings) - 1; @endphp
      @forelse ($balanceRings as $ringIndex => $ring)
        @php
          $ariaLabel = $ring['name'].': '.(int) $ring['available'].' days left of '.(int) $ring['allocated'].' allocated, '.(int) $ring['pending'].' pending, '.(int) $ring['used'].' used';
        @endphp
        @include('partials.balance-ring', [
          'name' => $ring['name'],
          'allocated' => $ring['allocated'],
          'avail' => $ring['available'],
          'pending' => $ring['pending'],
          'used' => $ring['used'],
          'allocatedLabel' => 'days allocated this year',
          'href' => route('admin.balances.index', ['leave_type_id' => $ring['id']]),
          'ariaLabel' => $ariaLabel,
          'stretched' => $ringIndex === $lastRingIndex,
        ])
      @empty
        <article class="card bal empty-bal">
          <div class="bal-body">
            <h3>No balances for this year yet</h3>
            <a class="link" href="{{ route('admin.balances.index') }}">View balances</a>
          </div>
        </article>
      @endforelse

      <article class="card next who-next">
        <h3>Who's out next</h3>
        <div class="who-next-list" role="region" aria-label="Who's out next" tabindex="0">
          @forelse ($upcomingLeave as $out)
            <div class="next-row">
              <div class="datebox">
                <b>{{ $out->start_date->format('j') }}</b>
                <span>{{ $out->start_date->format('M') }}</span>
              </div>
              <div class="who-detail">
                <div class="next-t">{{ $out->user->name }}</div>
                <div class="muted">{{ $out->leaveType->name }}</div>
                <div class="muted">
                  {{ $out->start_date->format('M d, Y') }}
                  @if ($out->start_date->ne($out->end_date))
                    – {{ $out->end_date->format('M d, Y') }}
                  @endif
                </div>
                <div class="next-foot">
                  @if ($out->start_date->toDateString() <= now()->toDateString() && $out->end_date->toDateString() >= now()->toDateString())
                    <span class="pill approved"><i></i>On leave now</span>
                  @endif
                  <span class="muted">{{ $out->days }} {{ Str::plural('working day', $out->days) }}</span>
                </div>
              </div>
            </div>
          @empty
            <p class="muted">Nobody is on leave soon.</p>
          @endforelse
        </div>
      </article>
    </div>
  </section>

</div>
@endsection
