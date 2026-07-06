#!/bin/sh
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is missing in .env.docker"
    echo "Run: docker compose --env-file .env.docker run --rm backend php artisan key:generate --show"
    echo "Then paste the value into APP_KEY in .env.docker and restart."
    exit 1
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

wait_for_database() {
    attempt=0
    max_attempts=30

    while [ "$attempt" -lt "$max_attempts" ]; do
        if php -r "
            \$host = getenv('DB_HOST') ?: 'mysql';
            \$database = getenv('DB_DATABASE') ?: 'event_saas';
            \$username = getenv('DB_USERNAME') ?: 'event_saas';
            \$password = getenv('DB_PASSWORD') ?: '';
            new PDO(\"mysql:host={\$host};port=3306;dbname={\$database}\", \$username, \$password);
        " >/dev/null 2>&1; then
            return 0
        fi

        attempt=$((attempt + 1))
        echo "Waiting for database... (${attempt}/${max_attempts})"
        sleep 2
    done

    echo "Database is not reachable after ${max_attempts} attempts."
    exit 1
}

wait_for_database

php artisan migrate --force

if [ "${RUN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan storage:link --force >/dev/null 2>&1 || true

exec "$@"
