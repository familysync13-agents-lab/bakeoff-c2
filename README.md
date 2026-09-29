# bakeoff-c2 - Shared Reading Lists (pre-V0 stack bake-off, candidate 2)

Candidate 2: Laravel + Inertia + React. Built by the Builder agent; every change reaches main only through a pull request that passes the shared `gate` check and is approved by the owner.

## Stack

- Laravel 13 on PHP 8.5 (Composer), official Laravel React starter kit (Inertia 3 + React 19 + Tailwind CSS 4, TypeScript), Node 24 for the Vite build
- PostgreSQL via Eloquent and Laravel migrations; starter-kit authentication (Fortify, email + password: registration, password reset/confirmation)
- Pest (tests), Pint (PHP lint/format), Larastan **level 7** (`phpstan.neon`), `vp check` (oxlint + oxfmt) and `tsc` for the frontend
- Sentry: `sentry/sentry-laravel` (server, `SENTRY_DSN`) and `@sentry/react` (browser, `PUBLIC_SENTRY_DSN`), both tagged with `APP_RELEASE` / `APP_ENV`
- Laravel Boost: development dependency only (`require-dev`)

## Preview interface (tasks/T0/contract.json)

- `Dockerfile`: stage `check` runs `composer ci:check` (vp check, tsc, Pint, Larastan, Pest) against `DATABASE_URL`; the last stage is the preview image.
- Preview start (`docker/start.sh`): migrations + idempotent seed (Alice, Bob), `artisan optimize`, then PHP's built-in server on `0.0.0.0:$PORT` (runs as uid 1000, no capabilities needed).
- `GET /healthz` -> `{"status":"ok","env":<APP_ENV>,"users":<count>}`.
- Environment mapping: `APP_SECRET` -> app key, `DATABASE_URL` -> `pgsql` connection, `APP_RELEASE` -> Sentry release.

## Accounts and reading lists (T2)

- Fortify: sign-up at `/signup` (name, email, password >= 8 characters, no confirmation field), sign-in at `/login`, sign-out `POST /logout` -> `/`; home is `/lists`. Failed sign-ins are throttled (5 per minute per email + IP); successful ones never are. Failure message: `lang/en/auth.php`.
- `ReadingListController` + `ReadingListPolicy`: `/lists`, `/lists/new`, `/lists/{id}`, `/lists/{id}/edit` (PATCH/DELETE `/lists/{id}`). Index and new-list form require sign-in (redirect to `/login`); a single list answers 404 to everyone but its owner, guests included (ownership is checked before validation); deletion is permanent.
- 403/404 responses render the Inertia `error` page inside the app shell.

## Book search (T3)

- `App\Services\BookSearch` calls `GET {BOOK_API_BASE_URL}/search.json?q=…&limit=10` server-side (`config/services.php` `book_api`) with a 2 s connect / 4 s total timeout; HTTP errors, invalid JSON, unexpected shapes and timeouts all become `BookSearchUnavailable`.
- `BookController`: `GET /lists/{id}/books/search?q=` (JSON, owner only; 503 `{"message":"Book search is unavailable"}` on failure) and `POST /lists/{id}/books` (owner only, 404 for everyone else, checked before validation). Books are unique per list by Open Library work key (or a hash of title/authors/year when the key is missing).
- UI: `resources/js/components/book-search.tsx` on the list page; added books are listed under "Books".

## Local development

```sh
composer install && npm ci && npm run build
php artisan migrate --seed
composer dev
# checks; tests use DATABASE_URL (RefreshDatabase wipes it: point it at a test database) or in-memory SQLite when unset
DATABASE_URL=$TEST_DATABASE_URL composer ci:check
```
