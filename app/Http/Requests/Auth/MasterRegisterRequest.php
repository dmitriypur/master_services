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
        ];
    }
}
