# Executive Cricket Club (ECC) - Backend

Laravel 11 API-first backend for Executive Cricket Club.

## Tech Stack
- **Framework**: Laravel 11
- **Database**: SQLite (Local), MySQL/PostgreSQL (Production)
- **Cache/Queue**: Redis
- **Broadcasting**: Laravel Reverb + Redis
- **Auth**: JWT (Flutter), Session (Admin)
- **Docs**: Scribe/Scramble (OpenAPI)

## Local Setup

1. **Prerequisites**
   - PHP 8.2+
   - Composer
   - Redis (running locally or via Docker)
   - **Note**: The project uses `predis` by default. If you prefer the `phpredis` extension, ensure it's enabled in `php.ini` and set `REDIS_CLIENT=phpredis` in `.env`.

## Redis Verification
If you encounter `Class "Redis" not found` or connection errors:
1. Ensure `REDIS_CLIENT=predis` is in your `.env`.
2. Run `php artisan optimize:clear`.
3. Verify connection:
   ```bash
   php artisan tinker
   # Then type:
   Cache::store('redis')->put('test_key', 'it_works', 10);
   Cache::store('redis')->get('test_key');
   # Should return "it_works"
   ```

2. **Installation**
   ```bash
   git clone <repo>
   cd ecc-backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   touch database/database.sqlite # If using SQLite
   php artisan migrate --seed
   ```

3. **Running Services**
   - **App**: `php artisan serve`
   - **Queues**: `php artisan horizon`
   - **Reverb**: `php artisan reverb:start`
   - **Redis**: Ensure redis-server is running.

4. **Testing**
   ```bash
   php artisan test
   ```

## API Usage (Flutter)
- Base URL: `http://localhost:8000/api/v1`
- Auth: Bearer Token (JWT)
- Endpoints: See `/docs` or Postman Collection.

## Postman
- Import `postman/ECC_API_v1.postman_collection.json`
- Import `postman/ECC_Local.postman_environment.json`
- Set `base_url` to `http://localhost:8000`

## Admin Panel
- Access at `http://localhost:8000/admin`
- Log in with super admin credentials (seeded).
