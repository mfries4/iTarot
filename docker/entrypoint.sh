#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Avoid permission issues when bind-mounting from host
mkdir -p var/cache var/log migrations
chown -R www-data:www-data var migrations || true

export COMPOSER_ALLOW_SUPERUSER=1

# Install PHP deps (idempotent)
if [ -f composer.json ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Wait for MySQL
echo "Waiting for MySQL to be ready…"

DB_HOST="${DB_HOST:-}"
DB_PORT="${DB_PORT:-}"
DB_USER="${DB_USER:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="${DB_NAME:-}"

if [ -n "${DATABASE_URL:-}" ]; then
  DB_HOST="$(php -r '$u=parse_url(getenv("DATABASE_URL")); echo $u["host"] ?? "";')"
  DB_PORT="$(php -r '$u=parse_url(getenv("DATABASE_URL")); echo $u["port"] ?? "";')"
  DB_USER="$(php -r '$u=parse_url(getenv("DATABASE_URL")); echo $u["user"] ?? "";')"
  DB_PASSWORD="$(php -r '$u=parse_url(getenv("DATABASE_URL")); echo $u["pass"] ?? "";')"
  DB_NAME="$(php -r '$u=parse_url(getenv("DATABASE_URL")); $p=$u["path"] ?? ""; echo ltrim($p, "/");')"
fi

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-itarot}"
DB_NAME="${DB_NAME:-itarot}"

export DB_HOST DB_PORT DB_USER DB_PASSWORD DB_NAME

until php -r '
  $host = getenv("DB_HOST");
  $port = getenv("DB_PORT");
  $db   = getenv("DB_NAME");
  $user = getenv("DB_USER");
  $pass = getenv("DB_PASSWORD");
  $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $host, $port, $db);
  try {
    new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  } catch (Throwable $e) {
    exit(1);
  }
' >/dev/null 2>&1; do
  sleep 1
done

# Doctrine: create DB, create migration file, then run migrations
php bin/console doctrine:database:create --if-not-exists --no-interaction

# Generates a new migration only if the schema has changed.
# When no changes are detected, Doctrine prints an "[ERROR]"-styled message even though it's not fatal;
# we silence that case to keep container logs clean.
DIFF_OUTPUT="$(php bin/console doctrine:migrations:diff --no-interaction --allow-empty-diff 2>&1)" || {
  echo "$DIFF_OUTPUT"
  exit 1
}

if ! echo "$DIFF_OUTPUT" | grep -q "No changes detected"; then
  echo "$DIFF_OUTPUT"
fi

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"
