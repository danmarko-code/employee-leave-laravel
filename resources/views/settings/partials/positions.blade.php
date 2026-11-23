<details class="add-panel" {{ $errors->any() ? 'open' : '' }}>
  <summary class="btn primary">Add position</summary>
  <div class="card">
    <form method="POST" action="{{ route('settings.positions.store') }}">
      @csrf
      <div class="field">
        <label for="add-position-department">Department</label>
        <select class="input" id="add-position-department" name="department_id" required>
          <option value="">Select a department</option>
          @foreach ($activeDepartments as $department)
            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
          @endforeach
        </select>
        @error('department_id') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="add-position-name">Name</label>
        <input class="input" id="add-position-name" name="name" value="{{ old('name') }}" required maxlength="120">
        @error('name') <div class="ferr">{{ $message }}</div> @enderror
      </div>
      <button type="submit" class="btn primary">Add position</button>
    </form>
  </div>
</details>

<form class="filter-bar" method="GET" action="{{ route('settings.positions') }}">
  <label for="position-department-filter">Department</label>
  <select class="input compact" id="position-department-filter" name="department_id">
    <option value="">All departments</option>
    @foreach ($activeDepartments as $department)
      <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn sm">Filter</button>
  @if ($departmentId)
    <a class="btn sm" href="{{ route('settings.positions') }}">Clear</a>
  @endif
</form>

<div class="tbl-wrap">
  <table class="tbl">
    <thead><tr><th>Department</th><th>Name</th><th class="num">People</th><th>Status</th><th></th></tr></thead>
    <tbody>
      @forelse ($positions as $position)
        @php($editing = (int) $editingId === $position->id)
        @php($cancelParams = $departmentId ? ['department_id' => $departmentId] : [])
        <tr class="{{ $position->is_active ? '' : 'off' }}">
          <td>{{ $position->department->name ?? '—' }}</td>
          @if ($editing)
            <td>
              <form id="position-edit-{{ $position->id }}" method="POST" action="{{ route('settings.positions.update', $position) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_active" value="0">
              </form>
              <input class="input compact" form="position-edit-{{ $position->id }}" name="name" value="{{ old('name', $position->name) }}" required maxlength="120">
              @error('name') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td class="num">{{ $position->users_count }}</td>
            <td>
              <label class="check compact-check"><input type="checkbox" form="position-edit-{{ $position->id }}" name="is_active" value="1" {{ old('is_active', $position->is_active ? '1' : '0') ? 'checked' : '' }}><span>Active</span></label>
              @error('is_active') <div class="ferr">{{ $message }}</div> @enderror
            </td>
            <td>
              <div class="row-actions">
                <button type="submit" form="position-edit-{{ $position->id }}" class="btn sm primary">Save</button>
                <a class="link" href="{{ route('settings.positions', $cancelParams) }}">Cancel</a>
              </div>
            </td>
          @else
            <td><b>{{ $position->name }}</b></td>
            <td class="num">{{ $position->users_count }}</td>
            <td><span class="pill {{ $position->is_active ? 'approved' : 'cancelled' }}"><i></i>{{ $position->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td><a class="link" href="{{ route('settings.positions', ['edit' => $position->id] + $cancelParams) }}">Edit</a></td>
          @endif
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h3>No positions yet</h3></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($positions->hasPages())
  <div class="pager">
    @if ($positions->onFirstPage()) <button class="btn" disabled>Previous</button> @else <a class="btn" href="{{ $positions->previousPageUrl() }}">Previous</a> @endif
    @if ($positions->hasMorePages()) <a class="btn" href="{{ $positions->nextPageUrl() }}">Next</a> @else <button class="btn" disabled>Next</button> @endif
  </div>
@endif
