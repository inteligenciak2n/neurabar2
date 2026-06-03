# Guia de Deploy — NeuraBar (Produção)

> Domínios utilizados neste guia como referência:
> - **Landing page**: `neurabar.com` e `www.neurabar.com`
> - **Aplicação**: `app.neurabar.com`
>
> Substitua pelos seus domínios reais em todos os passos.

---

## Pré-requisitos

- **Servidor**: Ubuntu 22.04+ com ao menos 2 vCPUs e 4 GB RAM
- **Acesso**: SSH com usuário `sudo`
- **RDS PostgreSQL**: instância provisionada (AWS RDS, Supabase, Neon, etc.)
- **DNS**: registros A apontando `neurabar.com`, `www.neurabar.com` e `app.neurabar.com` para o IP público do servidor

---

## 1. Preparar o Servidor

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependências essenciais
sudo apt install -y curl git unzip nginx certbot python3-certbot-nginx

# Instalar Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Aplicar grupo sem precisar re-logar
newgrp docker
```

---

## 2. Clonar o Repositório e Configurar Ambiente

```bash
# Clonar o projeto
git clone https://github.com/sua-org/neurabar2.git /var/www/neurabar
cd /var/www/neurabar

# Copiar e editar o .env de produção
cp .env.example .env
nano .env
```

> **Se estiver usando git push (post-receive hook)**, garanta que o hook roda como o usuário correto
> e que os arquivos fiquem com a ownership certa:
>
> ```bash
> # Conteúdo do .git/hooks/post-receive no servidor
> #!/bin/sh
> GIT_WORK_TREE=/var/www/neurabar git checkout -f
> chown -R ubuntu:ubuntu /var/www/neurabar
> chmod -R o+rX /var/www/neurabar
> ```

### Corrigir permissões no servidor (obrigatório)

Se o diretório foi criado como root ou os arquivos foram checked out como root, corrija antes de subir os containers:

```bash
# Transferir ownership para o usuário ubuntu
sudo chown -R ubuntu:ubuntu /var/www/neurabar

# Garantir que storage/ e bootstrap/cache/ sejam graváveis pelo container (www-data = UID 33)
sudo chown -R ubuntu:www-data /var/www/neurabar/storage /var/www/neurabar/bootstrap/cache
chmod -R 775 /var/www/neurabar/storage /var/www/neurabar/bootstrap/cache
```

### Variáveis críticas do `.env` para produção

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.neurabar.com
LANDING_PAGE_URL=https://neurabar.com

APP_PORT=9090

# PostgreSQL externo (RDS)
DB_CONNECTION=saas
DB_HOST=<rds-endpoint>.rds.amazonaws.com
DB_PORT=5432
DB_USERNAME=<usuario>
DB_PASSWORD=<senha>
DB_SAAS_DATABASE=laravel_saas
DB_OP_DEFAULT_1_DATABASE=operation_default_1
DB_OP_DEFAULT_2_DATABASE=operation_default_2

# Redis local (container)
REDIS_HOST=redis
REDIS_PORT=6379

# WebSocket (Soketi)
PUSHER_APP_ID=neurabar
PUSHER_APP_KEY=<chave-aleatoria>
PUSHER_APP_SECRET=<segredo-aleatorio>
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Frontend aponta para HTTPS (Nginx faz o proxy WSS→WS internamente)
VITE_PUSHER_HOST=app.neurabar.com
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
BROADCAST_CONNECTION=pusher
```

---

## 3. Build e Subir os Containers Docker

O `Dockerfile.prod` usa **multi-stage build**: Stage 1 (Node 20) lê as variáveis `VITE_*` do `.env` e compila os assets do Vite; Stage 2 (PHP 8.2 + Apache) instala as dependências Composer. Nenhuma instalação manual prévia é necessária.

```bash
cd /var/www/neurabar

# Build completo + subir em background
docker compose -f compose.prod.yaml up -d --build

# Se o build falhar, rode com output completo para ver o erro exato:
docker compose -f compose.prod.yaml build --no-cache --progress=plain app 2>&1 | tee /tmp/build.log

# Verificar se todos os containers subiram
docker compose -f compose.prod.yaml ps

# Gerar a APP_KEY (somente na primeira vez)
docker compose -f compose.prod.yaml exec app php artisan key:generate

# Executar migrations
docker compose -f compose.prod.yaml exec app php artisan migrate --force

# Compilar caches de config, rotas, views e eventos
docker compose -f compose.prod.yaml exec app php artisan optimize
docker compose -f compose.prod.yaml exec app php artisan view:cache
```

---

## 5. Configurar Nginx (Sem SSL — primeiro passo)

Crie o arquivo de configuração inicial sem SSL para que o Certbot possa validar o domínio:

```bash
sudo nano /etc/nginx/sites-available/neurabar
```

Cole o conteúdo abaixo:

```nginx
# Mapeamento para upgrade de WebSocket
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

# ─── Landing Page ────────────────────────────────────────────────────────────
server {
    listen 80;
    server_name neurabar.com www.neurabar.com;

    root /var/www/neurabar/landingpage;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}

# ─── Aplicação + WebSocket ───────────────────────────────────────────────────
server {
    listen 80;
    server_name app.neurabar.com;

    # WebSocket / Soketi — rota Pusher Protocol (/app/<key> e /apps/<id>)
    location ~ ^/(app|apps)/ {
        proxy_pass         http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade           $http_upgrade;
        proxy_set_header   Connection        $connection_upgrade;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400s;
    }

    # Laravel — todas as demais rotas
    location / {
        proxy_pass         http://127.0.0.1:9090;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
    }
}
```

```bash
# Ativar o site e testar
sudo ln -s /etc/nginx/sites-available/neurabar /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. Instalar Certificados SSL (Let's Encrypt)

```bash
# Emitir certificados para todos os domínios de uma vez
sudo certbot --nginx \
    -d neurabar.com \
    -d www.neurabar.com \
    -d app.neurabar.com \
    --non-interactive \
    --agree-tos \
    --email contato@neurabar.com \
    --redirect
```

O Certbot irá:
1. Validar cada domínio via HTTP-01 challenge
2. Emitir os certificados em `/etc/letsencrypt/live/`
3. **Reescrever automaticamente** o arquivo do Nginx adicionando os blocos HTTPS (443) e redirect HTTP→HTTPS

### Verificar renovação automática

```bash
# Testar o processo de renovação (dry-run)
sudo certbot renew --dry-run

# Confirmar o timer systemd ativo
sudo systemctl status certbot.timer
```

---

## 7. Configuração Nginx Final (Pós-Certbot)

Após o Certbot modificar o arquivo, a configuração ficará similar a esta. Revise e ajuste se necessário:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

# ─── Landing Page — HTTP redirect ────────────────────────────────────────────
server {
    listen 80;
    server_name neurabar.com www.neurabar.com;
    return 301 https://$host$request_uri;
}

# ─── Landing Page — HTTPS ────────────────────────────────────────────────────
server {
    listen 443 ssl;
    server_name neurabar.com www.neurabar.com;

    ssl_certificate     /etc/letsencrypt/live/neurabar.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/neurabar.com/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam         /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/neurabar/landingpage;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}

# ─── Aplicação — HTTP redirect ───────────────────────────────────────────────
server {
    listen 80;
    server_name app.neurabar.com;
    return 301 https://$host$request_uri;
}

# ─── Aplicação — HTTPS + WSS ─────────────────────────────────────────────────
server {
    listen 443 ssl;
    server_name app.neurabar.com;

    ssl_certificate     /etc/letsencrypt/live/neurabar.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/neurabar.com/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam         /etc/letsencrypt/ssl-dhparams.pem;

    # WebSocket / Soketi (wss://app.neurabar.com/app/<key>)
    location ~ ^/(app|apps)/ {
        proxy_pass         http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade           $http_upgrade;
        proxy_set_header   Connection        $connection_upgrade;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400s;
    }

    # Laravel
    location / {
        proxy_pass         http://127.0.0.1:9090;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
    }
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 8. Comandos Úteis de Operação

```bash
# Ver logs em tempo real
docker compose -f compose.prod.yaml logs -f app
docker compose -f compose.prod.yaml logs -f queue

# Reiniciar um serviço
docker compose -f compose.prod.yaml restart app

# Deploy de nova versão (pull + rebuild completo)
git pull origin main
docker compose -f compose.prod.yaml exec app php artisan down
docker compose -f compose.prod.yaml up -d --build app queue
docker compose -f compose.prod.yaml exec app php artisan db:migrate-all --force
docker compose -f compose.prod.yaml exec app php artisan optimize
docker compose -f compose.prod.yaml exec app php artisan view:cache
docker compose -f compose.prod.yaml exec app php artisan up

# Limpar caches
docker compose -f compose.prod.yaml exec app php artisan optimize:clear

# Escalar workers de fila
docker compose -f compose.prod.yaml up -d --scale queue=3
```

---

## Diagrama de Roteamento

```
Internet
  │
  ├── neurabar.com / www.neurabar.com
  │       └── Nginx :443 (HTTPS) ──► /var/www/neurabar/landingpage/ (estático)
  │
  └── app.neurabar.com
          ├── Nginx :443 (HTTPS) ──► container app :9090  (Laravel)
          │       └── wss://app.neurabar.com/app/<key>
          │               └── Nginx :443 (WSS→WS) ──► container soketi :6001
          └── Todos os requests HTTP :80 → redirect 301 HTTPS
```


# 1. Buildar a imagem de assets (só da primeira vez, ou quando o frontend mudar)
docker build -f Dockerfile.frontend -t neurabar/assets:latest \
  --build-arg APP_URL=https://app.neurabar.com \
  --build-arg VITE_PUSHER_APP_KEY="your_key" \
  --build-arg VITE_PUSHER_HOST=app.neurabar.com \
  --build-arg VITE_PUSHER_PORT=443 \
  --build-arg VITE_PUSHER_SCHEME=https \
  .

# 2. Buildar e subir o app (sem recompilar o frontend)
docker compose -f compose.prod.yaml up -d --build