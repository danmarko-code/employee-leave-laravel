@extends('layouts.app')

@section('title', 'Overview | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>{{ Auth::user()->name }}'s overview</h1>
    <p>Employees and leave applications at a glance.</p>
  </div>
  <a class="btn primary" href="{{ route('admin.users.create') }}">Add employee</a>
</div>

<div class="stats" style="margin-bottom: 22px;">
  <div class="card stat"><div class="n">{{ $employeeCount }}</div><div class="l">Employees</div></div>
  <div class="card stat"><div class="n">{{ $allCount }}</div><div class="l">Leave applications</div></div>
  <div class="card stat hl"><div class="n">{{ $pendingCount }}</div><div class="l">Pending review</div></div>
  <div class="card stat"><div class="n">{{ $approvedCount }}</div><div class="l">Approved</div></div>
  <div class="card stat"><div class="n">{{ $rejectedCount }}</div><div class="l">Rejected</div></div>
</div>

<div class="sh">
  <h2>Waiting for review</h2>
  <a class="link" href="{{ route('approvals.index') }}">View all approvals</a>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead>
      <tr><th>Employee</th><th>Leave type</th><th>Dates</th><th class="num">Days</th><th>Status</th></tr>
    </thead>
    <tbody>
      @forelse ($pending as $req)
        <tr>
          <td><b>{{ $req->user->name }}</b></td>
          <td>{{ $req->leaveType->name }}</td>
          <td>{{ $req->start_date->format('M d, Y') }} – {{ $req->end_date->format('M d, Y') }}</td>
          <td class="num">{{ $req->days }}</td>
          <td><span class="pill pending"><i></i>Pending</span></td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h3>You are all caught up</h3><p>New applications will show up here.</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
