#!/bin/sh
set -eu
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Match the host user so dependencies and generated files remain editable.
export LOCAL_UID="$(id -u)"
export LOCAL_GID="$(id -g)"
if [ "$LOCAL_UID" = 0 ]; then
    echo 'Execute este script com seu usuário normal, sem sudo.' >&2
    exit 1
fi

docker compose build app

install_dependencies() {
    if [ "${DEPENDENCY_NETWORK:-}" = host ]; then
        docker run --rm --init --network host \
            --user "$LOCAL_UID:$LOCAL_GID" \
            --mount "type=bind,src=$(pwd),dst=/var/www/html" \
            supplier-management-dev "$@"
    else
        docker compose run --rm --no-deps app "$@"
    fi
}

install_dependencies composer install --no-interaction --prefer-dist
if ! grep -q '^APP_KEY=base64:' .env; then
    docker compose run --rm --no-deps app php artisan key:generate
fi
install_dependencies sh -c 'if [ -f package-lock.json ]; then npm ci; else npm install; fi'
docker compose up -d --wait mysql
docker compose run --rm app php artisan migrate --force
if [ ! -L public/storage ]; then
    docker compose run --rm --no-deps app php artisan storage:link
fi
docker compose up -d app vite queue
