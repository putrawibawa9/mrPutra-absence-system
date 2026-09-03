#!/usr/bin/env bash
#
# Server-side deploy for absensi.mrputra.xyz (non-git shared host).
# Uploaded via rsync, then run on the server:
#   ssh -p 65002 u936565777@145.79.14.101 "cd /home/u936565777/domains/mrputra.xyz/public_html/absensi && bash deploy.sh"
#
# Self-healing & safe: creates missing framework dirs, ensures the MySQL
# connection is set, validates DB connectivity BEFORE migrating, and only then
# flips to maintenance + migrates. Aborts on the first error.

set -euo pipefail
cd "$(dirname "$0")"
echo "==> Project: $(pwd)"

echo "==> [0] Ensuring writable framework directories"
mkdir -p bootstrap/cache \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/public \
         database
chmod -R 775 bootstrap/cache storage 2>/dev/null || true

echo "==> [1] Checking .env"
if [ ! -f .env ]; then
  echo "FATAL: .env tidak ada di server. Restore .env dulu (berisi DB password & APP_KEY)."
  exit 1
fi
# Ensure DB_CONNECTION=mysql (root cause of the earlier backup failure)
if grep -qE '^DB_CONNECTION=mysql([[:space:]]|$)' .env; then
  echo "  DB_CONNECTION already mysql"
elif grep -qE '^DB_CONNECTION=' .env; then
  sed -i.bak 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
  echo "  fixed DB_CONNECTION=mysql (backup: .env.bak)"
else
  printf '\nDB_CONNECTION=mysql\n' >> .env
  echo "  added DB_CONNECTION=mysql"
fi
# Ensure an app key exists
grep -qE '^APP_KEY=base64:' .env || php artisan key:generate --force

echo "==> [2] Clearing stale caches"
php artisan optimize:clear

echo "==> [3] Linking storage"
php artisan storage:link || true

echo "==> [4] Verifying DB connection (stops here if credentials are wrong)"
php artisan migrate:status

echo "==> [5] Maintenance mode ON"
php artisan down || true

echo "==> [6] Migrating + backfilling tokens"
php artisan migrate --force
php artisan tokens:backfill

echo "==> [7] Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
echo "==> DONE. Live: https://absensi.mrputra.xyz"
