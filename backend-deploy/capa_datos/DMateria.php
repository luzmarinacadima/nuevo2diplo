<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DMateria {
    private $dbHelper;
    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }
    // CU4 Administrar Materia
    public function create(array $data): int {
        $nombre = $data['nombre'] ?? '';
        if ($this->existsByNombre($nombre)) {
            return 0;
        }
        return $this->dbHelper->insert('materia', $data);
    }
    public function update(int $id, array $data): bool {
        $nombre = $data['nombre'] ?? '';
        if ($this->existsByNombre($nombre, $id)) {
            return false;
        }
        return $this->dbHelper->update('materia', $data, $id) > 0;
    }
    public function delete(int $id): bool {
        return $this->dbHelper->delete('materia', $id) > 0;
    }
    //tambien CU5 Administrar Grupo
    public function getAll(): array {
        $sql = "SELECT * FROM materia ORDER BY id";
        $db = $this->dbHelper->connect();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    //tambien CU5 Administrar Grupo
    public function getById(int $id) {
        $sql = "SELECT * FROM materia WHERE id = :id LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Verifica si una materia tiene referencias. Llama a DMateria->checkReferences().
        public function checkReferences(int $id): bool {
        if (!$this->existsMateria($id)) {
            return false;
        }
        $sql = "SELECT COUNT(*) FROM grupo WHERE materia_id = :id";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $refs = intval($stmt->fetchColumn());
        // true = materia sin referencias, se puede eliminar
        return $refs === 0;
    }
// fin CU4 Administrar Materia
    // CU7 Gestionar Clases
    public function getMateriasByGrupo(int $idGrupo): array {
        if ($idGrupo <= 0) {
            return [];
        }
        $sql = "
            SELECT m.*
            FROM materia m
            INNER JOIN grupo g ON g.materia_id = m.id
            WHERE g.id_grupo = :id_grupo
            ORDER BY m.id
        ";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_grupo' => $idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public function existsMateria(int $idMateria): bool {
        $sql = "SELECT 1 FROM materia WHERE id = :id LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $idMateria]);
        return (bool)$stmt->fetchColumn();
    }
// fin CU7 Gestionar Clases
    private function existsByNombre(string $nombre, int $excludeId = 0): bool {
        $sql = "SELECT 1 FROM materia WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))";
        $params = ['nombre' => $nombre];
        if ($excludeId > 0) {
            $sql .= " AND id <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    private function getTabla() {
        return $this->getAll();
    }

    private function guardarMateria($datos) {
        return $this->create($datos);
    }

    private function actualizarMateria($id, $datos) {
        return $this->update((int)$id, $datos);
    }

    private function borrarMateria($id) {
        return $this->delete((int)$id);
    }
}
