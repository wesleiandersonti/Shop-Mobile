#!/usr/bin/env bash
# Shop Mobile VM Installer (Ubuntu 24.04)
# Padrão community-style (interactive)

set -euo pipefail

YW="\e[33m"; GN="\e[1;92m"; RD="\e[1;31m"; BL="\e[36m"; CL="\e[0m"
CM="  ✔️  "; CROSS="  ✖️  "; INFO="  💡  "

APP_DIR="/var/www/shop-mobile"
REPO_URL_DEFAULT="https://github.com/wesleiandersonti/Shop-Mobile.git"
DB_NAME_DEFAULT="shop_mobile"
DB_USER_DEFAULT="shopmobile"
DOMAIN_DEFAULT="shop.local"

msg_info(){ echo -e "${YW}${INFO}$1${CL}"; }
msg_ok(){ echo -e "${GN}${CM}$1${CL}"; }
msg_error(){ echo -e "${RD}${CROSS}$1${CL}"; }

check_root(){
  if [[ "$(id -u)" -ne 0 ]]; then
    msg_error "Execute como root."
    exit 1
  fi
}

need_cmd(){ command -v "$1" >/dev/null 2>&1 || { msg_error "Comando ausente: $1"; exit 1; }; }

ask_settings(){
  if whiptail --title "Shop Mobile VM Installer" --yesno "Usar configurações padrão?" 10 60; then
    REPO_URL="$REPO_URL_DEFAULT"
    DOMAIN="$DOMAIN_DEFAULT"
    DB_NAME="$DB_NAME_DEFAULT"
    DB_USER="$DB_USER_DEFAULT"
    DB_PASS="shopmobile123"
    PHP_SOCK="php8.3-fpm"
  else
    REPO_URL=$(whiptail --inputbox "Git repo URL" 10 80 "$REPO_URL_DEFAULT" 3>&1 1>&2 2>&3)
    DOMAIN=$(whiptail --inputbox "Domínio (cloudflared)" 10 80 "$DOMAIN_DEFAULT" 3>&1 1>&2 2>&3)
    DB_NAME=$(whiptail --inputbox "Database name" 10 80 "$DB_NAME_DEFAULT" 3>&1 1>&2 2>&3)
    DB_USER=$(whiptail --inputbox "Database user" 10 80 "$DB_USER_DEFAULT" 3>&1 1>&2 2>&3)
    DB_PASS=$(whiptail --passwordbox "Database password" 10 80 3>&1 1>&2 2>&3)
    PHP_SOCK=$(whiptail --inputbox "PHP-FPM socket (php8.3-fpm ou php8.2-fpm)" 10 80 "php8.3-fpm" 3>&1 1>&2 2>&3)
  fi
}

install_stack(){
  msg_info "Atualizando sistema"
  apt update -y && apt upgrade -y
  timedatectl set-timezone America/Sao_Paulo || true

  msg_info "Instalando pacotes"
  apt install -y nginx mariadb-server git unzip curl ufw php-fpm php-mysql php-curl php-mbstring php-xml php-zip
}

setup_db(){
  msg_info "Configurando banco"
  mysql -e "CREATE DATABASE IF NOT EXISTS \\`${DB_NAME}\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
  mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
  mysql -e "GRANT ALL PRIVILEGES ON \\`${DB_NAME}\\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
}

deploy_app(){
  msg_info "Baixando aplicação"
  rm -rf "$APP_DIR"
  git clone "$REPO_URL" "$APP_DIR"
  chown -R www-data:www-data "$APP_DIR"

  msg_info "Importando schema"
  if [[ -f "$APP_DIR/database/ecommerce.sql" ]]; then
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$APP_DIR/database/ecommerce.sql" || true
  fi

  msg_info "Ajustando db_connect.php"
  if [[ -f "$APP_DIR/database/db_connect.php" ]]; then
    sed -i "s/\$servername = .*/\$servername = \"localhost\";/" "$APP_DIR/database/db_connect.php" || true
    sed -i "s/\$username = .*/\$username = \"${DB_USER}\";/" "$APP_DIR/database/db_connect.php" || true
    sed -i "s/\$password = .*/\$password = \"${DB_PASS}\";/" "$APP_DIR/database/db_connect.php" || true
    sed -i "s/\$dbname = .*/\$dbname = \"${DB_NAME}\";/" "$APP_DIR/database/db_connect.php" || true
  fi

  mkdir -p "$APP_DIR/uploads"
  chown -R www-data:www-data "$APP_DIR/uploads"
  chmod -R 775 "$APP_DIR/uploads"
}

setup_nginx(){
  msg_info "Configurando Nginx"
  cat >/etc/nginx/sites-available/shop-mobile.conf <<EOF
server {
    listen 80;
    server_name ${DOMAIN};

    root ${APP_DIR};
    index index.php index.html;
    client_max_body_size 50M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/${PHP_SOCK}.sock;
    }

    location ~ /\\.ht {
        deny all;
    }
}
EOF

  ln -sf /etc/nginx/sites-available/shop-mobile.conf /etc/nginx/sites-enabled/shop-mobile.conf
  rm -f /etc/nginx/sites-enabled/default
  nginx -t
  systemctl reload nginx
}

setup_firewall(){
  msg_info "Configurando firewall"
  ufw allow OpenSSH
  ufw allow 80/tcp
  ufw --force enable
}

summary(){
  IP_LOCAL=$(hostname -I | awk '{print $1}')
  echo
  msg_ok "Instalação concluída"
  echo -e "${BL}App:${CL} ${APP_DIR}"
  echo -e "${BL}URL local:${CL} http://${IP_LOCAL}"
  echo -e "${BL}Domínio:${CL} http://${DOMAIN}"
  echo -e "${BL}Cloudflared:${CL} ${DOMAIN} -> http://${IP_LOCAL}:80"
  echo -e "${BL}DB:${CL} ${DB_NAME} / ${DB_USER}"
}

main(){
  check_root
  need_cmd whiptail
  ask_settings
  install_stack
  setup_db
  deploy_app
  setup_nginx
  setup_firewall
  summary
}

main "$@"
