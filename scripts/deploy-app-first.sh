#!/usr/bin/env bash
#
# Sincroniza a aplicação com o servidor de produção e executa o ciclo de deploy
# do Laravel na stack nativa. Assets Vite continuam sob responsabilidade de
# scripts/deploy-assets.sh.
#
# Uso:
#   scripts/deploy-app-first.sh
#
# Variáveis opcionais:
#   REMOTE_HOST=neurabar
#   REMOTE_PATH=/var/www/neurabar
#   REMOTE_PHP=/usr/bin/php8.3
#   REMOTE_COMPOSER=/usr/local/bin/composer
#   HEALTHCHECK_URL=https://app.neurabar.com/up

set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

REMOTE_HOST="${REMOTE_HOST:-neurabar}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/neurabar}"
REMOTE_PHP="${REMOTE_PHP:-/usr/bin/php8.3}"
REMOTE_COMPOSER="${REMOTE_COMPOSER:-/usr/local/bin/composer}"
SSH_OPTIONS=(-o BatchMode=yes -o ConnectTimeout=10)
DEPLOY_STARTED=false

handle_error() {
        local exit_code=$?

        if [[ "$DEPLOY_STARTED" == true ]]; then
                cat >&2 <<EOF
Erro: o deploy falhou após ativar o modo de manutenção.
A aplicação foi mantida indisponível para evitar servir uma versão incompleta.

Após corrigir a causa, finalize manualmente com:
    ssh $REMOTE_HOST "cd '$REMOTE_PATH' && '$REMOTE_PHP' artisan up"
EOF
        fi

        exit "$exit_code"
}

trap handle_error ERR

echo "==> Validando o ambiente remoto em $REMOTE_HOST..."
if ! ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" \
    "test -d '$REMOTE_PATH' && test -w '$REMOTE_PATH' && test -x '$REMOTE_PHP' && test -x '$REMOTE_COMPOSER' && test \"\$(dpkg --print-architecture)\" = amd64"; then
    cat >&2 <<EOF
Erro: o servidor não está acessível ou o ambiente remoto está incompleto.

Confirme no servidor:
  - $REMOTE_PATH existe e permite escrita pelo usuário SSH;
  - $REMOTE_PHP existe e é executável;
    - $REMOTE_COMPOSER existe e é executável;
    - a EC2 usa arquitetura x86_64/amd64.
EOF
    exit 1
fi

if ! ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" 'sudo -n true'; then
    cat >&2 <<'EOF'
Erro: o usuário remoto precisa executar sudo sem prompt para preparar as
permissões do Laravel. Configure a permissão necessária antes do deploy.
EOF
    exit 1
fi

echo "==> Sincronizando os arquivos da aplicação..."
rsync -az --delete --checksum \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='.git/' \
    --exclude='.github/' \
    --exclude='.idea/' \
    --exclude='.vscode/' \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='storage/' \
    --exclude='bootstrap/cache/' \
    --exclude='public/build/' \
    --exclude='public/hot' \
    --exclude='public/storage' \
    --exclude='tests/' \
    --exclude='compose.yaml' \
    --exclude='compose.prod.yaml' \
    --exclude='Dockerfile*' \
    --exclude='sail/' \
    ./ "$REMOTE_HOST:$REMOTE_PATH/"

echo "==> Preparando diretórios graváveis do Laravel..."
ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" \
    "mkdir -p \
        '$REMOTE_PATH/bootstrap/cache' \
        '$REMOTE_PATH/storage/framework/cache/data' \
        '$REMOTE_PATH/storage/framework/sessions' \
        '$REMOTE_PATH/storage/framework/views' \
        '$REMOTE_PATH/storage/app/public' \
        '$REMOTE_PATH/storage/app/private' \
        '$REMOTE_PATH/storage/logs' && \
    sudo -n setfacl -R -m u:ubuntu:rwx,u:www-data:rwx \
        '$REMOTE_PATH/bootstrap/cache' '$REMOTE_PATH/storage' && \
    sudo -n setfacl -dR -m u:ubuntu:rwx,u:www-data:rwx \
        '$REMOTE_PATH/bootstrap/cache' '$REMOTE_PATH/storage'"

echo "==> Primeiro envio concluído; os diretórios do Laravel estão preparados."

