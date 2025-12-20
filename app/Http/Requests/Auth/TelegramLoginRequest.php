<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TelegramLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required'],
            'auth_date' => ['required'],
            'hash' => ['required', 'string'],
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'username' => ['sometimes', 'string'],
            'photo_url' => ['sometimes', 'string'],
        ];
    }
}
