<?php
require_once __DIR__ . '/../capa_datos/DEstudiante.php';

class NEstudiante {
    private $dEstudiante;

    public function __construct() {
        $this->dEstudiante = new DEstudiante();
    }

    // CU2 Administrar Estudiante
    public function create(array $data): bool {
        return $this->dEstudiante->create($data) > 0;
    }
    public function update(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }
        return $this->dEstudiante->update($id, $data) > 0;
    }
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        return $this->dEstudiante->delete($id) > 0;
    }
    public function getAll() {
        return $this->dEstudiante->getAll();
    }
    public function getById(int $id) {
        if ($id <= 0) {
            return false;
        }
        return $this->dEstudiante->getById($id);
    }
    public function getByCi(string $ci): array {
        if ($ci === '') {
            return [];
        }
        $row = $this->dEstudiante->getByCi($ci);
        return $row ?: [];
    }
    public function obtenerUsuariosLibres(): array {
        return $this->dEstudiante->obtenerUsuariosLibres();
    }
//hasta aca CU2 Administrar Estudiante
    //CU6 Asignar Grupo a un Estudiante
        /**
         * Verifica si existe un estudiante por id o código. Llama a DEstudiante->existsStudent().
         */
        public function existsStudent($idOrCode): bool {
            return $this->dEstudiante->existsStudent($idOrCode);
        }
    //  CU8 Registrar Asistencia
        /**
         * Obtiene los estudiantes pendientes de asistencia en una clase. Llama a DEstudiante->getPendientesByClass().
         */
        public function getPendientesByClass(int $id_clase): array {
            if ($id_clase <= 0) {
                return [];
            }
            return $this->dEstudiante->getPendientesByClass($id_clase);
        }
        /**
         * Obtiene un estudiante por su código. Llama a DEstudiante->getByCodigo().
         */
        public function getByCodigo(string $codigo_estudiante): array {
            $codigo = trim($codigo_estudiante);
            if ($codigo === '') {
                return [];
            }
            $row = $this->dEstudiante->getByCodigo($codigo);
            return $row ?: [];
        }


    // getEstudiantes(): PDOStatement
    private function getEstudiantes(): \PDOStatement {
        return $this->getAll();
    }

    // setEstudiante(id_usuario, ci, codigo_estudiante, estado): boolean
    private function setEstudiante($id_usuario, $ci, $codigo_estudiante, $estado): bool {
        $datos = [
            'usuario_id' => $id_usuario,
            'ci' => $ci,
            'codigo_estudiante' => $codigo_estudiante,
            'estado' => $estado
        ];
        return $this->create($datos);
    }

    // modificarEstudiante(id_usuario, ci, codigo_estudiante, estado): boolean
    private function modificarEstudiante($id_usuario, $ci, $codigo_estudiante, $estado): bool {
        $datos = [
            'ci' => $ci,
            'codigo_estudiante' => $codigo_estudiante,
            'estado' => $estado
        ];
        return $this->update((int)$id_usuario, $datos);
    }

    // eliminarEstudiante(codigo: string): boolean
    private function eliminarEstudiante(string $codigo): bool {
        if ($codigo === '' || !ctype_digit($codigo)) {
            return false;
        }
        return $this->delete((int)$codigo);
    }

}
