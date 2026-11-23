<?php

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position
            && ($this->user()?->can('update', $position) ?? false);
    }

    public function rules(): array
    {
        /** @var Position $position */
        $position = $this->route('position');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('positions', 'name')
                    ->where('department_id', $position->department_id)
                    ->ignore($position->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Position $position */
            $position = $this->route('position');

            if ($this->has('is_active') && ! $this->boolean('is_active') && $position->users()->exists()) {
                $validator->errors()->add('is_active', 'Reassign users before deactivating this position.');
            }
        });
    }
}
