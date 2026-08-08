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
HEALTHCHECK_URL="${HEALTHCHECK_URL:-https://app.neurabar.com/up}"
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

echo "==> Validando arquivos locais..."
for required_file in artisan composer.json composer.lock; do
    if [[ ! -f "$required_file" ]]; then
        echo "Erro: $required_file não encontrado em $PROJECT_ROOT." >&2
        exit 1
    fi
done

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

if ! ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" "test -f '$REMOTE_PATH/.env'"; then
    echo "Erro: $REMOTE_PATH/.env não existe no servidor; deploy cancelado." >&2
    exit 1
fi

if ! ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" 'sudo -n true'; then
    cat >&2 <<'EOF'
Erro: o usuário remoto precisa executar sudo sem prompt para recarregar o
PHP-FPM. Configure a permissão necessária no sudoers antes do deploy.
EOF
    exit 1
fi

echo "==> Colocando a aplicação em modo de manutenção..."
ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" \
    "cd '$REMOTE_PATH' && '$REMOTE_PHP' artisan down --retry=60"
DEPLOY_STARTED=true

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

echo "==> Instalando dependências e finalizando o deploy remoto..."
ssh "${SSH_OPTIONS[@]}" "$REMOTE_HOST" \
    "REMOTE_PATH='$REMOTE_PATH' REMOTE_PHP='$REMOTE_PHP' REMOTE_COMPOSER='$REMOTE_COMPOSER' bash -se" <<'REMOTE_SCRIPT'
set -Eeuo pipefail

cd "$REMOTE_PATH"

"$REMOTE_COMPOSER" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

"$REMOTE_PHP" artisan storage:link --force
"$REMOTE_PHP" artisan assets:link
"$REMOTE_PHP" artisan db:migrate-all --force
"$REMOTE_PHP" artisan optimize
"$REMOTE_PHP" artisan view:cache
"$REMOTE_PHP" artisan queue:restart

sudo -n systemctl reload php8.3-fpm

"$REMOTE_PHP" artisan up
REMOTE_SCRIPT
DEPLOY_STARTED=false

echo "==> Validando a saúde da aplicação..."
if ! curl --fail --silent --show-error --retry 5 --retry-delay 2 \
    "$HEALTHCHECK_URL" >/dev/null; then
    cat >&2 <<EOF
Erro: o deploy terminou, mas o health check falhou.
Verifique os logs em $REMOTE_HOST:$REMOTE_PATH/storage/logs/laravel.log.
EOF
    exit 1
fi

echo "==> Deploy da aplicação concluído com sucesso."