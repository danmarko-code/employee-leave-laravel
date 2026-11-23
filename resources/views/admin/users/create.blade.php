@extends('layouts.app')

@section('title', 'Add employee | Leave system')

@section('content')
<div class="ph">
  <div><h1>Add employee</h1><p>A random temporary password will be created and shown once.</p></div>
  <a class="btn" href="{{ route('users') }}">← Back to employees</a>
</div>

@if ($errors->any())
  <div class="alert err" role="alert"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg><span>{{ $errors->first() }}</span></div>
@endif

<form method="POST" action="{{ route('admin.users.store') }}" class="card" style="max-width:660px;"
      data-user-form
      data-form-config="{{ json_encode($formConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) }}"
      data-department-id="{{ old('department_id') }}"
      data-position-id="{{ old('position_id') }}">
  @csrf
  <div class="field">
    <label for="name">Full name</label>
    <input class="input" id="name" name="name" value="{{ old('name') }}" required maxlength="120">
    @error('name') <div class="ferr">{{ $message }}</div> @enderror
  </div>
  <div class="field">
    <label for="email">Work email</label>
    <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="190">
    @error('email') <div class="ferr">{{ $message }}</div> @enderror
  </div>
  <div class="field">
    <label for="role_id">Role</label>
    <select class="input" id="role_id" name="role_id" required>
      @foreach ($roles as $role)
        <option value="{{ $role->id }}" {{ old('role_id', $employeeRoleId) == $role->id ? 'selected' : '' }}>{{ $role->display_name }}</option>
      @endforeach
    </select>
    @error('role_id') <div class="ferr">{{ $message }}</div> @enderror
  </div>
  <div class="field">
    <label for="department_id">Department</label>
    <select class="input" id="department_id" name="department_id" required>
      <option value="">Select a department</option>
      @foreach ($departments as $department)
        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
      @endforeach
    </select>
    @error('department_id') <div class="ferr">{{ $message }}</div> @enderror
    @if ($departments->isEmpty())
      <div class="hint">No departments yet. Add one before assigning a department.</div>
    @endif
  </div>
  <div class="field">
    <label for="position_id">Position</label>
    <select class="input" id="position_id" name="position_id" required disabled>
      <option value="">Select a department first</option>
    </select>
    @error('position_id') <div class="ferr">{{ $message }}</div> @enderror
    @if ($departments->isEmpty())
      <div class="hint">No positions yet. Add a position before assigning one.</div>
    @endif
  </div>
  <div class="field">
    <label for="phone">Phone number</label>
    <input class="input" id="phone" name="phone" value="{{ old('phone') }}" maxlength="20">
    @error('phone') <div class="ferr">{{ $message }}</div> @enderror
  </div>
  <label class="check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}><span>Account is active</span></label>
  <div style="margin-top:24px;display:flex;gap:10px;">
    <button type="submit" class="btn primary">Create account</button>
    <a class="btn" href="{{ route('users') }}">Cancel</a>
  </div>
</form>
@endsection
