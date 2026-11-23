<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class SettingsPositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Department::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'edit' => ['sometimes', 'integer', 'min:1'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
        ];
    }
}
