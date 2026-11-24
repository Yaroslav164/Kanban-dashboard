# Архитектура и разработка

## Активное приложение

Запускаемая версия находится в корне репозитория. `Dockerfile` копирует корневые `app/`, `routes/`, `resources/`, `database/` и собранный Vite-клиент. Каталоги `backend/` и `frontend/` — первоначальные отдельные каркасы. Они исключены из образа правилами в `.dockerignore` и не участвуют в корневых командах Composer, npm или CI.

Серверный стек — PHP 8.3, Laravel 13, Sanctum, Socialite, Reverb, l5-swagger и PostgreSQL. Клиент — Vue 3 Composition API, TypeScript и Pinia. Vite собирает `resources/js/app.ts` и связанные Vue-компоненты.

## Слои сервера

Контроллеры из `app/Http/Controllers` принимают HTTP-запросы, проверяют входные данные и вызывают сервисы. Сервисы в `app/Services` реализуют сценарии досок, колонок, карточек, доступа участников, ролей, активности и шаблонов. Репозитории в `app/Repositories` выполняют выборки и изменения Eloquent-моделей из `app/Models`.

Изменяющие операции сервисов используют `App\Services\UnitOfWork`. `run()` открывает `DB::transaction()`. Callback помечает успешное завершение вызовом `commit($result)`. Транзакция фиксируется, когда callback возвращает результат. Исключение из callback или отсутствие подтверждения приводит к откату; `rollback($error)` пробрасывает исключение.

## Данные и доступ

Основные сущности — пользователь, доска, колонка и карточка. Доски, колонки и карточки используют soft delete. Карточки содержат даты, состояние выполнения, метки и чеклисты. Исполнители связаны с карточками через `card_assignees`. Комментарии и содержимое вложений хранятся в `card_comments` и `card_attachments`; бинарные данные вложения сохраняются в PostgreSQL. Миграции находятся в `database/migrations`.

Маршруты входа и GitHub OAuth объявлены в `routes/web.php`. Канбан-маршруты подключены через `routes/api.php` с `auth:sanctum` и описаны в `routes/kanban.php`. Доступ к доскам и разрешениям проверяется сервисами и репозиториями. Роли `owner`, `editor` и `viewer` встроены; дополнительные роли и наборы разрешений хранятся для конкретной доски.

Маршруты API и схемы запросов/ответов описаны в [OpenAPI 3.0.3](openapi.yaml). Исходные PHP-аннотации хранятся в `app/OpenApi`. `php artisan l5-swagger:generate` создаёт Swagger-документацию; UI доступен по `/api/documentation`.

## Синхронизация между вкладками

При изменениях публикуется событие `App\Events\BoardChanged` после фиксации транзакции. Событие содержит идентификатор доски и рассылается приватным каналам `users.{id}` участников. Авторизация канала сверяет ID пользователя с ID сессии в `routes/channels.php`. Клиент подключается через Laravel Echo в `resources/js/realtime.ts`; при событии перечитывает состояние доски.

## Шаблоны и карточки

Шаблон хранит название, описание, метки и чеклисты исходной карточки. Применение шаблона создаёт новую карточку в выбранной колонке и сбрасывает пункты чеклистов в невыполненное состояние. Порядок блоков деталей карточки сохраняется на клиенте.

Загрузка ограничивает файл размером 10 MiB и суммарный объём вложений на карточке 50 MiB, максимум 20 файлов. Вложения скачиваются через защищённый маршрут, требующий сессию.

## Локальная среда

Для запуска нужен Docker Compose. Скопируйте `.env.example` в `.env`, задайте ключ приложения и выполните `docker compose up -d --build --wait`. Compose запускает PostgreSQL 16, миграцию, Reverb и Laravel-приложение. По умолчанию интерфейс доступен на порту 8000, PostgreSQL — 15432, Reverb — 8080.

Для GitHub OAuth задайте `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET` и локальный callback `/auth/github/callback`. Набор переменных окружения по умолчанию указан в `.env.example`; производственные настройки Render описаны в `render.yaml` и `docker/render-start.sh`.

## Проверки и инструменты

CI workflow `.github/workflows/ci.yml` устанавливает PHP 8.3, Node.js 24 и PostgreSQL 16. Он запускает Pint, ESLint/Prettier, TypeScript-проверку, Pest с минимумом 80% покрытия, тесты компонентов карточек, Vite build, генерацию и сравнение OpenAPI, затем Playwright.

Корневые скрипты npm объявлены в `package.json`:

| Скрипт | Назначение |
| --- | --- |
| `npm run dev` | Vite dev server |
| `npm run build` | Сборка клиентских ресурсов |
| `npm run test` | Vitest |
| `npm run test:card-coverage` | Vitest coverage для компонентного блока карточки с порогом 80% по строкам на файл |
| `npm run typecheck` | `vue-tsc --noEmit` |
| `npm run lint` | ESLint и Prettier check |
| `npm run test:e2e` | Playwright |

PHP-зависимости перечислены в `composer.json`; тесты Pest находятся в `tests/Feature` и `tests/Unit`. Frontend unit-тесты — рядом с модулями в `resources/js`. Playwright сценарии хранятся в `tests/e2e`.

Команды для локальной проверки OpenAPI и запуска Playwright, включая запуск браузерных тестов против Docker Compose, приведены в [корневом README](../README.md).
