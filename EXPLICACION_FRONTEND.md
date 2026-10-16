# Explicación técnica de la capa de presentación

## Arquitectura

La capa resultante es un cliente web estático de tres capas. Nginx sirve `index.html`, CSS y JavaScript en la VM 1. El navegador llama por HTTP/HTTPS al backend de la VM 2. Solo el backend accede a PostgreSQL; ninguna ruta del frontend contiene SQL o credenciales.

El proyecto original no es Laravel: sus archivos `P*.php` eran presentadores procedurales que consultaban directamente servicios de negocio y producían HTML. La extracción conserva su lenguaje visual mediante el CSS original, pero elimina la dependencia de PHP en tiempo de ejecución.

## Tecnologías

- HTML5 semántico y accesible.
- CSS original de AsistencIAU, incluyendo modo claro/oscuro, tarjetas, tablas, formularios y notificaciones.
- JavaScript nativo, sin framework ni dependencias externas.
- `fetch` con `credentials: include` para la sesión HTTP.
- Nginx como servidor de archivos y fallback para navegación por hash.

## Flujo de ejecución

1. `index.html` carga `config/api.js`, `styles/style.css` y `js/app.js`.
2. El cliente solicita `GET /auth/me`.
3. Si existe sesión, muestra el nombre, rol y tarjetas equivalentes al dashboard original; si no, muestra login.
4. Cada tarjeta cambia el hash y carga el recurso correspondiente desde la API.
5. Las respuestas JSON se escapan antes de insertarse en tablas.
6. Los errores de red o HTTP se muestran como notificaciones/alertas; no se reemplazan por datos simulados.

## Separación de responsabilidades

El frontend decide presentación, navegación, validación básica y serialización HTTP. El backend debe decidir autenticación, autorización, reglas de asistencia, validación definitiva, transacciones, reportes y acceso a PostgreSQL. La lista completa de contratos se encuentra en [DEPENDENCIAS_BACKEND.md](./DEPENDENCIAS_BACKEND.md).

## Alcance verificado

Se comprobó estáticamente que `frontend-deploy` contiene solo HTML, CSS, JavaScript, configuración de Nginx y carpetas de recursos vacías preparadas para imágenes. No requiere ejecutar Laravel ni PHP. La carga visual local puede iniciarse con un servidor estático; las operaciones de negocio quedan pendientes de que los compañeros publiquen la API descrita.
