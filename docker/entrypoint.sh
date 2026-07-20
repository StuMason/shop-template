#!/bin/bash
set -e

echo "============================================"
echo "Laravel Application Startup"
echo "============================================"

# Configuration (set via ENV in Dockerfile)
AUTO_MIGRATE="${AUTO_MIGRATE:-true}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-30}"

STEP=1
TOTAL_STEPS=3

# ===========================================
# Step 0: Ensure SQLite database file exists
# ===========================================
# No external database to wait for — make sure the SQLite file exists and is
# writable by www-data (php-fpm) before migrations run. If a Coolify volume is
# mounted over the database dir, this creates the file inside the volume.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
    chmod -R 775 "$(dirname "$DB_FILE")"
    echo "SQLite database ready at $DB_FILE"
fi

DATA_DIR="$(dirname "${DB_DATABASE:-/var/www/html/database/database.sqlite}")"

# ===========================================
# Step 0.1: Application key
# ===========================================
# A one-command `docker compose up` must work without manual setup. When
# APP_KEY isn't supplied, persist a generated one beside the database so it
# survives restarts (sessions and encrypted columns stay valid). Platforms
# that set APP_KEY explicitly skip this entirely.
if [ -z "${APP_KEY:-}" ]; then
    KEY_FILE="$DATA_DIR/app_key"
    if [ ! -f "$KEY_FILE" ]; then
        php artisan key:generate --show > "$KEY_FILE"
    fi
    export APP_KEY="$(cat "$KEY_FILE")"
    echo "APP_KEY loaded from $KEY_FILE"
fi

# ===========================================
# Step 1: Database Migrations (if enabled)
# ===========================================
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo ""
    echo "[$STEP/$TOTAL_STEPS] Waiting for database connection..."

    # Wait for database to be available
    # We use db:show instead of a simple connection check because it verifies
    # both connectivity AND schema access. If db:show fails, migrations would
    # fail anyway, so this gives us an early, clear error message.
    WAITED=0
    until php artisan db:show > /dev/null 2>&1; do
        WAITED=$((WAITED + 1))
        if [ $WAITED -ge $DB_WAIT_TIMEOUT ]; then
            echo "ERROR: Database connection timeout after ${DB_WAIT_TIMEOUT}s" >&2
            echo "       Check that your database is running and accessible." >&2
            exit 1
        fi
        echo "       Waiting for database... ($WAITED/${DB_WAIT_TIMEOUT}s)"
        sleep 1
    done
    echo "       Database connected!"

    echo ""
    echo "[$STEP/$TOTAL_STEPS] Running database migrations..."
    if ! php artisan migrate --force; then
        echo "ERROR: Database migrations failed!" >&2
        echo "       Check migration files and database state." >&2
        exit 1
    fi
    echo "       Migrations completed successfully."

    # Structural roles (admin/staff/customer) are required by shop:ensure-admin
    # and by customer registration. They are foundational, not demo data, so
    # ensure them on every boot (RolesSeeder is idempotent) independent of
    # AUTO_SEED — which only gates the demo catalogue below. Without this a
    # deploy with AUTO_SEED=false boots with no roles and ensure-admin throws
    # RoleDoesNotExist on assignRole('admin').
    php artisan db:seed --class=RolesSeeder --force || echo "WARNING: roles seeding failed (continuing)"

    # Provision/refresh the admin from ADMIN_EMAIL / ADMIN_PASSWORD on every
    # boot, so credentials are owned by config rather than a one-off seed.
    php artisan shop:ensure-admin || echo "WARNING: admin provisioning failed (continuing)"

    if [ "${AUTO_SEED:-false}" = "true" ]; then
        # Seed once, ever — a marker in the data volume means restarts (and
        # `docker compose up` after the first) never duplicate the catalogue.
        SEED_MARKER="$DATA_DIR/.seeded"
        if [ ! -f "$SEED_MARKER" ]; then
            echo ""
            echo "[$STEP/$TOTAL_STEPS] Seeding database (first boot, AUTO_SEED=true)..."
            if php artisan db:seed --force; then
                touch "$SEED_MARKER"
            else
                echo "WARNING: seeding failed (continuing)"
            fi
        fi
    fi
else
    echo ""
    echo "[$STEP/$TOTAL_STEPS] Skipping migrations (AUTO_MIGRATE=false)"
fi
STEP=$((STEP + 1))

# ===========================================
# Step 2: Application Optimization
# ===========================================
echo ""
# Passport keys sign the admin MCP's OAuth tokens. Generate on first boot;
# set PASSPORT_PRIVATE_KEY/PASSPORT_PUBLIC_KEY envs to persist across deploys.
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --no-interaction || true
fi
# passport:keys writes root-owned 0600 keys; php-fpm runs as www-data.
chown www-data:www-data storage/oauth-*.key 2>/dev/null || true

echo "[$STEP/$TOTAL_STEPS] Optimizing application..."
php artisan optimize
echo "       Optimization completed (config, routes, views, events cached)."
STEP=$((STEP + 1))

# ===========================================
# Step 3: Storage Link
# ===========================================
echo ""
echo "[$STEP/$TOTAL_STEPS] Ensuring storage link..."
php artisan storage:link 2>/dev/null || true
echo "       Storage link ready."

echo ""
echo "============================================"
echo "Application ready. Starting services..."
echo "============================================"
echo ""

# Start supervisor (replaces this process with PID 1)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf