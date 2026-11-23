<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leaveType = $this->route('leaveType');

        return $leaveType instanceof LeaveType && ($this->user()?->can('update', $leaveType) ?? false);
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
