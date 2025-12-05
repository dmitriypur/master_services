# Фронтенд

- Страницы (Inertia + Vue):
  - Мастер — Календарь: `resources/js/Pages/Master/Calendar.vue`
    - Создание записи → POST `/api/appointments` (мастер авторизован)
    - Просмотр/удаление/напоминание
  - Мастер — WebView: `resources/js/Pages/Master/CalendarWebView.vue`
    - Интеграция Telegram WebApp (`telegram-web-app.js`), редирект в полную версию
  - Клиент — Поиск/Бронирование: `resources/js/Pages/Client/Booking.vue` (рендер из `BookingController`)
  - Клиент — Календарь мастера: `resources/js/Pages/Client/MasterCalendar.vue`
    - Создание записи от клиента (payload включает `master_id`, `service_id`, контакт)
  - Авторизация WebApp: `resources/js/Pages/Auth/TelegramWebApp.vue`
    - Отправка initData → `/auth/telegram/webapp`.
    - Если ответ `200` → редирект на `'/master/calendar'` или `'/master/settings'`.
    - Если ответ `404/401` → фронт редиректит на `'/master/register?initData=…'` (`resources/js/Pages/Auth/TelegramWebApp.vue:62–66`).

- Ключевая логика:
  - Валидация форм на клиенте, нормализация телефона, выбор услуги/клиента
  - Взаимодействие с CSRF и Inertia
