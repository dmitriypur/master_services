# Запуск и остановка приложения (Docker)

## Предпосылки
- MacOS, установлен `Docker Desktop` (Compose V2: команда `docker compose`).
- Порт `8000` свободен (используется Nginx).

## Первый запуск
1. Создать `.env` (если не создан) на основе примера:
   - `cp .env.example .env`
   - В `.env` значения уже подготовлены для Docker (`DB_HOST=postgres`, `REDIS_HOST=redis`).
2. Собрать и поднять контейнеры:
   - `docker compose up -d`
3. Установить зависимости и инициализировать приложение:
   - `docker compose exec app composer install`
   - `./bin/artisan key:generate`
   - `./bin/artisan migrate --force`
4. Frontend (Vite, HMR):
   - На хосте: `npm install`
   - Для разработки: `npm run dev`

Приложение доступно: `http://localhost:8000`

## Повседневный запуск
- `docker compose up -d`
- Опционально: `npm run dev` для HMR, иначе используйте `npm run build` и статические ассеты.

## Доступ извне через ngrok
1. Установить ngrok (macOS): `brew install ngrok`
2. Авторизоваться: `ngrok config add-authtoken <ваш_токен>` (токен в кабинете ngrok).
3. Запустить контейнеры: `docker compose up -d`
4. Пробросить публичный HTTPS‑URL на локальный Nginx: `ngrok http 8000`
   - Альтернатива с явным хост‑заголовком: `ngrok http --host-header=rewrite 8000`
5. Обновить `.env` на время разработки:
   - `APP_URL=https://<ваш-ngrok-домен>`
   - Если используете SPA/санктум‑куки: `SANCTUM_STATEFUL_DOMAINS=<ваш-ngrok-домен>` и при необходимости `SESSION_DOMAIN=<ваш-ngrok-домен>`
   - Применить изменения: `./bin/artisan config:clear`
6. Открыть публичный URL из вывода ngrok (например `https://abc123.ngrok.app`) — приложение доступно извне.

Примечания:
- Для стабильного домена используйте зарезервированный ngrok subdomain: `ngrok http --domain=<your-subdomain>.ngrok.app 8000` (нужен платный план).
- При работе Telegram Webhook/WebApp укажите публичный ngrok‑URL в настройках вебхука (эндпоинт вашего бота) и перерегистрируйте его.

## Остановка БЕЗ потери данных
- Быстрая остановка контейнеров (сохранит все данные и сети):
  - `docker compose stop`
- Полная остановка и удаление контейнеров/сетей, НО с сохранением томов данных:
  - `docker compose down`

Важно: не используйте `docker compose down -v` — это удалит тома (`pgdata`, `redisdata`) и приведёт к потере данных.

## Перезапуск
- `docker compose restart`

## Полезные команды
- Логи Nginx: `docker compose logs -f nginx`
- Логи PHP-FPM (app): `docker compose logs -f app`
- Artisan внутри Docker: `./bin/artisan <команда>`
  - Примеры: `./bin/artisan migrate`, `./bin/artisan queue:work`
- Консоль БД:
  - `docker compose exec postgres psql -U booking -d booking`
- Бэкап БД:
  - `docker compose exec postgres pg_dump -U booking -d booking > backup.sql`

## Структура Docker
- `docker-compose.yml`:
  - `app` (PHP-FPM, Composer)
  - `nginx` (проксирует на `app`, порт `8000`)
  - `postgres` (данные в томе `pgdata`)
  - `redis` (данные в томе `redisdata`)
  - `queue-worker` (можно заменить на `./bin/artisan queue:work` если требуется)

## Частые проблемы
- 419/CSRF: при запросах из SPA отправляйте `X-CSRF-TOKEN` и `credentials: 'same-origin'`.
- Порты заняты: убедитесь, что `8000`, `5432`, `6379` свободны.
- Миграции не применились: проверьте `./bin/artisan migrate --force` и логи `app`.