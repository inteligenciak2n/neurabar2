#!/usr/bin/env bash
#
# Builda os assets do frontend localmente usando as vars de .env.production
# (Vite carrega .env.production automaticamente no modo "production", que é
# o modo padrão do `vite build`) e sincroniza public/build/ para o servidor
# via rsync, sem precisar reconstruir a imagem Docker inteira.
#
# Uso:
#   scripts/deploy-assets.sh
#
# Variáveis de ambiente opcionais para sobrescrever os padrões:
#   REMOTE_HOST=neurabar REMOTE_PATH=/var/www/neurabar/public scripts/deploy-assets.sh

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

REMOTE_HOST="${REMOTE_HOST:-neurabar}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/neurabar/public}"
ENV_FILE="${ENV_FILE:-.env.production}"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Erro: $ENV_FILE não encontrado em $PROJECT_ROOT" >&2
    exit 1
fi

echo "==> Verificando conexão SSH com $REMOTE_HOST..."
if ! ssh -o BatchMode=yes -o ConnectTimeout=5 "$REMOTE_HOST" 'test -d '"$REMOTE_PATH"''; then
    echo "Erro: não foi possível conectar em $REMOTE_HOST ou $REMOTE_PATH não existe." >&2
    exit 1
fi

echo "==> Instalando dependências do frontend..."
npm ci

echo "==> Buildando assets com as variáveis de $ENV_FILE (modo production do Vite)..."
npm run build

if [[ ! -d "public/build" ]]; then
    echo "Erro: public/build não foi gerado pelo build." >&2
    exit 1
fi

echo "==> Sincronizando public/build/ para $REMOTE_HOST:$REMOTE_PATH/build/..."
rsync -az --delete \
    public/build/ \
    "$REMOTE_HOST:$REMOTE_PATH/build/"

echo "==> Deploy de assets concluído com sucesso."
