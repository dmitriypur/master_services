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
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['integer', Rule::exists('services', 'id')],
            'initData' => ['required', 'string'],
        ];
    }
}

