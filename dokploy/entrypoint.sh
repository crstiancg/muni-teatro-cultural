#!/bin/sh
# Arranque del contenedor en Dokploy. storage/ es un volumen persistente: ahí
# quedan las fotos subidas, las llaves de Passport, los logs y los backups.
set -e
cd /var/www/html

DATOS=/opt/racc-dokploy/datos
export MYSQL_PWD="$DB_PASSWORD"
MYSQL="mysql -h$DB_HOST -P${DB_PORT:-3306} -u$DB_USERNAME $DB_DATABASE"

mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

echo ">> Esperando a MySQL ($DB_HOST)..."
until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" --silent >/dev/null 2>&1; do
  sleep 2
done

# primera vez con la base vacía: datos de la presentación (si vienen en la imagen)
TABLAS=$($MYSQL -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")
BASE_NUEVA=0
if [ "$TABLAS" = "0" ]; then
  if [ -f "$DATOS/base.sql.gz" ]; then
    echo ">> Base vacía: importando los datos de la presentación"
    gunzip -c "$DATOS/base.sql.gz" | $MYSQL
  else
    BASE_NUEVA=1
  fi
fi

if [ ! -f storage/app/public/.datos-importados ] && [ -f "$DATOS/storage.tar.gz" ]; then
  echo ">> Copiando las fotos de la presentación"
  tar -xzf "$DATOS/storage.tar.gz" -C storage/app/public
  touch storage/app/public/.datos-importados
fi

[ -f storage/oauth-private.key ] || php artisan passport:keys --force
chown -R www-data:www-data storage bootstrap/cache
# con permisos abiertos la librería OAuth rechaza las llaves y todo el login da 500
chmod 600 storage/oauth-private.key storage/oauth-public.key

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache || echo "!! route:cache falló: sigue sin caché de rutas"
php artisan view:cache || true
php artisan migrate --force

# base sin datos: el admin inicial (password@gmail.com / password, cambiarla)
if [ "$BASE_NUEVA" = "1" ]; then
  php artisan db:seed --class=PermissionSeeder --force
fi

exec "$@"
