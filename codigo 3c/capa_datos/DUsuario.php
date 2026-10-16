<?php
require_once __DIR__ . '/DatabaseHelper.php';

class DUsuario {
    private $dHelper;
    public function __construct() {
        $this->dHelper = DatabaseHelper::getInstance();
    }

    // CU1 Administrar Usuarios
    // Inserta un usuario en la base de datos (CU1)
    public function create(array $data): int {
        return $this->dHelper->insert('usuario', $data);
    }

    // Actualiza un usuario en la base de datos (CU1)
    public function update(int $id, array $data): int {
        return $this->dHelper->update('usuario', $data, $id);
    }

    // Elimina un usuario de la base de datos (CU1)
    public function delete(int $id): int {
        return $this->dHelper->delete('usuario', $id);
    }

    // Obtiene todos los usuarios de la base de datos (CU1)
    public function getAll() {
        $sql = "SELECT * FROM usuario ORDER BY id";
        $db = $this->dHelper->connect();
        return $db->query($sql);
    }

    // Obtiene un usuario por ID de la base de datos (CU1)
    public function getById(int $id) {
        $sql = "SELECT * FROM usuario WHERE id = :id";
        $db = $this->dHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Busca un usuario por nombre de usuario y contraseña (CU1)
    public function login(string $username, string $password) {
        $sql = "SELECT * FROM usuario WHERE nombre_usuario = :nombre_usuario AND contrasena = :contrasena LIMIT 1";
        $db = $this->dHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'nombre_usuario' => $username,
            'contrasena' => $password
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verifica si un usuario tiene referencias en otras tablas (CU1)
    public function checkReferences(int $id): bool {
        if (!$this->userExists($id)) {
            return false;
        }

        $sql = "
            SELECT
                (SELECT COUNT(*) FROM profesor WHERE usuario_id = :id) +
                (SELECT COUNT(*) FROM estudiante WHERE usuario_id = :id) AS refs
        ";

        $db = $this->dHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $refs = intval($stmt->fetchColumn());

        // true = se puede eliminar (sin referencias)
        return $refs === 0;
    }
//fin cu1
    private function userExists(int $id): bool {
        $sql = "SELECT 1 FROM usuario WHERE id = :id LIMIT 1";
        $db = $this->dHelper->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    // Métodos según diagrama procedimental

    /**
     * guardarUsuario(): int
     * Inserta un nuevo usuario en la BD
     */
    private function guardarUsuario($datos) {
        return $this->create($datos);
    }

    /**
     * actualizarUsuario(): int
     * Actualiza un usuario existente
     */
    private function actualizarUsuario($id, $datos) {
        return $this->update((int)$id, $datos);
    }

    /**
     * borrarUsuario(): int
     * Elimina un usuario de la BD
     */
    private function borrarUsuario($id) {
        return $this->delete((int)$id);
    }

    /**
     * getTabla(): PDOStatement
     * Obtiene todos los usuarios de la BD
     */
    private function getTabla() {
        return $this->getAll();
    }

    /**
     * findById(id: int): DUsuario
     * Busca un usuario por ID
     */
    private function findById($id) {
        return $this->getById((int)$id);
    }

    /**
     * validarCredenciales(user, pass): boolean
     * Valida las credenciales de login
     */
    private function validarCredenciales($usuario, $contrasena) {
        return (bool)$this->login((string)$usuario, (string)$contrasena);
    }

}
