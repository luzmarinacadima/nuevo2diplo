<?php
require_once __DIR__ . '/../capa_datos/DGrupo.php';

class NGrupo {
    private $dGrupo;

    public function __construct() {
        $this->dGrupo = new DGrupo();
    }

    /**
     * Crea un nuevo grupo.
     */
    public function create(array $data): bool {
        try {
            $nombre = trim((string)($data['nombre'] ?? ''));
            $capacidadMaxima = intval($data['capacidad_maxima'] ?? 0);
            $materiaId = intval($data['materia_id'] ?? $data['id_materia'] ?? 0);
            $profesorId = intval($data['profesor_id'] ?? $data['id_profesor'] ?? 0);

            // Validaciones básicas antes de persistir
            if ($nombre === '' || $capacidadMaxima <= 0) {
                return false;
            }

            return $this->dGrupo->create([
                'nombre' => $nombre,
                'capacidad_maxima' => $capacidadMaxima,
                'materia_id' => $materiaId,
                'profesor_id' => $profesorId,
                'capacidad_actual' => 0,
            ]) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
    /**
     * Actualiza los datos de un grupo existente. Llama a DGrupo->update().
     */
    public function update(int $id, array $data): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            // Soporte para CU Asignación: permitir actualización parcial de capacidad_actual
            if (array_key_exists('capacidad_actual', $data) && count($data) === 1) {
                $capacidadActual = intval($data['capacidad_actual']);
                if ($capacidadActual < 0) {
                    return false;
                }
                return $this->dGrupo->update($id, ['capacidad_actual' => $capacidadActual]);
            }

            $nombre = trim((string)($data['nombre'] ?? ''));
            $capacidadMaxima = intval($data['capacidad_maxima'] ?? 0);
            $materiaId = intval($data['materia_id'] ?? 0);
            $profesorId = intval($data['profesor_id'] ?? 0);

            if ($nombre === '' || $capacidadMaxima <= 0 || $materiaId <= 0 || $profesorId <= 0) {
                return false;
            }

            return $this->dGrupo->update($id, [
                'nombre' => $nombre,
                'capacidad_maxima' => $capacidadMaxima,
                'materia_id' => $materiaId,
                'profesor_id' => $profesorId,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
    /**
     * Elimina un grupo por su ID. Llama a DGrupo->delete().
     */
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }

        try {
            return $this->dGrupo->delete($id);
        } catch (Throwable $e) {
            return false;
        }
    }
    /**
     * Obtiene todos los grupos registrados. Llama a DGrupo->getAll().
     */
    public function getAll(): array {
        return $this->dGrupo->getAll();
    }
    /**
     * Obtiene los datos de un grupo por su ID. Llama a DGrupo->getById().
     */
    public function getById(int $id): array {
        return $this->dGrupo->getById($id);
    }
    //aca termina el cu5
    /**
     * CU7 Gestionar Clases
     * Obtiene todos los grupos registrados. Llama a DGrupo->getAll().
     */
    public function getGrupos(): array {
        return $this->dGrupo->getGrupos();
    }

    /**
     * Verifica si existe un grupo por su ID. Llama a DGrupo->existsGrupo().
     */
    public function existsGrupo(int $idGrupo): bool {
        if ($idGrupo <= 0) return false;
        return $this->dGrupo->existsGrupo($idGrupo);
    }

    /**
     *  Obtiene los datos de un grupo por su ID. Llama a DGrupo->getGrupo().
     */
    public function getGrupo(int $idGrupo): array {
        return $this->dGrupo->getGrupo($idGrupo);
    }
}
