<?php
require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/DEstudiante.php';

class DAsignacion {
    private $dbHelper;

    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }

    /**
    * Asignación MASIVA de estudiantes a un grupo usando la capa de negocio.
     */
    public function bulkAssign(array $data): array {
        $codes = $data['codes'] ?? [];
        $groupId = intval($data['groupId'] ?? 0);
        if (!is_array($codes) || $groupId <= 0) {
            return [];
        }
        $result = [];
        foreach ($codes as $code) {
            // Aquí se llama a la función de asignación individual
            $result[] = $this->assignToGroup($code, $groupId);
        }
        return $result;
    }
    /**
     * Asigna 1 SOLO estudiante a 1 GRUPO usando la capa de negocio.
     * Inserta una asignación de estudiante a grupo en la base de datos.
     */
    public function assignToGroup(string $code, int $groupId): array {
        $db = $this->dbHelper->connect();
        // Buscar el id del estudiante por código
        $sql = "SELECT estudiante_id FROM estudiante WHERE codigo_estudiante = :codigo LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['codigo' => $code]);
        $estudianteId = $stmt->fetchColumn();
        if (!$estudianteId) {
            return ['ok' => false, 'code' => $code, 'groupId' => $groupId, 'message' => 'Estudiante no existe'];
        }
        // Verificar si ya está asignado
        $sqlCheck = "SELECT 1 FROM asignacion WHERE estudiante_id = :id AND grupo_id = :gid LIMIT 1";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $estudianteId, 'gid' => $groupId]);
        if ($stmtCheck->fetchColumn()) {
            return ['ok' => false, 'code' => $code, 'groupId' => $groupId, 'message' => 'Ya asignado'];
        }
        // Insertar asignación
        $sqlInsert = "INSERT INTO asignacion (estudiante_id, grupo_id) VALUES (:id, :gid)";
        $stmtInsert = $db->prepare($sqlInsert);
        $ok = $stmtInsert->execute(['id' => $estudianteId, 'gid' => $groupId]);
        if ($ok) {
            return ['ok' => true, 'code' => $code, 'groupId' => $groupId, 'message' => 'Asignado'];
        } else {
            return ['ok' => false, 'code' => $code, 'groupId' => $groupId, 'message' => 'Error al asignar'];
        }
    }

    /**
     * Obtiene todas las asignaciones de estudiantes a un grupo.
     * Solo consulta la base de datos.
     */
    public function getAssignments(int $groupId): array {
        $db = $this->dbHelper->connect();
        $sql = "
            SELECT a.grupo_id, a.fecha_asignacion, e.estudiante_id, e.codigo_estudiante, u.nombres, u.apellidos
            FROM asignacion a
            INNER JOIN estudiante e ON e.estudiante_id = a.estudiante_id
            INNER JOIN usuario u ON u.id = e.usuario_id
            WHERE a.grupo_id = :grupo_id
            ORDER BY a.fecha_asignacion DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(['grupo_id' => $groupId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}
