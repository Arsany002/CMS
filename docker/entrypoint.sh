#!/usr/bin/env bash
# Container entrypoint — run as www-data
# CONTAINER_ROLE controls which initialisation steps run:
#   app       → full startup: migrate, passport, cache (default)
#   worker    → config cache only, then start queue worker
#   scheduler → config cache only, then start scheduler
set -euo pipefail

APP_DIR=/var/www/html
ROLE="${CONTAINER_ROLE:-app}"

# ── Wait for MySQL ────────────────────────────────────────────────────────────
wait_for_db() {
    local host="${DB_HOST:-db}"
    local port="${DB_PORT:-3306}"
    local db="${DB_DATABASE}"
    local user="${DB_USERNAME}"
    local pass="${DB_PASSWORD}"
    local attempts=0

    echo "[entrypoint] Waiting for MySQL at ${host}:${port}..."
    until php -r "
        try {
            new PDO('mysql:host=${host};port=${port};dbname=${db}', '${user}', '${pass}');
            exit(0);
        } catch (PDOException \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            echo "[entrypoint] ERROR: Could not connect to MySQL after 30 attempts. Aborting."
            exit 1
        fi
        echo "[entrypoint]   attempt ${attempts}/30 — retrying in 2 s..."
        sleep 2
    done
    echo "[entrypoint] MySQL is ready."
}

# ── Main ──────────────────────────────────────────────────────────────────────
cd "$APP_DIR"

wait_for_db

echo "[entrypoint] Caching configuration (role: ${ROLE})..."
php artisan config:cache

if [ "$ROLE" = "app" ]; then
    # ── Full server initialisation ────────────────────────────────────────────

    # Generate APP_KEY if the caller forgot to set one
    if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "base64:" ]; then
        echo "[entrypoint] APP_KEY missing — generating one..."
        php artisan key:generate --force
        # Re-cache after key generation
        php artisan config:cache
    fi

    echo "[entrypoint] Running database migrations..."
    php artisan migrate --force

    # Generate Passport RSA keys if they are absent
    if [ ! -f "${APP_DIR}/storage/oauth-private.key" ] || \
       [ ! -f "${APP_DIR}/storage/oauth-public.key" ]; then
        echo "[entrypoint] Generating Passport OAuth keys..."
        php artisan passport:keys --force
    fi
    chmod 600 "${APP_DIR}/storage/oauth-private.key" \
              "${APP_DIR}/storage/oauth-public.key" 2>/dev/null || true

    # Seed the Passport personal-access client (idempotent via PassportClientSeeder)
    echo "[entrypoint] Seeding Passport personal-access client..."
    php artisan db:seed --class=PassportClientSeeder --force

    # Warm up route and view caches
    php artisan route:cache
    php artisan view:cache

    # Ensure storage dirs are writable (especially on first run with a fresh volume)
    chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

    echo "[entrypoint] Backend initialisation complete."
fi

exec "$@"
