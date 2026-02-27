#!/usr/bin/env bash
set -euo pipefail

# ====== CONFIG ======
APP_DIR="/var/www/shop-mobile"
BACKUP_ROOT="/opt/backups/shop-mobile"
DB_NAME="shop_mobile"
DB_USER="shopmobile"
DB_PASS="CHANGE_ME"
RETENTION_DAYS=14
DATE_TAG="$(date +%F_%H-%M-%S)"

DB_DIR="$BACKUP_ROOT/db"
UP_DIR="$BACKUP_ROOT/uploads"
LOG_DIR="$BACKUP_ROOT/logs"

mkdir -p "$DB_DIR" "$UP_DIR" "$LOG_DIR"

# ====== BACKUP DB ======
DB_FILE="$DB_DIR/${DB_NAME}_${DATE_TAG}.sql"
MYSQL_PWD="$DB_PASS" mysqldump -u "$DB_USER" "$DB_NAME" > "$DB_FILE"
gzip -f "$DB_FILE"

# ====== BACKUP UPLOADS ======
UP_FILE="$UP_DIR/uploads_${DATE_TAG}.tar.gz"
tar -czf "$UP_FILE" -C "$APP_DIR" uploads

# ====== CLEANUP OLD FILES ======
find "$DB_DIR" -type f -name "*.sql.gz" -mtime +"$RETENTION_DAYS" -delete
find "$UP_DIR" -type f -name "*.tar.gz" -mtime +"$RETENTION_DAYS" -delete

# ====== LOG ======
echo "[$(date '+%F %T')] Backup OK | DB=$(basename "$DB_FILE").gz | UP=$(basename "$UP_FILE")" >> "$LOG_DIR/backup.log"
