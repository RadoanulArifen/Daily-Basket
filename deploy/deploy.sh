#!/usr/bin/env bash
# Run after uploading new files to the server:
#   cd /var/www/vibe-ecommerce && sudo -u www-data bash deploy/deploy.sh
#
# Puts the store in maintenance mode for the few seconds the update takes.

set -euo pipefail
cd "$(dirname "$0")/.."

echo "→ Maintenance mode on"
php artisan down --retry=15 || true

echo "→ PHP packages"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress

echo "→ Front-end build"
# The zip ships a ready-built public/build; rebuild when Node is available so it always matches the code.
if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
else
    echo "   npm not found — using the prebuilt public/build from the package"
fi
rm -rf public/vendor/livewire   # old published Livewire script copy; Alpine is in our own bundles now

echo "→ Database updates"
php artisan migrate --force

echo "→ Caches"
php artisan storage:link 2>/dev/null || true
php artisan optimize          # config, routes, views, events
php artisan queue:restart     # workers pick up the new code

echo "→ Maintenance mode off"
php artisan up

php artisan store:launch-check || true
php artisan store:web-check || true   # loads the live pages: blocked scripts, missing files, extra CSP headers
echo "✓ Deployed"
