@extends('layouts.app')

@section('title', 'Request Details | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>Request #{{ $leaveRequest->id }}</h1>
    <p>Submitted on {{ $leaveRequest->created_at->format('M d, Y') }}</p>
  </div>
  <a class="btn" href="{{ route('requests') }}">← Back to requests</a>
</div>

@if (session('status'))
  <div class="alert info" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

@if ($waitingForAdmin)
  <div class="alert info" role="alert" style="margin-bottom: 20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    <span>Waiting for another administrator.</span>
  </div>
@endif

<div class="card" style="max-width: 640px;">
  <dl class="dl">
    <dt>Status</dt>
    <dd><span class="pill {{ $leaveRequest->status }}"><i></i>{{ ucfirst($leaveRequest->status) }}</span></dd>

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
    <dd><b>{{ $leaveRequest->days }}</b> {{ Str::plural('working day', $leaveRequest->days) }}</dd>

    <dt>Reason</dt>
    <dd>{{ $leaveRequest->reason }}</dd>

    <dt>Attachment</dt>
    <dd>
      @include('partials.attachment-preview', ['emptyLabel' => 'No document attached'])
    </dd>
  </dl>

  <h3 class="dr-h" style="margin-top:24px;">Progress & Timeline</h3>
  <ul class="tl">
    @forelse ($leaveRequest->histories as $h)
      <li class="done">
        <i></i>
        <b>{{ ucfirst($h->status_to) }}</b>
        <small>{{ $h->created_at->format('M d, Y g:ia') }} by {{ $h->actor ? $h->actor->name : 'System' }}</small>
        @if ($h->note)
          <div class="quote">{{ $h->note }}</div>
        @endif
      </li>
    @empty
      <li class="wait"><i></i><b>Submitted</b><small>Awaiting review</small></li>
    @endforelse
  </ul>

  @can('cancel', $leaveRequest)
    <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); display:flex; justify-content: flex-end;">
      <form method="POST" action="{{ route('requests.cancel', $leaveRequest) }}" onsubmit="return confirm('Are you sure you want to cancel this leave application?')">
        @csrf
        <button type="submit" class="btn danger">Cancel application</button>
      </form>
    </div>
  @endcan
</div>
@endsection
