<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Auth;

class MasterScheduleExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
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
