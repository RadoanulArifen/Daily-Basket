#!/usr/bin/env bash
# Nightly backup: database + uploaded images, kept for 14 days in storage/backups.
# Copy these files off the server too (see docs/deployment.md) — a backup on the
# same disk doesn't survive a disk failure.

set -euo pipefail
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="$APP_DIR/storage/backups"
KEEP_DAYS=14
STAMP="$(date +%Y%m%d-%H%M)"
mkdir -p "$BACKUP_DIR"

# Read database settings from .env without running it as a script.
env_value() { grep -E "^$1=" "$APP_DIR/.env" | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB_HOST="$(env_value DB_HOST)"; DB_PORT="$(env_value DB_PORT)"
DB_NAME="$(env_value DB_DATABASE)"; DB_USER="$(env_value DB_USERNAME)"; DB_PASS="$(env_value DB_PASSWORD)"

# Password goes in a temporary file, so it never shows in the process list.
CNF="$(mktemp)"; trap 'rm -f "$CNF"' EXIT; chmod 600 "$CNF"
printf "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n" "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}" "$DB_USER" "$DB_PASS" > "$CNF"

mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --no-tablespaces "$DB_NAME" \
  | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

tar -czf "$BACKUP_DIR/media-$STAMP.tar.gz" -C "$APP_DIR/storage/app" public

find "$BACKUP_DIR" -name 'db-*.sql.gz' -mtime +$KEEP_DAYS -delete
find "$BACKUP_DIR" -name 'media-*.tar.gz' -mtime +$KEEP_DAYS -delete

echo "$(date '+%F %T') backup ok: db-$STAMP.sql.gz, media-$STAMP.tar.gz"
