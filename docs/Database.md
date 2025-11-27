# База данных

- Пользователи `users`
  - Поля: `name`, `email`, `password`, `role` (`master|superadmin`), `telegram_id`
  - Мастер‑настройки: `user_master_settings` (`address`, `work_days[]`, `work_time_from/to`, `slot_duration_min`, `lat/lon`) — `database/migrations/2025_11_23_160200_create_user_master_settings_table.php`

- Услуги `services`
  - `id`, `name`
  - Связь мастера: `master_services` pivot (`price`, `is_active`) — `User::services()` в `app/Models/User.php:77`

- Клиенты `clients`
  - `user_id` (мастер), `name`, `phone`, `telegram_id`, `whatsapp_phone`, `preferred_channels[]`
  - Миграция: `database/migrations/2025_11_23_171500_create_clients_table.php`
  - Модель: `app/Models/Client.php:10` (нормализация телефона)

- Записи `appointments`
  - `master_id`, `client_id`, `service_id`, `starts_at`, `ends_at`, `status`, `source`, `reminder_*`
  - Миграции: `2025_11_23_163353_create_appointments_table.php`, FK клиента: `2025_11_23_171600_add_client_fk_to_appointments_table.php`

- Логи уведомлений `notification_logs`
  - `appointment_id`, `channel`, `status`, `sent_at`, `error_message` — `app/Models/NotificationLog.php:10`

- Очереди `jobs`, `job_batches`, `failed_jobs`: `database/migrations/0001_01_01_000002_create_jobs_table.php`

