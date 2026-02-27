#!/usr/bin/env bash
set -euo pipefail

# ====== CONFIG ======
APP_DIR="/var/www/shop-mobile"
BACKUP_ROOT="/opt/backups/shop-mobile"
DB_NAME="shop_mobile"
DB_USER="shopmobile"
DB_PASS="CHANGE_ME"

DB_DIR="$BACKUP_ROOT/db"
UP_DIR="$BACKUP_ROOT/uploads"

usage() {
  echo "Uso: $0 --db <arquivo.sql.gz|arquivo.sql> --uploads <arquivo.tar.gz> [--force]"
  echo "Exemplo:"
  echo "  $0 --db /opt/backups/shop-mobile/db/shop_mobile_2026-02-27_02-30-00.sql.gz --uploads /opt/backups/shop-mobile/uploads/uploads_2026-02-27_02-30-00.tar.gz --force"
  exit 1
}

DB_FILE=""
UP_FILE=""
FORCE="false"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --db)
      DB_FILE="${2:-}"; shift 2 ;;
    --uploads)
      UP_FILE="${2:-}"; shift 2 ;;
    --force)
      FORCE="true"; shift ;;
    *)
      usage ;;
  esac
done

[[ -z "$DB_FILE" || -z "$UP_FILE" ]] && usage
[[ ! -f "$DB_FILE" ]] && { echo "Arquivo de banco não encontrado: $DB_FILE"; exit 1; }
[[ ! -f "$UP_FILE" ]] && { echo "Arquivo de uploads não encontrado: $UP_FILE"; exit 1; }

if [[ "$FORCE" != "true" ]]; then
  echo "ATENÇÃO: isso vai sobrescrever banco e uploads atuais."
  read -r -p "Digite RESTORE para continuar: " CONFIRM
  [[ "$CONFIRM" != "RESTORE" ]] && { echo "Operação cancelada."; exit 1; }
fi

TMP_SQL=""
if [[ "$DB_FILE" == *.gz ]]; then
  TMP_SQL="/tmp/restore_$(date +%s).sql"
  gzip -dc "$DB_FILE" > "$TMP_SQL"
else
  TMP_SQL="$DB_FILE"
fi

echo "[1/3] Restaurando banco..."
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" "$DB_NAME" < "$TMP_SQL"

if [[ "$DB_FILE" == *.gz ]]; then
  rm -f "$TMP_SQL"
fi

echo "[2/3] Restaurando uploads..."
tar -xzf "$UP_FILE" -C "$APP_DIR"

if command -v chown >/dev/null 2>&1; then
  chown -R www-data:www-data "$APP_DIR/uploads" || true
fi

echo "[3/3] Finalizado com sucesso."
echo "Restore concluído."
