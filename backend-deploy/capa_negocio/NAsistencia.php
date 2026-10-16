<?php
require_once __DIR__ . '/../capa_datos/DAsistencia.php';
require_once __DIR__ . '/adapter/IReporteGenerador.php';
require_once __DIR__ . '/strategy/EvaluadorAsistencia.php';
require_once __DIR__ . '/strategy/AsistenciaManual.php';
require_once __DIR__ . '/strategy/TardanzaAutomatica.php';
require_once __DIR__ . '/strategy/FaltaAutomatica.php';
require_once __DIR__ . '/NClase.php';
require_once __DIR__ . '/adapter/PdfReportAdapter.php';
require_once __DIR__ . '/adapter/ExcelReportAdapter.php';

class NAsistencia {
    private $dAsistencia;
    private $nClase;

    public function __construct() {
        $this->dAsistencia = new DAsistencia();
    }

    private function getReporteGenerador(string $formato): IReporteGenerador {
        return match (strtolower($formato)) {
            'pdf' => new \PdfReportAdapter(new \PdfGenerator()),
            'excel' => new \ExcelReportAdapter(new \ExcelWriter()),
            default => throw new \Exception("Formato no soportado"),
        };
    }

    public function getStatusBoardByClass(int $id_clase): array {
        return $this->dAsistencia->getStatusBoardByClass($id_clase);
    }

    /**
     * REGISTRA LA ASISTENCIA EN TIEMPO REAL
     * ¡CLIENTE 100% PURO SIMÉTRICO!
     */
    public function addAttendance(array $asistencia, string $estrategiaNombre): bool {
        try {
            // Paso 1: Obtener datos base o registros de la asistencia (Consulta)
            $id_clase = intval($asistencia['id_clase'] ?? 0);
            $data = $this->dAsistencia->getStatusBoardByClass($id_clase);

            if (empty($data) && $id_clase <= 0) {
                return false;
            }

            // Paso 2: Obtener información complementaria de la clase programada
            $this->nClase = new NClase();
            $claseDetalle = $this->nClase->getClaseDetalle($id_clase);

            // Paso 3: Crear el contexto del patrón Strategy
            $context = new EvaluadorAsistencia();

            // Paso 4: Establecer la estrategia según el tipo solicitado dinámicamente
            switch (strtolower($estrategiaNombre)) {
                case 'tardanza':
                    $context->setEstrategia(new TardanzaAutomatica());
                    break;
                case 'falta':
                    $context->setEstrategia(new FaltaAutomatica());
                    break;
                case 'manual':
                    $context->setEstrategia(new AsistenciaManual());
                    break;
                default:
                    return false;
            }

            // Paso 5: Ejecutar la estrategia delegando los datos y retornar el ejecutable directo
            return $context->procesarAsistencia($asistencia, $claseDetalle, $this->dAsistencia);

        } catch (Exception $e) {
            error_log("Error al procesar asistencia bajo patrón Strategy: " . $e->getMessage());
            return false;
        }
    }

    public function validateAttendance(array $asistencia): bool {
        return $this->dAsistencia->validateAttendance($asistencia);
    }

    public function existe(string $codigo_estudiante, int $id_clase, int $excludedId = 0): bool {
        return $this->dAsistencia->existe($codigo_estudiante, $id_clase, $excludedId);
    }

    public function generarReporte(int $id_clase, string $formato): string {
        $generador = $this->getReporteGenerador($formato);
        $registros = $this->dAsistencia->generarReporte($id_clase);
        if (empty($registros)) return '';

        return $generador->generarReporte('Reporte de Asistencia', $registros);
    }

    public function finalizar(int $id_clase): bool {
        return $this->dAsistencia->finalizar($id_clase);
    }

    public function getDashboardEstudiante(int $usuarioId): array {
        if ($usuarioId <= 0) {
            return ['estudiante' => [], 'grupos' => [], 'clases' => [], 'historial' => [], 'resumen' => ['presente' => 0, 'tarde' => 0, 'falta' => 0]];
        }
        return $this->dAsistencia->getDashboardEstudiante($usuarioId);
    }

    public function marcarAsistenciaEstudiante(int $usuarioId, int $claseId): array {
        if ($usuarioId <= 0 || $claseId <= 0) {
            return ['ok' => false, 'message' => 'No se pudo identificar la clase seleccionada.'];
        }

        $clase = $this->dAsistencia->getStudentClassForAttendance($usuarioId, $claseId);
        if (!$clase) {
            return ['ok' => false, 'message' => 'La clase no pertenece a ninguno de tus grupos asignados.'];
        }

        date_default_timezone_set('America/La_Paz');
        $ahora = new \DateTimeImmutable('now', new \DateTimeZone('America/La_Paz'));
        $inicio = new \DateTimeImmutable($clase['dia'] . ' ' . $clase['hora_inicio'], new \DateTimeZone('America/La_Paz'));
        $fin = new \DateTimeImmutable($clase['dia'] . ' ' . $clase['hora_fin'], new \DateTimeZone('America/La_Paz'));
        // Cambia este valor para ajustar cuántos minutos después del inicio se considera "Tarde".
        $minutosTolerancia = 2;
        $finTolerancia = $inicio->modify('+' . $minutosTolerancia . ' minutes');

        if ($ahora < $inicio) {
            return ['ok' => false, 'message' => 'La asistencia estará disponible a las ' . $inicio->format('H:i') . '.'];
        }
        if ($ahora > $fin) {
            return ['ok' => false, 'message' => 'La clase ya finalizó a las ' . $fin->format('H:i') . '.'];
        }

        $tipo = $ahora <= $finTolerancia ? 'Presente' : 'Tarde';
        return $this->dAsistencia->saveStudentAttendance(
            $clase['codigo_estudiante'],
            $claseId,
            $tipo,
            $ahora->format('H:i:s')
        );
    }
}