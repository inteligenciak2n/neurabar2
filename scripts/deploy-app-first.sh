#!/usr/bin/env bash
#
# Sincroniza a aplicação com o servidor de produção e executa o ciclo de deploy
# do Laravel na stack nativa. Assets Vite continuam sob responsabilidade de
# scripts/deploy-assets.sh.
#
# Uso:
#   scripts/deploy-app.sh
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
    "test -d '$REMOTE_PATH' && test -w '$REMOTE_PATH' && test -x '$REMOTE_PHP' && test -x '$REMOTE_COMPOSER'"; then
    cat >&2 <<EOF
Erro: o servidor não está acessível ou o ambiente remoto está incompleto.

Confirme no servidor:
  - $REMOTE_PATH existe e permite escrita pelo usuário SSH;
  - $REMOTE_PHP existe e é executável;
  - $REMOTE_COMPOSER existe e é executável.
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

DEPLOY_STARTED=false

