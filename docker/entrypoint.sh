#!/bin/sh
set -eu

PORT="${PORT:-8080}"

cat > /etc/apache2/ports.conf <<EOF
Listen ${PORT}
EOF

cat > /etc/apache2/conf-available/server-name.conf <<EOF
ServerName ${APP_HOST:-localhost}
EOF
a2enconf server-name >/dev/null

cat > /etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /proc/self/fd/2
    CustomLog /proc/self/fd/1 combined
</VirtualHost>
EOF

rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

rm -f /var/www/html/bootstrap/cache/*.php

normalize_env_value() {
    printf '%s' "$1" | tr '[:upper:]' '[:lower:]' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//'
}

DB_CONNECTION_NORMALIZED="$(normalize_env_value "${DB_CONNECTION:-}")"
if [ -n "$DB_CONNECTION_NORMALIZED" ]; then
    export DB_CONNECTION="$DB_CONNECTION_NORMALIZED"
fi

if [ -z "${DB_URL:-}" ]; then
    if [ -n "${DATABASE_URL:-}" ] && echo "$DATABASE_URL" | grep -Eq '^mysql(i)?://'; then
        export DB_URL="$DATABASE_URL"
    elif [ -n "${MYSQL_URL:-}" ] && echo "$MYSQL_URL" | grep -Eq '^mysql(i)?://'; then
        export DB_URL="$MYSQL_URL"
    elif [ -n "${MYSQL_PRIVATE_URL:-}" ] && echo "$MYSQL_PRIVATE_URL" | grep -Eq '^mysql(i)?://'; then
        export DB_URL="$MYSQL_PRIVATE_URL"
    elif [ -n "${MYSQL_PUBLIC_URL:-}" ] && echo "$MYSQL_PUBLIC_URL" | grep -Eq '^mysql(i)?://'; then
        export DB_URL="$MYSQL_PUBLIC_URL"
    fi
fi

if [ -z "${DB_CONNECTION:-}" ] || [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    if [ -n "${MYSQLHOST:-}" ] || [ -n "${DB_HOST:-}" ] || [ -n "${DB_URL:-}" ]; then
        export DB_CONNECTION=mysql
    fi
fi

if [ "${DB_CONNECTION:-sqlite}" = "mysql" ]; then
    export DB_HOST="${DB_HOST:-${MYSQLHOST:-}}"
    export DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
    export DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE:-}}"
    export DB_USERNAME="${DB_USERNAME:-${MYSQLUSER:-}}"
    export DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"
fi

echo "Database startup check: DB_CONNECTION='${DB_CONNECTION:-unset}', DB_HOST set=$([ -n "${DB_HOST:-}" ] && echo yes || echo no), MYSQLHOST set=$([ -n "${MYSQLHOST:-}" ] && echo yes || echo no), DB_URL set=$([ -n "${DB_URL:-}" ] && echo yes || echo no)" >&2
echo "Session startup check: SESSION_DRIVER='${SESSION_DRIVER:-unset}', SESSION_DOMAIN set=$([ -n "${SESSION_DOMAIN:-}" ] && echo yes || echo no), SESSION_SECURE_COOKIE='${SESSION_SECURE_COOKIE:-unset}'" >&2

MAIL_MAILER_NORMALIZED="$(normalize_env_value "${MAIL_MAILER:-}")"
if [ -n "${RESEND_API_KEY:-}" ]; then
    if [ "$MAIL_MAILER_NORMALIZED" = "resend" ] || [ "$MAIL_MAILER_NORMALIZED" = "smtp" ] || [ -z "$MAIL_MAILER_NORMALIZED" ]; then
        export MAIL_MAILER=smtp
        export MAIL_HOST="${MAIL_HOST:-smtp.resend.com}"
        export MAIL_PORT="${MAIL_PORT:-587}"
        export MAIL_USERNAME="${MAIL_USERNAME:-resend}"
        export MAIL_PASSWORD="${MAIL_PASSWORD:-${RESEND_API_KEY}}"
        export MAIL_SCHEME="${MAIL_SCHEME:-tls}"
        export MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"
    fi
fi

echo "Mail startup check: MAIL_MAILER='${MAIL_MAILER:-unset}', MAIL_HOST set=$([ -n "${MAIL_HOST:-}" ] && echo yes || echo no), MAIL_USERNAME set=$([ -n "${MAIL_USERNAME:-}" ] && echo yes || echo no), MAIL_PASSWORD set=$([ -n "${MAIL_PASSWORD:-}" ] && echo yes || echo no), MAIL_FROM_ADDRESS set=$([ -n "${MAIL_FROM_ADDRESS:-}" ] && echo yes || echo no), MAIL_TO_OVERRIDE set=$([ -n "${MAIL_TO_OVERRIDE:-}" ] && echo yes || echo no)" >&2

if [ "${DB_CONNECTION:-sqlite}" = "mysql" ] && [ -z "${DB_URL:-}" ]; then
    missing_mysql_env=false

    if [ -z "${DB_HOST:-}" ]; then
        echo "DB_CONNECTION=mysql but DB_HOST/MYSQLHOST is not set." >&2
        missing_mysql_env=true
    fi

    if [ -z "${DB_DATABASE:-}" ]; then
        echo "DB_CONNECTION=mysql but DB_DATABASE/MYSQLDATABASE is not set." >&2
        missing_mysql_env=true
    fi

    if [ -z "${DB_USERNAME:-}" ]; then
        echo "DB_CONNECTION=mysql but DB_USERNAME/MYSQLUSER is not set." >&2
        missing_mysql_env=true
    fi

    if [ "$missing_mysql_env" = "true" ]; then
        echo "Attach a Railway MySQL database to this service and set the MySQL environment variables, or set DB_URL to Railway's MySQL connection URL." >&2
        exit 1
    fi
fi

if [ "${APP_ENV:-production}" = "production" ] \
    && [ "${DB_CONNECTION:-sqlite}" = "sqlite" ] \
    && [ "${ALLOW_EPHEMERAL_SQLITE:-false}" != "true" ]; then
    echo "Refusing to start production with SQLite inside the container." >&2
    echo "Container redeploys can replace the filesystem, so SQLite data stored here may disappear." >&2
    echo "Attach a managed database, or set ALLOW_EPHEMERAL_SQLITE=true only for a disposable demo." >&2
    exit 1
fi

mkdir -p \
    /var/www/html/bootstrap/cache \
    /var/www/html/database \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
fi

chown -R www-data:www-data \
    /var/www/html/bootstrap/cache \
    /var/www/html/database \
    /var/www/html/storage

touch /var/www/html/storage/logs/laravel.log
chown www-data:www-data /var/www/html/storage/logs/laravel.log
tail -n 0 -F /var/www/html/storage/logs/laravel.log >&2 &

if [ -z "${APP_KEY:-}" ] && [ -n "${APP_KEY_BASE64:-}" ]; then
    export APP_KEY="base64:${APP_KEY_BASE64}"
fi

if [ -z "${APP_KEY:-}" ]; then
    if [ "${APP_ENV:-production}" = "production" ]; then
        echo "APP_KEY is not set. Set APP_KEY or provide a fixed APP_KEY_BASE64 secret." >&2
        echo "A temporary key would invalidate encrypted sessions and cookies after every restart." >&2
        exit 1
    fi

    echo "APP_KEY is not set. Generating a temporary development key." >&2
    export APP_KEY="$(php artisan key:generate --show)"
fi

php artisan migrate --force
if [ "${RUN_DATABASE_SEEDER:-false}" = "true" ]; then
    php artisan db:seed --force
fi
php artisan storage:link || true

if [ "${RUN_QUEUE_WORKER:-false}" = "true" ]; then
    if [ "${QUEUE_CONNECTION:-sync}" = "sync" ]; then
        echo "Queue worker skipped because QUEUE_CONNECTION=sync runs jobs inline." >&2
    else
        echo "Starting queue worker: queue:work ${QUEUE_CONNECTION:-database}" >&2
        php artisan queue:work "${QUEUE_CONNECTION:-database}" \
            --sleep="${QUEUE_WORKER_SLEEP:-3}" \
            --tries="${QUEUE_WORKER_TRIES:-3}" \
            --timeout="${QUEUE_WORKER_TIMEOUT:-90}" >&2 &
    fi
fi

exec "$@"
