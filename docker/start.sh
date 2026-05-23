#!/bin/sh
# First-time or fresh setup inside the app container.
# Run with: docker-compose exec app sh docker/start.sh

echo "==> Copying .env.docker to .env..."
cp .env.docker .env

echo "==> Running migrations + seed..."
php artisan migrate:fresh --seed --force

echo "==> Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "Done. App is running at http://localhost:8000"
