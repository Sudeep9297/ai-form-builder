#!/usr/bin/env sh
set -eu

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
tries=0
until php -r '
$host = getenv("DB_HOST");
$port = getenv("DB_PORT") ?: 3306;
$db = getenv("DB_DATABASE");
$user = getenv("DB_USERNAME");
$pass = getenv("DB_PASSWORD");
new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
' >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 40 ]; then
        echo "MySQL did not become available in time."
        exit 1
    fi
    sleep 3
done

php artisan migrate --force --seed
php artisan storage:link || true
php artisan config:cache
php artisan route:cache

exec apache2-foreground
