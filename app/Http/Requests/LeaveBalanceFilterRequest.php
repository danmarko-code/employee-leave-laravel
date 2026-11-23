<?php

namespace App\Http\Requests;

use App\Models\LeaveBalance;
use Illuminate\Foundation\Http\FormRequest;

class LeaveBalanceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', LeaveBalance::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'year' => ['nullable', 'integer', 'digits:4', 'min:2000', 'max:2100'],
        ];
    }
}
