<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'address' => ['required', 'string', 'max:255'],
            'work_days' => ['required', 'array', 'min:1', 'max:7'],
            'work_days.*' => ['string', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'work_time_from' => ['required', 'date_format:H:i', 'before:work_time_to'],
            'work_time_to' => ['required', 'date_format:H:i', 'after:work_time_from'],
            'slot_duration_min' => ['required', 'integer', Rule::in([15, 30, 60])],
            'services' => ['sometimes', 'array'],
            'services.*' => ['integer', Rule::exists('services', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'city_id.required' => 'Выберите город',
            'city_id.integer' => 'Неверный город',
            'city_id.exists' => 'Город не найден',
            'address.required' => 'Укажите адрес',
            'address.string' => 'Адрес должен быть строкой',
            'address.max' => 'Адрес слишком длинный',
            'work_days.required' => 'Выберите рабочие дни',
            'work_days.array' => 'Рабочие дни в неверном формате',
            'work_days.min' => 'Выберите хотя бы один рабочий день',
            'work_days.*.in' => 'Недопустимый день недели',
            'work_time_from.required' => 'Укажите время начала',
            'work_time_from.date_format' => 'Неверный формат времени начала',
            'work_time_from.before' => 'Время начала должно быть раньше времени окончания',
            'work_time_to.required' => 'Укажите время окончания',
            'work_time_to.date_format' => 'Неверный формат времени окончания',
            'work_time_to.after' => 'Время окончания должно быть позже времени начала',
            'slot_duration_min.required' => 'Укажите длительность слота',
            'slot_duration_min.integer' => 'Длительность слота должна быть числом',
            'slot_duration_min.in' => 'Допустимые значения: 15, 30, 60 минут',
            'services.array' => 'Список услуг в неверном формате',
            'services.*.integer' => 'Неверная услуга',
            'services.*.exists' => 'Некоторая услуга не найдена',
        ];
    }

    protected function prepareForValidation(): void
    {
        $from = $this->input('work_time_from');
        $to = $this->input('work_time_to');

        $this->merge([
            'work_time_from' => $this->normalizeTime($from),
            'work_time_to' => $this->normalizeTime($to),
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
