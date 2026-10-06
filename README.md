# Task Manager — Vue.js + Laravel

Полноценный task manager с авторизацией:

- **Backend** — Laravel (PHP), REST API, session-авторизация, SQLite.
- **Frontend** — Vue 3 + Vite, сборка прямо в `backend/public` (SPA отдаётся Laravel'ом).

## Возможности

- Регистрация и вход по email/паролю (session cookie, сессия на сервере).
- CRUD задач: название, описание, приоритет (низкий/средний/высокий), срок.
- Отметка выполнения, фильтрация (все / активные / выполненные), поиск.
- Статистика (всего / активные / выполнено), бейджи приоритета и сроков.
- Задачи изолированы: пользователь видит только свои.

## Структура

```
├── backend/    # Laravel API (routes/api.php, app/Http/Controllers)
├── frontend/   # Vue 3 + Vite (src/views, src/components, src/api.js)
└── README.md
```

## Запуск

### 1. Backend (терминал 1)

```bash
cd backend
php artisan serve        # http://localhost:8000
```

База — SQLite (`database/database.sqlite`), миграции уже применены.
Если нужно начать с нуля: `php artisan migrate:fresh --force`.

### 2. Frontend

**Прод-режим (рекомендуется)** — уже собрано, ничего делать не нужно:
SPA лежит в `backend/public` и открывается по адресу http://localhost:8000.

**Dev-режим с HMR** (терминал 2):

```bash
cd frontend
npm run dev              # http://localhost:5173, /api проксируется на :8000
```

### Демо-аккаунт

| Email | Пароль |
|---|---|
| `demo@example.com` | `password` |

## API

Все запросы — JSON, авторизация — по сессии (cookie).

| Метод | Путь | Описание |
|---|---|---|
| POST | `/api/auth/register` | Регистрация (`name`, `email`, `password`, `password_confirmation`) |
| POST | `/api/auth/login` | Вход (`email`, `password`) |
| POST | `/api/auth/logout` | Выход |
| GET | `/api/auth/me` | Текущий пользователь |
| GET | `/api/tasks` | Список задач. Фильтры: `completed=true/false`, `q=текст` |
| POST | `/api/tasks` | Создать (`title`, `description?`, `priority?`, `due_date?`) |
| PATCH | `/api/tasks/{id}` | Обновить |
| PATCH | `/api/tasks/{id}/toggle` | Переключить статус выполнения |
| DELETE | `/api/tasks/{id}` | Удалить |

## Сборка фронтенда в прод

```bash
cd frontend
npm run build            # кладёт dist в ../backend/public (не затирая index.php)
```
