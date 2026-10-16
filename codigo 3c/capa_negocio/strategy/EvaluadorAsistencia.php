<?php
require_once __DIR__ . '/IEstrategiaAsistencia.php';
// 🔥 CORREGIDO: Cambiado de 3 a 2 niveles
require_once __DIR__ . '/../../capa_datos/DAsistencia.php';

class EvaluadorAsistencia {
    private ?IEstrategiaAsistencia $estrategia = null;

    public function setEstrategia(IEstrategiaAsistencia $estrategia): void {
        $this->estrategia = $estrategia;
    }

    /**
     * Pasa los 3 parámetros de control directamente a la estrategia activa
     */
    public function procesarAsistencia(array $asistencia, array $claseDetalle, DAsistencia $dAsistencia): bool {
        if ($this->estrategia === null) {
            throw new \Exception("No se ha establecido una estrategia de asistencia.");
        }
        
        // Delegación pura hacia la estrategia concreta
        return $this->estrategia->aplicarAlgoritmo($asistencia, $claseDetalle, $dAsistencia);
    }
}