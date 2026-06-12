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

    if [ "${AUTO_SEED:-false}" = "true" ]; then
        echo ""
        echo "[$STEP/$TOTAL_STEPS] Seeding database (AUTO_SEED=true)..."
        php artisan db:seed --force || echo "WARNING: seeding failed (continuing)"
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