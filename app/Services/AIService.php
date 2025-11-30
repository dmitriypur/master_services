<?php

declare(strict_types=1);

namespace App\Services;

class AIService
{
    public function parseBookingData(string $text): array
    {
        $clientName = null;
        $time = null;
        $serviceName = null;
        $phone = null;
        $comment = null;

        // 1. Телефон (ищем последовательность цифр от 10 до 12 знаков, возможно с +)
        // Улучшенная регулярка: +7 999 123-45-67 или 89991234567
        if (preg_match('/(?:\+7|8)?[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}/', $text, $m)) {
            $phone = $m[0];
            // Удаляем телефон из текста, чтобы не мешал искать время (если там есть цифры)
            // $textWithoutPhone = str_replace($phone, '', $text); 
        }

        // 2. Время
        // Форматы: 14:30, 14-30, в 14, 14 00
        if (preg_match('/\b([01]?\d|2[0-3])[:\-\s]([0-5]\d)\b/u', $text, $m)) {
            $time = sprintf('%02d:%02d', $m[1], $m[2]);
        } elseif (preg_match('/\bв\s+([01]?\d|2[0-3])\b/u', $text, $m)) {
             // "в 15" -> 15:00
             $time = sprintf('%02d:00', $m[1]);
        }

        $lowerText = mb_strtolower($text);

        // 3. Услуга (простой поиск по ключевым словам)
        $servicesMap = [
            'маникюр' => 'Маникюр',
            'педикюр' => 'Педикюр',
            'стриж' => 'Стрижка',
            'брови' => 'Брови',
            'ресниц' => 'Ресницы',
            'окрашивание' => 'Окрашивание',
            'эпиляц' => 'Эпиляция',
            'массаж' => 'Массаж',
        ];

        foreach ($servicesMap as $key => $value) {
            if (str_contains($lowerText, $key)) {
                $serviceName = $value;
                break; 
            }
        }

        // 4. Имя
        // Эвристика: ищем слово с Большой буквы, которое не является началом предложения (не всегда работает)
        // и не является услугой или ключевым словом.
        // Проще: берем все слова с большой буквы, исключаем известные.
        
        $stopWords = [
            'Завтра', 'Сегодня', 'Вчера', 'В', 'На', 'С', 'Клиент', 'Телефон', 'Номер', 'Запись', 'Хочет', 'Нужно',
            'Запиши', 'Добавь', 'Поставь', 'Создай', 'Сделай', 'Зовут', 'Имя', 'Человек', 'Мужчина', 'Женщина', 'Девушка', 'Парень'
        ];
        // Добавим услуги в стоп-слова (с большой буквы)
        foreach ($servicesMap as $s) {
            $stopWords[] = $s; 
            $stopWords[] = mb_convert_case($s, MB_CASE_TITLE, "UTF-8");
        }

        // Разбиваем на слова
        $words = preg_split('/[\s,.;]+/', $text);
        
        // Попробуем найти имя после ключевых слов "Зовут", "Клиент", "Имя"
        $nameMarkers = ['зовут', 'клиент', 'имя', 'это'];
        foreach ($words as $index => $word) {
            if (in_array(mb_strtolower($word), $nameMarkers) && isset($words[$index + 1])) {
                $potentialName = $words[$index + 1];
                // Если следующее слово с большой буквы и не стоп-слово
                $firstChar = mb_substr($potentialName, 0, 1);
                if (mb_strtoupper($firstChar) === $firstChar && mb_strlen($potentialName) > 2) {
                     if (!in_array($potentialName, $stopWords)) {
                         $clientName = $potentialName;
                         break;
                     }
                }
            }
        }

        if (!$clientName) {
            foreach ($words as $index => $word) {
                if (empty($word)) continue;
                
                // Пропускаем первое слово в предложении, если это глагол повелительного наклонения (Запиши, Добавь)
                if ($index === 0 && in_array($word, ['Запиши', 'Добавь', 'Создай'])) continue;

                $firstChar = mb_substr($word, 0, 1);
                
                // Проверка на большую букву (кириллица или латиница)
                if (mb_strtoupper($firstChar) === $firstChar && mb_strtolower($firstChar) !== $firstChar) {
                    // Исключаем цифры
                    if (preg_match('/\d/', $word)) continue;
                    
                    // Исключаем стоп-слова
                    if (in_array($word, $stopWords) || in_array(mb_convert_case($word, MB_CASE_TITLE, "UTF-8"), $stopWords)) {
                        continue;
                    }
                    
                    // Если длина больше 2 букв - скорее всего имя
                    if (mb_strlen($word) > 2) {
                        $clientName = $word;
                        break; // Берем первое подходящее
                    }
                }
            }
        }
        
        // Если не нашли, пробуем просто взять первое слово, если оно не служебное (для случаев "светлана на 15:00")
        if (!$clientName && count($words) > 0) {
             $first = $words[0];
             if (mb_strlen($first) > 2 && !in_array(mb_strtolower($first), ['завтра', 'сегодня', 'записать', 'клиент'])) {
                 $clientName = mb_convert_case($first, MB_CASE_TITLE, "UTF-8");
             }
        }


        return [
            'client_name' => $clientName ?? '',
            'time' => $time ?? '',
            'service_name' => $serviceName ?? '',
            'phone' => $phone ?? '',
            'comment' => $comment ?? '',
        ];
    }
}
