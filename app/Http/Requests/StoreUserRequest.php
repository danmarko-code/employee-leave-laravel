<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesUserAssignment;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use ValidatesUserAssignment;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->whereIn('name', ['employee', 'admin']),
            ],
        ], $this->userAssignmentRules(), [
            'phone' => ['nullable', 'string', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'is_active' => ['boolean'],
        ]);
    }

    public function messages(): array
    {
        return $this->userAssignmentMessages();
    }
}
