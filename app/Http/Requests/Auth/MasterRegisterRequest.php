<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', Rule::exists('services', 'id')],
            'phone' => ['nullable', 'string', 'min:5', 'max:11', 'regex:/^\d+$/'],
            'initData' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
            'telegram_user' => ['nullable', 'array'],
            'address' => ['nullable', 'string', 'max:255'],
            'work_days' => ['nullable', 'array'],
            'work_days.*' => ['integer', 'min:1', 'max:7'],
            'work_time_from' => ['nullable', 'date_format:H:i'],
            'work_time_to' => ['nullable', 'date_format:H:i'],
            'slot_duration_min' => ['nullable', 'integer', 'min:15', 'max:240'],
        ];
    }
}
