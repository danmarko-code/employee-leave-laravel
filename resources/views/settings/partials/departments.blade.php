<details class="add-panel" {{ $errors->any() ? 'open' : '' }}>
  <summary class="btn primary">Add department</summary>
  <div class="card">
    <form method="POST" action="{{ route('settings.departments.store') }}">
      @csrf
      <div class="field">
        <label for="add-department-name">Name</label>
        <input class="input" id="add-department-name" name="name" value="{{ old('name') }}" required maxlength="100">
        @error('name') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="add-department-code">Code</label>
        <input class="input" id="add-department-code" name="code" value="{{ old('code') }}" maxlength="20">
        @error('code') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <button type="submit" class="btn primary">Add department</button>
    </form>
  </div>
</details>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Name</th><th>Code</th><th class="num">People</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($departments as $department)
        @php($editing = (int) $editingId === $department->id)
        <tr class="{{ $department->is_active ? '' : 'off' }}">
          @if ($editing)
            <td>
              <form id="department-edit-{{ $department->id }}" method="POST" action="{{ route('settings.departments.update', $department) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_active" value="0">
              </form>
              <input class="input compact" form="department-edit-{{ $department->id }}" name="name" value="{{ old('name', $department->name) }}" required maxlength="100">
              @error('name') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td>
              <input class="input compact" form="department-edit-{{ $department->id }}" name="code" value="{{ old('code', $department->code) }}" maxlength="20">
              @error('code') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td class="num">{{ $department->users_count }}</td>
            <td>
              <label class="check compact-check"><input type="checkbox" form="department-edit-{{ $department->id }}" name="is_active" value="1" {{ old('is_active', $department->is_active ? '1' : '0') ? 'checked' : '' }}><span>Active</span></label>
              @error('is_active') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td>
              <div class="row-actions">
                <button type="submit" form="department-edit-{{ $department->id }}" class="btn sm primary">Save</button>
                <a class="link" href="{{ route('settings.departments') }}">Cancel</a>
              </div>
            </td>
          @else
            <td><b>{{ $department->name }}</b></td>
            <td>{{ $department->code ?: '—' }}</td>
            <td class="num">{{ $department->users_count }}</td>
            <td><span class="pill {{ $department->is_active ? 'approved' : 'cancelled' }}"><i></i>{{ $department->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td><a class="link" href="{{ route('settings.departments', ['edit' => $department->id]) }}">Edit</a></td>
          @endif
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h3>No departments yet</h3></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($departments->hasPages())
  <div class="pager">
    @if ($departments->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $departments->previousPageUrl() }}">Previous</a> @endif
    @if ($departments->hasMorePages()) <a class="btn" href="{{ $departments->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
