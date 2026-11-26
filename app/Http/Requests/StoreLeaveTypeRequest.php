<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LeaveType::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/', 'unique:leave_types,code'],
            'days_allowed' => ['required', 'integer', 'min:0', 'max:365'],
            'day_basis' => ['sometimes', 'string', Rule::in(['working', 'calendar'])],
            'grant_mode' => ['sometimes', 'string', Rule::in(['automatic', 'admin'])],
            'is_paid' => ['boolean'],
            'requires_doc' => ['boolean'],
            'is_active' => ['boolean'],
            'is_statutory' => ['boolean'],
            'legal_basis' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
