# Guia de Instalação - Sistema M3U Streaming

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior (ou MariaDB 10.3+)
- Apache ou Nginx
- Extensões PHP necessárias:
  - PDO
  - PDO_MySQL
  - cURL
  - JSON
  - mbstring

## Passo 1: Configurar o MySQL

### 1.1 Criar o Banco de Dados

Acesse o MySQL via terminal ou phpMyAdmin:

\`\`\`bash
mysql -u root -p
\`\`\`

Execute os comandos:

\`\`\`sql
CREATE DATABASE m3u_streaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'm3u_user'@'localhost' IDENTIFIED BY 'sua_senha_segura';
GRANT ALL PRIVILEGES ON m3u_streaming.* TO 'm3u_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
\`\`\`

### 1.2 Importar as Tabelas

Importe os scripts SQL na ordem:

\`\`\`bash
mysql -u m3u_user -p m3u_streaming < scripts/001_create_tables.sql
mysql -u m3u_user -p m3u_streaming < scripts/002_add_banners_and_settings.sql
\`\`\`

Ou via phpMyAdmin:
1. Acesse phpMyAdmin
2. Selecione o banco `m3u_streaming`
3. Vá em "Importar"
4. Importe primeiro `001_create_tables.sql`
5. Depois importe `002_add_banners_and_settings.sql`

## Passo 2: Configurar o Sistema

### 2.1 Editar Configurações do Banco

Edite o arquivo `config/database.php`:

\`\`\`php
private $host = 'localhost';
private $db_name = 'm3u_streaming';
private $username = 'm3u_user';        // Seu usuário MySQL
private $password = 'sua_senha_segura'; // Sua senha MySQL
\`\`\`

### 2.2 Editar Configurações Gerais

Edite o arquivo `config/config.php`:

\`\`\`php
define('SITE_URL', 'http://seudominio.com'); // Seu domínio
define('JWT_SECRET', 'troque-por-uma-chave-aleatoria-segura');
\`\`\`

## Passo 3: Configurar o Servidor Web

### Apache (.htaccess já incluído)

Certifique-se que o `mod_rewrite` está ativo:

\`\`\`bash
sudo a2enmod rewrite
sudo systemctl restart apache2
\`\`\`

Configure as permissões:

\`\`\`bash
sudo chown -R www-data:www-data /var/www/html/m3u-streaming
sudo chmod -R 755 /var/www/html/m3u-streaming
\`\`\`

### Nginx

Adicione ao seu arquivo de configuração:

\`\`\`nginx
server {
    listen 80;
    server_name seudominio.com;
    root /var/www/html/m3u-streaming;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
\`\`\`

## Passo 4: Criar Usuário Admin Inicial

Execute o script de instalação:

\`\`\`bash
php install.php
\`\`\`

Ou crie manualmente via MySQL:

\`\`\`sql
INSERT INTO admins (username, password, email, created_at) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@exemplo.com', NOW());
\`\`\`

**Credenciais padrão:**
- Usuário: `admin`
- Senha: `admin123`

**IMPORTANTE:** Altere a senha imediatamente após o primeiro login!

## Passo 5: Acessar o Sistema

### Painel Admin
Acesse: `http://seudominio.com/admin/login.php`

**Primeiro acesso:**
1. Login: `admin`
2. Senha: `admin123`
3. Vá em "Configurações" e altere a senha

### Área do Cliente
Acesse: `http://seudominio.com/`

## Passo 6: Configurar DNS e Clientes

### 6.1 Adicionar Servidores DNS

No painel admin:
1. Vá em "Configuração DNS"
2. Clique em "Adicionar DNS"
3. Preencha:
   - **Nome:** Nome do servidor (ex: "Servidor Principal")
   - **URL Base:** URL do servidor M3U (ex: `http://blder.xyz`)
   - **Endpoint:** Caminho do arquivo (ex: `/get.php`)
   - **Parâmetros Extras:** Parâmetros adicionais (ex: `type=m3u_plus&output=mpegts`)
   - **Status:** Ativo

### 6.2 Adicionar Clientes

1. Vá em "Gerenciar Clientes"
2. Clique em "Adicionar Cliente"
3. Preencha:
   - **Username:** Nome de usuário (números ou letras)
   - **Password:** Senha (números ou letras)
   - **Servidor DNS:** Selecione o DNS
   - **Data de Expiração:** Data de vencimento
   - **Status:** Ativo

### 6.3 Configurar Banners (Opcional)

1. Vá em "Gerenciar Banners"
2. Clique em "Adicionar Banner"
3. Configure:
   - **Título:** Nome do banner
   - **URL da Imagem:** Link da imagem
   - **Posição:** Onde aparece (após login, topo, antes do player)
   - **Link:** URL de destino (opcional)
   - **Status:** Ativo

## Verificação da Instalação

### Teste 1: Banco de Dados
\`\`\`bash
mysql -u m3u_user -p m3u_streaming -e "SHOW TABLES;"
\`\`\`

Deve mostrar 6 tabelas:
- admins
- banners
- client_credentials
- dns_configs
- sessions
- settings

### Teste 2: PHP
Crie um arquivo `test.php`:

\`\`\`php
<?php
phpinfo();
\`\`\`

Verifique se as extensões PDO e PDO_MySQL estão ativas.

### Teste 3: Conexão com Banco
Crie um arquivo `test-db.php`:

\`\`\`php
<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
echo $conn ? "Conexão OK!" : "Erro na conexão!";
\`\`\`

## Solução de Problemas

### Erro: "Connection Error"
- Verifique as credenciais em `config/database.php`
- Confirme que o MySQL está rodando: `sudo systemctl status mysql`
- Teste a conexão: `mysql -u m3u_user -p`

### Erro: "Access denied"
- Verifique as permissões do usuário MySQL
- Execute novamente os comandos GRANT

### Erro: "Table doesn't exist"
- Importe novamente os scripts SQL
- Verifique se está no banco correto

### Página em branco
- Verifique os logs: `tail -f /var/log/apache2/error.log`
- Ative display_errors em `config/config.php`

### .htaccess não funciona
- Ative mod_rewrite: `sudo a2enmod rewrite`
- Verifique AllowOverride no Apache config

## Segurança em Produção

Antes de colocar em produção:

1. **Desative erros PHP:**
\`\`\`php
// Em config/config.php
error_reporting(0);
ini_set('display_errors', 0);
\`\`\`

2. **Altere JWT_SECRET:**
\`\`\`php
define('JWT_SECRET', 'gere-uma-chave-aleatoria-longa-e-segura');
\`\`\`

3. **Use HTTPS:**
\`\`\`bash
sudo certbot --apache -d seudominio.com
\`\`\`

4. **Proteja arquivos sensíveis:**
\`\`\`apache
# No .htaccess
<FilesMatch "^(config|scripts)">
    Require all denied
</FilesMatch>
