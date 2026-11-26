@php
  $ringR = 42;
  $ringC = 2 * pi() * $ringR;
  $total = max((float) ($allocated ?? 0), 0);
  $avail = max((float) ($avail ?? 0), 0);
  $pending = max((float) ($pending ?? 0), 0);
  $used = max((float) ($used ?? 0), 0);
  $stretched = ! empty($stretched ?? false);

  $lenAvail = $total > 0 ? ($ringC * $avail) / $total : 0;
  $lenPending = $total > 0 ? ($ringC * $pending) / $total : 0;
  $lenUsed = $total > 0 ? ($ringC * $used) / $total : 0;
@endphp

@if (isset($href))
  <a class="card bal{{ $stretched ? ' stretched' : '' }}" href="{{ $href }}"@if (isset($ariaLabel)) aria-label="{{ $ariaLabel }}"@endif>
@else
  <article class="card bal{{ $stretched ? ' stretched' : '' }}"@if (isset($ariaLabel)) aria-label="{{ $ariaLabel }}"@endif>
@endif
  <div class="bal-ring-slot">
    <div class="ring-wrap">
      <svg class="ring" viewBox="0 0 100 100" aria-hidden="true">
        <circle class="track" cx="50" cy="50" r="{{ $ringR }}" stroke-dasharray="{{ $ringC }}"></circle>
        @if ($avail > 0)
          <circle class="arc" cx="50" cy="50" r="{{ $ringR }}" stroke="var(--lagoon)" data-len="{{ round($lenAvail, 2) }}" stroke-dasharray="{{ round($lenAvail, 2) }} {{ round($ringC - $lenAvail + 0.5, 2) }}" stroke-dashoffset="0"></circle>
        @endif
        @if ($pending > 0)
          <circle class="arc" cx="50" cy="50" r="{{ $ringR }}" stroke="var(--sun)" data-len="{{ round($lenPending, 2) }}" stroke-dasharray="{{ round($lenPending, 2) }} {{ round($ringC - $lenPending + 0.5, 2) }}" stroke-dashoffset="{{ round(-$lenAvail, 2) }}"></circle>
        @endif
        @if ($used > 0)
          <circle class="arc" cx="50" cy="50" r="{{ $ringR }}" stroke="#b8c5ba" data-len="{{ round($lenUsed, 2) }}" stroke-dasharray="{{ round($lenUsed, 2) }} {{ round($ringC - $lenUsed + 0.5, 2) }}" stroke-dashoffset="{{ round(-($lenAvail + $lenPending), 2) }}"></circle>
        @endif
      </svg>
      <div class="ring-c">
        <strong data-count="{{ (int)$avail }}">{{ (int)$avail }}</strong>
        <span>days left</span>
      </div>
    </div>
  </div>
  <div class="bal-body">
    <div class="bal-head">
      <h3>{{ $name }}</h3>
      <div class="of">{{ (int)$allocated }} {{ $allocatedLabel ?? 'days this year' }}</div>
    </div>
    <div class="legend">
      @if ($stretched)
        <div><i class="available"></i><span>Available</span><b>{{ (int)$avail }}</b></div>
        <div><i class="pending"></i><span>Pending</span><b>{{ (int)$pending }}</b></div>
        <div><i class="used"></i><span>Used</span><b>{{ (int)$used }}</b></div>
      @else
        <div><i class="available"></i>Available<b>{{ (int)$avail }}</b></div>
        <div><i class="pending"></i>Pending<b>{{ (int)$pending }}</b></div>
        <div><i class="used"></i>Used<b>{{ (int)$used }}</b></div>
      @endif
    </div>
  </div>
@if (isset($href))
  </a>
@else
  </article>
@endif
