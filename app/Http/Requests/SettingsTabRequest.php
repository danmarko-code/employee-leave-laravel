<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class SettingsTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Department::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'edit' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
