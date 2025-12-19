<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Master;

use Illuminate\Foundation\Http\FormRequest;

class MasterCrmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'private_notes' => ['required', 'string', 'min:1'],
        ];
    }
}
