#!/bin/sh
set -eu

export APP_URL="${RENDER_EXTERNAL_URL:?}"
export SANCTUM_STATEFUL_DOMAINS="${RENDER_EXTERNAL_HOSTNAME:?}"
export APP_KEY
APP_KEY=$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY_SEED"), true));')
export REVERB_HOST=127.0.0.1 REVERB_PORT=8080 REVERB_SCHEME=http

php artisan migrate --force

php artisan reverb:start --host=127.0.0.1 --port=8080 &
reverb_pid=$!
PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=127.0.0.1 --port=8000 --no-reload &
app_pid=$!
caddy run --config /app/docker/render.Caddyfile --adapter caddyfile &
proxy_pid=$!

trap 'kill "$proxy_pid" "$app_pid" "$reverb_pid" 2>/dev/null || true' EXIT
trap 'exit 0' INT TERM
while kill -0 "$proxy_pid" "$app_pid" "$reverb_pid" 2>/dev/null; do
    sleep 2
done
exit 1
