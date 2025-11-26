<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
        ];
    }
}
