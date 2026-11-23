<?php

namespace App\Http\Requests;

use App\Support\Report;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', Report::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'status' => ['nullable', 'string', 'in:all,pending,approved,rejected,cancelled'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->query('status') === '') {
            $this->merge(['status' => null]);
        }
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
