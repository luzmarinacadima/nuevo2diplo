# AsistencIAU: backend para VM Ubuntu

Este paquete contiene la API HTTP, las capas de negocio y acceso a datos del proyecto PHP clásico. No contiene la presentación, archivos del frontend, logs, pruebas del proyecto original ni credenciales.

## Requisitos

- Ubuntu 22.04/24.04.
- Nginx.
- PHP 8.2+ con `php-fpm`, `php-pgsql` y `php-mbstring`.
- PostgreSQL 13+.

```bash
sudo apt update
sudo apt install -y nginx postgresql php8.2-fpm php8.2-pgsql php8.2-mbstring
```

## Base de datos

Crear una base y un usuario separados, sin incluir contraseñas en el repositorio:

```bash
sudo -u postgres createuser --pwprompt asistenciau
sudo -u postgres createdb -O asistenciau asistenciau
psql -h 127.0.0.1 -U asistenciau -d asistenciau -f database/schema.sql
```

El esquema se basa en `codigo 3c/bd_asistencia.sql`, pero elimina los inserts de ejemplo y las sentencias `ALTER TABLE` duplicadas. Si se usa una base existente, comprobar que las columnas `clase.finalizada` y `asistencia.hora_marcada` existan antes de publicar.

## Configuración

Copiar las variables de [config/.env.example](config/.env.example) a un mecanismo seguro del sistema, por ejemplo `/etc/asistenciau/backend.env`, y cargarlo en PHP-FPM/Nginx. No poner contraseñas en archivos públicos:

```ini
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=asistenciau
DB_USER=asistenciau
DB_PASSWORD=una-contraseña-secreta
CORS_ALLOWED_ORIGINS=https://frontend.example.com
SESSION_SECURE=1
```

La aplicación usa variables de entorno reales (`getenv`) y no lee `.env` automáticamente. En PHP-FPM se pueden declarar con `env[DB_PASSWORD] = ...` en un pool privado. Ajustar `CORS_ALLOWED_ORIGINS` al origen exacto de la VM frontend.

## Publicación con Nginx

```bash
sudo mkdir -p /var/www/backend-deploy
sudo cp -r backend-deploy/* /var/www/backend-deploy/
sudo cp backend-deploy/nginx.conf /etc/nginx/sites-available/asistenciau-api
sudo ln -sfn /etc/nginx/sites-available/asistenciau-api /etc/nginx/sites-enabled/asistenciau-api
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm
```

Cambiar `server_name` y el socket PHP si la distribución usa otra versión. El bloque solo entrega `/api/index.php`; los PHP de negocio no se publican directamente.

## Contratos y seguridad

Las rutas coinciden con `frontend-deploy/DEPENDENCIAS_BACKEND.md`: `/api/auth/*`, `/api/groups`, `/api/subjects`, `/api/students`, `/api/students/dashboard`, `/api/students/attendance`, `/api/teachers`, `/api/users`, `/api/classes`, `/api/attendance/classes/{id}*` y `/api/assignments`.

La autenticación usa sesión PHP `HttpOnly` y `fetch` con `credentials: include`. En producción se exige HTTPS (`SESSION_SECURE=1`). El backend valida roles y nunca devuelve `contrasena`. El esquema original almacena contraseñas en texto plano; la API conserva esa compatibilidad para no alterar la regla existente, pero debe migrarse a `password_hash()`/`password_verify()` antes de producción real.

## Pruebas

Validar sintaxis sin conectarse a PostgreSQL:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Arranque local:

```bash
DB_PASSWORD='secret' CORS_ALLOWED_ORIGINS='http://localhost:8080' \
php -S 127.0.0.1:8000 api/index.php
curl -i http://127.0.0.1:8000/auth/me
```

La segunda llamada debe devolver `401` sin iniciar sesión. Las pruebas de consultas, autenticación válida, reportes y CORS requieren PostgreSQL inicializado y no pudieron ejecutarse en la copia de trabajo. No se simulan respuestas.

## Integración pendiente con el frontend

El frontend independiente ya incluye formularios CRUD, asignaciones, tablero de asistencia, dashboard del estudiante y descarga de reportes. La integración completa todavía requiere ejecutar pruebas con PostgreSQL real, porque esas operaciones no se pueden validar contra la copia sin una base inicializada.
