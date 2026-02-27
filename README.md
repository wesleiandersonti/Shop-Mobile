# Shop Mobile

Sistema de loja virtual mobile-first com painel administrativo e integração com WhatsApp (Evolution API), desenvolvido em PHP + MySQL.

---

## 📌 Visão Geral

O **Shop Mobile** é uma aplicação web focada em vendas rápidas por catálogo, com fluxo simples para:

- Exibição de produtos e categorias
- Carrinho de compras
- Registro de pedidos
- Painel administrativo para gestão de:
  - produtos
  - categorias
  - pedidos
  - clientes
  - sliders
  - configurações da loja
- Envio de mensagens via WhatsApp usando **Evolution API**

---

## 🧱 Stack

- **Backend:** PHP (PDO)
- **Banco de dados:** MySQL/MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Web server:** Nginx ou Apache
- **Integrações:** Evolution API (WhatsApp)

---

## 📂 Estrutura de Pastas

```text
.
├── admin/                # Painel administrativo
├── css/                  # Estilos da aplicação
├── database/             # Conexão e scripts SQL
├── include/              # Funções auxiliares (ex: carrinho)
├── includes/             # Componentes e autenticação
├── js/                   # Scripts JS
├── uploads/              # Mídias (produtos/sliders)
├── index.php             # Página inicial da loja
├── product.php           # Página de produto
├── cart.php              # Carrinho
├── save_order.php        # Persistência de pedidos
└── search_products.php   # Busca de produtos
```

---

## ⚙️ Requisitos

- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.5+
- Extensões PHP: `pdo`, `pdo_mysql`, `curl`, `mbstring`, `json`
- Nginx/Apache

---

## 🚀 Instalação (Local/Servidor)

> Também adotaremos instaladores no padrão **Proxmox community-style** para todos os sistemas (ver `installers/README.md`).

### 1) Clonar repositório

```bash
git clone https://github.com/wesleiandersonti/Shop-Mobile.git
cd Shop-Mobile
```

### 2) Criar banco e importar schema

```bash
mysql -u root -p -e "CREATE DATABASE shop_mobile CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p shop_mobile < database/ecommerce.sql
```

> Se você utiliza outro dump, importe o arquivo SQL correspondente.

### 3) Configurar conexão com banco

Edite `database/db_connect.php` com os dados do seu ambiente:

- host
- database
- usuário
- senha

### 4) Ajustar permissões de uploads

```bash
chmod -R 775 uploads
```

### 5) Subir servidor web

Aponte o document root para a pasta do projeto e teste acesso no navegador.

---

## 🔐 Segurança (Recomendado para Produção)

Antes de publicar em ambiente real:

1. Remover credenciais hardcoded de arquivos versionados
2. Usar variáveis de ambiente (`.env`) para segredos
3. Não versionar:
   - logs
   - backups SQL com dados reais
   - uploads sensíveis
4. Restringir endpoints administrativos
5. Adicionar proteção CSRF em formulários admin
6. Habilitar TLS/HTTPS na borda
7. Configurar firewall e fail2ban no servidor

---

## 🌐 Deploy recomendado (cenário VM)

- VM Ubuntu 24.04 LTS
- Nginx + PHP-FPM + MySQL
- Exposição via cloudflared tunnel (sem abrir portas públicas diretas)
- Backup diário de banco + diretório `uploads/`
- Instalação padronizada via scripts no estilo community (`installers/`)

---

## 🧪 Checklist rápido de validação

- [ ] Login admin funcionando
- [ ] Cadastro/edição de produto
- [ ] Carrinho adicionando/removendo itens
- [ ] Pedido sendo salvo no banco
- [ ] Mensagem Evolution API enviada com sucesso
- [ ] Busca de produtos operacional

---

## 🛣️ Roadmap sugerido

- Refatoração de configuração para `.env`
- Hardening de autenticação e sessão
- Token CSRF no admin
- Pipeline CI/CD
- Testes automatizados de fluxos críticos
- Suite de instaladores padrão (`installers/*.sh`) para provisionamento Proxmox

---

## 📄 Licença

Uso privado/proprietário (ajuste conforme sua política de distribuição).

---

## 👤 Autor

**Weslei Anderson**  
GitHub: https://github.com/wesleiandersonti
