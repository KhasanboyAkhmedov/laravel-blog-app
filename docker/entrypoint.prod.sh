#!/bin/sh
set -e

echo ""
echo "=========================================="
echo "  Blog App — Production Startup"
echo "=========================================="

# ── 1. Generate app key if not provided ──────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "→ Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── 2. Run migrations ─────────────────────────────────────────────────────────
echo "→ Running migrations..."
php artisan migrate --force

# ── 3. Seed only if the users table is empty (first deploy) ──────────────────
USER_COUNT=$(php artisan tinker --execute="echo DB::table('users')->count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ]; then
    echo "→ Seeding database (first run)..."
    php artisan db:seed --force
fi

# ── 4. Cache everything for production performance ───────────────────────────
echo "→ Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 5. Configure nginx to use Railway's PORT ─────────────────────────────────
# Railway sets $PORT. Default to 8080 if not set.
export PORT="${PORT:-8080}"
echo "→ Configuring nginx on port $PORT..."
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# ── 6. Start PHP-FPM in the background ───────────────────────────────────────
echo "→ Starting PHP-FPM..."
php-fpm -D

echo ""
echo "  ✓ App live on port $PORT"
echo "=========================================="
echo ""

# ── 7. Start nginx in the foreground (keeps the container alive) ──────────────
exec nginx -g 'daemon off;'
