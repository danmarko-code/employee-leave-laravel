@extends('layouts.app')

@section('title', 'Review request | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>Review request #{{ $leaveRequest->id }}</h1>
    <p>Submitted by {{ $leaveRequest->user->name }} on {{ $leaveRequest->created_at->format('M d, Y') }}</p>
  </div>
  <a class="btn" href="{{ route('approvals.index') }}">← Back to approvals</a>
</div>

@if (session('status'))
  <div class="alert info" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

@if ($errors->any())
  <div class="alert err" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
    <span>{{ $errors->first() }}</span>
  </div>
@endif

<div class="card" style="max-width: 760px;">
  <dl class="dl">
    <dt>Status</dt>
    <dd><span class="pill {{ $leaveRequest->status }}"><i></i>{{ ucfirst($leaveRequest->status) }}</span></dd>

    <dt>Employee</dt>
    <dd><b>{{ $leaveRequest->user->name }}</b></dd>

    <dt>Leave type</dt>
    <dd><b>{{ $leaveRequest->leaveType->name }}</b></dd>

    <dt>Dates</dt>
    <dd>
      {{ $leaveRequest->start_date->format('M d, Y') }}
      @if ($leaveRequest->start_date->ne($leaveRequest->end_date))
        – {{ $leaveRequest->end_date->format('M d, Y') }}
      @endif
    </dd>

    <dt>Working days</dt>
    <dd>{{ $leaveRequest->days }}</dd>

    <dt>Reason</dt>
    <dd>{{ $leaveRequest->reason }}</dd>

    <dt>Document</dt>
    <dd>
      @can('downloadAttachment', $leaveRequest)
        <a class="link" href="{{ route('requests.attachment', $leaveRequest) }}">Download {{ $leaveRequest->attachment_name ?: 'Supporting Document' }}</a>
      @else
        <span class="muted">Not attached</span>
      @endcan
    </dd>
  </dl>

  <h3 class="dr-h">Balance impact</h3>
  <div class="impact">
    <span>{{ $leaveRequest->user->name }}'s {{ strtolower($leaveRequest->leaveType->name) }}</span>
    <b>{{ $availableBefore }} days before, {{ $availableAfter }} after</b>
  </div>

  @if ($leaveRequest->canTransitionTo('approved') || $leaveRequest->canTransitionTo('rejected'))
    <form method="POST" action="{{ route('approvals.approve', $leaveRequest) }}" style="margin-top: 22px;">
      @csrf
      <div class="field {{ $errors->has('note') ? 'err' : '' }}">
        <label for="decNote">Remarks for {{ $leaveRequest->user->name }} <span class="muted">(required when rejecting)</span></label>
        <textarea class="input area" id="decNote" name="note" maxlength="1000">{{ old('note') }}</textarea>
        @error('note')
          <div class="ferr">{{ $message }}</div>
        @enderror
      </div>
      <div class="dr-foot" style="margin: 0 -22px -22px;">
        <button type="submit" class="btn danger" formaction="{{ route('approvals.reject', $leaveRequest) }}">Reject</button>
        <button type="submit" class="btn primary">Approve</button>
      </div>
    </form>
  @endif

  <h3 class="dr-h" style="margin-top: 26px;">Progress</h3>
  <ul class="tl">
    @forelse ($leaveRequest->histories as $history)
      <li class="{{ $history->status_to === 'approved' ? 'done' : ($history->status_to === 'pending' ? 'wait' : 'bad') }}">
        <i></i>
        <b>{{ ucfirst($history->status_to) }}</b>
        <small>{{ $history->created_at->format('M d, Y g:ia') }} by {{ $history->actor ? $history->actor->name : 'System' }}</small>
        @if ($history->note)
          <div class="quote">{{ $history->note }}</div>
        @endif
      </li>
    @empty
      <li class="wait"><i></i><b>Submitted</b><small>Awaiting review</small></li>
    @endforelse
  </ul>
</div>
@endsection
