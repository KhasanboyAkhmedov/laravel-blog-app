#!/bin/sh
set -e

echo ""
echo "=========================================="
echo "  Blog App — Starting up"
echo "=========================================="

# ── 1. Copy .env if it doesn't exist yet ─────────────────────────────────────
if [ ! -f .env ]; then
    echo "→ Copying .env.docker → .env"
    cp .env.docker .env
fi

# ── 2. Install PHP packages if vendor/ is missing ────────────────────────────
if [ ! -d vendor ]; then
    echo "→ Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# ── 3. Run migrations ─────────────────────────────────────────────────────────
# --force allows running in non-interactive mode
# Uses "migrate" not "migrate:fresh" so existing data is never wiped
echo "→ Running migrations..."
php artisan migrate --force

# ── 4. Seed only if the users table is empty (first run) ──────────────────────
USER_COUNT=$(php artisan tinker --execute="echo DB::table('users')->count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ]; then
    echo "→ Seeding database (first run)..."
    php artisan db:seed --force
fi

# ── 5. Fix permissions ────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── 6. Clear stale config cache ───────────────────────────────────────────────
php artisan config:clear
php artisan route:clear

echo ""
echo "  ✓ App ready → http://localhost:8000"
echo "  ✓ Vite HMR  → http://localhost:5173"
echo "=========================================="
echo ""

# ── Start PHP-FPM ─────────────────────────────────────────────────────────────
exec php-fpm
