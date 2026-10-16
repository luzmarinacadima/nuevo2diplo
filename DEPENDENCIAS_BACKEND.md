# Dependencias del frontend con el backend

La aplicación original acopla `index.php` y las clases `capa_presentacion/P*.php` directamente con `capa_negocio/N*.php` y PostgreSQL. Esa ejecución no se copia al frontend. El backend Laravel/PHP que desplieguen los compañeros debe ofrecer los siguientes contratos bajo `API_BASE_URL`.

| Método | Ruta | Uso | Solicitud/respuesta mínima |
|---|---|---|---|
| GET | `/auth/me` | Recuperar la sesión actual al cargar | `{ "user": { "id", "nombres", "apellidos", "tipo_usuario" } }`; HTTP 401 si no hay sesión |
| POST | `/auth/login` | Inicio de sesión | JSON `{ "username", "password" }`; devuelve `{ "user": ... }` |
| POST | `/auth/logout` | Cierre de sesión | HTTP 204 o JSON `{ "ok": true }` |
| GET | `/groups` | Listar grupos | `data[]` con `id`, `nombre`, `capacidad_maxima` |
| POST/PATCH/DELETE | `/groups`, `/groups/{id}` | Crear, editar y eliminar grupos | Validar materia, profesor y capacidad; JSON de resultado |
| GET | `/subjects` | Listar materias | `data[]` con `id`, `nombre` |
| POST/PATCH/DELETE | `/subjects`, `/subjects/{id}` | CRUD de materias | Rechazar eliminación si hay grupos asociados |
| GET | `/students` | Listar estudiantes | `data[]` con `usuario_id`, `codigo_estudiante`, `ci`, `estado` |
| POST/PATCH/DELETE | `/students`, `/students/{id}` | CRUD de estudiantes | Validar usuario base, CI, código y estado |
| GET | `/teachers` | Listar profesores | `data[]` con `usuario_id`, `codigo_profesor` |
| POST/PATCH/DELETE | `/teachers`, `/teachers/{id}` | CRUD de profesores | Validar usuario profesor y código |
| GET | `/users` | Listar usuarios | `data[]` con id, nombres, apellidos, nombre_usuario y tipo_usuario |
| POST/PATCH/DELETE | `/users`, `/users/{id}` | Administración de usuarios | Nunca devolver contraseñas; validar referencias antes de borrar |
| GET | `/classes` | Listar clases | `data[]` con id, nombre, fecha, hora y estado; filtrar por usuario según rol |
| POST/PATCH/DELETE | `/classes`, `/classes/{id}` | Crear, editar y eliminar clases | Validar grupo, materia, profesor y autorización |
| GET | `/attendance/classes/{classId}` | Estado de asistencia | Lista de estudiantes y estado actual |
| POST | `/attendance/classes/{classId}` | Marcar asistencia | JSON con `codigo_estudiante` y `tipo` |
| POST | `/attendance/classes/{classId}/finish` | Finalizar asistencia | JSON con `estrategia` (`manual`, `tardanza` o `falta`) |
| GET | `/attendance/classes/{classId}/report?format=pdf` | Reporte PDF | Respuesta binaria `application/pdf` |
| GET | `/attendance/classes/{classId}/report?format=excel` | Reporte Excel | Respuesta binaria compatible con Excel |
| GET | `/students/available` | Usuarios libres para registrar estudiante | Lista de usuarios disponibles |
| GET | `/students/dashboard` | Dashboard del estudiante autenticado | `{ "data": { "estudiante", "grupos", "clases", "historial", "resumen" } }` |
| POST | `/students/attendance` | Marcar asistencia del estudiante autenticado | JSON `{ "clase_id": 1 }`; el backend calcula presente/tarde según horario |
| GET/POST | `/assignments` | Asignar estudiante a grupo | Filtrar por `group_id`, `student_id` y devolver la asignación |

## Requisitos de seguridad y CORS

1. Permitir únicamente el origen público de la VM frontend; no usar `*` con credenciales.
2. Permitir `GET, POST, PATCH, DELETE, OPTIONS` y los encabezados `Accept, Content-Type, Authorization, X-XSRF-TOKEN`.
3. Responder correctamente a `OPTIONS`.
4. Aplicar autorización por rol en el backend; ocultar tarjetas en el navegador no es una medida de seguridad.
5. Usar HTTPS y cookies `HttpOnly`, `Secure` y `SameSite` apropiadas, o tokens de corta duración. Nunca enviar contraseñas a PostgreSQL desde el frontend.
6. Devolver errores con `{ "message": "...", "errors": { ... } }` y códigos HTTP 401/403/404/422/500 coherentes.
