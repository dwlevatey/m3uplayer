#!/bin/bash

echo "==========================================="
echo "  Sistema M3U Streaming - Instalação"
echo "==========================================="
echo ""

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    echo "⚠ Execute como root: sudo ./install.sh"
    exit 1
fi

# Detectar sistema operacional
if [ -f /etc/debian_version ]; then
    OS="debian"
    echo "✓ Sistema detectado: Debian/Ubuntu"
elif [ -f /etc/redhat-release ]; then
    OS="redhat"
    echo "✓ Sistema detectado: RedHat/CentOS"
else
    echo "❌ Sistema operacional não suportado"
    exit 1
fi

# Instalar dependências
echo ""
echo "1. Instalando dependências..."

if [ "$OS" = "debian" ]; then
    apt-get update
    apt-get install -y apache2 php php-mysql php-curl php-json php-mbstring mysql-server
    a2enmod rewrite
    systemctl restart apache2
elif [ "$OS" = "redhat" ]; then
    yum install -y httpd php php-mysqlnd php-curl php-json php-mbstring mariadb-server
    systemctl start httpd
    systemctl start mariadb
    systemctl enable httpd
    systemctl enable mariadb
fi

echo "✓ Dependências instaladas"

# Configurar MySQL
echo ""
echo "2. Configurando MySQL..."
echo "Digite a senha root do MySQL:"
read -s MYSQL_ROOT_PASSWORD

mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS m3u_streaming CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'm3u_user'@'localhost' IDENTIFIED BY 'M3uStr3am!ng@2024';
GRANT ALL PRIVILEGES ON m3u_streaming.* TO 'm3u_user'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "✓ Banco de dados configurado"

# Copiar arquivos
echo ""
echo "3. Copiando arquivos..."
INSTALL_DIR="/var/www/html/m3u-streaming"
mkdir -p $INSTALL_DIR
cp -r * $INSTALL_DIR/
chown -R www-data:www-data $INSTALL_DIR
chmod -R 755 $INSTALL_DIR

echo "✓ Arquivos copiados para $INSTALL_DIR"

# Executar instalação PHP
echo ""
echo "4. Executando instalação do sistema..."
cd $INSTALL_DIR
php install.php

echo ""
echo "==========================================="
echo "  ✓ INSTALAÇÃO CONCLUÍDA!"
echo "==========================================="
echo ""
echo "Acesse: http://seu-ip/m3u-streaming/admin/login.php"
echo "Login: admin | Senha: admin123"
echo ""
