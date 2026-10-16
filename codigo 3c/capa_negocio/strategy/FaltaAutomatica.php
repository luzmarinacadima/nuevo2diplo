<?php
require_once __DIR__ . '/IEstrategiaAsistencia.php';
require_once __DIR__ . '/../NEstudiante.php';
// 🔥 CORREGIDO: Cambiado de 3 a 2 niveles
require_once __DIR__ . '/../../capa_datos/DAsistencia.php';

class FaltaAutomatica implements IEstrategiaAsistencia {
    
    /**
     * Aplica la política de Falta Automática de forma masiva sobre los alumnos pendientes.
     * Sincronizado al 100% con la nueva firma unificada de la interfaz.
     */
    public function aplicarAlgoritmo(array $asistencia, array $claseDetalle, DAsistencia $dAsistencia): bool {
        $id_clase = intval($asistencia['id_clase'] ?? 0);
        
        // La estrategia interactúa con el negocio de estudiantes de forma independiente
        $nEstudiante = new NEstudiante();
        $pendientes = $nEstudiante->getPendientesByClass($id_clase);
        
        // Si no hay alumnos rezagados, el algoritmo termina con éxito de inmediato
        if (empty($pendientes)) {
            return true;
        }

        $resultadoGlobal = true;

        // Procesamos masivamente el lote de alumnos ausentes
        foreach ($pendientes as $est) {
            $ok = $dAsistencia->addAttendance([
                'id_clase' => $id_clase,
                'codigo_estudiante' => $est['codigo_estudiante'],
                'tipo' => 'falta'
            ]);

            if (!$ok) {
                $resultadoGlobal = false;
            }
        }

        return $resultadoGlobal;
    }
}
