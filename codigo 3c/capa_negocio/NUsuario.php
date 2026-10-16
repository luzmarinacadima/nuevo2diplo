<?php
require_once __DIR__ . '/../capa_datos/DUsuario.php';

class NUsuario {
    // Atributo según diagrama procedimental
    private $dUsuario;
    private const TIPOS_USUARIO_VALIDOS = ['admin', 'profesor', 'estudiante'];

    public function __construct() {
        $this->dUsuario = new DUsuario();
    }

    // CU1 Administrar Usuarios
    // Crea un usuario (CU1)
    public function create(array $data): bool {
        $payload = $this->normalizarDatosUsuario($data);
        if ($payload === null) {
            return false;
        }
        // Validar que no exista un usuario con el mismo nombre_usuario
        $usuarios = $this->dUsuario->getAll();
        foreach ($usuarios as $usuario) {
            if (isset($usuario['nombre_usuario']) && $usuario['nombre_usuario'] === $payload['nombre_usuario']) {
                // Ya existe un usuario con ese nombre_usuario
                return false;
            }
        }
        return $this->dUsuario->create($payload) > 0;
    }

    // Actualiza un usuario (CU1)
    public function update(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }
        $payload = $this->normalizarDatosUsuario($data);
        if ($payload === null) {
            return false;
        }
        return $this->dUsuario->update($id, $payload) > 0;
    }

    // Elimina un usuario (CU1)
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        if (!$this->dUsuario->checkReferences($id)) {
            return false;
        }

        return $this->dUsuario->delete($id) > 0;
    }

    // Obtiene todos los usuarios (CU1)
    public function getAll() {
        return $this->dUsuario->getAll();
    }

    // Obtiene un usuario por ID (CU1)
    public function getById(int $id) {
        if ($id <= 0) {
            return false;
        }
        return $this->dUsuario->getById($id);
    }

    // Valida el login de usuario (CU1)
    public function login(string $username, string $password): array {
        $username = trim($username);
        $password = trim($password);
        if ($username === '' || $password === '') {
            return [];
        }
        $row = $this->dUsuario->login($username, $password);
        return $row ?: [];
    }

    // Verifica si un usuario puede eliminarse (CU1)
    public function checkReferences(int $id): bool {
        return $this->dUsuario->checkReferences($id);
    }

    private function normalizarDatosUsuario(array $data): ?array {
        $nombres = trim((string)($data['nombres'] ?? ''));
        $apellidos = trim((string)($data['apellidos'] ?? ''));
        $genero = trim((string)($data['genero'] ?? ''));
        $nombreUsuario = trim((string)($data['nombre_usuario'] ?? ''));
        $contrasena = trim((string)($data['contrasena'] ?? ''));
        $tipoUsuario = strtolower(trim((string)($data['tipo_usuario'] ?? '')));

        if ($nombres === '' || $apellidos === '' || $nombreUsuario === '' || $contrasena === '' || !$this->esTipoUsuarioValido($tipoUsuario)) {
            return null;
        }
//fin cu1 
        return [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'genero' => $genero,
            'nombre_usuario' => $nombreUsuario,
            'contrasena' => $contrasena,
            'tipo_usuario' => $tipoUsuario
        ];
    }

    private function esTipoUsuarioValido(string $tipoUsuario): bool {
        return in_array($tipoUsuario, self::TIPOS_USUARIO_VALIDOS, true);
    }

    // Métodos según diagrama procedimental

    /**
     * getUsuarios(): PDOStatement
     * Obtiene la lista de todos los usuarios
     */
    private function getUsuarios() {
        return $this->getAll();
    }

    /**
     * setUsuario(nombres, apellidos, genero, user, pass, tipo): boolean
     * Crea un nuevo usuario con los atributos básicos
     */
    private function setUsuario($nombres, $apellidos, $genero, $user, $pass, $tipo) {
        if (empty($nombres) || empty($apellidos) || empty($user) || empty($pass) || empty($tipo)) {
            return false;
        }

        $datos = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'genero' => $genero,
            'nombre_usuario' => $user,
            'contrasena' => $pass,
            'tipo_usuario' => $tipo
        ];

        return $this->create($datos);
    }

    /**
     * obtenerUsuarioPorId(id: int): array|false
     * Obtiene un usuario por su ID
     */
    private function obtenerUsuarioPorId($id) {
        return $this->getById((int)$id);
    }

    /**
     * modificarUsuario(id, nombres, apellidos, genero, user, pass, tipo, ci, cod_estudiante, estado, cod_profesor): boolean
     * Modifica un usuario existente
     */
    private function modificarUsuario($id, $nombres, $apellidos, $genero, $user, $pass, $tipo) {
        if (empty($id) || empty($nombres) || empty($apellidos) || empty($user) || empty($pass) || empty($tipo)) {
            return false;
        }
        // Solo pasar los campos que existen en la tabla usuario
        $datos = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'genero' => $genero,
            'nombre_usuario' => $user,
            'contrasena' => $pass,
            'tipo_usuario' => $tipo
        ];
        return $this->update((int)$id, $datos);
    }

    /**
     * eliminarUsuario(id: int): boolean
     * Elimina un usuario
     */
    private function eliminarUsuario($id) {
        return $this->delete((int)$id);
    }

    /**
     * validarLogin(user: string, pass: string): boolean
     * Valida las credenciales de login
     */
    private function validarLogin($user, $pass) {
        return !empty($this->login((string)$user, (string)$pass));
    }

    /**
     * verificarReferenciaUsuario(id: int): boolean
     * Verifica si el usuario puede ser eliminado (sin referencias foráneas)
     */
    private function verificarReferenciaUsuario($id) {
        return $this->checkReferences((int)$id);
    }
}
