<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;

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
            'days_allowed' => ['required', 'integer', 'min:1', 'max:365'],
            'is_paid' => ['boolean'],
            'requires_doc' => ['boolean'],
            'is_active' => ['boolean'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
