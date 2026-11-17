# Deploy en Dokploy

Una sola imagen (`dokploy/Dockerfile`) con el front compilado, Laravel, nginx, php-fpm
y el scheduler (backup diario). Dokploy la construye desde el repo de GitHub, la base
es un servicio MySQL aparte y `storage/` vive en un volumen persistente.

> Dokploy construye desde un **clon de git**: lo que está en `.gitignore` (el `.env`,
> `dokploy/datos/`, las fotos locales) **no llega a la imagen**. Todo se configura acá.

## 1. Base de datos

En el proyecto de Dokploy: **Create Service → Database → MySQL** (8.x).
Anotá el *Internal Host*, la base, el usuario y la contraseña: van en el paso 3.

## 2. Aplicación

**Create Service → Application**, y en la pestaña *General*:

| Campo | Valor |
|---|---|
| Provider | GitHub → `crstiancg/muni-teatro-cultural`, rama `main` |
| Build Type | **Dockerfile** |
| Docker File | `dokploy/Dockerfile` |
| Docker Context Path | `.` (la raíz del repo, no `dokploy/`) |

## 3. Variables de entorno (pestaña *Environment*)

```env
APP_NAME="CPC Puno"
APP_ENV=production
APP_DEBUG=false                     # CRÍTICO: en true un error muestra contraseñas
APP_KEY=                            # generala una vez: php artisan key:generate --show
APP_URL=https://cpc.munipuno.gob.pe
FRONTEND_URL=https://cpc.munipuno.gob.pe   # IGUAL que APP_URL: va impreso en el QR de cada ficha

DB_CONNECTION=mysql
DB_HOST=                            # el Internal Host del paso 1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# login del panel: se completan en el paso 6 (después del primer deploy)
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=

# consulta de DNI (RENIEC) para autocompletar nombres
APIS_NET_PE_TOKEN=

# ficha de registro de agente cultural (config/registro.php)
REGISTRO_TEMA=negro                 # color del PDF: negro | azul (#004173)

# identidad SEO (config/seo.php): perfiles oficiales, separados por coma
SEO_REDES=
```

Opcionales: `REGISTRO_VERIFICACION_URL` (otro dominio para el QR; por defecto el del
sitio), `REGISTRO_PREFIJO` (por defecto `CPC`), `REGISTRO_ENTIDAD`, `REGISTRO_GERENCIA`,
`REGISTRO_CONSEJO`, `SEO_MARCA` (por defecto `CPC Puno`), `AGRUPACIONES_MAXIMO`,
`USUARIOS_OCULTOS`. Ver `DEPLOY.md` §2 para el detalle de cada una.

> ⚠️ **`FRONTEND_URL` es el dominio que se imprime en el QR de las fichas.** Una ficha
> impresa con un QR roto no se arregla después. Si falta o tiene un valor de ejemplo,
> el sistema usa el dominio de la visita y el log del contenedor avisa con
> `!! ATENCION: FRONTEND_URL...`, pero configuralo bien desde el principio.

## 4. Volumen (pestaña *Advanced → Volumes*)

| Tipo | Nombre | Mount Path |
|---|---|---|
| Volume Mount | `cpc-storage` | `/var/www/html/storage` |

Ahí quedan las **fotos subidas**, las **llaves de Passport**, los logs, los backups y la
caché de fuentes del PDF. **Sin este volumen, cada deploy borra todas las fotos** y
desloguea a todo el mundo (las llaves se regeneran).

## 5. Dominio (pestaña *Domains*)

Host `cpc.munipuno.gob.pe`, **Container Port `80`**, HTTPS activado con Let's Encrypt.
Dokploy (Traefik) termina el HTTPS; nginx le avisa a PHP para que las URLs salgan con
`https` (ver `nginx.conf`).

Después: **Deploy**. En el primer arranque, con la base vacía, el contenedor:
espera a MySQL → genera las llaves de Passport → corre las migraciones (incluye el
registro de agentes y la corrección de tildes de las comisiones) → crea el admin inicial.

## 6. Primer deploy: cliente de login (una sola vez)

El panel no deja entrar hasta que exista el cliente "password grant" de Passport.
En Dokploy, pestaña **Docker → Open Terminal** del contenedor:

```bash
php artisan passport:client --password --name="Panel" --provider=users
```

Copiá el **Client ID** y el **Client secret** a `PASSPORT_PASSWORD_CLIENT_ID` y
`PASSPORT_PASSWORD_CLIENT_SECRET` (paso 3) y hacé **Redeploy**: la configuración se
cachea al arrancar, así que sin redeploy no las ve. El secret se muestra **una sola
vez** (Passport lo guarda hasheado): si se pierde, se crea otro cliente.

Después entrá con `password@gmail.com` / `password` y **cambiá la contraseña ya**.

## 7. Datos iniciales (opcional)

`dokploy/datos/` está en `.gitignore`, así que Dokploy no lo ve. Para cargar la base y
las fotos de la presentación, desde la terminal del contenedor (o el servicio MySQL):

```bash
gunzip -c base.sql.gz | mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
tar -xzf storage.tar.gz -C storage/app/public
php artisan migrate --force          # lleva esa base a la versión actual
```

Hacelo **antes** del paso 6 si la base trae usuarios, para no crear el admin dos veces.

## 8. Verificar después de publicar

- [ ] `https://DOMINIO/` carga la portada con el logo del CPCP y la pestaña dice "… | CPC Puno".
- [ ] Login del panel funciona (si da 500: paso 6, o permisos de las llaves).
- [ ] Aprobar un perfil de prueba → en *Curriculum Vitae* aparece **CPC0001** → **Ver ficha**
      abre el PDF en una sola hoja.
- [ ] Escanear el QR de esa ficha con el celular: abre `https://DOMINIO/v/…` y dice
      "Registro vigente". Si abre `127.0.0.1` o `tudominio`, revisá `FRONTEND_URL`.
- [ ] `/sitemap.xml`, `/robots.txt` y `/llms.txt` muestran el dominio real.
- [ ] En los logs del contenedor no aparece `!! ATENCION`.
- [ ] Compartir el link de la portada por WhatsApp muestra la imagen con el logo y "CPC Puno"
      (si muestra la vieja: forzar la relectura en developers.facebook.com/tools/debug).

## Deploys siguientes

Push a `main` → **Redeploy** (o activá *Autodeploy* en la pestaña *General*). Las
migraciones corren solas al arrancar y el volumen conserva fotos, llaves y backups.
