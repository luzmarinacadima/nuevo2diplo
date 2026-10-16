<?php
require_once __DIR__ . '/../capa_negocio/NClase.php';
require_once __DIR__ . '/../capa_negocio/NAsistencia.php';
require_once __DIR__ . '/../capa_negocio/NEstudiante.php';
require_once __DIR__ . '/../capa_negocio/adapter/PdfReportAdapter.php';
require_once __DIR__ . '/../capa_negocio/adapter/ExcelReportAdapter.php';

class PAsistencia {
    // Atributos del diagrama
    public int $id_clase;
    public string $codigo_estudiante;
    public string $tipo;
    // Atributos agregados para presentación
    public string $grupo_nombre;
    public string $materia_nombre;
    public string $profesor_nombre;

    private $nAsistencia;
    private $nEstudiante;
    private $nClase;

    public function __construct() {
        $this->nAsistencia = new NAsistencia();
        $this->nEstudiante = new NEstudiante();
        $this->nClase = new NClase();
    }

    // Métodos exactamente como el diagrama
    public function getStatusBoardByClass(int $id_clase): array {
        date_default_timezone_set('America/La_Paz');
        $statusBoard = $this->nAsistencia->getStatusBoardByClass($id_clase);
        $pendientes = $this->getPendientesByClass($id_clase);
        $claseDetalle = $this->getClaseDetalle($id_clase);
        $total = count($statusBoard) + count($pendientes);
        $presentes = 0; $faltas = 0; $licencias = 0; $tardes = 0;
        
        foreach ($statusBoard as $row) {
            $tipoRow = strtolower($row['tipo'] ?? '');
            if ($tipoRow === 'presente') $presentes++;
            elseif ($tipoRow === 'falta') $faltas++;
            elseif ($tipoRow === 'licencia') $licencias++;
            elseif ($tipoRow === 'tarde') $tardes++;
        }
        $pendientesCount = count($pendientes);

        echo '<div class="card card-md" style="margin-bottom:32px;">';
        echo '<h2 style="margin-bottom:0.7em;">Registro de Asistencia</h2>';
        echo '<div style="display:flex;flex-wrap:wrap;gap:2.5em 3em;align-items:center;margin-bottom:0.7em;">';
        echo '<div style="min-width:220px;">';
        echo '<div style="font-size:1.08em;margin-bottom:0.2em;"><b>Clase #'.htmlspecialchars($id_clase).'</b></div>';
        echo '<div style="margin-bottom:0.2em;"><b>Grupo:</b> '.htmlspecialchars($claseDetalle['grupo_nombre'] ?? '-').'</div>';
        echo '<div style="margin-bottom:0.2em;"><b>Materia:</b> '.htmlspecialchars($claseDetalle['materia_nombre'] ?? '-').'</div>';
        echo '<div style="margin-bottom:0.7em;"><b>Profesor:</b> '.htmlspecialchars($claseDetalle['profesor_nombre'] ?? '-').'</div>';
        echo '<div class="asistencia-status-summary" style="margin-bottom:0;gap:0.5em;">';
        echo '<span class="badge badge-total">Total: '.$total.'</span> ';
        echo '<span class="badge badge-pendientes">Pendientes: '.$pendientesCount.'</span> ';
        echo '<span class="badge badge-presentes">Presentes: '.$presentes.'</span> ';
        echo '<span class="badge badge-tardes" style="background:#fff7ed;color:#ea580c;border:1px solid #fdba74;padding:2px 8px;border-radius:12px;font-size:0.85em;font-weight:600;">Tardes: '.$tardes.'</span> ';
        echo '<span class="badge badge-faltas">Faltas: '.$faltas.'</span> ';
        echo '<span class="badge badge-licencias">Licencias: '.$licencias.'</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // Mostrar tabla de estado
        echo '<div class="asistencia-status-box">';
        echo '<div class="table-container" style="margin-top:0.5em;margin-bottom:1.5em;">';
        echo '<table class="table"><thead><tr><th>ESTUDIANTE</th><th>CÓDIGO</th><th>ESTADO</th></tr></thead><tbody>';
        foreach ($statusBoard as $row) {
            $nombre = isset($row['apellidos']) || isset($row['nombres']) ? trim(($row['apellidos'] ?? '') . ' ' . ($row['nombres'] ?? '')) : '';
            $estado = strtolower($row['tipo'] ?? '-');
            $badge = '<span class="status-pill';
            if ($estado === 'presente') $badge .= ' status-pill--present">Presente</span>';
            elseif ($estado === 'falta') $badge .= ' status-pill--absent">Falta</span>';
            elseif ($estado === 'licencia') $badge .= ' status-pill--license">Licencia</span>';
            elseif ($estado === 'tarde') $badge .= ' status-pill--late">Tarde</span>';
            else $badge .= ' status-pill--pending">'.ucfirst($estado).'</span>';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($nombre) . '</td>';
            echo '<td><span class="code-pill">' . htmlspecialchars($row['codigo_estudiante'] ?? '-') . '</span></td>';
            echo '<td>' . $badge . '</td>';
            echo '</tr>';
        }
        foreach ($pendientes as $est) {
            $nombre = isset($est['apellidos']) || isset($est['nombres']) ? trim(($est['apellidos'] ?? '') . ' ' . ($est['nombres'] ?? '')) : '';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($nombre) . '</td>';
            echo '<td><span class="code-pill">' . htmlspecialchars($est['codigo_estudiante'] ?? '-') . '</span></td>';
            echo '<td><span class="status-pill status-pill--pending">Pendiente</span></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        echo '</div>';

        // FORMULARIO INTERACTIVO: Registrar asistencia con inyección de estrategia por lote
        if (count($pendientes) > 0) {
            echo '<form method="POST" class="form-asistencia" style="margin-top:2.5em;max-width:520px;padding:2.2em 2.2em 1.7em 2.2em;background:#f9fafe;border-radius:18px;box-shadow:0 4px 18px #0001;">';
            echo '<input type="hidden" name="id_clase" value="'.htmlspecialchars($id_clase).'">';
            
            echo '<div class="input-group" style="margin-bottom:1.7em;">';
            echo '<label for="codigo_estudiante" style="font-weight:600;display:block;margin-bottom:0.5em;">Estudiante:</label>';
            echo '<select name="codigo_estudiante" id="codigo_estudiante" class="form-control select" style="width:100%;padding:0.7em 1em;border-radius:8px;border:1.5px solid #c3cbe4;font-size:1.1em;" required>';
            echo '<option value="">Seleccione un estudiante...</option>';
            foreach ($pendientes as $est) {
                $nombre = isset($est['apellidos']) || isset($est['nombres']) ? trim(($est['apellidos'] ?? '') . ' ' . ($est['nombres'] ?? '')) : '';
                echo '<option value="'.htmlspecialchars($est['codigo_estudiante']).'">'.htmlspecialchars($nombre.' ('.$est['codigo_estudiante'].')').'</option>';
            }
            echo '</select>';
            echo '</div>';
            
            echo '<div class="input-group" style="margin-bottom:1.7em;">';
            echo '<label for="tipo" style="font-weight:600;display:block;margin-bottom:0.5em;">Estado:</label>';
            echo '<select name="tipo" id="tipo" class="form-control select" style="width:100%;padding:0.7em 1em;border-radius:8px;border:1.5px solid #c3cbe4;font-size:1.1em;" required>';
            echo '<option value="Presente">Presente</option>';
            echo '<option value="Falta">Falta</option>';
            echo '<option value="Licencia">Licencia</option>';
            echo '</select>';
            echo '</div>';

            // AQUÍ SE AGREGA EL SELECTOR DE ESTRATEGIA (Para que viaje en tiempo real al dar Confirmar Registro)
            echo '<div class="input-group" style="margin-bottom:2em;">';
            echo '<label for="estrategia" style="font-weight:600;display:block;margin-bottom:0.5em;">Estrategia de Control:</label>';
            echo '<select name="estrategia" id="estrategia" class="form-control select" style="width:100%;padding:0.7em 1em;border-radius:8px;border:1.5px solid #ea580c;font-size:1.1em;background:#fff7ed;color:#ea580c;font-weight:600;">';
            echo '<option value="manual">Manual (Sin cambios automáticos)</option>';
            echo '<option value="tardanza" selected>Tardanza Automática (Evaluar retraso)</option>';
            echo '<option value="falta">Falta Automática</option>';
            echo '</select>';
            echo '</div>';

            echo '<div style="display:flex;gap:1.1em;margin-top:0.7em;">';
            echo '<button type="submit" class="btn btn-primary" style="flex:1;padding:1em 0;font-size:1.13em;border-radius:10px;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;border:none;">Confirmar Registro</button>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<div class="alert alert-info">Todos los estudiantes ya tienen registrada su asistencia.</div>';
        }

        // Sección de salida y controles administrativos inferiores
        echo '<div style="display:flex;gap:1.1em;margin-top:1.5em;align-items:center;">';
        echo '<a href="index.php?action=reporte_asistencia_pdf&format=pdf&clase_id='.htmlspecialchars($id_clase).'" class="btn" style="flex:1;padding:1em 0;font-size:1.13em;border-radius:10px;background:#f1f5fb;color:#2563eb;font-weight:600;text-align:center;text-decoration:none;border:1.5px solid #2563eb;">Reporte PDF</a>';
        echo '<a href="index.php?action=reporte_asistencia_pdf&format=excel&clase_id='.htmlspecialchars($id_clase).'" class="btn" style="flex:1;padding:1em 0;font-size:1.13em;border-radius:10px;background:#f1f5fb;color:#1d6f42;font-weight:600;text-align:center;text-decoration:none;border:1.5px solid #1d6f42;">Reporte Excel</a>';
        
        // Formulario del botón rojo Finalizar (Congela la clase definitivamente al terminar el día)
        echo '<form method="GET" action="index.php" style="flex:1; display:flex;">';
        echo '<input type="hidden" name="action" value="finalizar_asistencia">';
        echo '<input type="hidden" name="clase_id" value="'.htmlspecialchars($id_clase).'">';
        echo '<input type="hidden" name="estrategia" value="manual">'; // Cierre directo seguro
        echo '<button type="submit" class="btn" style="width:100%;padding:1em 0; font-size:1.13em; border-radius:10px; background:#fee2e2; color:#b91c1c; font-weight:600; border:1.5px solid #b91c1c; cursor:pointer;">Finalizar</button>';
        echo '</form>';
        
        echo '</div>';
        echo '</div>';
        return $statusBoard;
    }

    /**
     * PROCESA EL FORMULARIO EN TIEMPO REAL AL DAR CLIC EN "CONFIRMAR REGISTRO"
     */
    public function addAttendance(array $asistencia): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_merge($asistencia, $_POST);
            $id_clase = intval($data['id_clase'] ?? 0);
            $codigo_estudiante = $data['codigo_estudiante'] ?? '';
            $tipo = $data['tipo'] ?? '';
            
            // Capturamos el nuevo campo estrategia que ahora viaja desde el formulario interactivo
            $estrategiaElegida = $data['estrategia'] ?? 'manual';

            if ($id_clase > 0 && $codigo_estudiante !== '' && $tipo !== '') {
                // Verificar estado de cierre con NClase
                $data['is_finalizada'] = $this->nClase->isFinalizada($id_clase);
                
                // LLAMADA MUTADA: Le pasamos los datos del post y la estrategia al negocio
                $ok = $this->nAsistencia->addAttendance($data, $estrategiaElegida);
                if ($ok) {
                    echo '<div class="alert alert-success">Asistencia procesada correctamente.</div>';
                } else {
                    echo '<div class="alert alert-danger">No se pudo registrar la asistencia (Clase congelada o alumno duplicado).</div>';
                }
                return $ok;
            } else {
                echo '<div class="alert alert-danger">Faltan datos requeridos para registrar la asistencia.</div>';
            }
        }
        return false;
    }

    public function existe(string $codigo_estudiante, int $id_clase, int $excludedId = 0): bool {
        return $this->nAsistencia->existe($codigo_estudiante, $id_clase, $excludedId);
    }

    public function validateAttendance(array $asistencia): bool {
        return $this->nAsistencia->validateAttendance($asistencia);
    }

    public function getPendientesByClass(int $id_clase): array {
        return $this->nEstudiante->getPendientesByClass($id_clase);
    }

    public function getByCodigo(string $codigo_estudiante): array {
        return $this->nEstudiante->getByCodigo($codigo_estudiante);
    }

    public function generarReporte(int $id_clase): string {
        $format = $_GET['format'] ?? 'pdf';
        try {
            return $this->nAsistencia->generarReporte($id_clase, $format);
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            return '';
        }
    }

    public function exists(int $id_clase): bool {
        return $this->nClase->exists($id_clase);
    }
    
    public function finalizar(int $id_clase): bool {
        return $this->nAsistencia->finalizar($id_clase);
    }

    public function isFinalizada(int $id_clase): bool {
        return $this->nClase->isFinalizada($id_clase);
    }

    public function getClaseDetalle(int $id_clase): array {
        return $this->nClase->getClaseDetalle($id_clase);
    }

    public function isStudentInClass(int $id_clase, string $codigo_estudiante): bool {
        return $this->nClase->isStudentInClass($id_clase, $codigo_estudiante);
    }
}