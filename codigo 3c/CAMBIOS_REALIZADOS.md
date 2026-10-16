# ✅ Cambios Realizados (Actualizado)

Este archivo se actualiza para reflejar que se **eliminaron** los casos de uso relacionados a asistencia por QR y la pantalla de gestión de asistencias.

## Implementaciones Nuevas (Patrón Strategy)

- **CU8: Registrar Asistencia (Finalizar Clase)**: 
  - Se implementó el patrón **Strategy** para automatizar el cierre de clases.
  - El estado **Tarde** se aplica automáticamente si se elige la estrategia "Tardanza Automática" y han pasado más de 5 minutos desde la hora de inicio de la clase.
  - Por diseño, el estado **Tarde** no es seleccionable manualmente (para resaltar el valor del patrón), pero es visible en la UI en color naranja.
  - Se implementó la estrategia "Falta Automática" para convertir estudiantes pendientes en faltas al finalizar.

## Eliminaciones

- CU “Gestionar Asistencia”: se eliminó el acceso desde el panel y la pantalla de administración.
- CU “Registrar Asistencia mediante QR”: se eliminó el flujo completo (generación/descarga de QR por docente y registro por estudiante).

## Cambios en código

- Se quitaron rutas/acciones: `scan_qr`, `registrar_qr`, `generar_qr`, `generar_qr_docente`, `descargar_qr_docente`.
- Se retiró el botón “Generar QR” de la lista de clases.
- Se eliminó la clase de presentación asociada al QR.
- Se removieron métodos de negocio asociados exclusivamente al QR.

## Nota

El registro **manual** de asistencia desde Clases se mantiene (vía `markAttendance` / `addAttendance`).
