<?php

namespace App\Http\Requests;

use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;

class AuditLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AuditLog::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->input('from');
            $to = $this->input('to');

            if ($from && $to && $from > $to) {
                $validator->errors()->add('from', 'The start date must be before the end date.');
            }
        });
    }
}
