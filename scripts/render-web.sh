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
if [ -e public/storage ] || [ -L public/storage ]; then
    echo "Storage link already exists."
else
    php artisan storage:link
fi
php artisan config:cache
php artisan route:cache

if [ "${RUN_QUEUE_WORKER:-false}" = "true" ]; then
    php artisan queue:work --queue=ai,imports,webhooks,default --sleep=3 --tries=3 --timeout=120 &
fi

if [ -d /etc/apache2/mods-enabled ]; then
    rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
    a2enmod mpm_prefork rewrite >/dev/null
    echo "Apache MPM modules enabled:"
    ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || true
fi

exec apache2-foreground
