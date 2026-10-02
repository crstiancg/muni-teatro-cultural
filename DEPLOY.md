# Deploy en cPanel

Guía para publicar el sistema en un hosting cPanel **sin Node.js**. Front (Quasar) y API (Laravel) van en el **mismo dominio**: Laravel sirve la SPA y le escribe el `<head>` de cada página pública (título, descripción, Open Graph, JSON-LD) para que Google y WhatsApp vean el contenido.

```
https://tudominio/                 -> SPA (portal público y panel)
https://tudominio/api/...          -> API de Laravel
https://tudominio/storage/...      -> fotos y archivos subidos
https://tudominio/sitemap.xml      -> generado por Laravel
```

## Requisitos del hosting

- PHP 8.3 o superior con extensiones: `gd`, `exif`, `zip`, `pdo_mysql`, `mbstring`, `openssl`.
- MySQL / MariaDB.
- Acceso SSH o terminal de cPanel (para `composer` y `php artisan`).
- Cron jobs.
- El dominio debe poder apuntar a la carpeta `backend/public`.

## Camino recomendado: paquete .zip + SSH

En el servidor **no se instala ni se compila nada**: el paquete ya trae `vendor` de producción y el front compilado. Solo se descomprime, se completa el `.env` y se corren los comandos de `artisan` por SSH. Las secciones 1 a 4 de más abajo explican cada paso en detalle.

### En tu máquina

```bash
# 1. fronted/.env de producción: la API vacía (mismo dominio)
#    QCLI_API_BACKEND_URL=

# 2. compilar el front y publicarlo dentro de Laravel
cd fronted
quasar build
cd ../backend
php artisan spa:publicar

# 3. armar el paquete (agrega --con-datos para llevar la base y las fotos actuales)
php artisan deploy:empaquetar
```

Genera `backend/storage/app/deploy/racc-deploy-FECHA.zip`. El comando **se niega a empaquetar** si el front quedó apuntando a `127.0.0.1` (el error más común) y nunca incluye el `.env`, las claves de Passport, la configuración cacheada ni archivos de prueba.

### En el servidor (primera vez)

```bash
# subir el paquete (o con el Administrador de archivos de cPanel)
scp backend/storage/app/deploy/racc-deploy-FECHA.zip USUARIO@SERVIDOR:~/

ssh USUARIO@SERVIDOR
php -v                      # debe ser la misma versión de PHP que en tu máquina o mayor
cd ~
unzip racc-deploy-FECHA.zip # crea ~/racc (fuera de public_html: el .env no queda expuesto)
cd racc

cp .env.ejemplo-produccion .env
nano .env                   # completar base de datos, dominio, tokens (ver sección 2)

php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force   # SOLO la primera vez (ver 3.3)
php artisan passport:keys
php artisan passport:client --password --name="Panel" --provider=users
nano .env                   # pegar PASSPORT_PASSWORD_CLIENT_ID y _SECRET que imprimió el paso anterior
php artisan storage:link    # si falla: PUBLIC_DISK_EN_PUBLIC=true en el .env
chmod -R 775 storage bootstrap/cache
php artisan config:cache
php artisan route:cache
```

Si `php -v` muestra una versión vieja, cPanel suele tener varias: usa la ruta completa, por ejemplo `/opt/cpanel/ea-php84/root/usr/bin/php artisan ...`, también en el cron.

Después, en cPanel: el subdominio con raíz del documento en `racc/public`, **AutoSSL** activo, y el cron `* * * * * php /home/USUARIO/racc/artisan schedule:run >> /dev/null 2>&1`.

### Con datos iniciales (`--con-datos`)

El paquete trae `datos-iniciales/backup-FECHA.zip`. En lugar de `migrate` y `db:seed`:

```bash
cd ~/racc/datos-iniciales
unzip backup-FECHA.zip
mysql -u USUARIO_DB -p NOMBRE_DB < base.sql
cp -r storage/* ~/racc/storage/app/public/   # o a public/storage si usas PUBLIC_DISK_EN_PUBLIC
cd ~/racc && rm -rf datos-iniciales
```

Igual se generan llaves y cliente de Passport **nuevos** (`passport:keys` y `passport:client`), y se cambia la contraseña del admin.

### Actualizaciones

```bash
# en tu máquina: quasar build, spa:publicar y deploy:empaquetar (como arriba)
# en el servidor:
cd ~
unzip -o racc-deploy-FECHA.zip   # pisa el código; el .env y storage/ no vienen en el zip: se conservan
cd racc
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

## 1. Preparar el front (en tu máquina)

1. En `fronted/.env` de producción, deja la API **vacía** (rutas relativas, mismo dominio):

   ```env
   QCLI_API_BACKEND_URL=
   ```

   No va `QCLI_APP_SECRET`: el secret de Passport vive solo en el backend.

2. Compila y publica el build dentro de Laravel:

   ```bash
   cd fronted
   quasar build
   cd ../backend
   php artisan spa:publicar
   ```

   `spa:publicar` copia los assets a `backend/public/` y el `index.html` a `backend/resources/spa/index.html`. El `index.html` **no** va a `public/`: si Apache lo sirviera directo, se perderían las meta tags de cada página.

3. **Ensayo local antes de subir:** `php artisan serve` y abrir `http://127.0.0.1:8000` (sin `:9000`). Revisar portada, un perfil, una agrupación, login, panel, subir una foto, `/sitemap.xml` y `/robots.txt`.

## 2. `.env` del backend en producción

```env
APP_NAME="Registro Cultural de Puno"   # aparece en los títulos de Google
APP_ENV=production
APP_DEBUG=false                        # CRÍTICO: en true, un error muestra contraseñas y rutas
APP_URL=https://tudominio
FRONTEND_URL=https://tudominio         # igual que APP_URL (mismo dominio)

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# cliente "password grant" de Passport (ver paso 3.4)
PASSPORT_PASSWORD_CLIENT_ID=...
PASSPORT_PASSWORD_CLIENT_SECRET=...

# consulta de DNI (RENIEC) para autocompletar nombres
APIS_NET_PE_TOKEN=...

# máximo de agrupaciones que puede representar una persona
AGRUPACIONES_MAXIMO=2

# superadministrador: oculto en la lista de usuarios, nadie más puede verlo ni borrarlo
USUARIOS_OCULTOS=1

# solo si el hosting NO permite symlinks (ver paso 3.6)
# PUBLIC_DISK_EN_PUBLIC=true
```

Opcionales: `SEO_CIUDAD` (por defecto `Puno`) y `SEO_DESCRIPCION` (descripción del sitio en Google).

## 3. Primera instalación en el servidor

Desde la carpeta `backend`:

1. Dependencias:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate        # solo la primera vez, si APP_KEY está vacío
   ```

2. Base de datos:

   ```bash
   php artisan migrate --force
   ```

3. Roles y permisos (**una sola vez**):

   ```bash
   php artisan db:seed --class=PermissionSeeder --force
   ```

   > ⚠️ Este seeder crea el admin `password@gmail.com` con contraseña `password`. **Cámbiala apenas entres.** No vuelvas a correrlo después: vuelve a poner esa contraseña.

4. Passport (llaves y cliente del login):

   ```bash
   php artisan passport:keys
   php artisan passport:client --password --name="Panel" --provider=users
   ```

   Copia el **Client ID** y el **Client secret** que imprime a `PASSPORT_PASSWORD_CLIENT_ID` y `PASSPORT_PASSWORD_CLIENT_SECRET`. Usa un cliente **nuevo**: el de desarrollo estuvo expuesto en el bundle del front.

5. Permisos de carpetas: `storage/` y `bootstrap/cache/` con escritura para el usuario del hosting.

6. Archivos públicos (fotos, logos, certificados):

   ```bash
   php artisan storage:link
   ```

   Si el hosting no permite symlinks, en lugar de eso pon `PUBLIC_DISK_EN_PUBLIC=true` en el `.env`: los archivos se guardan directo en `public/storage`.

7. Caché de configuración y rutas:

   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

8. En cPanel → Dominios: el dominio apunta a `backend/public`, con **HTTPS** activo y redirección de `http://` y `www` a la versión principal.

9. Cron (cPanel → Cron Jobs), **cada minuto**:

   ```
   * * * * * php /home/USUARIO/backend/artisan schedule:run >> /dev/null 2>&1
   ```

   Corre el backup diario de las 3:00 (`backup:diario`).

## 4. Actualizaciones (deploys siguientes)

```bash
# en tu máquina
cd fronted && quasar build && cd ../backend && php artisan spa:publicar

# en el servidor, después de subir el código
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Si se agregaron permisos nuevos, crearlos con `php artisan tinker` (no correr el `PermissionSeeder` completo).

## 5. Verificar después de publicar

- [ ] La portada, un perfil (`/consejeros/{slug}`) y una agrupación (`/agrupaciones/{slug}`) cargan, y el título de la pestaña es el de esa página.
- [ ] Un perfil que no existe responde 404.
- [ ] Login funciona y el panel muestra el menú según el rol.
- [ ] Subir una foto y verla en el portal.
- [ ] `https://tudominio/sitemap.xml` y `/robots.txt` responden.
- [ ] Compartir un perfil por WhatsApp muestra la vista previa con foto.
- [ ] `APP_DEBUG=false` (una URL de API inexistente no debe mostrar detalles del error).

## 6. Después del lanzamiento

- Google Search Console: verificar el dominio y enviar `https://tudominio/sitemap.xml`.
- Pedir un enlace al portal desde la web oficial de la municipalidad.
- La política de privacidad (`/privacidad`) debe aprobarla el área legal, y completar los datos de contacto marcados como `PENDIENTE` en `fronted/src/config/institucion.js` (requiere nuevo build).

## Backups

- `php artisan backup:diario` genera `backend/storage/app/backups/backup-FECHA.zip` con `base.sql` y la carpeta de archivos subidos. Se conservan los últimos 7.
- Los backups quedan **en el mismo servidor**: descarga uno periódicamente a otro lugar.
- Restaurar: importar `base.sql` desde phpMyAdmin y copiar la carpeta `storage/` del zip a la carpeta del disco público (`storage/app/public`, o `public/storage` si usas `PUBLIC_DISK_EN_PUBLIC`).

## Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| "Falta el build del front" | No se corrió `php artisan spa:publicar` |
| Login: "Falta configurar el cliente de Passport" | Faltan `PASSPORT_PASSWORD_CLIENT_ID` / `_SECRET`, o hay config cacheada vieja: `php artisan config:cache` |
| Fotos rotas (404 en `/storage/...`) | Falta `storage:link` o `PUBLIC_DISK_EN_PUBLIC=true` |
| Los títulos dicen "Laravel" | Falta `APP_NAME` |
| Un cambio en el `.env` no se aplica | Volver a correr `php artisan config:cache` |
| Recargar una página del panel da 404 de Apache | El dominio no apunta a `backend/public` o falta su `.htaccess` |
