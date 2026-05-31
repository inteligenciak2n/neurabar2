# sail/postgresql/create-support-database.sh
#!/bin/bash

set -e

# Verificar se o banco já existe
DB_EXISTS=$(psql -U "$POSTGRES_USER" -tAc "SELECT 1 FROM pg_database WHERE datname='laravel_support'")

if [ "$DB_EXISTS" != "1" ]; then
    echo "Creating database laravel_support..."
    psql -U "$POSTGRES_USER" -c "CREATE DATABASE laravel_support"
    echo "Database laravel_support created successfully"
else
    echo "Database laravel_support already exists"
fi

# Conceder privilégios (sempre executa)
psql -U "$POSTGRES_USER" -c "GRANT ALL PRIVILEGES ON DATABASE laravel_support TO $POSTGRES_USER"