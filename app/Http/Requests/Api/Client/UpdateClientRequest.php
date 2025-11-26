<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telegram_id' => ['sometimes', 'nullable', 'integer'],
            'whatsapp_phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'preferred_channels' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
