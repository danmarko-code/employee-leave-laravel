<?php

namespace App\Http\Requests;

use App\Models\LeaveBalance;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $balance = $this->route('leaveBalance');

        return $balance instanceof LeaveBalance && ($this->user()?->can('update', $balance) ?? false);
    }

    public function rules(): array
    {
        return [
            'allocated_days' => ['required', 'numeric', 'min:0', 'max:3650'],
        ];
    }
}
