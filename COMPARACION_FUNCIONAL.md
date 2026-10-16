# Comparación funcional de la presentación

La comparación se realizó contra `codigo 3c/index.php`, `codigo 3c/capa_presentacion/*.php`, `style.css` y `logout.php`. El estado **Implementada** significa que existe código frontend y un contrato HTTP; el estado de prueba distingue las validaciones estáticas de una prueba real con PostgreSQL.

| Archivo original | Funcionalidad original | Archivo nuevo | Endpoint utilizado | Implementación | Prueba | Problemas pendientes |
|---|---|---|---|---|---|---|
| `index.php` | Login, sesión, panel por rol, logout y tema | `frontend-deploy/js/app.js` | `/auth/me`, `/auth/login`, `/auth/logout` | Implementada | Sintaxis y smoke HTTP 401; login real no probado | Requiere PostgreSQL y sesión HTTPS |
| `PGrupo.php` | Listar, crear, editar y eliminar grupos | `frontend-deploy/js/app.js` | `/groups`, `/groups/{id}` | Implementada | Sintaxis; CRUD real no probado | El backend debe tener materias/profesores existentes |
| `PMateria.php` | CRUD de materias y protección por referencias | `frontend-deploy/js/app.js` | `/subjects`, `/subjects/{id}` | Implementada | Sintaxis; CRUD real no probado | Mensajes dependen de respuestas del backend |
| `PEstudiante.php` | CRUD de estudiantes y usuarios base disponibles | `frontend-deploy/js/app.js` | `/students`, `/students/{id}`, `/students/available` | Implementada | Sintaxis; formulario real no probado | Edición conserva la validación del contrato actual |
| `PProfesor.php` | CRUD de profesores asociados a usuarios | `frontend-deploy/js/app.js` | `/teachers`, `/teachers/{id}`, `/users` | Implementada | Sintaxis; CRUD real no probado | Requiere usuario de tipo profesor |
| `PUsuario.php` | CRUD de usuarios, login y no exponer contraseña | `frontend-deploy/js/app.js` | `/users`, `/users/{id}`, `/auth/*` | Implementada | Sintaxis; autenticación real no probada | El esquema heredado usa contraseñas planas; debe migrarse antes de producción |
| `PClase.php` | CRUD de clases, listado por profesor y acciones de asistencia | `frontend-deploy/js/app.js` | `/classes`, `/classes/{id}` | Implementada | Sintaxis; CRUD real no probado | El backend aplica el filtro de profesor en listado |
| `PaGrupo.php` | Asignar estudiantes, validar cupos y actualizar capacidad | `frontend-deploy/js/app.js` | `/assignments` | Implementada | Sintaxis; operación real no probada | No se pudo probar sin PostgreSQL |
| `PAsistencia.php` | Tablero, marcar, finalizar y reportes PDF/Excel | `frontend-deploy/js/app.js` | `/attendance/classes/{id}`, `/finish`, `/report` | Implementada | Sintaxis; descarga y reglas reales no probadas | Reportes requieren FPDF/adaptadores y datos |
| `PMarcarAsistencia.php` | Dashboard del estudiante e identificación por horario | `frontend-deploy/js/app.js` | `/students/dashboard`, `/students/attendance` | Implementada | Sintaxis; integración real no probada | Endpoints fueron añadidos al backend porque eran necesarios |
| `style.css` | Colores, distribución, tarjetas, formularios, tablas, tema y toast | `frontend-deploy/styles/style.css` | No aplica | Conservada | Hash comparado con original | Ninguno conocido |
| `logout.php` | Destruir sesión | `frontend-deploy/js/app.js` | `POST /auth/logout` | Adaptada | Ruta HTTP smoke indirecta | Requiere cookie de sesión con HTTPS en Azure |

## Validaciones ejecutadas

- `node --check` pasó para `frontend-deploy/js/app.js` y `config/api.js`.
- `php -l` pasó para todos los PHP de `backend-deploy`.
- Se comprobó que el frontend no referencia `P*.php`, `index.php` ni `logout.php`.
- Se comprobó el arranque estático del frontend y respuesta HTTP 200.
- Se comprobó respuesta JSON HTTP 401 del backend sin sesión.
- No se ejecutaron CRUD, login válido, asistencia ni reportes contra PostgreSQL porque no había una base inicializada en el entorno.

## Integración pendiente

Configurar en el frontend la URL pública del backend:

```js
window.API_BASE_URL = 'https://backend.example.com/api';
```

El backend debe permitir exactamente el origen público del frontend mediante `CORS_ALLOWED_ORIGINS`, servir HTTPS y conservar cookies de sesión `HttpOnly` y `Secure`.
