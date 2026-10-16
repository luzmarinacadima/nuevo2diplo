<?php
require_once __DIR__ . '/../capa_negocio/NClase.php';
require_once __DIR__ . '/../capa_negocio/NAsistencia.php';
require_once __DIR__ . '/../capa_negocio/NGrupo.php';
require_once __DIR__ . '/../capa_negocio/NMateria.php';

class PClase {
    private $id_clase;
    private $dia;
    private $hora_inicio;
    private $hora_fin;
    private $codigo;
    private $grupoId;
    private $materiaId;
    private $usuarioId;
    private $nClase;
    private $nGrupo;
    private $nMateria;

    public function __construct() {
        $this->nClase = new NClase();
        $this->nGrupo = new NGrupo();
        $this->nMateria = new NMateria();
    }

    /**
     * Crea una nueva clase. Llama a NClase->create().
     */
    public function create(array $data): void {
        if (empty($data)) {
            $this->Registrar();
            return;
        }
        if (!$this->nClase->create($data)) {
            echo '<div class="alert alert-danger">No se pudo crear la clase.</div>';
        }
    }

    /**
     * Actualiza los datos de una clase. Llama a NClase->update().
     */
    public function update(int $id, array $data): void {
        if ($id <= 0 || empty($data)) {
            echo '<div class="alert alert-danger">No se pudo actualizar la clase.</div>';
            return;
        }
        if (!$this->nClase->update($id, $data)) {
            echo '<div class="alert alert-danger">No se pudo actualizar la clase.</div>';
        }
    }

    /**
     * Elimina una clase por su ID. Llama a NClase->delete().
     */
    public function delete(int $id): void {
        if ($id <= 0) {
            $this->Eliminar();
            return;
        }
        if (!$this->nClase->delete($id)) {
            echo '<div class="alert alert-danger">No se pudo eliminar la clase.</div>';
            return;
        }
        echo "<script>window.location='index.php?action=clases&ntype=success&nmsg=Clase%20eliminada%20correctamente';</script>";
        exit;
    }

    /**
     * Muestra todas las clases registradas. Llama a NClase->getAll().
     */
    public function getAll(): void {
        $this->ListarTabla(null, false);
    }

    // Métodos del diagrama (wrappers P -> N)
    /**
     * Obtiene todos los grupos. Llama a NGrupo->getGrupos().
     */
    public function getGrupos(): array {
        return $this->nGrupo->getGrupos();
    }

    /**
     * Verifica si existe un grupo por ID. Llama a NGrupo->existsGrupo().
     */
    public function existsGrupo(int $idGrupo): bool {
        return $this->nGrupo->existsGrupo($idGrupo);
    }

    /**
     * Obtiene las materias asociadas a un grupo. Llama a NMateria->getMateriasByGrupo().
     */
    public function getMateriasByGrupo(int $idGrupo): array {
        return $this->nMateria->getMateriasByGrupo($idGrupo);
    }

    /**
     * Verifica si existe una materia por ID. Llama a NMateria->existsMateria().
     */
    public function existsMateria(int $idMateria): bool {
        return $this->nMateria->existsMateria($idMateria);
    }

    /**
     * Muestra las clases asociadas a un profesor (usuario). Llama a NClase->getByProfesorUsuarioId().
     */
    public function getByProfesorUsuarioId(int $usuarioId): void {
        $clases = $this->nClase->getByProfesorUsuarioId($usuarioId);
        $this->ListarTabla($clases, true);
    }

    /**
     * Obtiene los datos de un grupo por ID. Llama a NGrupo->getGrupo().
     */
    public function getGrupo(int $idGrupo): array {
        return $this->nGrupo->getGrupo($idGrupo);
    }


    private function buildAttendancePdf(
        array $clase,
        array $registros,
        array $pendientes,
        array $conteoEstado,
        int $totalAsignados,
        int $totalRegistrados,
        int $totalPendientes
    ): string {
        $generatedAt = date('Y-m-d H:i:s');

        $rowsRegistros = [];
        foreach ($registros as $idx => $registro) {
            $codigo = trim((string)($registro['estudiante_codigo_estudiante'] ?? '-'));
            $nombre = trim((string)($registro['apellidos'] ?? '') . ' ' . (string)($registro['nombres'] ?? ''));
            if ($nombre === '') {
                $nombre = '(Sin nombre)';
            }
            $estado = trim((string)($registro['tipo'] ?? '-'));

            $rowsRegistros[] = [
                'nro' => (string)($idx + 1),
                'codigo' => $codigo,
                'estudiante' => $nombre,
                'estado' => $estado,
            ];
        }

        $rowsPendientes = [];
        foreach ($pendientes as $idx => $estudiantePendiente) {
            $codigo = trim((string)($estudiantePendiente['codigo_estudiante'] ?? '-'));
            $nombre = trim((string)($estudiantePendiente['apellidos'] ?? '') . ' ' . (string)($estudiantePendiente['nombres'] ?? ''));
            if ($nombre === '') {
                $nombre = '(Sin nombre)';
            }

            $rowsPendientes[] = [
                'nro' => 'P' . (string)($idx + 1),
                'codigo' => $codigo,
                'estudiante' => $nombre,
                'estado' => 'Pendiente',
            ];
        }

        $pageStreams = [];
        $pageNumber = 1;
        $page = $this->startAttendancePdfPage(
            $pageNumber,
            $generatedAt,
            $clase,
            $conteoEstado,
            $totalAsignados,
            $totalRegistrados,
            $totalPendientes,
            true
        );

        $stream = $page['stream'];
        $cursorY = floatval($page['cursor_y']);
        $minY = 68.0;
        $rowIndex = 0;

        $openNewPage = function () use (
            &$pageStreams,
            &$stream,
            &$cursorY,
            &$pageNumber,
            $generatedAt,
            $clase,
            $conteoEstado,
            $totalAsignados,
            $totalRegistrados,
            $totalPendientes
        ): void {
            $pageStreams[] = $stream;
            $pageNumber++;
            $next = $this->startAttendancePdfPage(
                $pageNumber,
                $generatedAt,
                $clase,
                $conteoEstado,
                $totalAsignados,
                $totalRegistrados,
                $totalPendientes,
                false
            );
            $stream = $next['stream'];
            $cursorY = floatval($next['cursor_y']);
        };

        $drawSection = function (string $title, array $rows) use (
            &$stream,
            &$cursorY,
            $minY,
            &$rowIndex,
            $openNewPage
        ): void {
            if (($cursorY - 36) < $minY) {
                $openNewPage();
            }

            $stream .= $this->pdfText(36, $cursorY, $title, 'F2', 12, 30, 41, 59);
            $cursorY -= 16;

            if (($cursorY - 24) < $minY) {
                $openNewPage();
                $stream .= $this->pdfText(36, $cursorY, $title . ' (cont.)', 'F2', 12, 30, 41, 59);
                $cursorY -= 16;
            }

            $stream .= $this->drawAttendanceTableHeader($cursorY);

            if (empty($rows)) {
                $stream .= $this->drawAttendanceTableRow([
                    'nro' => '-',
                    'codigo' => '-',
                    'estudiante' => 'Sin registros para esta seccion',
                    'estado' => '-',
                ], $rowIndex, $cursorY);
                $rowIndex++;
                $cursorY -= 10;
                return;
            }

            foreach ($rows as $row) {
                if (($cursorY - 20) < $minY) {
                    $openNewPage();
                    $stream .= $this->pdfText(36, $cursorY, $title . ' (cont.)', 'F2', 12, 30, 41, 59);
                    $cursorY -= 16;
                    $stream .= $this->drawAttendanceTableHeader($cursorY);
                }

                $stream .= $this->drawAttendanceTableRow($row, $rowIndex, $cursorY);
                $rowIndex++;
            }

            $cursorY -= 10;
        };

        $drawSection('Registros de asistencia', $rowsRegistros);
        if (!empty($rowsPendientes)) {
            $drawSection('Estudiantes pendientes', $rowsPendientes);
        }

        $pageStreams[] = $stream;
        return $this->buildPdfDocumentFromStreams($pageStreams);
    }

    private function startAttendancePdfPage(
        int $pageNumber,
        string $generatedAt,
        array $clase,
        array $conteoEstado,
        int $totalAsignados,
        int $totalRegistrados,
        int $totalPendientes,
        bool $includeSummary
    ): array {
        $stream = '';
        $stream .= $this->pdfSetFillColor(30, 88, 187);
        $stream .= $this->pdfRect(36, 784, 523, 38, 'f');
        $stream .= $this->pdfText(48, 806, 'Reporte de Asistencia', 'F2', 16, 255, 255, 255);
        $stream .= $this->pdfText(48, 792, 'AsistencIAU | Generado: ' . $generatedAt, 'F1', 9, 234, 242, 255);
        $stream .= $this->pdfText(500, 792, 'Pag. ' . $pageNumber, 'F1', 9, 234, 242, 255);

        $classLine = 'Clase #' . intval($clase['id_clase'] ?? 0)
            . ' | Fecha: ' . (string)($clase['dia'] ?? '-')
            . ' | Horario: ' . (string)($clase['hora_inicio'] ?? '-')
            . ' - ' . (string)($clase['hora_fin'] ?? '-');
        $classLine = $this->truncatePdfText($classLine, 94);

        if ($includeSummary) {
            $grupo = $this->truncatePdfText((string)($clase['grupo_nombre'] ?? '-'), 76);
            $materia = $this->truncatePdfText((string)($clase['materia_nombre'] ?? '-'), 76);
            $profesor = $this->truncatePdfText((string)($clase['profesor_nombre'] ?? '-'), 76);

            $stream .= $this->pdfSetFillColor(247, 250, 255);
            $stream .= $this->pdfSetStrokeColor(204, 219, 237);
            $stream .= $this->pdfRect(36, 708, 523, 66, 'B');
            $stream .= $this->pdfText(48, 756, $classLine, 'F2', 11, 30, 41, 59);
            $stream .= $this->pdfText(48, 740, 'Grupo: ' . $grupo, 'F1', 10, 45, 55, 72);
            $stream .= $this->pdfText(48, 726, 'Materia: ' . $materia . '  |  Profesor: ' . $profesor, 'F1', 10, 45, 55, 72);

            $stream .= $this->pdfSetFillColor(240, 247, 255);
            $stream .= $this->pdfSetStrokeColor(204, 219, 237);
            $stream .= $this->pdfRect(36, 650, 523, 48, 'B');
            $stream .= $this->pdfText(48, 682, 'Resumen general', 'F2', 11, 30, 64, 104);
            $stream .= $this->pdfText(
                48,
                666,
                'Asignados: ' . $totalAsignados . ' | Registrados: ' . $totalRegistrados . ' | Pendientes: ' . $totalPendientes,
                'F1',
                10,
                45,
                55,
                72
            );
            $stream .= $this->pdfText(
                48,
                652,
                'Presente: ' . intval($conteoEstado['Presente'] ?? 0)
                . ' | Tarde: ' . intval($conteoEstado['Tarde'] ?? 0)
                . ' | Falta: ' . intval($conteoEstado['Falta'] ?? 0)
                . ' | Licencia: ' . intval($conteoEstado['Licencia'] ?? 0)
                . ' | Otros: ' . intval($conteoEstado['Otros'] ?? 0),
                'F1',
                10,
                45,
                55,
                72
            );

            return ['stream' => $stream, 'cursor_y' => 620.0];
        }

        $stream .= $this->pdfText(48, 766, $classLine, 'F1', 10, 30, 41, 59);
        return ['stream' => $stream, 'cursor_y' => 742.0];
    }

    private function drawAttendanceTableHeader(float &$cursorY): string {
        $xLeft = 36.0;
        $xNroEnd = 78.0;
        $xCodigoEnd = 174.0;
        $xEstudianteEnd = 439.0;
        $xRight = 559.0;
        $rowHeight = 20.0;

        $top = $cursorY;
        $bottom = $top - $rowHeight;

        $stream = '';
        $stream .= $this->pdfSetFillColor(37, 99, 235);
        $stream .= $this->pdfSetStrokeColor(191, 219, 254);
        $stream .= $this->pdfRect($xLeft, $bottom, $xRight - $xLeft, $rowHeight, 'B');
        $stream .= $this->pdfLine($xNroEnd, $bottom, $xNroEnd, $top);
        $stream .= $this->pdfLine($xCodigoEnd, $bottom, $xCodigoEnd, $top);
        $stream .= $this->pdfLine($xEstudianteEnd, $bottom, $xEstudianteEnd, $top);

        $stream .= $this->pdfText(42, $bottom + 6, 'Nro', 'F2', 9, 255, 255, 255);
        $stream .= $this->pdfText(84, $bottom + 6, 'Codigo', 'F2', 9, 255, 255, 255);
        $stream .= $this->pdfText(180, $bottom + 6, 'Estudiante', 'F2', 9, 255, 255, 255);
        $stream .= $this->pdfText(445, $bottom + 6, 'Estado', 'F2', 9, 255, 255, 255);

        $cursorY = $bottom;
        return $stream;
    }

    private function drawAttendanceTableRow(array $row, int $index, float &$cursorY): string {
        $xLeft = 36.0;
        $xNroEnd = 78.0;
        $xCodigoEnd = 174.0;
        $xEstudianteEnd = 439.0;
        $xRight = 559.0;
        $rowHeight = 18.0;

        $top = $cursorY;
        $bottom = $top - $rowHeight;

        $nro = $this->truncatePdfText((string)($row['nro'] ?? '-'), 4);
        $codigo = $this->truncatePdfText((string)($row['codigo'] ?? '-'), 18);
        $estudiante = $this->truncatePdfText((string)($row['estudiante'] ?? '-'), 46);
        $estado = $this->truncatePdfText((string)($row['estado'] ?? '-'), 15);

        $estadoUpper = strtoupper($estado);
        $estadoColor = [30, 41, 59];
        if ($estadoUpper === 'PRESENTE') {
            $estadoColor = [22, 101, 52];
        } elseif ($estadoUpper === 'FALTA') {
            $estadoColor = [153, 27, 27];
        } elseif ($estadoUpper === 'LICENCIA') {
            $estadoColor = [30, 64, 175];
        } elseif ($estadoUpper === 'PENDIENTE') {
            $estadoColor = [146, 64, 14];
        }

        $fill = ($index % 2 === 0) ? [248, 251, 255] : [255, 255, 255];

        $stream = '';
        $stream .= $this->pdfSetFillColor($fill[0], $fill[1], $fill[2]);
        $stream .= $this->pdfSetStrokeColor(218, 230, 243);
        $stream .= $this->pdfRect($xLeft, $bottom, $xRight - $xLeft, $rowHeight, 'B');
        $stream .= $this->pdfLine($xNroEnd, $bottom, $xNroEnd, $top);
        $stream .= $this->pdfLine($xCodigoEnd, $bottom, $xCodigoEnd, $top);
        $stream .= $this->pdfLine($xEstudianteEnd, $bottom, $xEstudianteEnd, $top);

        $stream .= $this->pdfText(42, $bottom + 5, $nro, 'F1', 9, 30, 41, 59);
        $stream .= $this->pdfText(84, $bottom + 5, $codigo, 'F1', 9, 30, 41, 59);
        $stream .= $this->pdfText(180, $bottom + 5, $estudiante, 'F1', 9, 30, 41, 59);
        $stream .= $this->pdfText(445, $bottom + 5, $estado, 'F1', 9, $estadoColor[0], $estadoColor[1], $estadoColor[2]);

        $cursorY = $bottom;
        return $stream;
    }

    private function buildPdfDocumentFromStreams(array $pageStreams): string {
        if (empty($pageStreams)) {
            $pageStreams = [''];
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        $contentObjectNumbers = [];
        $pageObjectNumbers = [];
        $nextObjectNumber = 5;

        foreach ($pageStreams as $_stream) {
            $contentObjectNumbers[] = $nextObjectNumber++;
            $pageObjectNumbers[] = $nextObjectNumber++;
        }

        $this->appendPdfObject($pdf, $offsets, 1, '<< /Type /Catalog /Pages 2 0 R >>');
        $kids = implode(' ', array_map(static function ($n) {
            return $n . ' 0 R';
        }, $pageObjectNumbers));
        $this->appendPdfObject($pdf, $offsets, 2, '<< /Type /Pages /Kids [ ' . $kids . ' ] /Count ' . count($pageObjectNumbers) . ' >>');
        $this->appendPdfObject($pdf, $offsets, 3, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $this->appendPdfObject($pdf, $offsets, 4, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');

        foreach ($pageStreams as $index => $stream) {
            $contentObj = $contentObjectNumbers[$index];
            $pageObj = $pageObjectNumbers[$index];

            $this->appendPdfObject(
                $pdf,
                $offsets,
                $contentObj,
                "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream"
            );

            $this->appendPdfObject(
                $pdf,
                $offsets,
                $pageObj,
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]'
                . ' /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >>'
                . ' /Contents ' . $contentObj . ' 0 R >>'
            );
        }

        $startXref = strlen($pdf);
        $maxObject = max(array_keys($offsets));
        $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxObject; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size ' . ($maxObject + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= "startxref\n" . $startXref . "\n%%EOF";

        return $pdf;
    }

    private function appendPdfObject(string &$pdf, array &$offsets, int $number, string $body): void {
        $offsets[$number] = strlen($pdf);
        $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
    }

    private function pdfSetFillColor(int $r, int $g, int $b): string {
        return sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255) . "\n";
    }

    private function pdfSetStrokeColor(int $r, int $g, int $b): string {
        return sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255) . "\n";
    }

    private function pdfRect(float $x, float $y, float $w, float $h, string $paint): string {
        return sprintf('%.2F %.2F %.2F %.2F re %s', $x, $y, $w, $h, $paint) . "\n";
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2): string {
        return sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2) . "\n";
    }

    private function pdfText(
        float $x,
        float $y,
        string $text,
        string $font,
        int $size,
        int $r,
        int $g,
        int $b
    ): string {
        $safe = $this->sanitizePdfText($text);
        if ($safe === '') {
            $safe = ' ';
        }

        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safe);

        $stream = '';
        $stream .= $this->pdfSetFillColor($r, $g, $b);
        $stream .= "BT\n";
        $stream .= '/' . $font . ' ' . $size . " Tf\n";
        $stream .= sprintf('%.2F %.2F Td', $x, $y) . "\n";
        $stream .= '(' . $safe . ") Tj\n";
        $stream .= "ET\n";
        return $stream;
    }

    private function truncatePdfText(string $text, int $maxChars): string {
        $clean = $this->sanitizePdfText($text);
        if ($maxChars <= 0) {
            return '';
        }

        if (strlen($clean) <= $maxChars) {
            return $clean;
        }

        if ($maxChars <= 3) {
            return substr($clean, 0, $maxChars);
        }

        return substr($clean, 0, $maxChars - 3) . '...';
    }

    private function buildStatusBoardStats(array $rows): array {
        $stats = [
            'total' => count($rows),
            'pendientes' => 0,
            'presentes' => 0,
            'tardes' => 0,
            'faltas' => 0,
            'licencias' => 0,
            'otros' => 0,
        ];

        foreach ($rows as $row) {
            $estado = strtoupper(trim((string)($row['estado'] ?? 'Pendiente')));
            if ($estado === 'PENDIENTE') {
                $stats['pendientes']++;
            } elseif ($estado === 'PRESENTE') {
                $stats['presentes']++;
            } elseif ($estado === 'TARDE') {
                $stats['tardes']++;
            } elseif ($estado === 'FALTA') {
                $stats['faltas']++;
            } elseif ($estado === 'LICENCIA') {
                $stats['licencias']++;
            } else {
                $stats['otros']++;
            }
        }

        return $stats;
    }

    private function sanitizePdfText(string $text): string {
        $clean = preg_replace('/\s+/', ' ', trim($text));
        if (!is_string($clean)) {
            $clean = '';
        }

        $latin = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $clean);
        if ($latin === false) {
            return preg_replace('/[^\x20-\x7E]/', '?', $clean) ?? '';
        }

        return $latin;
    }



    // ListarTabla(): void
    private function ListarTabla(?array $clases = null, bool $modoProfesor = false): void {
        if ($clases === null) {
            $clases = $this->nClase->getAll();
        }

        echo '<div class="card">';
        echo '<div class="panel-header">'; //BOTON NUEVA CLASE
        echo '<h2 class="panel-title">Control de Clases y Asistencia</h2>';
        echo '<a href="index.php?action=registrar_clase" class="btn">➕ Nueva Clase</a>';
        echo '</div>';

        if (empty($clases)) {
            echo '<div class="empty-state">';
            echo '<span class="empty-state-icon">📅</span>';
            echo '<p>No hay clases registradas aún.</p>';
            echo '</div>';
        } else {
            echo '<div class="table-container"><table>';
            echo '<thead><tr><th>Fecha / Dia</th><th>Horario</th><th>Grupo</th><th>Materia</th><th>Profesor</th><th>Codigo</th><th class="actions-cell">Acciones</th></tr></thead>';
            echo '<tbody>';
            foreach ($clases as $clase) { //BOTON ASISTENCIA
                $acciones = "<a href='?action=marcar_asistencia&clase_id=" . $clase['id_clase'] . "' class='btn btn-success btn-sm'>Asistencia</a>";
                    if ($modoProfesor) {
                    $acciones .= " <a href='?action=reporte_asistencia_pdf&clase_id=" . $clase['id_clase'] . "' class='btn btn-outline btn-sm' target='_blank' rel='noopener'>Reporte PDF</a>";
                } //BOTON BORRAR
                    $acciones .= " <a href='?action=eliminar_clase&clase_id=" . $clase['id_clase'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Eliminar?\")'>Borrar</a>";

                echo "<tr>
                    <td><strong>" . htmlspecialchars($clase['dia']) . "</strong></td>
                    <td>" . htmlspecialchars($clase['hora_inicio']) . " - " . htmlspecialchars($clase['hora_fin']) . "</td>
                    <td>" . htmlspecialchars($clase['grupo_nombre'] ?? ('Grupo #' . $clase['grupo_id'])) . "</td>
                    <td>" . htmlspecialchars($clase['materia_nombre'] ?? '-') . "</td>
                    <td>" . htmlspecialchars($clase['profesor_nombre'] ?? ($clase['profesor_codigo_profesor'] ?? '-')) . "</td>
                    <td><span class='code-pill'>" . htmlspecialchars($clase['codigo']) . "</span></td>
                    <td class='actions-cell'>" . $acciones . "</td>
                </tr>";
            }
            echo '</tbody></table></div>';
        }
        echo '</div>';
    }

    // Registrar(): void
    private function Registrar(?int $usuarioId = null): void {
        // Usar NGrupo según arquitectura y diagrama
        $nGrupo = new NGrupo();
        $grupos = $nGrupo->getGrupos();

        $selectedGrupoId = 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $selectedGrupoId = isset($_POST['grupo_id']) ? intval($_POST['grupo_id']) : 0;
        } else {
            $selectedGrupoId = isset($_GET['grupo_id']) ? intval($_GET['grupo_id']) : 0;
        }

        if ($usuarioId !== null && $selectedGrupoId > 0) {
            $permitidos = array_map(static function ($g) {
                return intval($g['id_grupo'] ?? 0);
            }, $grupos);

            if (!in_array($selectedGrupoId, $permitidos, true)) {
                echo '<div class="alert alert-danger">No tiene permisos para crear clases en ese grupo.</div>';
                $selectedGrupoId = 0;
            }
        }

        $nMateria = new NMateria();
        $materias = $selectedGrupoId > 0 ? $nMateria->getMateriasByGrupo($selectedGrupoId) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_clase') {
            // Usa el grupo ya validado (incluye permisos si es profesor)
            $grupo_id = $selectedGrupoId;
            $materia_id = isset($_POST['materia_id']) ? intval($_POST['materia_id']) : 0;
            $dia = isset($_POST['dia']) ? $_POST['dia'] : '';
            $hora_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '';
            $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : '';
            $codigo = $this->nClase->generateClassCode();

            if ($grupo_id <= 0) {
                echo '<div class="alert alert-danger">Seleccione un grupo válido.</div>';
            } elseif (empty($materias)) {
                echo '<div class="alert alert-danger">No hay materias disponibles para el grupo seleccionado.</div>';
            } else {
                $materiaIdsValidas = array_map(static function ($m) {
                    return intval($m['id'] ?? 0);
                }, $materias);

                if ($materia_id <= 0 || !in_array($materia_id, $materiaIdsValidas, true)) {
                    echo '<div class="alert alert-danger">Seleccione una materia válida para el grupo.</div>';
                } else {
                    $datos = [
                        'grupo_id' => $grupo_id,
                        'dia' => $dia,
                        'hora_inicio' => $hora_inicio,
                        'hora_fin' => $hora_fin,
                        'codigo' => $codigo
                    ];

                    if ($this->nClase->create($datos)) {
                        echo "<script>window.location='index.php?action=clases&ntype=success&nmsg=Clase%20creada%20correctamente';</script>";
                        exit;
                    }

                    echo '<div class="alert alert-danger">No se pudo crear la clase.</div>';
                }
            }
        }
        
        echo '<div class="card card-sm">';
        echo '<h2>Registrar Nueva Clase</h2>';
        // Selector de grupo (GET): recarga para poblar Materia
        echo '<form method="GET">';
        echo '<input type="hidden" name="action" value="registrar_clase">';
        echo '<div class="form-group"><label>Grupo:</label>';
        echo '<select name="grupo_id" required class="form-control" onchange="this.form.submit()">';
        echo '<option value="">Seleccione un grupo...</option>';
        foreach ($grupos as $grupo) {
            $gid = intval($grupo['id_grupo'] ?? 0);
            $grupoNombre = $grupo['grupo_nombre'] ?? $grupo['nombre'] ?? ('Grupo #' . $gid);
            $selectedAttr = ($selectedGrupoId > 0 && $gid === $selectedGrupoId) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars((string)$gid) . '"' . $selectedAttr . '>' . htmlspecialchars((string)$grupoNombre) . '</option>';
        }
        echo '</select></div>';
        echo '</form>';

        // Formulario principal (POST): crear clase
        echo '<form method="POST">';
        echo '<input type="hidden" name="action" value="crear_clase">';
        echo '<input type="hidden" name="grupo_id" value="' . htmlspecialchars((string)$selectedGrupoId) . '">';

        echo '<div class="form-group"><label>Materia:</label>';
        $disabledMateria = $selectedGrupoId <= 0 ? ' disabled' : '';
        echo '<select name="materia_id" required class="form-control"' . $disabledMateria . '>';
        if ($selectedGrupoId <= 0) {
            echo '<option value="">Seleccione un grupo primero...</option>';
        } else {
            echo '<option value="">Seleccione una materia...</option>';
            foreach ($materias as $materia) {
                $mid = intval($materia['id'] ?? 0);
                $mNombre = $materia['nombre'] ?? ('Materia #' . $mid);
                echo '<option value="' . htmlspecialchars((string)$mid) . '">' . htmlspecialchars((string)$mNombre) . '</option>';
            }
        }
        echo '</select></div>';
        echo '<div class="form-group"><label>Fecha de la Clase:</label>';
        date_default_timezone_set('America/La_Paz');
        echo '<input type="date" name="dia" required class="form-control" value="'.date('Y-m-d').'"></div>';
        echo '<div class="form-group"><label>Hora Inicio:</label>';
        echo '<input type="time" name="hora_inicio" required class="form-control" value="07:00"></div>';
        echo '<div class="form-group"><label>Hora Fin:</label>';
        echo '<input type="time" name="hora_fin" required class="form-control" value="09:00"></div>';
        echo '<div class="form-actions">'; //BOTON CREAR CLASE
        echo '<button type="submit" class="btn">Crear Clase</button>';
        echo '<a href="index.php?action=clases" class="btn btn-danger">Cancelar</a>';
        echo '</div></form></div>';
    }

    // Eliminar(): void
    private function Eliminar(): void {
        if (isset($_GET['clase_id'])) {
            $clase_id = intval($_GET['clase_id']);
            $resultado = $this->nClase->delete($clase_id);
            if ($resultado) {
                echo "<script>window.location='index.php?action=clases&ntype=success&nmsg=Clase%20eliminada%20correctamente';</script>";
                exit;
            } else {
                echo '<div class="alert alert-danger">Error al eliminar la clase</div>';
            }
        }
    }
}
?>
