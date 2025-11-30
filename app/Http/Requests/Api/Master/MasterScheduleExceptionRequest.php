<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterScheduleExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'master_id' => ['required', 'integer', Rule::in([$userId])],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', Rule::in(['override', 'break', 'day_off'])],
            'start_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:type,override',
                'required_if:type,break',
                'prohibited_if:type,day_off',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:type,override',
                'required_if:type,break',
                'prohibited_if:type,day_off',
                'after:start_time',
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
