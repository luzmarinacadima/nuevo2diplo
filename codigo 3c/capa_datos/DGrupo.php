<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DGrupo {
    private $dbHelper;

    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }
    /**
     * CU5 Administrar Grupo
     * Inserta un nuevo grupo en la base de datos.
     */
    public function create(array $data): int {
        return $this->dbHelper->insert('grupo', $data);
    }
    /**
     * Actualiza los datos de un grupo existente por ID.
     */
    public function update(int $id_grupo, array $data): bool {
        return $this->dbHelper->update('grupo', $data, $id_grupo) > 0;
    }
    /**
     * Elimina un grupo por su ID.
     */
    public function delete(int $id_grupo): bool {
        return $this->dbHelper->delete('grupo', $id_grupo) > 0;
    }
    /**
     * Obtiene todos los grupos de la base de datos.
     */
    public function getAll(): array {
        $db = $this->dbHelper->connect();
        $sql = "SELECT * FROM grupo ORDER BY id_grupo DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtiene un grupo por su ID.
     */
    public function getById(int $id): array {
        $db = $this->dbHelper->connect();
        $sql = "SELECT * FROM grupo WHERE id_grupo = :id_grupo LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_grupo' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
//hasta aca el cu5
    /** CU7 Gestionar Clases
     * Obtiene todos los grupos (alias de getAll).
     */
    public function getGrupos(): array {
        return $this->getAll();
    }
    /**
     * Verifica si existe un grupo por su ID.
     */
    public function existsGrupo(int $idGrupo): bool {
        $grupo = $this->getById($idGrupo);
        return !empty($grupo);
    }
    /**
     * Obtiene los datos de un grupo por su ID (alias de getById).
     */
    /**
     * Obtiene los datos de un grupo por su ID.
     */
    public function getGrupo(int $idGrupo): array {
        $db = $this->dbHelper->connect();
        $sql = "SELECT * FROM grupo WHERE id_grupo = :id_grupo LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_grupo' => $idGrupo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
}
