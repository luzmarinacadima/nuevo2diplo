# Cambios realizados

## Análisis

- La carpeta principal de presentación original es `codigo 3c/capa_presentacion`, con `PGrupo.php`, `PMateria.php`, `PClase.php`, `PAsistencia.php`, `PMarcarAsistencia.php`, `PEstudiante.php`, `PaGrupo.php` y `PUsuario.php`.
- La presentación también dependía de `codigo 3c/index.php`, `style.css`, `logout.php` y del router de PHP.
- No se encontraron vistas Blade, `@extends`, `@include`, `route()`, `asset()`, `package.json`, Vite, Laravel ni Composer.
- Las clases `P*` mezclan HTML con llamadas a `N*`; las clases `N*` llaman a `D*`, `DatabaseHelper.php` y PostgreSQL. Por tanto, esos PHP no son ejecutables en una VM de frontend sin llevarse el backend completo.

## Contenido generado

- `frontend-deploy/index.html`: shell visual equivalente al encabezado original.
- `frontend-deploy/styles/style.css`: copia exacta del CSS original; no se rediseñó la interfaz.
- `frontend-deploy/js/app.js`: navegación, tema, login, toast, tablas y adaptador HTTP.
- `frontend-deploy/config/api.js`: única configuración de `API_BASE_URL`, sin secretos.
- `frontend-deploy/nginx.conf`: publicación estática y fallback de navegación.

## Exclusiones

No se copiaron controladores de negocio, modelos, datos, SQL, `DatabaseHelper.php`, FPDF, adaptadores de reportes, estrategias, pruebas PHP, logs, sesiones ni credenciales. Los originales no fueron modificados ni eliminados.

## Adaptación funcional

La generación de HTML del servidor se reemplazó por renderizado JavaScript y contratos HTTP. Se incorporaron formularios CRUD, asignaciones, tablero de asistencia, finalización, reportes, dashboard del estudiante y marcado según horario. Las operaciones que requieren datos siguen siendo reales: si el endpoint no existe o no responde, se muestra un error y no se presentan datos falsos. Los endpoints necesarios están enumerados en [DEPENDENCIAS_BACKEND.md](./DEPENDENCIAS_BACKEND.md).
