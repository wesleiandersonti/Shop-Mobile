# Agendar Backup Diário (Cron)

## 1) Dar permissão ao script

```bash
sudo mkdir -p /opt/backups/shop-mobile
sudo chmod +x /var/www/shop-mobile/scripts/backup.sh
```

## 2) Ajustar senha do banco no script

Editar:

`/var/www/shop-mobile/scripts/backup.sh`

Trocar:

`DB_PASS="CHANGE_ME"`

pela senha real do usuário `shopmobile`.

## 3) Criar cron diário

```bash
sudo crontab -e
```

Adicionar linha (todo dia às 02:30):

```cron
30 2 * * * /var/www/shop-mobile/scripts/backup.sh >> /opt/backups/shop-mobile/logs/cron.log 2>&1
```

## 4) Teste manual

```bash
sudo /var/www/shop-mobile/scripts/backup.sh
ls -lah /opt/backups/shop-mobile/db
ls -lah /opt/backups/shop-mobile/uploads
```

## 5) Restore rápido

Veja:

- `docs/BACKUP_RESTORE.md`
