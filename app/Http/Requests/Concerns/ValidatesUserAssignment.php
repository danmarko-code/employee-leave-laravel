<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesUserAssignment
{
    /**
     * Shared rules for department and position assignment.
     */
    protected function userAssignmentRules(): array
    {
        return [
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where(fn ($query) => $query
                    ->where('department_id', $this->integer('department_id'))
                    ->where('is_active', true)),
            ],
        ];
    }

    protected function userAssignmentMessages(): array
    {
        return [
            'department_id.required' => 'Select a department.',
            'position_id.required' => 'Select a position.',
        ];
    }
}
