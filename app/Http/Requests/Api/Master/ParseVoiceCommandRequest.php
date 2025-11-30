<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Master;

use Illuminate\Foundation\Http\FormRequest;

class ParseVoiceCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1'],
        ];
    }
}
