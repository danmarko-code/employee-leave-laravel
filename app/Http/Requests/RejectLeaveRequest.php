<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RejectLeaveRequest extends FormRequest
{
    /**
     * Only an administrator may reject, never their own request.
     */
    public function authorize(): bool
    {
        $leaveRequest = $this->route('leaveRequest');
        $user = $this->user();

        if (! $user || ! $leaveRequest instanceof LeaveRequest) {
            return false;
        }

        if ((int) $user->id === (int) $leaveRequest->user_id) {
            throw ValidationException::withMessages([
                'request' => 'You cannot reject your own request. Ask another administrator to review it.',
            ]);
        }

        return $user->can('reject', $leaveRequest);
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Add a remark so the employee knows why the request was rejected.',
        ];
    }
}
