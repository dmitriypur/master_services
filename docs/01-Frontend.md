# Фронтенд

- Стек: Inertia.js + Vue 3.
- Основные страницы:
  - Master: `resources/js/Pages/Master/Calendar.vue` — календарь; `resources/js/Pages/Master/CalendarWebView.vue` — облегчённое WebView; `resources/js/Pages/Auth/TelegramWebApp.vue` — авторизация WebApp.
  - Client: `resources/js/Pages/Client/Booking.vue` — поиск и выбор мастера; `resources/js/Pages/Client/MasterCalendar.vue` — календарь конкретного мастера.
- Поведение:
  - Мастер WebApp: при успешной валидации `initData` редирект на `'/master/calendar'` или `'/master/settings'` в зависимости от заполненности настроек (см. `app/Http/Controllers/Auth/AuthTelegramController.php:17`).
  - Клиент WebApp: форма бронирования, отправка на `POST /api/appointments`.
- Компоненты: модальные окна (`components/UI/Modal.vue`), кнопки (`components/UI/Button.vue`), дата‑пикер.
- API‑вызовы: `/api/services`, `/api/masters`, `/api/masters/{id}/slots`, `/api/appointments` и т.п.
- Хранение черновиков клиента: локально (WebApp state) и серверный черновик (Redis) для последующего сохранения в БД.

