<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DClase {
    private $id_clase;
    private $dia;
    private $hora_inicio;
    private $hora_fin;
    private $id_grupo;
    private $codigo;
    private $dbHelper;
    
    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }
    /**
     * Inserta una nueva clase en la base de datos.
     */
    public function create(array $data): int {
        return $this->dbHelper->insert('clase', $data);
    }
    /**
     * Actualiza los datos de una clase existente.
     */
    public function update(int $id, array $data): bool {
        return $this->dbHelper->update('clase', $data, $id) > 0;
    }
    /**
     * Elimina una clase por su ID.
     */
    public function delete(int $id): bool {
        return $this->dbHelper->delete('clase', $id) > 0;
    }
    /**
     * Obtiene todas las clases con información de grupo, materia y profesor.
     */
    public function getAll(): array {
        $sql = "
            SELECT 
                c.*, 
                g.nombre AS grupo_nombre,
                m.nombre AS materia_nombre,
                p.codigo_profesor AS profesor_codigo_profesor,
                COALESCE(u.nombres || ' ' || u.apellidos, p.codigo_profesor) AS profesor_nombre
            FROM clase c
            INNER JOIN grupo g ON g.id_grupo = c.grupo_id
            INNER JOIN materia m ON m.id = g.materia_id
            LEFT JOIN profesor p ON p.profesor_id = g.profesor_id
            LEFT JOIN usuario u ON u.id = p.usuario_id
            ORDER BY c.dia DESC, c.hora_inicio DESC
        ";
        $db = $this->dbHelper->connect();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtiene todas las clases de un grupo específico.
     */
    public function getByGroup(int $id_grupo): array {
        $sql = "SELECT * FROM clase WHERE grupo_id = :grupo_id";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['grupo_id' => $id_grupo]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Obtiene todas las clases asociadas a un profesor por su usuario.
     */
    public function getByProfesorUsuarioId(int $usuario_id): array {
        if ($usuario_id <= 0) {
            return [];
        }
        $sql = "
            SELECT 
                c.*, 
                g.nombre AS grupo_nombre,
                m.nombre AS materia_nombre,
                p.codigo_profesor AS profesor_codigo_profesor,
                COALESCE(u.nombres || ' ' || u.apellidos, p.codigo_profesor) AS profesor_nombre
            FROM clase c
            INNER JOIN grupo g ON g.id_grupo = c.grupo_id
            INNER JOIN materia m ON m.id = g.materia_id
            INNER JOIN profesor p ON p.profesor_id = g.profesor_id
            LEFT JOIN usuario u ON u.id = p.usuario_id
            WHERE p.usuario_id = :usuario_id
            ORDER BY c.dia DESC, c.hora_inicio DESC
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['usuario_id' => $usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
// hasta aqui el CU7
    // CU8 Registrar Asistencia
    /**
     * Verifica si existe una clase con el id dado.
     */
    public function exists(int $id_clase): bool {
        if ($id_clase <= 0) {
            return false;
        }
        $sql = "SELECT 1 FROM clase WHERE id_clase = :id LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id_clase]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Verifica si una clase está finalizada (si existe la columna 'finalizada').
     */
    public function isFinalizada(int $id_clase): bool {
        if ($id_clase <= 0) {
            return false;
        }
        $db = $this->dbHelper->connect();
        try {
            $colStmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = 'clase' AND column_name = 'finalizada' LIMIT 1");
            $colStmt->execute();
            $hasColumn = (bool)$colStmt->fetchColumn();
            if (!$hasColumn) {
                return false;
            }
            $stmt = $db->prepare('SELECT COALESCE(finalizada, false) FROM clase WHERE id_clase = :id_clase LIMIT 1');
            $stmt->execute(['id_clase' => $id_clase]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
    /**
     * Obtiene el detalle de una clase por su id, incluyendo información de grupo, materia y profesor.
     */
    public function getClaseDetalle(int $id_clase): array {
        if ($id_clase <= 0) {
            return [];
        }
        $sql = "
            SELECT 
                c.*, 
                g.nombre AS grupo_nombre,
                m.nombre AS materia_nombre,
                p.codigo_profesor AS profesor_codigo_profesor,
                COALESCE(u.nombres || ' ' || u.apellidos, p.codigo_profesor) AS profesor_nombre
            FROM clase c
            INNER JOIN grupo g ON g.id_grupo = c.grupo_id
            INNER JOIN materia m ON m.id = g.materia_id
            LEFT JOIN profesor p ON p.profesor_id = g.profesor_id
            LEFT JOIN usuario u ON u.id = p.usuario_id
            WHERE c.id_clase = :id_clase
            LIMIT 1
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_clase' => $id_clase]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
    /**
     * Verifica si un estudiante está asignado a una clase.
     */
    public function isStudentInClass(int $id_clase, string $codigo_estudiante): bool {
        $codigo = trim($codigo_estudiante);
        if ($id_clase <= 0 || $codigo === '') {
            return false;
        }
        $sql = "
            SELECT 1
            FROM clase c
            INNER JOIN asignacion a ON a.grupo_id = c.grupo_id
            INNER JOIN estudiante e ON e.estudiante_id = a.estudiante_id
            WHERE c.id_clase = :id_clase
              AND e.codigo_estudiante = :codigo_estudiante
            LIMIT 1
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id_clase' => $id_clase,
            'codigo_estudiante' => $codigo
        ]);
        return (bool)$stmt->fetchColumn();
    }
    // hasta aca el CU8 Registrar Asistencia

}
