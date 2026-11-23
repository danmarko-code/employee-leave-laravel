@extends('layouts.app')

@section('title', 'Edit balance | Leave system')

@section('content')
<div class="ph">
  <div><h1>Edit leave balance</h1><p>{{ $leaveBalance->user->name }} · {{ $leaveBalance->leaveType->name }} · {{ $leaveBalance->year }}</p></div>
  <a class="btn" href="{{ route('admin.balances.index') }}">← Back to balances</a>
</div>

@if ($errors->any())
  <div class="alert err" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg><span>{{ $errors->first() }}</span></div>
@endif

<form method="POST" action="{{ route('admin.balances.update', $leaveBalance) }}" class="card" style="max-width:520px;">
  @csrf
  @method('PUT')
  <div class="field">
    <label for="allocated_days">Allocated days</label>
    <input class="input" id="allocated_days" name="allocated_days" type="number" step="0.1" min="0" max="3650" value="{{ old('allocated_days', $leaveBalance->allocated_days) }}" required>
    <div class="ferr">@error('allocated_days'){{ $message }}@enderror</div>
  </div>
  <p class="muted">Currently {{ $leaveBalance->used_days }} used and {{ $leaveBalance->pending_days }} pending days.</p>
  <div style="margin-top:24px;display:flex;gap:10px;"><button type="submit" class="btn primary">Save balance</button><a class="btn" href="{{ route('admin.balances.index') }}">Cancel</a></div>
</form>
@endsection
