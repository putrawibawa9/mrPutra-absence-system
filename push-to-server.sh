#!/usr/bin/env bash
#
# Run on your Mac to upload the project to the live server.
# It will ask for the SSH password once.
#
#   bash push-to-server.sh
#
# Uploads code + vendor + compiled assets, but never touches the server's
# .env, storage (data/logs/uploads), or cached config.

set -euo pipefail

SRC="/Users/putrawibawa/Desktop/abspay_mrputra/"
DEST="u936565777@145.79.14.101:/home/u936565777/domains/mrputra.xyz/public_html/absensi/"

rsync -avz -e "ssh -p 65002" \
  --exclude='/.git/' \
  --exclude='/node_modules/' \
  --exclude='/.env' \
  --exclude='/storage/' \
  --exclude='/public/storage' \
  --exclude='/bootstrap/cache/' \
  --exclude='/.claude/' \
  --exclude='.DS_Store' \
  "$SRC" "$DEST"

echo "==> Upload complete."
