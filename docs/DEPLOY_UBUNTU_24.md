# Deploy Ubuntu 24.04 (Shop Mobile)

Guia objetivo para publicar o Shop Mobile em VM Ubuntu 24.04 LTS.

## 1) Pré-requisitos

- VM Ubuntu 24.04 atualizada
- Domínio/subdomínio (ou acesso por IP interno)
- Cloudflared Tunnel já funcional (opcional/recomendado)

## 2) Instalar stack base

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php-fpm php-mysql php-curl php-mbstring php-xml php-zip mariadb-server git unzip
```

## 3) Banco de dados

```bash
sudo mysql -e "CREATE DATABASE shop_mobile CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'shopmobile'@'localhost' IDENTIFIED BY 'SENHA_FORTE_AQUI';"
sudo mysql -e "GRANT ALL PRIVILEGES ON shop_mobile.* TO 'shopmobile'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

## 4) Clonar projeto

```bash
cd /var/www
sudo git clone https://github.com/wesleiandersonti/Shop-Mobile.git shop-mobile
sudo chown -R www-data:www-data /var/www/shop-mobile
```

## 5) Importar schema

```bash
cd /var/www/shop-mobile
mysql -u shopmobile -p shop_mobile < database/ecommerce.sql
```

## 6) Configurar conexão DB

Editar `database/db_connect.php` com os dados do servidor:

- host: `localhost`
- db: `shop_mobile`
- user: `shopmobile`
- pass: sua senha

## 7) Permissões

```bash
sudo mkdir -p /var/www/shop-mobile/uploads
sudo chown -R www-data:www-data /var/www/shop-mobile/uploads
sudo chmod -R 775 /var/www/shop-mobile/uploads
```

## 8) Nginx virtual host

```bash
sudo tee /etc/nginx/sites-available/shop-mobile.conf > /dev/null << 'EOF'
server {
    listen 80;
    server_name shop.seudominio.com;

    root /var/www/shop-mobile;
    index index.php index.html;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

sudo ln -s /etc/nginx/sites-available/shop-mobile.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

> Se sua VM usar PHP 8.2, ajuste `php8.3-fpm.sock` para `php8.2-fpm.sock`.

## 9) Firewall

```bash
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw enable
sudo ufw status
```

## 10) Publicar via Cloudflared

No conector (LXC), mapear:

- `shop.seudominio.com` → `http://IP_DA_VM:80`

## 11) Pós-deploy (obrigatório)

- Trocar credenciais padrão
- Validar login admin
- Testar criação de pedido
- Testar integração Evolution API
- Configurar backup diário:
  - banco (`mysqldump`)
  - diretório `uploads/`

## 12) Atualização de versão

```bash
cd /var/www/shop-mobile
sudo git pull origin main
sudo chown -R www-data:www-data /var/www/shop-mobile
sudo systemctl reload nginx php8.3-fpm
```

---

Se quiser, próxima etapa: arquivo de `backup.sh` + cron diário pronto.
