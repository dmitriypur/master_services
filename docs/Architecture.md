# Архитектура

- Стек: Laravel 12 (PHP 8.3+), Inertia.js + Vue 3, Filament 3, PostgreSQL, Redis, Docker, Telegram Bot/WebApp, REST API
- Принципы: тонкие контроллеры → Actions/Services → JsonResource, валидация — FormRequest
- Слои:
  - Фронтенд: страницы Inertia `resources/js/Pages/*` (мастер/клиент, авторизация через WebApp)
  - Бэкенд: контроллеры/экшены/сервисы/ресурсы/валидации `app/*`
  - Очереди/команды: `app/Jobs/*`, `app/Console/Commands/*` с Redis
  - Интеграции: `app/Services/Telegram/*`
  - База: миграции `database/migrations/*`, модели `app/Models/*`

- Входные точки:
  - Мастер: WebApp авторизация (подпись initData), календарь/настройки
    - `app/Http/Controllers/Auth/AuthTelegramController.php:17`
    - `app/Http/Controllers/Master/CalendarController.php:9`
  - Клиент: бронирование/календарь мастера
    - `app/Http/Controllers/Client/BookingController.php:9`
    - `app/Http/Controllers/Client/MasterCalendarController.php:9`

- Оповещения:
  - Джобы/команды для напоминаний (мастер/клиент) через Telegram
    - `app/Console/Commands/SendMasterReminders.php:1`
    - `app/Console/Commands/SendClientReminders.php:1`
    - `app/Jobs/SendTelegramReminderToMaster.php:18`
    - `app/Jobs/SendTelegramReminderToClient.php:18`

