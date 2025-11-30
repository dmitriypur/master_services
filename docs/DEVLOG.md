# Dev Log — 2025-11-27

## Сводка изменений
- Единый бот с двумя кнопками: «Для мастера» → `'/app?webview=1'`, «Для клиента» → `'/book?webview=1'`.
- Автоматическая авторизация WebApp: зарегистрированный мастер → календарь; незарегистрированный → сразу форма регистрации.
- Регистрация мастера упрощена: ФИО, город, услуги, телефон (ручной ввод), автоподстановка ФИО из Telegram `initDataUnsafe.user`.
- Телефон мастера сохраняется и показывается клиентам на карточке.
- В кабинете мастера добавлено поле «Телефон» и навигация к списку клиентов.
- Удалён функционал запроса контакта в боте.
- Все тексты ошибок/успеха локализованы на русском.

## Потоки входа
- Бот → «Для мастера» ведёт на `'/app?webview=1'`.
- WebApp авторизация:
  - Если мастер существует по `telegram_id`: редирект на `'/master/calendar'` (`app/Http/Controllers/Auth/AuthTelegramController.php:56`).
  - Если нет: 403 + `register_url`, фронт сразу редиректит на форму регистрации (`resources/js/Pages/Auth/TelegramWebApp.vue:70–90`).

## Регистрация мастера
- Бэкенд: создаёт пользователя с `name`, `city_id`, `phone`, роль `master`, и активирует выбранные услуги.
  - `app/Http/Controllers/Auth/MasterRegisterController.php:43–70`.
- Дублирующая регистрация: если мастер уже есть, выполняется логин и редирект на кабинет (`…:30–37`).
- Валидация: `MasterRegisterRequest` — имя, город, услуги, `phone` (цифры 5–11) (`app/Http/Requests/Auth/MasterRegisterRequest.php:13`).
- Фронт: форма регистрации с автозаполнением ФИО из `initDataUnsafe.user`, телефон вводится вручную (`resources/js/Pages/Master/Register.vue:1–30, 77–101`).

## Телефон
- Миграция: поле `users.phone` добавлено (`database/migrations/2025_11_27_120000_add_phone_to_users_table.php`).
- Модель: `User::$fillable` включает `city_id`, `phone` (`app/Models/User.php:26–33`).
- Ресурс мастера: поле `phone` отдаётся в API (`app/Http/Resources/MasterResource.php:14–28`).
- Клиентская карточка мастера: показ «Телефон: …» (`resources/js/Pages/Client/MasterCalendar.vue:1–20`).
- Кабинет: редактирование телефона в «Настройки», сохранение на бэке.
  - Фронт: `resources/js/Pages/Master/Settings.vue:18–22, 107–120`.
  - Валидация: `app/Http/Requests/Master/UpdateSettingsRequest.php:20–31`.
  - Сервис: нормализация и сохранение (`app/Actions/Master/UpdateSettingsAction.php:12–28`).

## Кабинет и навигация
- В шапках страниц мастера добавлена кнопка «Клиенты»:
  - Календарь (`resources/js/Pages/Master/Calendar.vue:1–6`).
  - Настройки (`resources/js/Pages/Master/Settings.vue:2–6`).
- Маршрут списка клиентов: `routes/web.php:67–74` (`GET /master/clients`).

## Бот
- Упрощённые кнопки:
  - «Для мастера» → `'/app?webview=1'`, «Для клиента» → `'/book?webview=1'` (`app/Http/Controllers/Telegram/WebhookController.php:39–60`).
- Удалён запрос контакта и обработка `message.contact`.
  - Контроллер (`app/Http/Controllers/Telegram/WebhookController.php:23–37`).
  - Request (`app/Http/Requests/TelegramWebhookRequest.php:18–23`).

## Админка
- В `UserResource` добавлены отображение и редактирование телефона:
  - Поле формы `phone` (`app/Filament/Resources/UserResource.php:31–46`).
  - Колонка `phone` в таблице (`…:117–134`).

## Проверки
- Миграции: `php artisan migrate --force` — успешно.
- Тесты: `composer test` — успешно.

## Текущий шаг
- Потоки входа согласованы: бот → WebApp → если не зарегистрирован — форма, если зарегистрирован — календарь.
- Телефон мастера: ручной ввод, редактируется в кабинете, показывается клиентам.
- Навигация: из календаря и настроек есть ссылка на «Клиенты».

## Возможные следующие задачи
- Добавить подсказки/маску ввода телефона (+7/8), если потребуется.
- Толще валидация услуг и UX‑улучшения в регистрации (индикаторы, ошибки по полям).
- Страница «Клиенты» — расширить действия (поиск, фильтры, импорт/экспорт).

