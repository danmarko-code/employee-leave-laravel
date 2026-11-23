<details class="add-panel" {{ $errors->any() ? 'open' : '' }}>
  <summary class="btn primary">Add leave type</summary>
  <div class="card">
    <form method="POST" action="{{ route('settings.leave-types.store') }}">
      @csrf
      <div class="field">
        <label for="add-type-name">Name</label>
        <input class="input" id="add-type-name" name="name" value="{{ old('name') }}" required maxlength="100">
        @error('name') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="add-type-code">Code</label>
        <input class="input" id="add-type-code" name="code" value="{{ old('code') }}" required maxlength="30">
        @error('code') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="add-type-days">Yearly entitlement (days)</label>
        <input class="input" id="add-type-days" name="days_allowed" type="number" value="{{ old('days_allowed', 15) }}" min="1" max="365" required>
        @error('days_allowed') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="add-type-color">Colour</label>
        <input class="input" id="add-type-color" name="color" value="{{ old('color', '#0F8B8D') }}" maxlength="20">
        @error('color') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <label class="check"><input type="hidden" name="is_paid" value="0"><input type="checkbox" name="is_paid" value="1" {{ old('is_paid', '1') == '1' ? 'checked' : '' }}><span>Paid leave</span></label>
      <label class="check"><input type="hidden" name="requires_doc" value="0"><input type="checkbox" name="requires_doc" value="1" {{ old('requires_doc', '0') == '1' ? 'checked' : '' }}><span>Requires a document</span></label>
      <label class="check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}><span>Active</span></label>
      <button type="submit" class="btn primary">Add leave type</button>
    </form>
  </div>
</details>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Leave type</th><th class="num">Entitlement</th><th>Paid</th><th>Needs document</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($leaveTypes as $type)
        @php($editing = (int) $editingId === $type->id)
        <tr class="{{ $type->is_active ? '' : 'off' }}">
          @if ($editing)
            <td>
              <form id="type-edit-{{ $type->id }}" method="POST" action="{{ route('settings.leave-types.update', $type) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_paid" value="0">
                <input type="hidden" name="requires_doc" value="0">
                <input type="hidden" name="is_active" value="0">
              </form>
              <input class="input compact" form="type-edit-{{ $type->id }}" name="name" value="{{ old('name', $type->name) }}" required maxlength="100">
              @error('name') <div class="ferr">{{ $message }}</div> @enderror
              <input class="input compact" form="type-edit-{{ $type->id }}" name="code" value="{{ old('code', $type->code) }}" required maxlength="30">
              @error('code') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td>
              <input class="input compact" form="type-edit-{{ $type->id }}" name="days_allowed" type="number" value="{{ old('days_allowed', $type->days_allowed) }}" min="1" max="365" required>
              @error('days_allowed') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td><input type="checkbox" form="type-edit-{{ $type->id }}" name="is_paid" value="1" {{ old('is_paid', $type->is_paid ? '1' : '0') == '1' ? 'checked' : '' }}></td>
            <td><input type="checkbox" form="type-edit-{{ $type->id }}" name="requires_doc" value="1" {{ old('requires_doc', $type->requires_doc ? '1' : '0') == '1' ? 'checked' : '' }}></td>
            <td>
              <label class="check compact-check"><input type="checkbox" form="type-edit-{{ $type->id }}" name="is_active" value="1" {{ old('is_active', $type->is_active ? '1' : '0') == '1' ? 'checked' : '' }}><span>Active</span></label>
              @error('is_active') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td>
              <div class="row-actions">
                <button type="submit" form="type-edit-{{ $type->id }}" class="btn sm primary">Save</button>
                <a class="link" href="{{ route('settings.leave-types') }}">Cancel</a>
              </div>
            </td>
          @else
            <td><b>{{ $type->name }}</b><br><span class="muted">{{ $type->code }}</span><br><span class="muted">{{ $type->color }}</span></td>
            <td class="num">{{ $type->days_allowed }}</td>
            <td>{{ $type->is_paid ? 'Yes' : 'No' }}</td>
            <td>{{ $type->requires_doc ? 'Yes' : 'No' }}</td>
            <td>
              <form method="POST" action="{{ route('settings.leave-types.toggle', $type) }}">
                @csrf
                <input type="hidden" name="is_active" value="{{ $type->is_active ? 0 : 1 }}">
                <button type="submit" class="btn sm">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</button>
              </form>
            </td>
            <td>
              <div class="row-actions">
                <a class="link" href="{{ route('settings.leave-types', ['edit' => $type->id]) }}">Edit</a>
                <form method="POST" action="{{ route('settings.leave-types.destroy', $type) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="link">Delete</button>
                </form>
              </div>
            </td>
          @endif
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty"><h3>No leave types yet</h3></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($leaveTypes->hasPages())
  <div class="pager">
    @if ($leaveTypes->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $leaveTypes->previousPageUrl() }}">Previous</a> @endif
    @if ($leaveTypes->hasMorePages()) <a class="btn" href="{{ $leaveTypes->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
