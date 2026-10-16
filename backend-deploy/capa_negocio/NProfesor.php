<?php
require_once __DIR__ . '/../capa_datos/DProfesor.php';

class NProfesor {

    // Atributo según diagrama
    private $dProfesor;

    public function __construct() {
        $this->dProfesor = new DProfesor();
    }

    // API del diagrama
    public function create(array $data): bool {
        return $this->dProfesor->create($data) > 0;
    }

    public function update(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }
        if (isset($data['codigo_profesor'])) {
            $profesores = $this->dProfesor->getAll();
            foreach ($profesores as $profesor) {
                if (
                    isset($profesor['codigo_profesor'], $profesor['usuario_id']) &&
                    $profesor['codigo_profesor'] === $data['codigo_profesor'] &&
                    intval($profesor['usuario_id']) !== $id
                ) {
                    // Ya existe otro profesor con ese código
                    return false;
                }
            }
        }
        return $this->dProfesor->update($id, $data) > 0;
    }

    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        return $this->dProfesor->delete($id) > 0;
    }

    public function getAll(): array {
        return $this->dProfesor->getAll();
    }

    public function getById(int $id) {
        if ($id <= 0) {
            return false;
        }
        return $this->dProfesor->getById($id);
    }

    public function checkReferences(int $id): bool {
        return $this->dProfesor->checkReferences($id);
    }

    // getProfesores(): PDOStatement
    private function getProfesores() {
        return $this->getAll();
    }

    // setProfesor(id_usuario, codigo_profesor): boolean
    private function setProfesor($id_usuario, $codigo_profesor) {
        if (empty($id_usuario) || empty($codigo_profesor)) {
            return false;
        }
        $datos = [
            'usuario_id' => $id_usuario,
            'codigo_profesor' => $codigo_profesor
        ];
        return $this->create($datos);
    }

    // modificarProfesor(id_usuario, codigo_profesor): boolean
    private function modificarProfesor($id_usuario, $codigo_profesor) {
        if (empty($id_usuario) || empty($codigo_profesor)) {
            return false;
        }
        $datos = [
            'usuario_id' => $id_usuario,
            'codigo_profesor' => $codigo_profesor
        ];
        return $this->update((int)$id_usuario, $datos);
    }

    // obtenerUsuariosDisponibles(): array
    private function obtenerUsuariosDisponibles() {
        return $this->dProfesor->obtenerUsuariosDisponibles();
    }

    // verificarReferenciasProfesor(id: int): boolean
    private function verificarReferenciasProfesor($id) {
        return $this->checkReferences((int)$id);
    }

    // eliminarProfesor(id: int): boolean
    private function eliminarProfesor($id) {
        return $this->delete((int)$id);
    }
    // Devuelve profesores con nombres y apellidos (JOIN usuario)
    private function getProfesoresConDatos() {
            return $this->dProfesor->getProfesoresConDatos();
    }
}
