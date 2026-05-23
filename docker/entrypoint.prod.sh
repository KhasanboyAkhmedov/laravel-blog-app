#!/bin/sh
set -e

echo ""
echo "=========================================="
echo "  Blog App — Production Startup"
echo "=========================================="

# ── 1. Write .env from Railway's injected environment variables ───────────────
# Railway injects env vars at the OS level, not via a .env file.
# Laravel's artisan commands require a .env file to exist, so we create
# one from the current environment on every boot.
echo "→ Writing .env from environment..."
{
    printf 'APP_NAME="%s"\n'     "${APP_NAME:-Blog App}"
    printf 'APP_ENV=%s\n'        "${APP_ENV:-production}"
    printf 'APP_KEY=%s\n'        "${APP_KEY:-}"
    printf 'APP_DEBUG=%s\n'      "${APP_DEBUG:-false}"
    printf 'APP_URL=%s\n'        "${APP_URL:-http://localhost}"
    printf 'LOG_CHANNEL=%s\n'    "${LOG_CHANNEL:-stack}"
    printf 'DB_CONNECTION=%s\n'  "${DB_CONNECTION:-pgsql}"
    printf 'DB_HOST=%s\n'        "${DB_HOST:-127.0.0.1}"
    printf 'DB_PORT=%s\n'        "${DB_PORT:-5432}"
    printf 'DB_DATABASE=%s\n'    "${DB_DATABASE:-laravel}"
    printf 'DB_USERNAME=%s\n'    "${DB_USERNAME:-postgres}"
    printf 'DB_PASSWORD=%s\n'    "${DB_PASSWORD:-}"
    printf 'SESSION_DRIVER=%s\n' "${SESSION_DRIVER:-database}"
    printf 'CACHE_STORE=%s\n'    "${CACHE_STORE:-database}"
    printf 'QUEUE_CONNECTION=%s\n' "${QUEUE_CONNECTION:-sync}"
} > .env

# ── 2. Generate APP_KEY if not provided ───────────────────────────────────────
# .env now exists, so key:generate can write to it safely.
if [ -z "$APP_KEY" ]; then
    echo "→ Generating APP_KEY (add this to Railway env vars to make it permanent)..."
    php artisan key:generate --force
fi

# ── 3. Run migrations ─────────────────────────────────────────────────────────
echo "→ Running migrations..."
php artisan migrate --force

# ── 4. Seed only if the users table is empty (first deploy) ──────────────────
USER_COUNT=$(php artisan tinker --execute="echo DB::table('users')->count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ]; then
    echo "→ Seeding database (first run)..."
    php artisan db:seed --force
fi

# ── 5. Cache everything for production performance ───────────────────────────
echo "→ Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 6. Configure nginx to listen on Railway's PORT ───────────────────────────
export PORT="${PORT:-8080}"
echo "→ Configuring nginx on port $PORT..."
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# ── 7. Start PHP-FPM in the background ───────────────────────────────────────
echo "→ Starting PHP-FPM..."
php-fpm -D

echo ""
echo "  ✓ App live on port $PORT"
echo "=========================================="
echo ""

# ── 8. Start nginx in the foreground (keeps the container alive) ─────────────
exec nginx -g 'daemon off;'
