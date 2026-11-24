# Полоса — канбан-доска

Веб-приложение для личной и командной работы. Активное приложение находится в корне репозитория и использует Laravel 13, PHP 8.3, PostgreSQL 16, Vue 3, TypeScript и Pinia. `backend/` и `frontend/` — остатки первоначальных отдельных каркасов. Docker-сборка исключает их через `.dockerignore`.

Пользователь регистрируется по email и паролю или входит через GitHub OAuth. Для новой учётной записи создаётся демонстрационная доска. Доски поддерживают колонки и карточки, drag-and-drop, комментарии, метки, участников, даты, чеклисты, вложения, шаблоны карточек и режимы доски, списка, дел и календаря. Изменения участников передаются через Laravel Reverb. Владельцы досок добавляют пользователей и назначают встроенные либо настраиваемые роли.

## Повседневная работа

- Введите название или часть описания в «Поиск карточек». Фильтры по исполнителю, метке, сроку и статусу сохраняются при переключении между доской, списком, делами и календарём.
- На экране «Мои задачи» собраны открытые карточки, назначенные вам на доступных досках. Выберите задачу в группе по сроку, чтобы открыть её карточку.
- «Уведомления» показывают назначения, упоминания и напоминания по вашим карточкам со сроком сегодня или завтра по часовому поясу приложения. В комментарии можно выбрать участника для упоминания. Из уведомления можно перейти к карточке.
- В карточке раскройте историю, чтобы увидеть автора и время изменений срока, исполнителей, позиции или состояния выполнения.
- Архивируйте карточку, чтобы убрать её со всех рабочих представлений. Из «Архива» на доске карточка возвращается в исходную колонку. Если колонка удалена, можно выбрать другую на той же доске. Для архивирования и восстановления требуется право удаления карточек.
- В настройках колонки укажите лимит задач в работе (WIP). Счётчик учитывает все карточки в колонке независимо от фильтров. При превышении лимита появляется предупреждение, и команда может продолжить перенос карточек.

## Локальный запуск

Нужен Docker с Compose. Создайте `.env` из `.env.example`, задайте `APP_KEY`, затем запустите сервисы:

```sh
docker run --rm php:8.3-cli php -r "echo 'base64:', base64_encode(random_bytes(32)), PHP_EOL;"
docker compose up -d --build --wait
```

Откройте [http://localhost:8000](http://localhost:8000) и зарегистрируйте пользователя. Compose автоматически применит миграции. PostgreSQL опубликован на локальном порту `15432`, Reverb — на `8080`. Данные PostgreSQL хранятся в Docker volume `postgres_data`.

Для входа через GitHub создайте OAuth App, укажите его `GITHUB_CLIENT_ID` и `GITHUB_CLIENT_SECRET` в `.env` и установите callback URL `http://localhost:8000/auth/github/callback`. После изменения настроек выполните `docker compose up -d`.

Остановка сервисов без удаления данных:

```sh
docker compose stop
```

## Разработка и проверки

PHP-пакеты фиксируются `composer.lock`, JavaScript-пакеты — `package-lock.json`. В CI используются PHP 8.3 с PCOV, Node.js 24 и PostgreSQL 16. Основные команды локальной проверки:

```sh
docker compose exec -e APP_ENV=testing -e DB_DATABASE=kanban_testing -e DB_URL= app php artisan test
docker compose exec -e APP_ENV=testing -e DB_DATABASE=kanban_testing -e DB_URL= app php vendor/bin/pest --coverage --min=80
docker compose exec app php vendor/bin/pint --test
npm ci
npm run test
npm run test:card-coverage
npm run typecheck
npm run lint
npm run build
```

Swagger UI локально доступен по [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation). OpenAPI 3.0.3 генерируется из PHP-аннотаций в `app/OpenApi`. Для сравнения установите PyYAML 6.0.3, как в CI. Сгенерируйте документ в Docker, скопируйте YAML в рабочую копию и сравните его с контрактом:

```sh
docker compose exec app php artisan l5-swagger:generate
docker compose cp app:/app/storage/api-docs ./storage/
python scripts/check-contract.py
```

Для проверки браузерных сценариев установите Chromium через `npx playwright install chromium`. Без `KANBAN_URL` Playwright сам запускает локальные `php artisan serve` и Reverb, поэтому приложение и серверные зависимости должны быть доступны локально. Чтобы направить браузерные тесты на приложение из Docker Compose, сначала запустите Compose, затем задайте `KANBAN_URL=http://localhost:8000` и выполните `npm run test:e2e`. В PowerShell:

```powershell
$env:KANBAN_URL='http://localhost:8000'
npm run test:e2e
```

Для включения Git pre-commit hook выполните:

```sh
git config core.hooksPath .githooks
```

## Документы проекта

- [Архитектура, модель данных и потоки запросов](docs/architecture.md)
- [Векторный индекс кода](docs/code-index.md) — навигация по индексированной структуре исходников, если индекс собран
- [OpenAPI 3.0.3](docs/openapi.yaml)
- [Инструкции для агентов](AGENTS.md)

Render-конфигурация находится в `render.yaml`. Она описывает Docker web service, PostgreSQL 16 и health check `/up`. Перед production-развёртыванием настройте OAuth credentials и проверьте лимиты и срок хранения базы данных для выбранного плана Render.
