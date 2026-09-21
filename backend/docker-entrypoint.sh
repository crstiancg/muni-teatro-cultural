#!/bin/sh
set -e
cd /app

if [ ! -f .env ]; then
  echo ">> Creando .env para Docker"
  sed -e 's/^DB_HOST=.*/DB_HOST=db/' \
      -e 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' \
      .env.example > .env
fi

echo ">> composer install"
composer install --no-interaction --prefer-dist --no-progress

grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

echo ">> Esperando a MySQL..."
until php -r 'new PDO("mysql:host=db;port=3306;dbname=backend","root","secret");' >/dev/null 2>&1; do
  sleep 2
done
echo ">> MySQL listo"

php artisan migrate --force

if [ ! -f storage/.seeded ]; then
  echo ">> Ejecutando seeders"
  php artisan db:seed --force && touch storage/.seeded
fi

[ -f storage/oauth-private.key ] || php artisan passport:keys --force

exec php artisan serve --host=0.0.0.0 --port=8000
