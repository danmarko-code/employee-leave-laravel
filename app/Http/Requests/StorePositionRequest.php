<?php

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Position::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('positions', 'name')->where('department_id', $this->integer('department_id')),
            ],
        ];
    }
}
