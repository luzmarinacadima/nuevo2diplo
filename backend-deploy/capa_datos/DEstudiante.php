<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DEstudiante {
    private $dbHelper;
    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }

    //CU2 Administrar Estudiante
    public function create(array $data): int {
        return $this->dbHelper->insert('estudiante', $data);
    }
    public function update(int $id, array $data): int {
        return $this->dbHelper->update('estudiante', $data, $id);
    }
    public function delete(int $id): int {
        return $this->dbHelper->delete('estudiante', $id);
    }
    public function getAll(): \PDOStatement {
        $sql = "
            SELECT
                e.*,
                u.nombres,
                u.apellidos,
                u.genero,
                u.nombre_usuario
            FROM estudiante e
            INNER JOIN usuario u ON u.id = e.usuario_id
            ORDER BY e.codigo_estudiante
        ";
        $db = $this->dbHelper->connect();
        return $db->query($sql);
    }
    public function getById(int $id) {
        $sql = "
            SELECT
                e.*,
                u.nombres,
                u.apellidos,
                u.genero,
                u.nombre_usuario
            FROM estudiante e
            INNER JOIN usuario u ON u.id = e.usuario_id
            WHERE e.usuario_id = :id
            LIMIT 1
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getByCi(string $ci) {
        $sql = "
            SELECT
                e.*,
                u.nombres,
                u.apellidos,
                u.genero,
                u.nombre_usuario
            FROM estudiante e
            INNER JOIN usuario u ON u.id = e.usuario_id
            WHERE e.ci = :ci
            LIMIT 1
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['ci' => $ci]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function obtenerUsuariosLibres(): array {
        $sql = "
            SELECT u.*
            FROM usuario u
            LEFT JOIN estudiante e ON u.id = e.usuario_id
            WHERE e.usuario_id IS NULL
              AND u.tipo_usuario = 'estudiante'
        ";
        $db = $this->dbHelper->connect();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
//hasta aca CU2 Administrar Estudiante
    //CU6 Asignar Grupo a un Estudiante
    // (Se deja sin tipado estricto para soportar ambos usos sin romper llamadas existentes.)
    public function existsStudent($idOrCode): bool {
        if (is_int($idOrCode) || (is_string($idOrCode) && $idOrCode !== '' && ctype_digit($idOrCode))) {
            $id = (int)$idOrCode;
            if ($id <= 0) {
                return false;
            }
            return $this->exists($id);
        }
        $code = trim((string)$idOrCode);
        if ($code === '') {
            return false;
        }
        return $this->existsByCodigo($code);
    }
    private function exists(int $id_estudiante): bool {
        $sql = "SELECT 1 FROM estudiante WHERE estudiante_id = :id_estudiante LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_estudiante' => $id_estudiante]);
        return (bool)$stmt->fetchColumn();
    }
    private function existsByCodigo(string $codigo, int $excludeUsuarioId = 0): bool {
        $sql = "SELECT 1 FROM estudiante WHERE codigo_estudiante = :codigo";
        $params = ['codigo' => $codigo];

        if ($excludeUsuarioId > 0) {
            $sql .= " AND usuario_id <> :exclude_id";
            $params['exclude_id'] = $excludeUsuarioId;
        }

        $sql .= " LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    // CU8 Registrar Asistencia
    public function getPendientesByClass(int $id_clase): array {
        if ($id_clase <= 0) {
            return [];
        }
        $sql = "
            SELECT
                e.codigo_estudiante,
                u.nombres,
                u.apellidos
            FROM clase c
            INNER JOIN asignacion a ON a.grupo_id = c.grupo_id
            INNER JOIN estudiante e ON e.estudiante_id = a.estudiante_id
            INNER JOIN usuario u ON u.id = e.usuario_id
            LEFT JOIN asistencia asi
                ON asi.clase_id = c.id_clase
               AND asi.estudiante_codigo_estudiante = e.codigo_estudiante
            WHERE c.id_clase = :id_clase
              AND asi.id_asistencia IS NULL
            ORDER BY u.apellidos, u.nombres, e.codigo_estudiante
        ";

        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_clase' => $id_clase]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getByCodigo(string $codigo_estudiante): array {
        $sql = "
            SELECT
                e.*,
                u.nombres,
                u.apellidos,
                u.genero,
                u.nombre_usuario
            FROM estudiante e
            INNER JOIN usuario u ON u.id = e.usuario_id
            WHERE e.codigo_estudiante = :codigo_estudiante
            LIMIT 1
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['codigo_estudiante' => $codigo_estudiante]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
//fin cu8



    // getTabla(): PDOStatement
    private function getTabla(): \PDOStatement {
        return $this->getAll();
    }

    // guardarEstudiante(): int
    private function guardarEstudiante($datos): int {
        return $this->create($datos);
    }

    // actualizarEstudiante(): int
    private function actualizarEstudiante($id, $datos): int {
        return $this->update((int)$id, $datos);
    }

    // borrarEstudiante(): int
    private function borrarEstudiante($id): int {
        return $this->delete((int)$id);
    }

    // findByCi(ci: string): DEstudiante
    private function findByCi(string $ci) {
        return $this->getByCi($ci);
    }


}
