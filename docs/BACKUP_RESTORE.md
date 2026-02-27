# Backup & Restore (Shop Mobile)

Guia rápido para backup e restauração do sistema.

## O que precisa de backup

1. **Banco de dados** (`shop_mobile`)
2. **Uploads** (`uploads/`)
3. Opcional: arquivos de configuração locais (`database/db_connect.php`)

---

## Backup manual

### Banco
```bash
mysqldump -u shopmobile -p shop_mobile > /opt/backups/shop-mobile/db/shop_mobile_$(date +%F_%H-%M).sql
```

### Uploads
```bash
tar -czf /opt/backups/shop-mobile/uploads/uploads_$(date +%F_%H-%M).tar.gz -C /var/www/shop-mobile uploads
```

---

## Restore

### Restaurar banco
```bash
mysql -u shopmobile -p shop_mobile < /opt/backups/shop-mobile/db/ARQUIVO.sql
```

### Restaurar uploads
```bash
tar -xzf /opt/backups/shop-mobile/uploads/ARQUIVO.tar.gz -C /var/www/shop-mobile
sudo chown -R www-data:www-data /var/www/shop-mobile/uploads
```

---

## Política recomendada

- Backup diário automático
- Retenção de 14 a 30 dias
- Teste de restore 1x por mês
- Cópia externa (NAS/outro host)
