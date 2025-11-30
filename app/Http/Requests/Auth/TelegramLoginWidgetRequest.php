<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TelegramLoginWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string'],
            'auth_date' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ];
    }
}
