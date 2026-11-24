<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
{
    /**
     * Only an administrator may approve, never their own request.
     */
    public function authorize(): bool
    {
        $leaveRequest = $this->route('leaveRequest');
        $user = $this->user();

        if (! $user || ! $leaveRequest instanceof LeaveRequest) {
            return false;
        }

        return $user->can('approve', $leaveRequest);
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
