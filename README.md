# Полоса

Личная канбан-доска на Laravel 13, PHP 8.3, PostgreSQL 16, Vue 3 и Pinia. Карточки можно перетаскивать между колонками. Открытые вкладки одного пользователя получают изменения через Laravel Reverb.

## Запуск в Docker

Из корня проекта скопируйте `.env.example` в `.env`. Сгенерируйте ключ следующей командой и поместите результат целиком в `APP_KEY` файла `.env`.

```sh
docker run --rm php:8.3-cli php -r "echo 'base64:', base64_encode(random_bytes(32)), PHP_EOL;"
docker compose up -d --build --wait
```

Откройте [доски](http://localhost:8000) и создайте аккаунт. Compose сам выполнит миграции. PostgreSQL доступен на порту 15432 хоста, Reverb — на 8080. Данные хранятся в томе PostgreSQL. Команда `docker compose stop` останавливает сервисы.

Для входа через GitHub укажите `GITHUB_CLIENT_ID` и `GITHUB_CLIENT_SECRET` своего OAuth App. Callback URL при локальном запуске — `http://localhost:8000/auth/github/callback`. После изменения окружения выполните `docker compose up -d`.

## API и проверки

[Swagger UI](http://localhost:8000/api/documentation) описывает сессию, CRUD, переносы и авторизацию приватных каналов. Для локальной Swagger UI удобно сначала войти в приложение в соседней вкладке. Сервер генерирует JSON и YAML из PHPDoc в `app/OpenApi`. Эталон для CI хранится в [docs/openapi.yaml](docs/openapi.yaml).

```sh
docker compose exec -e APP_ENV=testing app php artisan test
docker compose exec -e APP_ENV=testing app php vendor/bin/pest --coverage --min=80
docker compose exec app php vendor/bin/pint --test
docker compose exec app php artisan l5-swagger:generate
```

Для клиентских проверок используйте Node 24 и npm. Playwright проверяет схему реальных HTTP-ответов, сохранение порядка и обновление второй вкладки.

```sh
npm ci
npm run test
npm run typecheck
npm run lint
npm run build
npx playwright install chromium
docker compose cp app:/app/storage/api-docs ./storage/
```

В PowerShell выполните `$env:KANBAN_URL='http://localhost:8000'`, затем `npm run test:e2e`. В Bash — `KANBAN_URL=http://localhost:8000 npm run test:e2e`. Для сравнения YAML установите PyYAML и выполните `python scripts/check-contract.py`. Скрипты CI используют отдельную БД `kanban_testing`.

В Git-репозитории pre-commit включается командой `git config core.hooksPath .githooks`. Unix требует права на запуск файла `.githooks/pre-commit`. Подробности реализации и приёмки — в [чеклисте проекта](tasks/todo.md).
