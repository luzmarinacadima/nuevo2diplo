<?php
require_once __DIR__ . '/../capa_datos/DaGrupo.php';
require_once __DIR__ . '/../capa_datos/DAsignacion.php';

class NaGrupo {
    private DaGrupo $daGrupo;
    private DAsignacion $dAsignacion;

    public function __construct() {
        $this->daGrupo = new DaGrupo();
        $this->dAsignacion = new DAsignacion();
    }
    /**
 * CU6 Asignar Grupo a un Estudiante
     * Obtiene los datos de un grupo por su ID.
     */
    public function getById(int $groupId): array {
        if ($groupId <= 0) {
            return [];
        }
        $grupo = $this->daGrupo->getById($groupId);
        // Incluir asignaciones como parte del array de retorno
        $asignaciones = $this->dAsignacion->getAssignments($groupId);
        $grupo['asignaciones'] = $asignaciones;
        return $grupo;
    }
    /**
     * Obtiene todos los grupos registrados.
     */
    public function getAll(): array {
        // Lógica de negocio: obtener todos los grupos, se podría agregar paginación o filtros
        $grupos = $this->daGrupo->getAll();
        // Ejemplo: filtrar grupos inactivos si existiera ese campo
        // $grupos = array_filter($grupos, fn($g) => $g['activo'] ?? true);
        return $grupos;
    }
    /**
     * Actualiza los datos de un grupo.
     */
    public function update(int $groupId, array $data): bool {
        // Lógica de negocio: validar datos antes de actualizar
        if ($groupId <= 0 || empty($data) || !is_array($data)) {
            return false;
        }
        // Ejemplo de lógica: no permitir capacidad_actual mayor a capacidad_maxima
        if (isset($data['capacidad_actual']) && isset($data['capacidad_maxima'])) {
            if ($data['capacidad_actual'] > $data['capacidad_maxima']) {
                return false;
            }
        }
        // Solo permitir actualización de campos válidos
        $permitidos = ['nombre', 'capacidad_actual', 'capacidad_maxima'];
        $dataFiltrada = array_intersect_key($data, array_flip($permitidos));
        if (empty($dataFiltrada)) {
            return false;
        }
        return $this->daGrupo->update($groupId, $dataFiltrada);
    }
    /**
     * Asignación masiva de estudiantes a un grupo. 
     */
    public function bulkAssign(array $data): array {
        $groupId = intval($data['groupId'] ?? $data['grupoId'] ?? $data['grupo_id'] ?? 0);
        if ($groupId <= 0) {
            return [['ok' => false, 'message' => 'ID de grupo inválido']];
        }

        // Obtiene los códigos directamente del array de datos
        $codes = $data['codes'] ?? $data['codigos'] ?? [];

        if (empty($codes)) {
            return [['ok' => false, 'message' => 'No se encontraron códigos de estudiantes para procesar']];
        }

        // 3. Realiza validaciones de negocio (Capacidad)
        if (!$this->daGrupo->checkCapacity($groupId)) {
            $result = [];
            foreach ($codes as $code) {
                $result[] = ['ok' => false, 'code' => $code, 'groupId' => $groupId, 'message' => 'Sin cupos disponibles'];
            }
            return $result;
        }

        // 4. Realiza inserciones en la base de datos mediante DAsignacion
        $result = $this->dAsignacion->bulkAssign(['groupId' => $groupId, 'codes' => $codes]);

        // 5. Actualiza el estado del grupo
        $this->actualizarCapacidadActual($groupId);

        return $result;
    }
    /**
     * Actualiza la capacidad_actual del grupo en base a la cantidad de estudiantes asignados.
     */
    private function actualizarCapacidadActual(int $groupId): void {
        // Contar estudiantes asignados
        $asignados = $this->dAsignacion->getAssignments($groupId);
        $n = is_array($asignados) ? count($asignados) : 0;
        $this->daGrupo->update($groupId, ['capacidad_actual' => $n]);
    }
    
    /**
     * Verifica si un grupo tiene capacidad disponible.
     */
    public function checkCapacity(int $groupId): bool {
        // Lógica de negocio: se puede agregar reglas adicionales si es necesario
        if ($groupId <= 0) {
            return false;
        }
        return $this->daGrupo->checkCapacity($groupId);
    }
    /**
    * Asigna 1 SOLO estudiante a 1 GRUPO usando la capa de negocio.
    */
    public function assignToGroup(string $code, int $groupId): array {
        // Validar capacidad antes de asignar
        if (!$this->daGrupo->checkCapacity($groupId)) {
            return ['ok' => false, 'code' => $code, 'groupId' => $groupId, 'message' => 'Sin cupos'];
        }
        $result = $this->dAsignacion->assignToGroup($code, $groupId);
        $this->actualizarCapacidadActual($groupId);
        return $result;
    }
    // fin CU6 Asignar Grupo a un Estudiante
}