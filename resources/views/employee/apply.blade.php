@extends('layouts.app')

@section('title', 'Apply for leave | Leave system')

@section('content')
<div class="ph">
  <div>
    <h1>Apply for leave</h1>
    <p>Your request goes to the administrator for review.</p>
  </div>
</div>

@if ($errors->any())
  <div class="alert info" role="alert" style="background:var(--bad-bg);color:var(--bad);border:1px solid rgba(179,38,30,.25);margin-bottom:20px;">
    <svg class="ic" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--bad)"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
    <span>{{ $errors->first() }}</span>
  </div>
@endif

<div class="apply-grid">
  <form class="card" id="applyForm" method="POST" action="{{ route('apply.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="field {{ $errors->has('leave_type_id') ? 'err' : '' }}">
      <label for="f-type">Leave type</label>
      <div class="inp">
        <select class="input" id="f-type" name="leave_type_id" required>
          <option value="" disabled {{ old('leave_type_id') ? '' : 'selected' }}>Select a leave type</option>
          @foreach ($leaveTypes as $type)
            @php
              $remaining = (int) $type->available_days;
              $isAdminGrantZero = $type->grant_mode === 'admin' && (float) $type->available_days <= 0;
            @endphp
            <option value="{{ $type->id }}"
                    data-available="{{ $type->available_days }}"
                    data-reqdoc="{{ $type->requires_doc ? '1' : '0' }}"
                    {{ $isAdminGrantZero ? 'disabled' : '' }}
                    {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
              @if ($isAdminGrantZero)
                {{ $type->name }} (0 {{ Str::plural('day', 0) }} remaining — Ask HR to grant this leave)
              @else
                {{ $type->name }} ({{ $remaining }} {{ Str::plural('day', $remaining) }} remaining)
              @endif
            </option>
          @endforeach
        </select>
      </div>
      <div class="ferr">@error('leave_type_id'){{ $message }}@enderror</div>
    </div>

    <div class="row2">
      <div class="field {{ $errors->has('start_date') ? 'err' : '' }}">
        <label for="f-start">Start date</label>
        <div class="inp">
          <input class="input" type="date" id="f-start" name="start_date" min="{{ date('Y-m-d') }}" value="{{ old('start_date', date('Y-m-d', strtotime('+1 weekday'))) }}" required>
        </div>
        <div class="ferr">@error('start_date'){{ $message }}@enderror</div>
      </div>

      <div class="field {{ $errors->has('end_date') ? 'err' : '' }}">
        <label for="f-end">End date</label>
        <div class="inp">
          <input class="input" type="date" id="f-end" name="end_date" min="{{ date('Y-m-d') }}" value="{{ old('end_date', date('Y-m-d', strtotime('+1 weekday'))) }}" required>
        </div>
        <div class="ferr" id="endDateErr">@error('end_date'){{ $message }}@enderror</div>
      </div>
    </div>

    <div class="chips">
      <span>Quick pick</span>
      <button type="button" class="chip" data-days="1">1 day</button>
      <button type="button" class="chip" data-days="2">2 days</button>
      <button type="button" class="chip" data-days="3">3 days</button>
      <button type="button" class="chip" data-days="5">1 week</button>
    </div>

    <div class="field {{ $errors->has('reason') ? 'err' : '' }}">
      <label for="f-reason">Reason</label>
      <textarea class="input" id="f-reason" name="reason" rows="3" placeholder="Add any details your reviewer should know" required>{{ old('reason') }}</textarea>
      <div class="ferr">@error('reason'){{ $message }}@enderror</div>
    </div>

    <div class="field {{ $errors->has('attachment') ? 'err' : '' }}">
      <label for="f-file">Supporting document <span class="muted" id="docReqText">(optional, PDF, JPG, PNG up to 5MB)</span></label>
      <div class="inp" style="padding: 6px 12px; height: auto;">
        <input type="file" id="f-file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
      </div>
      <div class="ferr">@error('attachment'){{ $message }}@enderror</div>
    </div>

    <button class="btn primary lg block" id="submitBtn" type="submit">
      <span class="btn-t">Submit application <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
      <span class="spin"></span>
    </button>
  </form>

  <aside class="card summary" id="applySummary">
    <h3>Summary</h3>
    <div class="sum-days" id="sumDays">0 working days</div>
    <div class="bar"><i id="sumBar" style="width: 0%;"></i></div>
    <div class="sum-rows">
      <div class="row"><span>Available before</span><strong id="sumBefore">–</strong></div>
      <div class="row"><span>After this request</span><strong id="sumAfter">–</strong></div>
      <div class="row"><span>Reviewed by</span><strong>Administrator</strong></div>
    </div>
  </aside>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const typeSelect = document.getElementById('f-type');
  const startInput = document.getElementById('f-start');
  const endInput = document.getElementById('f-end');
  const submitBtn = document.getElementById('submitBtn');
  const endDateErr = document.getElementById('endDateErr');
  const docText = document.getElementById('docReqText');
  const sumDays = document.getElementById('sumDays');
  const sumBefore = document.getElementById('sumBefore');
  const sumAfter = document.getElementById('sumAfter');
  const sumBar = document.getElementById('sumBar');
  let serverEndError = endDateErr ? endDateErr.textContent.trim() : '';

  function setNeutralSummary() {
    sumDays.textContent = '0 working days';
    sumBefore.textContent = '–';
    sumAfter.textContent = '–';
    sumBar.style.width = '0%';
    sumBar.style.background = '';
  }

  function setDateError(message) {
    if (endDateErr) endDateErr.textContent = message;
  }

  function clearClientDateError() {
    serverEndError = '';
    setDateError('');
  }

  function updateSummary() {
    const opt = typeSelect.selectedOptions[0];
    const avail = opt && opt.value ? parseFloat(opt.dataset.available || 0) : null;
    const reqDoc = opt && opt.dataset.reqdoc === '1';

    if (docText) {
      docText.textContent = reqDoc ? '(REQUIRED for this leave type, up to 5MB)' : '(optional, PDF, JPG, PNG up to 5MB)';
      docText.style.color = reqDoc ? 'var(--warn)' : 'var(--muted)';
    }

    const sVal = startInput.value;
    const eVal = endInput.value;

    if (sVal && eVal && eVal < sVal) {
      setDateError('The end date must be on or after the start date.');
      setNeutralSummary();
      if (submitBtn) submitBtn.disabled = true;
      return;
    }

    setDateError(serverEndError);
    if (submitBtn) submitBtn.disabled = false;

    if (!sVal || !eVal) {
      setNeutralSummary();
      return;
    }

    const days = countLocalWorkingDays(sVal, eVal);
    sumDays.textContent = days + (days === 1 ? ' working day' : ' working days');

    if (avail !== null) {
      sumBefore.textContent = avail + (avail === 1 ? ' day' : ' days');
      const rem = Math.max(0, avail - days);
      sumAfter.textContent = rem + (rem === 1 ? ' day' : ' days');
      const pct = avail > 0 ? Math.min(100, Math.round((days / avail) * 100)) : 0;
      sumBar.style.width = pct + '%';
      sumBar.style.background = days > avail ? 'var(--bad)' : 'var(--lagoon)';
    } else {
      sumBefore.textContent = '–';
      sumAfter.textContent = '–';
      sumBar.style.width = '0%';
      sumBar.style.background = '';
    }
  }

  typeSelect.addEventListener('change', updateSummary);
  startInput.addEventListener('change', () => {
    clearClientDateError();
    updateSummary();
  });
  endInput.addEventListener('change', () => {
    clearClientDateError();
    updateSummary();
  });

  // Quick pick chips
  document.querySelectorAll('.chip[data-days]').forEach(btn => {
    btn.addEventListener('click', () => {
      const days = parseInt(btn.dataset.days, 10);
      let sVal = startInput.value;

      if (!sVal) {
        sVal = formatLocalDate(new Date());
        startInput.value = sVal;
      }

      clearClientDateError();
      endInput.value = formatLocalDate(endAfterLocal(sVal, days));
      updateSummary();
    });
  });

  updateSummary();
});
</script>
@endpush
@endsection
