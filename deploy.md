# Deploy nativo do NeuraBar no Ubuntu 24.04

Este runbook instala e opera a produção sem Docker. Topologia adotada:

- `neurabar.com` e `www.neurabar.com`: landing page estática;
- `app.neurabar.com`: Laravel 12 via Nginx e PHP-FPM 8.3;
- PostgreSQL e Redis locais;
- Soketi em um único container Docker, publicado como WSS pelo Nginx;
- Supervisor para filas; cron para o scheduler.

Os comandos assumem o usuário `ubuntu` e o projeto em `/var/www/neurabar`.
Troque-os de forma consistente se o servidor usar outros valores.

## 1. Arquitetura e pré-requisitos

```text
Internet
  +-- neurabar.com / www.neurabar.com
  |     +-- Nginx :443 --> /var/www/neurabar/landingpage
  +-- app.neurabar.com
        +-- HTTPS --> Nginx --> PHP-FPM --> Laravel public/index.php
        +-- WSS /app/* e /apps/* --> Nginx --> Soketi 127.0.0.1:6001

Laravel e workers --> PostgreSQL 127.0.0.1:5432
                    --> Redis      127.0.0.1:6379
```

Antes de iniciar:

1. Use uma EC2 x86_64. Instâncias AWS Graviton/ARM64 não são suportadas por
   esta configuração do Soketi.
2. Aponte os registros DNS `A` dos três nomes para o IP do servidor.
3. Libere somente TCP `22`, `80` e `443` no firewall da nuvem.
4. Garanta acesso do servidor ao repositório privado.
5. Faça backup dos bancos e do `.env` da instalação anterior.

Valide a arquitetura antes de prosseguir:

```bash
test "$(dpkg --print-architecture)" = amd64 \
    || { echo 'ERRO: esta stack exige EC2 x86_64/amd64'; exit 1; }
uname -m
```

O resultado de `uname -m` deve ser `x86_64`.

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
```

O perfil `Nginx Full` só existe depois que o pacote Nginx registra seus perfis
no UFW. Como o firewall é configurado antes da instalação da stack, as regras
explícitas acima funcionam também em uma EC2 recém-criada. O status deve listar
somente as portas `22/tcp`, `80/tcp` e `443/tcp` entre as regras de entrada.

PostgreSQL, Redis, PHP-FPM e a porta do container Soketi não devem ficar
expostos à Internet.

## 2. Instalar a stack

O Ubuntu 24.04 fornece PHP 8.3 em seu repositório oficial. A aplicação declara
compatibilidade com PHP `^8.2`, portanto não é necessário adicionar um PPA.

```bash
sudo apt update
sudo DEBIAN_FRONTEND=noninteractive apt upgrade -y
sudo apt install -y \
    ca-certificates curl git unzip rsync acl \
    nginx postgresql postgresql-contrib redis-server supervisor \
    docker.io docker-compose-v2 \
    certbot python3-certbot-nginx \
    php8.3-fpm php8.3-cli php8.3-common php8.3-pgsql \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-bcmath php8.3-gd php8.3-intl php8.3-redis \
    php8.3-opcache
```

Instale Node.js 22 LTS somente para o build do Vite. O runtime Node do Soketi
fica encapsulado em sua imagem Docker:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

Instale o Composer validando o instalador:

```bash
cd /tmp
EXPECTED_CHECKSUM="$(curl -fsSL https://composer.github.io/installer.sig)"
curl -fsSL https://getcomposer.org/installer -o composer-setup.php
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
test "$EXPECTED_CHECKSUM" = "$ACTUAL_CHECKSUM"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

```bash
sudo systemctl enable --now php8.3-fpm postgresql redis-server supervisor nginx docker
sudo systemctl --no-pager --full status php8.3-fpm postgresql redis-server supervisor nginx docker
```

## 3. Ajustar o PHP-FPM

Mantenha o pool PHP como `www-data`. Não altere seu usuário para `ubuntu`.
Crie `/etc/php/8.3/fpm/conf.d/99-neurabar.ini`:

```ini
expose_php = Off
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 24M
max_execution_time = 60
date.timezone = America/Sao_Paulo

opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
```

```bash
sudo php-fpm8.3 -t
sudo systemctl restart php8.3-fpm
sudo systemctl --no-pager --full status php8.3-fpm
```

Com `opcache.validate_timestamps=0`, reinicie ou recarregue o PHP-FPM após cada
deploy de código PHP.

O teste precisa de `sudo` porque a configuração global abre
`/var/log/php8.3-fpm.log`, normalmente protegido como `root:root` com modo
`0600`. Executar `php-fpm8.3 -t` como `ubuntu` retorna `Permission denied` mesmo
quando a configuração está correta. Não altere a permissão do log para contornar
isso; para diagnosticar falhas, use:

```bash
sudo php-fpm8.3 -tt
sudo journalctl -u php8.3-fpm --since '10 minutes ago' --no-pager
```

## 4. Configurar PostgreSQL

A aplicação usa três databases iniciais e provisiona novos databases para
tenants dedicados. A role `neurabar` precisa de `CREATEDB`; ela não precisa de
`SUPERUSER`, `CREATEROLE` ou `REPLICATION`. Entre no PostgreSQL:

```bash
sudo -u postgres psql
```

Substitua a senha e execute:

```sql
CREATE ROLE neurabar LOGIN CREATEDB PASSWORD 'SUBSTITUA_POR_UMA_SENHA_FORTE';
CREATE DATABASE laravel_saas OWNER neurabar ENCODING 'UTF8';
CREATE DATABASE operation_default_1 OWNER neurabar ENCODING 'UTF8';
CREATE DATABASE operation_default_2 OWNER neurabar ENCODING 'UTF8';
\q
```

Se a role já existir, conceda a capacidade sem recriá-la:

```bash
sudo -u postgres psql -c 'ALTER ROLE neurabar WITH CREATEDB;'
```

Como `neurabar` é owner dos três databases iniciais e também será owner dos
databases que criar, ela pode criar, alterar e remover schemas dentro deles. Não
é necessário conceder acesso global adicional ao schema `public`.

Confirme que o serviço não está público e teste o login:

```bash
sudo -u postgres psql -tAc "SHOW listen_addresses;"
sudo ss -lntp | grep ':5432'
psql -h 127.0.0.1 -U neurabar -d laravel_saas -c 'select current_database();'
sudo -u postgres psql -tAc \
    "SELECT rolname, rolcreatedb, rolsuper, rolcreaterole FROM pg_roles WHERE rolname = 'neurabar';"
```

O listener deve estar limitado a `localhost`, `127.0.0.1` e/ou `::1`.
Na consulta da role, o resultado esperado é `neurabar | t | f | f`.

Valide a criação de schemas sem deixar objetos de teste:

```bash
psql -h 127.0.0.1 -U neurabar -d laravel_saas \
    -c 'CREATE SCHEMA deploy_permission_test; DROP SCHEMA deploy_permission_test;'
```

> `CREATEDB` aumenta o impacto de um eventual vazamento das credenciais da
> aplicação. Mantenha o PostgreSQL restrito ao loopback, use uma senha exclusiva
> e monitore a criação de databases. Não conceda `SUPERUSER` como atalho.

## 5. Configurar Redis

Edite `/etc/redis/redis.conf` e garanta estas diretivas:

```conf
bind 127.0.0.1 ::1
protected-mode yes
port 6379
requirepass SUBSTITUA_POR_UMA_SENHA_REDIS_FORTE
appendonly yes
appendfsync everysec
```

```bash
sudo systemctl restart redis-server
redis-cli -h 127.0.0.1 -a 'SUBSTITUA_POR_UMA_SENHA_REDIS_FORTE' ping
sudo ss -lntp | grep ':6379'
```

O comando deve retornar `PONG`, com listener apenas no loopback.

## 6. Preparar a aplicação

```bash
sudo mkdir -p /var/www/neurabar
sudo chown ubuntu:ubuntu /var/www/neurabar
git clone https://github.com/inteligenciak2n/neurabar2.git /var/www/neurabar
cd /var/www/neurabar

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    storage/app/public 

sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo setfacl -R -m u:ubuntu:rwx,u:www-data:rwx storage bootstrap/cache
sudo setfacl -dR -m u:ubuntu:rwx,u:www-data:rwx storage bootstrap/cache

test -f .env || cp .env.example .env
chmod 640 .env
nano .env

composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction
```

Em repositório privado, configure antes uma deploy key ou outro acesso não
interativo.

## 7. `.env` de produção

Use este bloco como base. Preserve também as variáveis de e-mail, AWS, Asaas,
LaraOwl e demais integrações da aplicação. Nunca versione o `.env`.

```dotenv
APP_NAME=NeuraBar
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.neurabar.com
LANDING_PAGE_URL=https://neurabar.com
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_MAINTENANCE_DRIVER=file
TRUSTED_PROXIES=127.0.0.1

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=saas
DB_HOST=127.0.0.1
DB_PORT=5432
DB_USERNAME=neurabar
DB_PASSWORD="SUBSTITUA_PELA_SENHA_DO_POSTGRES"
DB_SSLMODE=prefer
DB_SAAS_DATABASE=laravel_saas
DB_OP_DEFAULT_1_DATABASE=operation_default_1
DB_OP_DEFAULT_2_DATABASE=operation_default_2

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD="SUBSTITUA_PELA_SENHA_DO_REDIS"
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_DOMAIN=app.neurabar.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=redis
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=180

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=neurabar
PUSHER_APP_KEY="SUBSTITUA_POR_UMA_CHAVE_ALEATORIA"
PUSHER_APP_SECRET="SUBSTITUA_POR_UM_SEGREDO_ALEATORIO"
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST=app.neurabar.com
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https

FILESYSTEM_DISK=local
```

Gere segredos independentes com `openssl rand -hex 32`. Gere a chave Laravel
somente se `APP_KEY` estiver vazia:

```bash
php artisan key:generate --force
```

Não altere a `APP_KEY` durante uma migração: isso invalida sessões e dados
criptografados existentes.

## 8. Build, migrations e permissões

As variáveis `VITE_*` entram nos arquivos durante o build. Portanto, finalize o
`.env` antes destes comandos:

```bash
cd /var/www/neurabar
npm ci
npm run build

php artisan storage:link
php artisan assets:link
php artisan db:migrate-all --force
php artisan optimize
php artisan view:cache
```

Nunca use `db:migrate-all --fresh` em produção, pois ele apaga as tabelas.

```bash
sudo chown -R ubuntu:www-data /var/www/neurabar
sudo find /var/www/neurabar -type d -exec chmod 750 {} \;
sudo find /var/www/neurabar -type f -exec chmod 640 {} \;
sudo chmod 750 /var/www/neurabar/artisan

sudo setfacl -R -m u:ubuntu:rwx,u:www-data:rwx \
    /var/www/neurabar/storage /var/www/neurabar/bootstrap/cache
sudo setfacl -dR -m u:ubuntu:rwx,u:www-data:rwx \
    /var/www/neurabar/storage /var/www/neurabar/bootstrap/cache
```

Não aplique `777` nem dê escrita ao Nginx sobre todo o projeto.

## 9. Subir o Soketi em Docker

Somente o Soketi roda em container. O arquivo `compose.soketi.yaml` fixa a
plataforma `linux/amd64`, publica a porta apenas em `127.0.0.1:6001` e configura
reinício automático. PostgreSQL, Redis, Nginx, PHP-FPM, Laravel e workers
continuam nativos.

A imagem está fixada por digest da variante `linux/amd64`; assim, uma alteração
na tag remota não modifica produção silenciosamente. Atualizações devem trocar o
digest no repositório, passar por validação e só então executar `pull` e `up -d`.

Remova instalações nativas anteriores do Soketi, se existirem:

```bash
sudo supervisorctl stop neurabar-soketi 2>/dev/null || true
sudo rm -f /etc/supervisor/conf.d/neurabar-soketi.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo npm uninstall -g @soketi/soketi || true
sudo rm -rf /opt/soketi /opt/node-v18.20.8
```

Crie o arquivo de credenciais do container. Use exatamente os mesmos valores de
`PUSHER_APP_ID`, `PUSHER_APP_KEY` e `PUSHER_APP_SECRET` do `.env` Laravel:

```bash
sudo mkdir -p /etc/neurabar
sudo nano /etc/neurabar/soketi.env
```

```dotenv
SOKETI_DEBUG=0
SOKETI_HOST=0.0.0.0
SOKETI_PORT=6001
SOKETI_METRICS_SERVER_PORT=9601
SOKETI_DEFAULT_APP_ID=neurabar
SOKETI_DEFAULT_APP_KEY=SUBSTITUA_PELA_MESMA_PUSHER_APP_KEY
SOKETI_DEFAULT_APP_SECRET=SUBSTITUA_PELO_MESMO_PUSHER_APP_SECRET
```

Proteja as credenciais e suba o container:

```bash
sudo chown root:root /etc/neurabar/soketi.env
sudo chmod 600 /etc/neurabar/soketi.env

cd /var/www/neurabar
sudo docker compose -f compose.soketi.yaml pull
sudo docker compose -f compose.soketi.yaml up -d
sudo docker compose -f compose.soketi.yaml ps
sudo docker compose -f compose.soketi.yaml logs --tail=100 soketi
```

Confirme a arquitetura do container e o bind local:

```bash
SOKETI_CONTAINER="$(sudo docker compose -f compose.soketi.yaml ps -q soketi)"
sudo docker inspect "$SOKETI_CONTAINER" \
    --format '{{.Platform}} {{.State.Status}} {{.RestartCount}}'
sudo ss -lntp | grep ':6001'
```

O resultado esperado contém `linux running 0`, e a porta `6001` deve aparecer
somente em `127.0.0.1`.

## 10. Configurar Supervisor

Crie `/etc/supervisor/conf.d/neurabar-worker.conf`:

```ini
[program:neurabar-worker]
process_name=%(program_name)s_%(process_num)02d
directory=/var/www/neurabar
command=/usr/bin/php8.3 artisan queue:work redis --queue=default,broadcasts,payments --sleep=3 --tries=3 --timeout=120 --max-time=3600
user=ubuntu
numprocs=2
autostart=true
autorestart=true
startsecs=5
stopasgroup=true
killasgroup=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/www/neurabar/storage/logs/worker.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=5
environment=APP_ENV="production"
```

`REDIS_QUEUE_RETRY_AFTER=180` deve ser maior que `--timeout=120`, evitando que
um job ainda em execução seja liberado duas vezes.

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart neurabar-worker:*
sudo supervisorctl status
sudo ss -lntp | grep ':6001'
```

Os workers devem estar `RUNNING`; o Soketi é gerenciado pelo Docker e deve ouvir
apenas no loopback.

## 11. Configurar o scheduler

Abra `sudo crontab -u ubuntu -e` e adicione uma única entrada:

```cron
* * * * * cd /var/www/neurabar && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1
```

```bash
cd /var/www/neurabar
php artisan schedule:list
```

## 12. Nginx inicial e certificado

Primeiro use HTTP para emitir o certificado sem referenciar arquivos TLS que
ainda não existem:

```bash
sudo mkdir -p /var/www/letsencrypt/.well-known/acme-challenge
sudo chown -R www-data:www-data /var/www/letsencrypt
sudo nano /etc/nginx/sites-available/neurabar
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name neurabar.com www.neurabar.com;
    root /var/www/neurabar/landingpage;
    index index.html;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }
}

server {
    listen 80;
    listen [::]:80;
    server_name app.neurabar.com;
    root /var/www/neurabar/public;
    index index.php;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

```bash
sudo ln -sfn /etc/nginx/sites-available/neurabar /etc/nginx/sites-enabled/neurabar
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

sudo certbot certonly \
    --webroot --webroot-path /var/www/letsencrypt \
    -d neurabar.com -d www.neurabar.com -d app.neurabar.com \
    --email contato@neurabar.com --agree-tos --no-eff-email
```

## 13. Configuração final completa do Nginx

Antes de ativar o virtual host, ajuste os limites globais em
`/etc/nginx/nginx.conf`. Preserve os demais includes e blocos existentes:

```nginx
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 10000;
    multi_accept on;
}
```

Valide também o limite efetivo do processo após recarregar o Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
NGINX_PID="$(cat /run/nginx.pid)"
grep 'Max open files' "/proc/${NGINX_PID}/limits"
```

Substitua todo `/etc/nginx/sites-available/neurabar` por:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    '' close;
}

server {
    listen 80;
    listen [::]:80;
    server_name neurabar.com www.neurabar.com app.neurabar.com;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name neurabar.com www.neurabar.com;

    ssl_certificate /etc/letsencrypt/live/neurabar.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/neurabar.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/neurabar/landingpage;
    index index.html;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ /\. {
        deny all;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name app.neurabar.com;

    ssl_certificate /etc/letsencrypt/live/neurabar.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/neurabar.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/neurabar/public;
    index index.php;
    client_max_body_size 20m;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location ~ ^/(app|apps)/ {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400s;
        proxy_send_timeout 86400s;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 120s;
    }

    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|webp|ico|woff2?)$ {
        expires 7d;
        access_log off;
        try_files $uri /index.php?$query_string;
    }

    location ~ /\. {
        deny all;
    }
}
```

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo certbot renew --dry-run
systemctl list-timers certbot.timer
```

## 14. Validação do primeiro deploy

```bash
cd /var/www/neurabar
php artisan about --only=environment
php artisan db:show --database=saas
php artisan schedule:list
sudo supervisorctl status
sudo nginx -t
curl -fsS https://app.neurabar.com/up
curl -fsSI https://neurabar.com
curl -fsSI https://www.neurabar.com
```

O endpoint `/up` deve retornar HTTP `200`. Valide também login e sessão, assets
sem 404, um evento WebSocket, um job de fila, e-mail e integrações externas.

```bash
tail -f /var/www/neurabar/storage/logs/laravel.log
tail -f /var/www/neurabar/storage/logs/worker.log
sudo docker compose -f /var/www/neurabar/compose.soketi.yaml logs -f soketi
sudo journalctl -u nginx -u php8.3-fpm -u postgresql -u redis-server -f
```

## 15. Deploy de novas versões

O script `scripts/deploy-app.sh` automatiza o deploy do backend via SSH e rsync.
Ele preserva `.env`, `storage`, `bootstrap/cache`, `vendor` e `public/build`,
executa Composer e migrations no servidor, reconstrói os caches, reinicia os
workers e recarrega o PHP-FPM.

Como `public/build` é implantado separadamente, execute os scripts nesta ordem:

```bash
scripts/deploy-assets.sh
scripts/deploy-app.sh
```

Os padrões podem ser sobrescritos quando necessário:

```bash
REMOTE_HOST=neurabar \
REMOTE_PATH=/var/www/neurabar \
HEALTHCHECK_URL=https://app.neurabar.com/up \
scripts/deploy-app.sh
```

O usuário SSH precisa escrever em `/var/www/neurabar` e executar
`systemctl reload php8.3-fpm` via `sudo` sem prompt. Faça backup antes de
migrations destrutivas.

Para executar o mesmo procedimento manualmente:

```bash
cd /var/www/neurabar
php artisan down --retry=60
git pull --ff-only origin main

composer install \
    --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan db:migrate-all --force
php artisan optimize
php artisan view:cache
sudo supervisorctl restart neurabar-worker:*
sudo docker compose -f compose.soketi.yaml up -d
sudo systemctl reload php8.3-fpm
php artisan up
curl -fsS https://app.neurabar.com/up
```

Se o deploy chega por `git push env_prod`, o hook deve executar essa sequência
após atualizar a working tree. Não execute Composer, NPM ou Artisan como `root`
e não grave segredos no hook.

## 16. Backup e operação

Exemplo de backup manual:

```bash
sudo install -d -o ubuntu -g ubuntu -m 700 /var/backups/neurabar
cd /var/backups/neurabar
DATE="$(date +%F-%H%M%S)"

PGPASSWORD='SENHA' pg_dump -h 127.0.0.1 -U neurabar -Fc laravel_saas \
    > "laravel_saas-${DATE}.dump"
PGPASSWORD='SENHA' pg_dump -h 127.0.0.1 -U neurabar -Fc operation_default_1 \
    > "operation_default_1-${DATE}.dump"
PGPASSWORD='SENHA' pg_dump -h 127.0.0.1 -U neurabar -Fc operation_default_2 \
    > "operation_default_2-${DATE}.dump"
```

Prefira `~/.pgpass` com permissão `600` para automação. Envie os backups para
outro servidor ou object storage e teste periodicamente a restauração.

```bash
# Estado
sudo systemctl status nginx php8.3-fpm postgresql redis-server supervisor docker
sudo supervisorctl status

# Soketi
sudo docker compose -f compose.soketi.yaml ps
sudo docker compose -f compose.soketi.yaml logs --tail=100 soketi
sudo docker compose -f compose.soketi.yaml pull
sudo docker compose -f compose.soketi.yaml up -d

# Workers, falhas e caches
php artisan queue:restart
php artisan queue:failed
php artisan optimize:clear
php artisan optimize
php artisan view:cache
```

## 17. Critérios de conclusão

- Serviços iniciam automaticamente após reboot.
- PostgreSQL, Redis e a porta publicada do Soketi escutam somente no loopback.
- Os três databases estão migrados e possuem backup restaurável.
- Workers `default`, `broadcasts` e `payments` estão `RUNNING`.
- Scheduler consta no crontab de `ubuntu` e em `schedule:list`.
- `https://app.neurabar.com/up` retorna `200`.
- Landing page responde no domínio raiz e no `www`.
- WebSocket conecta por `wss://app.neurabar.com/app/...`.
- Certbot renova os certificados sem erro.
- Nenhum segredo está versionado e nenhuma porta interna está pública.