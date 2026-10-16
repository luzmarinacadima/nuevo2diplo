<?php
require_once __DIR__ . '/../capa_datos/DMateria.php';

class NMateria {
    private $dMateria;

    public function __construct() {
        $this->dMateria = new DMateria();
    }
    // CU4 Administrar Materia
    // Crea una nueva materia con el nombre proporcionado. Llama a DMateria->create().
    public function create(array $data): bool {
        $nombre = $this->normalizarNombre($data['nombre'] ?? '');
        if ($nombre === '') {
            return false;
        }
        return $this->dMateria->create(['nombre' => $nombre]) > 0;
    }
    // Actualiza el nombre de una materia existente. Llama a DMateria->update().
    public function update(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }
        if (!$this->dMateria->existsMateria($id)) {
            return false;
        }
        $nombre = $this->normalizarNombre($data['nombre'] ?? '');
        if ($nombre === '') {
            return false;
        }
        return $this->dMateria->update($id, ['nombre' => $nombre]);
    }
    // Elimina una materia por su ID si no tiene referencias. Llama a DMateria->delete().
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        if (!$this->checkReferences($id)) {
            return false;
        }
        return $this->dMateria->delete($id);
    }
    //tambien  CU5 Administrar Grupo
    // Obtiene todas las materias registradas. Llama a DMateria->getAll().
    public function getAll(): array {
        return $this->dMateria->getAll();
    }
    //tambien  CU5 Administrar Grupo
    // Obtiene los datos de una materia por su ID. Llama a DMateria->getById().
    public function getById(int $id): array {
        if ($id <= 0) {
            return [];
        }
        $row = $this->dMateria->getById($id);
        return $row ?: [];
    }
    // Verifica si una materia tiene referencias. Llama a DMateria->checkReferences().
    public function checkReferences(int $id): bool {
        return $this->dMateria->checkReferences($id);
    }
//fin CU4 Administrar Materia y CU5 Administrar Grupo
    //  CU7 Gestionar Clases
    // Obtiene las materias asociadas a un grupo. Llama a DMateria->getMateriasByGrupo().
    public function getMateriasByGrupo(int $idGrupo): array {
        return $this->dMateria->getMateriasByGrupo($idGrupo);
    }
    // Verifica si existe una materia por su ID. Llama a DMateria->existsMateria().
    public function existsMateria(int $idMateria): bool {
        if ($idMateria <= 0) {
            return false;
        }
        return $this->dMateria->existsMateria($idMateria);
    }
//fin cu7 more private methods

    private function normalizarNombre($nombre): string {
        return trim((string)$nombre);
    }

    private function getMaterias(): array {
        return $this->getAll();
    }

    private function setMateria($nombre) {
        if (empty($nombre)) {
            return false;
        }
        $datos = ['nombre' => $nombre];
        return $this->create($datos);
    }

    private function modificarMateria($id, $nombre) {
        if (empty($nombre) || empty($id)) {
            return false;
        }
        $datos = ['nombre' => $nombre];
        return $this->update((int)$id, $datos);
    }

    private function eliminarMateria($id) {
        return $this->delete((int)$id);
    }

    private function verificarReferenciasMateria($id) {
        return $this->checkReferences((int)$id);
    }
}
