<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department instanceof Department
            && ($this->user()?->can('update', $department) ?? false);
    }

    public function rules(): array
    {
        /** @var Department $department */
        $department = $this->route('department');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Department $department */
            $department = $this->route('department');

            if ($this->has('is_active') && ! $this->boolean('is_active')) {
                if ($department->users()->exists() || $department->positions()->where('is_active', true)->exists()) {
                    $validator->errors()->add('is_active', 'Reassign users and positions before deactivating this department.');
                }
            }
        });
    }
}
