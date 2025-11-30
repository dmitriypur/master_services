<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointments;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        // Если пользователь не авторизован или не мастер, master_id обязателен
        $requiresMaster = $user === null || $user->role !== 'master';

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where(function ($q) {
                $userId = $this->user()?->id;
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            })],
            'client_name' => ['required_without:client_id', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'min:5', 'max:11', 'regex:/^\d+$/'],
            'preferred_channels' => ['nullable', 'array'],
            'preferred_channels.*' => ['string', Rule::in(['phone', 'telegram', 'whatsapp'])],
            'master_id' => $requiresMaster
                ? ['required', 'integer', Rule::exists('users', 'id')->where('role', 'master')]
                : ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'master')],
            'source' => ['nullable', Rule::in(['manual', 'client'])],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Укажите дату',
            'date.date_format' => 'Неверный формат даты (Y-m-d)',
            'time.required' => 'Укажите время',
            'time.date_format' => 'Неверный формат времени (H:i)',
            'service_id.required' => 'Выберите услугу',
            'service_id.integer' => 'Неверная услуга',
            'service_id.exists' => 'Услуга не найдена',
            'client_id.integer' => 'Неверный клиент',
            'client_id.exists' => 'Клиент не найден',
            'client_name.required_without' => 'Укажите имя клиента',
            'client_phone.min' => 'Телефон: минимум 5 цифр',
            'client_phone.max' => 'Телефон: максимум 11 цифр',
            'client_phone.regex' => 'Телефон должен содержать только цифры',
            'preferred_channels.array' => 'Неверный формат каналов связи',
            'preferred_channels.*.in' => 'Недопустимый канал связи',
            'master_id.required' => 'Мастер не определён',
            'master_id.integer' => 'Неверный мастер',
            'master_id.exists' => 'Мастер не найден',
            'source.in' => 'Недопустимый источник',
        ];
    }

    protected function prepareForValidation(): void
    {
        $time = $this->input('time');

        $this->merge([
            'time' => $this->normalizeTime($time),
        ]);
    }

    private function normalizeTime($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $value)) {
            return substr($value, 0, 5);
        }
        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return is_string($value) ? $value : null;
        }
    }
}
