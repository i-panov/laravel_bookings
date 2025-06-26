# Bookings API

Современное Laravel-приложение для управления бронированиями через REST API.

## Возможности
- Регистрация и управление бронированиями и слотами
- Авторизация по API-токену
- Гибкая политика доступа (Policy)
- Централизованная обработка ошибок
- Docker-окружение для быстрого старта
- Тесты на Pest

## Быстрый старт

### 1. Клонирование и запуск
```bash
git clone <repo-url>
cd bookings
docker-compose up --build
```

### 2. Установка зависимостей (если не через Docker)
```bash
composer install
npm install
```

### 3. Миграции и сиды
```bash
docker-compose exec app php artisan migrate --seed
```

### 4. Запуск тестов
```bash
docker-compose exec app php artisan test
# или
./vendor/bin/pest
```

## API

### Авторизация
Все запросы требуют заголовок:
```
Authorization: Bearer <api_token>
```

### Примеры эндпоинтов
- `GET   /api/bookings` — список бронирований пользователя
- `POST  /api/bookings` — создать бронирование с несколькими слотами
- `PATCH /api/bookings/{booking}/slots/{slot}` — обновить слот
- `POST  /api/bookings/{booking}/slots` — добавить слот
- `DELETE /api/bookings/{booking}` — удалить бронирование

#### Пример создания бронирования
```json
POST /api/bookings
{
  "slots": [
    {"start_time": "2025-07-01T10:00:00+03:00", "end_time": "2025-07-01T11:00:00+03:00"}
  ]
}
```

## Структура проекта
- `app/Models` — модели Eloquent
- `app/Http/Controllers/Api` — API-контроллеры
- `app/Http/Middleware` — middleware для авторизации
- `app/Policies` — политики доступа
- `database/migrations` — миграции
- `database/seeders` — сиды
- `routes/api.php` — маршруты API
- `tests/Feature` — функциональные тесты

## Переменные окружения
- `DB_*` — настройки базы данных
- `QUEUE_CONNECTION` — драйвер очереди (по умолчанию database)
- `APP_KEY` — ключ приложения

## Разработка
- Генерация IDE-хелперов: `php artisan ide-helper:generate`
- Форматирование кода: `./vendor/bin/pint`

## Лицензия
MIT
