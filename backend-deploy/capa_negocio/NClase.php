<?php
require_once __DIR__ . '/../capa_datos/DClase.php';
require_once __DIR__ . '/../capa_datos/DAsistencia.php';

class NClase {
    private $dClase;

    public function __construct() {
        $this->dClase = new DClase();
    }

    /**
     * CU7 Gestionar Clases
     * Crea una nueva clase con los datos proporcionados.
     * Valida datos y existencia de grupo. Llama a DClase->create().
     */
    public function create(array $data): bool {
        $payload = $this->normalizarDatosClase($data);
        if ($payload === null) {
            return false;
        }
        if (!$this->grupoExiste($payload['grupo_id'])) {
            return false;
        }
        return $this->dClase->create($payload) > 0;
    }
    /**
     * Actualiza los datos de una clase existente.
     * Valida datos y existencia de grupo. Llama a DClase->update().
     */
    public function update(int $id, array $data): bool {
        if ($id <= 0 || !$this->dClase->exists($id)) {
            return false;
        }
        $payload = $this->normalizarDatosClase($data);
        if ($payload === null) {
            return false;
        }
        if (!$this->grupoExiste($payload['grupo_id'])) {
            return false;
        }
        return $this->dClase->update($id, $payload);
    }
    /**
     * Normaliza y valida los datos de clase recibidos.
     * Devuelve un array listo para la capa de datos o null si hay error.
     */
    private function normalizarDatosClase(array $data): ?array {
        $grupoId = intval($data['grupo_id'] ?? 0);
        $dia = trim((string)($data['dia'] ?? ''));
        $horaInicio = trim((string)($data['hora_inicio'] ?? ''));
        $horaFin = trim((string)($data['hora_fin'] ?? ''));
        $codigo = trim((string)($data['codigo'] ?? ''));

        if ($grupoId <= 0 || $dia === '' || $horaInicio === '' || $horaFin === '' || $codigo === '') {
            return null;
        }

        if (strtotime($horaInicio) === false || strtotime($horaFin) === false) {
            return null;
        }

        if (strtotime($horaInicio) >= strtotime($horaFin)) {
            return null;
        }

        return [
            'grupo_id' => $grupoId,
            'dia' => $dia,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'codigo' => $codigo
        ];
    }
    /**
     * Verifica si existe el grupo dado usando NGrupo.
     */
    private function grupoExiste(int $idGrupo): bool {
        return $idGrupo > 0;
    }
    /**
     * Elimina una clase por su ID. Valida existencia usando el método público exists de DClase antes de eliminar.
     */
    public function delete(int $id): bool {
        if ($id <= 0 || !$this->dClase->exists($id)) {
            return false;
        }
        return $this->dClase->delete($id);
    }
    /**
     * Obtiene todas las clases registradas. Llama a DClase->getAll().
     */
    public function getAll(): array {
        return $this->dClase->getAll();
    }
    /**
     * Genera un código único para una clase (interno de negocio).
     */
    public function generateClassCode(): string {
        return 'CL' . date('YmdHis') . rand(100, 999);
    }
    /**
     * Obtiene las clases asociadas a un profesor (usuario).
     */
    public function getByProfesorUsuarioId(int $usuarioId): array {
        if ($usuarioId <= 0) {
            return [];
        }
        return $this->dClase->getByProfesorUsuarioId($usuarioId);
    }
//hasta aca estan bien los metodos cu7
    // CU8 Registrar Asistencia
    /**
     * Verifica si existe una clase por su ID (llama a DClase->exists).
     */
    public function exists(int $id_clase): bool {
        return $this->dClase->exists($id_clase);
    }
    /**
     * Verifica si una clase está finalizada. Llama a DClase->isFinalizada().
     */
    public function isFinalizada(int $id_clase): bool {
        return $this->dClase->isFinalizada($id_clase);
    }
    /**
     * Obtiene el detalle de una clase por su ID. Llama a DClase->getClaseDetalle().
     */
    public function getClaseDetalle(int $id_clase): array {
        return $this->dClase->getClaseDetalle($id_clase);
    }
    /**
     * Verifica si un estudiante está asignado a una clase. Llama a DClase->isStudentInClass().
     */
    public function isStudentInClass(int $id_clase, string $codigo_estudiante): bool {
        return $this->dClase->isStudentInClass($id_clase, $codigo_estudiante);
    }

}
?>
