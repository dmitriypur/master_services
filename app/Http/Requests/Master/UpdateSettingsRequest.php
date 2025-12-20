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
            'phone' => ['required', 'string', 'min:5', 'max:11', 'regex:/^\d+$/'],
            'work_days' => ['required', 'array', 'min:1', 'max:7'],
            'work_days.*' => ['integer', 'min:1', 'max:7'],
            'work_time_from' => ['required', 'date_format:H:i', 'before:work_time_to'],
            'work_time_to' => ['required', 'date_format:H:i', 'after:work_time_from'],
            'slot_duration_min' => ['nullable', 'integer', 'min:5', 'max:60'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['array'],
            'services.*.id' => ['required', 'integer', Rule::exists('services', 'id')],
            'services.*.price' => ['nullable', 'integer', 'min:0'],
            'services.*.duration' => ['required', 'integer', 'min:1', 'max:1440'],
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
            'work_days.max' => 'Не более 7 рабочих дней',
            'work_days.*.integer' => 'Неверный формат дня недели',
            'work_days.*.min' => 'Неверный день недели',
            'work_days.*.max' => 'Неверный день недели',
            'work_time_from.required' => 'Укажите время начала',
            'work_time_from.date_format' => 'Неверный формат времени начала',
            'work_time_from.before' => 'Время начала должно быть раньше времени окончания',
            'work_time_to.required' => 'Укажите время окончания',
            'work_time_to.date_format' => 'Неверный формат времени окончания',
            'work_time_to.after' => 'Время окончания должно быть позже времени начала',
            'services.required' => 'Выберите хотя бы одну услугу',
            'services.min' => 'Выберите хотя бы одну услугу',
            'services.array' => 'Список услуг в неверном формате',
            'services.*.array' => 'Некоторая услуга в неверном формате',
            'services.*.id.required' => 'Некоторая услуга не выбрана',
            'services.*.id.integer' => 'Некоторая услуга в неверном формате',
            'services.*.id.exists' => 'Некоторая услуга не найдена',
            'services.*.price.integer' => 'Цена должна быть числом',
            'services.*.price.min' => 'Цена не может быть отрицательной',
            'services.*.duration.required' => 'Укажите длительность услуги',
            'services.*.duration.integer' => 'Длительность должна быть числом',
            'services.*.duration.min' => 'Длительность должна быть больше 0',
            'services.*.duration.max' => 'Длительность слишком большая',
            'phone.required' => 'Укажите номер телефона',
            'phone.regex' => 'Телефон: только цифры, 5–11',
        ];
    }

    protected function prepareForValidation(): void
    {
        $from = $this->input('work_time_from');
        $to = $this->input('work_time_to');
        $days = $this->input('work_days');

        if (is_array($days)) {
            // Фильтруем массив от null и пустых значений, затем приводим к int
            $cleanDays = array_filter($days, fn ($v) => ! is_null($v) && $v !== '');
            $this->merge([
                'work_days' => array_map('intval', array_values($cleanDays)),
            ]);
        }

        $services = $this->input('services');
        if (is_array($services)) {
            $normalized = [];
            foreach ($services as $row) {
                if (is_array($row)) {
                    if (! array_key_exists('id', $row)) {
                        continue;
                    }
                    $normalized[] = [
                        'id' => (int) $row['id'],
                        'price' => array_key_exists('price', $row) && $row['price'] !== null ? (int) $row['price'] : null,
                        'duration' => array_key_exists('duration', $row) && $row['duration'] !== null ? (int) $row['duration'] : null,
                    ];
                } elseif (is_numeric($row)) {
                    $normalized[] = [
                        'id' => (int) $row,
                        'price' => null,
                        'duration' => null,
                    ];
                }
            }
            $this->merge([
                'services' => $normalized,
            ]);
        }

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
