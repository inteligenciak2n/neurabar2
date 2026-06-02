# sail/postgresql/create-support-database.sh
#!/bin/bash

set -e

create_db_if_not_exists() {
    local DB_NAME="$1"
    local DB_EXISTS
    DB_EXISTS=$(psql -U "$POSTGRES_USER" -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'")

    if [ "$DB_EXISTS" != "1" ]; then
        echo "Creating database $DB_NAME..."
        psql -U "$POSTGRES_USER" -c "CREATE DATABASE $DB_NAME"
        echo "Database $DB_NAME created successfully"
    else
        echo "Database $DB_NAME already exists"
    fi

    psql -U "$POSTGRES_USER" -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $POSTGRES_USER"
}

# Banco central SaaS
create_db_if_not_exists "laravel_saas"

# Bancos operacionais compartilhados
create_db_if_not_exists "operation_default_1"
create_db_if_not_exists "operation_default_2"