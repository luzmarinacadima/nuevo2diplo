# AsistencIAU: frontend separado

Este directorio contiene el frontend independiente extraído de la copia del proyecto. El repositorio original es una aplicación PHP clásica de tres capas; no usa Laravel, Blade, Vite ni Composer para la presentación. Por eso `frontend-deploy` es un sitio estático servido por Nginx y no requiere ejecutar PHP.

## Desarrollo local

```bash
cd frontend-deploy
python3 -m http.server 8080
```

La aplicación se abre en `http://localhost:8080`. La URL del backend se configura en `frontend-deploy/config/api.js` mediante `API_BASE_URL`. En un entorno real se recomienda generar ese archivo durante el despliegue y no almacenar credenciales.

## Ubuntu y Nginx

```bash
sudo apt update
sudo apt install -y nginx
sudo mkdir -p /var/www/frontend-deploy
sudo cp -r frontend-deploy/* /var/www/frontend-deploy/
sudo cp frontend-deploy/nginx.conf /etc/nginx/sites-available/frontend-deploy
sudo ln -sfn /etc/nginx/sites-available/frontend-deploy /etc/nginx/sites-enabled/frontend-deploy
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

No hay dependencias npm que instalar ni una compilación que ejecutar. La comprobación mínima es `curl -I http://localhost/` y abrir la página desde un navegador. Las rutas de navegación son hashes (`#listar`, `#materias`, etc.); la regla `try_files` de Nginx permite recargar cualquier ruta del sitio.

## Backend

El frontend requiere los contratos descritos en [DEPENDENCIAS_BACKEND.md](./DEPENDENCIAS_BACKEND.md). El backend debe habilitar CORS para el origen de la VM frontend, usar cookies de sesión seguras (`SameSite=None; Secure` cuando corresponda) o documentar el mecanismo Bearer equivalente, y responder JSON. El frontend no conoce ni contiene credenciales de PostgreSQL.

## Validación realizada

- Se inspeccionó la estructura completa de la copia y se conservaron los estilos originales en `frontend-deploy/styles/style.css`.
- Se comprobó que el frontend no contiene PHP, SQL, clases de negocio, acceso a datos ni credenciales.
- Se revisaron las referencias de HTML a CSS y JavaScript.
- No fue posible probar operaciones con datos porque la copia no expone una API HTTP y no se debe inventar una respuesta del backend. La aplicación muestra el error de conexión de forma explícita hasta que los endpoints estén disponibles.

La matriz detallada de equivalencia entre la presentación original y las pantallas independientes está en [COMPARACION_FUNCIONAL.md](./COMPARACION_FUNCIONAL.md). El frontend ahora incluye formularios CRUD, asignaciones, asistencia, dashboard del estudiante y descarga de reportes; las pruebas contra datos reales requieren PostgreSQL inicializado.
