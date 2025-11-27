# Интеграции и инфраструктура

- Telegram
  - WebApp подпись initData: `app/Services/Telegram/TelegramWebAppService.php:18`, конфиг `config/services.php:29`
  - Отправка сообщений: `app/Services/Telegram/TelegramBotService.php:19`
  - Напоминания мастеру/клиенту: команды + джобы

- Redis
  - CACHE/QUEUE/SESSION по env, конфиг: `config/queue.php`, `config/cache.php`, `config/session.php`

- Docker
  - Сервисы: php-fpm/nginx/postgres/redis/queue‑worker — `docker-compose.yml`

