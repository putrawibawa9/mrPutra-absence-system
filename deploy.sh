#!/usr/bin/env bash
#
# Deploy script for absensi.mrputra.xyz
# Run ON THE SERVER from the project root:
#   cd /home/u936565777/domains/mrputra.xyz/public_html/absensi
#   bash deploy.sh
#
# Assumes PROJECT_PATH is a git clone of origin/main. Verify first with:
#   git remote -v
# If it is NOT a git checkout, stop and use the rsync/SFTP method instead.
#
# Safety: aborts on the first error. If it aborts during/after migrate, the
# site stays in maintenance mode — investigate, restore the DB backup if
# needed, then run `php artisan up`.

set -euo pipefail

cd "$(dirname "$0")"
echo "==> Project: $(pwd)"

echo "==> [1/8] Maintenance mode ON"
php artisan down || true

echo "==> [2/8] Backing up database (writes to ./database/pre-deploy-*.sql)"
php artisan db:backup-mysql --filename="pre-deploy-$(date +%Y%m%d-%H%M%S).sql"

echo "==> [3/8] Fetching latest code (origin/main)"
git fetch origin
# Hard reset guarantees the working tree (incl. compiled public/build assets)
# matches the remote. This DISCARDS any uncommitted changes to tracked files
# on the server. Gitignored files (.env, storage, db backups) are preserved.
git reset --hard origin/main

echo "==> [4/8] Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [5/8] Current migration status:"
php artisan migrate:status || true

echo "==> [6/8] Running migrations"
php artisan migrate --force

echo "==> [7/8] Backfilling token ledger for existing payments"
php artisan tokens:backfill

echo "==> [8/8] Rebuilding caches"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
echo "==> Deploy complete. Site is live."
