#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

APP_NAME="SOKRAT CRM V2"
REPO_URL="https://github.com/ahmdosamasokrat-svg/sokrat-crm-v2.git"
APP_DIR="/var/www/html/crm-v2"
DB_NAME="sokrat_crm_v2"
DB_USER="sokrat_crm_v2_app"
SITE_NAME="sokrat-crm-v2"
SITE_CONF="/etc/apache2/sites-available/${SITE_NAME}.conf"
CREDENTIALS_FILE="/root/sokrat-crm-v2-credentials.txt"

APP_CREATED=0
DB_CREATED=0
DB_USERS_CREATED=0
SITE_CREATED=0
DEFAULT_DISABLED=0

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
    printf '\nERROR: %s\n' "$*" >&2
    exit 1
}

cleanup_on_error() {
    rc=$?
    trap - ERR
    set +e

    printf '\nInstallation failed. Cleaning CRM-specific changes...\n' >&2

    if [ "$SITE_CREATED" -eq 1 ]; then
        a2dissite "$SITE_NAME" >/dev/null 2>&1 || true
        rm -f "$SITE_CONF"
    fi

    if [ "$DEFAULT_DISABLED" -eq 1 ]; then
        a2ensite 000-default >/dev/null 2>&1 || true
    fi

    apache2ctl configtest >/dev/null 2>&1 && systemctl reload apache2 >/dev/null 2>&1 || true

    if command -v mysql >/dev/null 2>&1; then
        if [ "$DB_CREATED" -eq 1 ]; then
            mysql -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" >/dev/null 2>&1 || true
        fi
        if [ "$DB_USERS_CREATED" -eq 1 ]; then
            mysql -e "DROP USER IF EXISTS '${DB_USER}'@'127.0.0.1'; DROP USER IF EXISTS '${DB_USER}'@'localhost';" >/dev/null 2>&1 || true
        fi
    fi

    if [ "$APP_CREATED" -eq 1 ]; then
        rm -rf "$APP_DIR"
    fi

    printf 'CRM-specific rollback complete. System packages installed by apt were left in place.\n' >&2
    exit "$rc"
}

trap cleanup_on_error ERR

[ "${EUID}" -eq 0 ] || fail "Run this installer as root or with sudo."

[ -r /etc/os-release ] || fail "Cannot detect operating system."
. /etc/os-release

[ "${ID:-}" = "ubuntu" ] || fail "This installer supports Ubuntu 24.04 only."
[ "${VERSION_ID:-}" = "24.04" ] || fail "This installer supports Ubuntu 24.04 only. Detected: ${VERSION_ID:-unknown}."

[ ! -e "$APP_DIR" ] || fail "$APP_DIR already exists. Fresh installation only."
[ ! -e "$SITE_CONF" ] || fail "$SITE_CONF already exists. Fresh installation only."

log "Installing Apache, MySQL, PHP 8.3, Composer, Git and required PHP extensions"
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y \
    apache2 \
    mysql-server \
    git \
    curl \
    ca-certificates \
    unzip \
    openssl \
    composer \
    libapache2-mod-php8.3 \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl

systemctl enable --now mysql apache2

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
[ "$PHP_VERSION" = "8.3" ] || fail "PHP 8.3 is required. Detected: $PHP_VERSION"

log "Checking database isolation names"
if mysql -NBe "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}'" | grep -Fxq "$DB_NAME"; then
    fail "Database ${DB_NAME} already exists. Fresh installation only."
fi

if mysql -NBe "SELECT User FROM mysql.user WHERE User='${DB_USER}' LIMIT 1" | grep -Fxq "$DB_USER"; then
    fail "MySQL user ${DB_USER} already exists. Fresh installation only."
fi

log "Downloading SOKRAT CRM V2"
git clone --depth 1 "$REPO_URL" "$APP_DIR"
APP_CREATED=1
cd "$APP_DIR"

log "Installing PHP dependencies"
COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

DB_PASSWORD="$(openssl rand -hex 24)"
CRM_ADMIN_USER="admin"
CRM_ADMIN_PASSWORD="$(openssl rand -hex 12)"

log "Creating isolated CRM database and database user"
mysql -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
DB_CREATED=1
DB_USERS_CREATED=1
mysql -e "CREATE USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}'; CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1'; GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

log "Creating production environment file"
cp .env.example .env

set_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

SERVER_IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
[ -n "$SERVER_IP" ] || SERVER_IP="127.0.0.1"

set_env APP_NAME SOKRAT_CRM_V2
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "http://${SERVER_IP}"
set_env DB_CONNECTION mysql
set_env DB_HOST 127.0.0.1
set_env DB_PORT 3306
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASSWORD"
set_env CRM_V2_ADMIN_USER "$CRM_ADMIN_USER"
set_env CRM_V2_ADMIN_PASSWORD "$CRM_ADMIN_PASSWORD"

php artisan key:generate --force --no-interaction
php artisan config:clear --no-ansi

log "Running CRM database migrations"
php artisan migrate --force --no-interaction

log "Seeding CRM pipeline stages and statuses"
php artisan db:seed --class='Database\Seeders\CrmV2PipelineSeeder' --force --no-interaction

php artisan storage:link --no-interaction >/dev/null 2>&1 || true

log "Setting Laravel permissions"
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} +
find storage bootstrap/cache -type f -exec chmod 664 {} +

runuser -u www-data -- php artisan view:clear --no-ansi
runuser -u www-data -- php artisan view:cache --no-ansi

log "Configuring Apache"
cat > "$SITE_CONF" <<APACHE
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/sokrat-crm-v2-error.log
    CustomLog \${APACHE_LOG_DIR}/sokrat-crm-v2-access.log combined
</VirtualHost>
APACHE
SITE_CREATED=1

a2enmod rewrite >/dev/null
if a2query -s 000-default >/dev/null 2>&1; then
    a2dissite 000-default >/dev/null
    DEFAULT_DISABLED=1
fi
a2ensite "$SITE_NAME" >/dev/null
apache2ctl configtest
systemctl reload apache2

log "Running health checks"
php artisan migrate:status --no-ansi >/dev/null
curl -fsS --max-time 15 "http://127.0.0.1/login" >/dev/null

cat > "$CREDENTIALS_FILE" <<CREDS
SOKRAT CRM V2
URL=http://${SERVER_IP}/
Admin user=${CRM_ADMIN_USER}
Admin password=${CRM_ADMIN_PASSWORD}
Database=${DB_NAME}
Database user=${DB_USER}
Database password=${DB_PASSWORD}
Installed from=${REPO_URL}
CREDS
chmod 600 "$CREDENTIALS_FILE"

trap - ERR

printf '\n==================================================\n'
printf 'SOKRAT CRM V2 INSTALLATION COMPLETE\n'
printf '==================================================\n'
printf 'URL: http://%s/\n' "$SERVER_IP"
printf 'Admin user: %s\n' "$CRM_ADMIN_USER"
printf 'Admin password: %s\n' "$CRM_ADMIN_PASSWORD"
printf 'Credentials saved to: %s\n' "$CREDENTIALS_FILE"
printf 'Database: %s\n' "$DB_NAME"
printf 'Database user: %s\n' "$DB_USER"
printf '==================================================\n'
