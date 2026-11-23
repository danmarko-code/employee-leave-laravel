<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leaveType = $this->route('leaveType');

        return $leaveType instanceof LeaveType && ($this->user()?->can('update', $leaveType) ?? false);
    }

    public function rules(): array
    {
        /** @var LeaveType $leaveType */
        $leaveType = $this->route('leaveType');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->ignore($leaveType->id)],
            'code' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/', Rule::unique('leave_types', 'code')->ignore($leaveType->id)],
            'days_allowed' => ['required', 'integer', 'min:1', 'max:365'],
            'is_paid' => ['boolean'],
            'requires_doc' => ['boolean'],
            'is_active' => ['boolean'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
