<?php
require_once __DIR__ . '/IEstrategiaAsistencia.php';
// 🔥 CORREGIDO: Cambiado de 3 a 2 niveles
require_once __DIR__ . '/../../capa_datos/DAsistencia.php';

class AsistenciaManual implements IEstrategiaAsistencia {
    
    /**
     * Aplica el registro manual respetando el estado enviado desde la interfaz de usuario.
     * Sincronizado al 100% con la firma unificada de 3 parámetros.
     */
    public function aplicarAlgoritmo(array $asistencia, array $claseDetalle, DAsistencia $dAsistencia): bool {
        // Encapsulamos el registro individual en un lote secuencial para el bucle while
        $loteEstudiantes = [
            [
                'codigo_estudiante' => trim((string)($asistencia['codigo_estudiante'] ?? '')),
                'tipo' => trim((string)($asistencia['tipo'] ?? ($asistencia['estado'] ?? 'presente')))
            ]
        ];

        $resultadoGlobal = true;

        // Procesamiento iterativo condicional mediante bucle while con array_shift
        while ($estudiante = array_shift($loteEstudiantes)) {
            if ($estudiante['codigo_estudiante'] === '') {
                $resultadoGlobal = false;
                continue;
            }

            // Consumimos el objeto de persistencia inyectado directamente
            $ok = $dAsistencia->addAttendance([
                'id_clase' => $asistencia['id_clase'],
                'codigo_estudiante' => $estudiante['codigo_estudiante'],
                'tipo' => strtolower($estudiante['tipo'])
            ]);

            if (!$ok) {
                $resultadoGlobal = false;
            }
        }

        return $resultadoGlobal;
    }
}