<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DProfesor {
    // Atributos según diagrama
    private $codigo_profesor;
    private $id;
    private $dbHelper;

    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }

    // API del diagrama
    public function create(array $data): int {
        return $this->dbHelper->insert('profesor', $data);
    }

    public function update(int $usuario_id, array $data): int {
        $db = $this->dbHelper->connect();
        $set = "";
        foreach ($data as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ", ");
        $sql = "UPDATE profesor SET $set WHERE usuario_id = :usuario_id";
        $data['usuario_id'] = $usuario_id;
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function delete(int $usuario_id): int {
        $db = $this->dbHelper->connect();
        $sql = "DELETE FROM profesor WHERE usuario_id = :usuario_id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['usuario_id' => $usuario_id]);
        return $stmt->rowCount();
    }

    public function getAll(): array {
        $sql = "SELECT * FROM profesor ORDER BY codigo_profesor";
        $db = $this->dbHelper->connect();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id) {
        $sql = "SELECT * FROM profesor WHERE usuario_id = :id LIMIT 1";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkReferences(int $id): bool {
        return (bool)$this->getById($id);
    }

    // Métodos según diagrama
    private function getTabla() {
        return $this->getAll();
    }

    private function guardarProfesor($datos) {
        return $this->create($datos);
    }

    private function actualizarProfesor($usuario_id, $datos) {
        return $this->update((int)$usuario_id, $datos);
    }

    private function borrarProfesor($usuario_id) {
        return $this->delete((int)$usuario_id);
    }

    private function findById($id) {
        return $this->getById((int)$id);
    }

    public function getProfesoresConDatos(): array {
        $sql = "SELECT p.usuario_id, u.nombres, u.apellidos FROM profesor p JOIN usuario u ON p.usuario_id = u.id";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUsuariosDisponibles(): array {
        $sql = "SELECT u.* FROM usuario u LEFT JOIN profesor p ON u.id = p.usuario_id WHERE p.usuario_id IS NULL AND u.tipo_usuario = 'profesor'";
        $db = $this->dbHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
