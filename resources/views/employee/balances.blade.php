@extends('layouts.app')

@section('title', 'My balances | Leave system')

@section('content')
<div class="ph">
  <div><h1>My balances</h1><p>Your leave entitlement for {{ $currentYear }}.</p></div>
  <a class="btn primary" href="{{ route('apply') }}">Apply for leave</a>
</div>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Leave type</th><th class="num">Allocated</th><th class="num">Available</th><th class="num">Pending</th><th class="num">Used</th></tr></thead>
    <tbody>
      @forelse ($balances as $balance)
        <tr>
          <td><b>{{ $balance->leaveType->name }}</b><br><span class="muted">{{ $balance->leaveType->code }}</span></td>
          <td class="num">{{ (int) $balance->allocated_days }}</td>
          <td class="num">{{ (int) $balance->available_days }}</td>
          <td class="num">{{ (int) $balance->pending_days }}</td>
          <td class="num">{{ (int) $balance->used_days }}</td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h3>No active leave types yet</h3></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
