<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'telegram_id' => ['nullable', 'integer'],
            'whatsapp_phone' => ['nullable', 'string', 'max:255'],
            'preferred_channels' => ['nullable', 'array'],
        ];
    }
}
