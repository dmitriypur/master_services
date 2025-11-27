# Бэкенд

- Контроллеры:
  - Мастер — слоты/записи/уведомления:
    - `app/Http/Controllers/Api/Master/AppointmentController.php:1` — создать запись, получить запись по слоту
    - `app/Http/Controllers/Api/Master/AppointmentNotificationController.php:1` — уведомить клиента
  - Клиент — список/создание/обновление клиентов:
    - `app/Http/Controllers/Api/ClientController.php:1`
  - Поиск мастеров/услуг/слотов:
    - `app/Http/Controllers/Api/Master/MasterSearchController.php:1`
    - `app/Http/Controllers/Api/ServiceController.php:1`
  - Веб‑страницы:
    - Мастер: `app/Http/Controllers/Master/CalendarController.php:1`, `SettingsController.php:1`
    - Клиент: `app/Http/Controllers/Client/BookingController.php:1`, `MasterCalendarController.php:1`
  - WebApp авторизация (мастер): `app/Http/Controllers/Auth/AuthTelegramController.php:17`

- Actions/Services:
  - Создание записи: `app/Actions/Appointments/CreateAppointmentAction.php:1`
  - Уведомления: `app/Actions/Appointments/NotifyAppointmentAction.php:1`
  - Настройки мастера: `app/Actions/Master/UpdateSettingsAction.php:1`
  - Telegram:
    - WebApp подпись: `app/Services/Telegram/TelegramWebAppService.php:18`
    - Bot API: `app/Services/Telegram/TelegramBotService.php:19`

- Валидации:
  - Запись: `app/Http/Requests/Appointments/StoreAppointmentRequest.php:1`
  - Настройки мастера: `app/Http/Requests/Master/UpdateSettingsRequest.php:1`

- Роуты:
  - API: `routes/api.php:15`–`31`
  - Web: `routes/web.php:41`–`67`

- Команды/Очереди:
  - Команды: `app/Console/Commands/*`, расписание — `app/Console/Kernel.php:18`
  - Джобы: `app/Jobs/*`

