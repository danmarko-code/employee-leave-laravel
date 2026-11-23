<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesUserAssignment;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use ValidatesUserAssignment;

    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && ($this->user()?->can('update', $target) ?? false);
    }

    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return array_merge([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($target->id)],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->whereIn('name', ['employee', 'admin']),
            ],
        ], $this->userAssignmentRules(), [
            'phone' => ['nullable', 'string', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        return $this->userAssignmentMessages();
    }

    /**
     * Protect the final active administrator from being demoted or deactivated.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $target = $this->route('user');

            if (! $target instanceof User || ! AdminGuard::isLastAdmin($target)) {
                return;
            }

            $newRole = Role::find($this->input('role_id'));

            if ($newRole && $newRole->name !== 'admin') {
                $validator->errors()->add('role_id', 'The last administrator cannot be demoted.');
            }

            if ($this->has('is_active') && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'The last administrator cannot be deactivated.');
            }
        });
    }
}
