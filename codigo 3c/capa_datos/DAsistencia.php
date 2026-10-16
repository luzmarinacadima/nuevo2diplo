
<?php
require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/DClase.php';

class DAsistencia {
    private $dbHelper;

    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
        $this->ensureStudentAttendanceColumns();
    }

    private function ensureStudentAttendanceColumns(): void {
        $this->dbHelper->connect()->exec(
            'ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS hora_marcada TIME NULL'
        );
    }
    public function getStatusBoardByClass(int $id_clase): array {
        $db = $this->dbHelper->connect();
        $sql = "SELECT a.estudiante_codigo_estudiante AS codigo_estudiante, u.nombres, u.apellidos, a.tipo
            FROM asistencia a
            JOIN estudiante e ON a.estudiante_codigo_estudiante = e.codigo_estudiante
            JOIN usuario u ON e.usuario_id = u.id
            WHERE a.clase_id = :id_clase
            ORDER BY u.apellidos, u.nombres";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_clase' => $id_clase]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAttendance(array $asistencia): bool {
        $db = $this->dbHelper;
        // Validar que no exista ya asistencia para ese estudiante en esa clase
        if ($this->existe($asistencia['codigo_estudiante'], $asistencia['id_clase'])) {
            echo '<pre style="background:#fef2f2;color:#991b1b;padding:1em;border-radius:8px;">DEBUG DAsistencia: Ya existe asistencia para este estudiante en esta clase.</pre>';
            return false;
        }
        $data = [
            'clase_id' => $asistencia['id_clase'],
            'estudiante_codigo_estudiante' => $asistencia['codigo_estudiante'],
            'tipo' => $asistencia['tipo']
        ];
        try {
            $inserted = $db->insert('asistencia', $data);
            if ($inserted <= 0) {
                echo '<pre style="background:#fef2f2;color:#991b1b;padding:1em;border-radius:8px;">DEBUG DAsistencia: Insert falló, valor devuelto: ' . var_export($inserted, true) . '</pre>';
            }
            return $inserted > 0;
        } catch (\PDOException $e) {
            echo '<pre style="background:#fef2f2;color:#991b1b;padding:1em;border-radius:8px;">PDOException: ' . htmlspecialchars($e->getMessage()) . '</pre>';
            return false;
        }
    }

    public function validateAttendance(array $asistencia): bool {
        // Ejemplo simple: validar que los campos no estén vacíos
        return isset($asistencia['id_clase'], $asistencia['codigo_estudiante'], $asistencia['tipo'])
            && $asistencia['id_clase'] > 0
            && $asistencia['codigo_estudiante'] !== ''
            && $asistencia['tipo'] !== '';
    }
    public function existe(string $codigo_estudiante, int $id_clase, int $excludedId = 0): bool {
        $db = $this->dbHelper->connect();
        $sql = "SELECT COUNT(*) FROM asistencia WHERE estudiante_codigo_estudiante = :codigo_estudiante AND clase_id = :id_clase";
        $params = [
            'codigo_estudiante' => $codigo_estudiante,
            'id_clase' => $id_clase
        ];
        if ($excludedId > 0) {
            $sql .= " AND id_asistencia != :excludedId";
            $params['excludedId'] = $excludedId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Devuelve los registros de asistencia de la clase para el reporte.
     * La generación del PDF debe hacerse en la capa de negocio o presentación.
     */
    public function generarReporte(int $id_clase): array {
        return $this->getStatusBoardByClass($id_clase);
    }

    /**
     * Actualiza el tipo de asistencia de un estudiante en una clase.
     */
    public function updateAttendance(int $id_clase, string $codigo, string $nuevoTipo): bool {
        $db = $this->dbHelper->connect();
        $sql = "UPDATE asistencia SET tipo = :tipo WHERE clase_id = :cid AND estudiante_codigo_estudiante = :codigo";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'tipo' => $nuevoTipo,
            'cid' => $id_clase,
            'codigo' => $codigo
        ]);
    }

    public function finalizar(int $id_clase): bool { 
        $db = DatabaseHelper::getInstance();
        // Suponiendo que la tabla clase tiene un campo finalizada boolean
        $updated = $db->update('clase', ['finalizada' => true], $id_clase);
        return $updated > 0;
    }

    public function getDashboardEstudiante(int $usuarioId): array {
        $db = $this->dbHelper->connect();
        $studentSql = "SELECT e.codigo_estudiante, u.nombres, u.apellidos
            FROM estudiante e INNER JOIN usuario u ON u.id = e.usuario_id
            WHERE e.usuario_id = :usuario_id LIMIT 1";
        $stmt = $db->prepare($studentSql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!$estudiante) {
            return ['estudiante' => [], 'grupos' => [], 'clases' => [], 'historial' => [], 'resumen' => ['presente' => 0, 'tarde' => 0, 'falta' => 0]];
        }

        $groupSql = "SELECT g.id_grupo AS grupo_id, g.nombre AS grupo_nombre, m.nombre AS materia_nombre
            FROM asignacion a INNER JOIN estudiante e ON e.estudiante_id = a.estudiante_id
            INNER JOIN grupo g ON g.id_grupo = a.grupo_id INNER JOIN materia m ON m.id = g.materia_id
            WHERE e.codigo_estudiante = :codigo ORDER BY m.nombre, g.nombre";
        $stmt = $db->prepare($groupSql);
        $stmt->execute(['codigo' => $estudiante['codigo_estudiante']]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $classSql = "SELECT c.id_clase, c.dia, c.hora_inicio, c.hora_fin,
                g.id_grupo AS grupo_id, g.nombre AS grupo_nombre, m.nombre AS materia_nombre,
                COALESCE(u.nombres || ' ' || u.apellidos, p.codigo_profesor) AS profesor_nombre,
                a.tipo AS estado_asistencia, a.hora_marcada
            FROM clase c INNER JOIN grupo g ON g.id_grupo = c.grupo_id
            INNER JOIN materia m ON m.id = g.materia_id
            INNER JOIN profesor p ON p.profesor_id = g.profesor_id
            LEFT JOIN usuario u ON u.id = p.usuario_id
            INNER JOIN asignacion ag ON ag.grupo_id = c.grupo_id
            INNER JOIN estudiante e ON e.estudiante_id = ag.estudiante_id
            LEFT JOIN asistencia a ON a.clase_id = c.id_clase AND a.estudiante_codigo_estudiante = e.codigo_estudiante
            WHERE e.codigo_estudiante = :codigo
            ORDER BY c.dia DESC, c.hora_inicio DESC";
        $stmt = $db->prepare($classSql);
        $stmt->execute(['codigo' => $estudiante['codigo_estudiante']]);
        $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $history = array_values(array_filter($clases, static function (array $clase): bool {
            return !empty($clase['estado_asistencia']);
        }));
        $resumen = ['presente' => 0, 'tarde' => 0, 'falta' => 0];
        foreach ($history as $registro) {
            $tipo = strtolower((string)$registro['estado_asistencia']);
            if ($tipo === 'presente') $resumen['presente']++;
            elseif ($tipo === 'tarde') $resumen['tarde']++;
            elseif (isset($resumen[$tipo])) $resumen[$tipo]++;
        }
        foreach ($history as &$registro) {
            $registro['hora_marcada'] = $registro['hora_marcada'] ?? '';
            $registro['tipo'] = $registro['estado_asistencia'];
        }
        unset($registro);
        return compact('estudiante', 'grupos', 'clases', 'resumen') + ['historial' => $history];
    }

    public function getStudentClassForAttendance(int $usuarioId, int $claseId): array {
        $db = $this->dbHelper->connect();
        $sql = "SELECT e.codigo_estudiante, c.dia, c.hora_inicio, c.hora_fin
            FROM estudiante e INNER JOIN asignacion ag ON ag.estudiante_id = e.estudiante_id
            INNER JOIN clase c ON c.grupo_id = ag.grupo_id
            WHERE e.usuario_id = :usuario_id AND c.id_clase = :clase_id
            LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId, 'clase_id' => $claseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveStudentAttendance(string $codigoEstudiante, int $claseId, string $tipo, string $horaMarcada): array {
        $db = $this->dbHelper->connect();
        if ($this->existe($codigoEstudiante, $claseId)) {
            return ['ok' => false, 'message' => 'Ya marcaste asistencia en esta clase.'];
        }
        $stmt = $db->prepare("INSERT INTO asistencia (clase_id, estudiante_codigo_estudiante, tipo, hora_marcada)
            SELECT :clase_id, :codigo, :tipo, :hora_marcada
            WHERE NOT EXISTS (
                SELECT 1 FROM asistencia
                WHERE clase_id = :clase_id_check
                  AND estudiante_codigo_estudiante = :codigo_check
            )");
        $stmt->execute([
            'clase_id' => $claseId,
            'codigo' => $codigoEstudiante,
            'tipo' => $tipo,
            'hora_marcada' => $horaMarcada,
            'clase_id_check' => $claseId,
            'codigo_check' => $codigoEstudiante
        ]);
        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'message' => 'Ya marcaste asistencia en esta clase.'];
        }
        return ['ok' => true, 'message' => 'Asistencia registrada como ' . strtolower($tipo) . '.'];
    }
}
