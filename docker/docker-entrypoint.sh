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

# Executa o comando original
exec "$@"