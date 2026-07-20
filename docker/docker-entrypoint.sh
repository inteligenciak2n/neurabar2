#!/bin/bash
set -e

# Ajusta permissões para o diretório montado
if [ -d "/var/www/html/storage" ]; then
    echo "Ajustando permissões do storage..."
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    
    # Cria diretórios necessários se não existirem
    mkdir -p /var/www/html/storage/framework/{sessions,views,cache,testing}
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/storage/tmp
    
    chown -R www-data:www-data /var/www/html/storage/framework /var/www/html/storage/tmp 2>/dev/null || true
    chmod -R 775 /var/www/html/storage/framework /var/www/html/storage/tmp 2>/dev/null || true
    
    # Define variáveis de ambiente para o PHP
    export TMPDIR=/var/www/html/storage/tmp
    export TEMP=/var/www/html/storage/tmp
    export TMP=/var/www/html/storage/tmp
fi

# Limpa cache de views para evitar problemas
if [ -f "/var/www/html/artisan" ]; then
    echo "Limpando cache de views..."
    php /var/www/html/artisan view:clear 2>/dev/null || true
    php /var/www/html/artisan cache:clear 2>/dev/null || true
fi

# Executa o comando original
exec "$@"