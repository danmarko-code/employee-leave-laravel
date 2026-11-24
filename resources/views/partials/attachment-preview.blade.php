@php
  $emptyLabel = $emptyLabel ?? 'No document attached';
  $previewUrl = route('requests.attachment.preview', $leaveRequest);
  $downloadUrl = route('requests.attachment', $leaveRequest);
  $isImage = in_array($attachmentMime, ['image/jpeg', 'image/png'], true);
  $isPdf = $attachmentMime === 'application/pdf';
@endphp

@if ($leaveRequest->attachment_path && $isImage)
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <button type="button" class="link" data-attachment-preview data-preview-url="{{ $previewUrl }}" data-preview-name="{{ $attachmentName }}" aria-label="Preview {{ $attachmentName }}">
      <img src="{{ $previewUrl }}" alt="{{ $attachmentName }}" style="max-height:120px;display:block;border-radius:var(--r-s);">
    </button>
    <a class="link" href="{{ $downloadUrl }}">Download {{ $attachmentName }}</a>
  </div>
@elseif ($leaveRequest->attachment_path && $isPdf)
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <a class="link" href="{{ $previewUrl }}" target="_blank" rel="noopener">View {{ $attachmentName }}</a>
    <a class="link" href="{{ $downloadUrl }}">Download {{ $attachmentName }}</a>
  </div>
@elseif ($leaveRequest->attachment_path)
  <a class="link" href="{{ $downloadUrl }}">Download {{ $attachmentName }}</a>
@else
  <span class="muted">{{ $emptyLabel }}</span>
@endif
