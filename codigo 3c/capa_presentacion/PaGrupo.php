<?php
require_once __DIR__ . '/../capa_negocio/NaGrupo.php';
require_once __DIR__ . '/../capa_negocio/NEstudiante.php';

class PaGrupo {
    private array $assignments = [];
    private array $codes = [];
    private int $groupId = 0;

    private $naGrupo;
    private $nEstudiante;

    public function __construct() {
        $this->naGrupo = new NaGrupo();
        $this->nEstudiante = new NEstudiante();
    }

    /**
     * CU6 Asignar Grupo a un Estudiante
     * Muestra la lista de estudiantes asignados a un grupo.
     */
    public function getAssignments(int $groupId): void {
        $data = $this->naGrupo->getById($groupId);
        $rows = $data['asignaciones'] ?? [];
        echo '<div class="card">';
        echo '<div class="panel-header">';
        echo '<h3 class="panel-title">Asignaciones realizadas</h3>';
        echo '</div>';
        if (empty($rows)) {
            echo '<p>No hay asignaciones para este grupo.</p>';
        } else {
            echo '<div class="table-container"><table>';
            echo '<thead><tr><th>Codigo</th><th>Nombre</th><th>Fecha</th></tr></thead>';
            echo '<tbody>';
            foreach ($rows as $row) {
                $codigo = (string)($row['codigo_estudiante'] ?? '');
                $nombre = trim((string)($row['apellidos'] ?? '') . ' ' . (string)($row['nombres'] ?? ''));
                $fecha = (string)($row['fecha_asignacion'] ?? '');
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($codigo) . '</strong></td>';
                echo '<td>' . htmlspecialchars($nombre) . '</td>';
                echo '<td>' . htmlspecialchars($fecha) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
        echo '</div>';
    }

    /**
     * Asigna 1 SOLO estudiante a 1 GRUPO usando la capa de negocio.
     */
    public function assignToGroup(string $code, int $groupId): array { //Asignar
        $this->groupId = $groupId;
        // Llama a la capa de negocio para asignar un solo estudiante (flujo individual)
        return $this->naGrupo->assignToGroup($code, $groupId);
    }
    /**
     * Obtiene los datos de un grupo por ID.
     */
    public function getById(int $groupId): array {
        return $this->naGrupo->getById($groupId);
    }

    /**
     * Asignación MASIVA de estudiantes a un grupo usando la capa de negocio.
     */
    public function bulkAssign(array $data): array { //csv
        $this->assignments = [];
        $groupId = intval($data['groupId'] ?? $data['grupoId'] ?? $data['grupo_id'] ?? 0);
        $codes = $data['codes'] ?? $data['codigos'] ?? [];
        if (!is_array($codes)) {
            $codes = [];
        }
        $this->assignments = $this->naGrupo->bulkAssign(['groupId' => $groupId, 'codes' => $codes]);
        return $this->assignments;
    }

    /**
     * Muestra el formulario y la tabla principal de asignación de estudiantes a grupos.
     * Procesa el formulario POST, lee los códigos, llama a bulkAssign y muestra resultados.
     * Llama a NaGrupo->getAll() para listar los grupos.
     */
    public function getAll(): array {
        $grupos = $this->naGrupo->getAll();
        // Render UI: formulario moderno con carga de CSV
        echo '<div class="card card-md" style="max-width: 480px; margin: 32px auto;">';
        echo '<div class="panel-header">';
        echo '<h2 class="panel-title" style="font-size:1.5rem;">Asignar a un Grupo estudiantes</h2>';
        echo '</div>';
        echo '<form method="POST" enctype="multipart/form-data" autocomplete="off">';
        // Grupo
        echo '<div class="form-group">';
        echo '<label for="grupo_id">GRUPO</label>';
        echo '<select class="form-control" id="grupo_id" name="grupo_id" required>';
        echo '<option value="">Seleccione...</option>';
        foreach ($grupos as $grupo) {
            $id = intval($grupo['id_grupo'] ?? 0);
            $nombre = (string)($grupo['nombre'] ?? ('Grupo ' . $id));
            echo '<option value="' . $id . '">' . htmlspecialchars($nombre) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        // Codigos
        echo '<div class="form-group">';
        echo '<label for="codes_input">CODIGO ESTUDIANTE</label>';
        echo '<input class="form-control" id="codes_input" type="text" name="codes_input" placeholder="EST001, EST002, EST003">';
        echo '</div>';
        // CSV
        echo '<div class="form-group">';
        echo '<label for="csv_file">O CARGAR CSV</label>';
        echo '<input class="form-control" id="csv_file" type="file" name="csv_file" accept=".csv">';
        echo '</div>';
        // Botón
        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn btn-block" style="font-size:1.1rem;">ASIGNAR</button>';
        echo '</div>';
        echo '</form>';
        // Procesar asignación masiva si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grupo_id'])) {
            $groupId = intval($_POST['grupo_id']);
            $codes = [];
            // Si se subió un archivo CSV, procesar el archivo
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $csvTmp = $_FILES['csv_file']['tmp_name'];
                $csvRows = array_map('str_getcsv', file($csvTmp));
                foreach ($csvRows as $row) {
                    if (isset($row[0])) {
                        $code = trim($row[0]);
                        if ($code !== '') $codes[] = $code;
                    }
                }
            } elseif (isset($_POST['codes_input'])) {
                $codes = array_map('trim', explode(',', $_POST['codes_input']));
            }
            $codes = array_filter($codes, fn($c) => $c !== '');
            $result = $this->bulkAssign(['groupId' => $groupId, 'codes' => $codes]);
            // Tarjeta resultado de asignación (más ancha y centrada)
            echo '<div class="card card-soft card-md" style="margin-top: 18px; box-shadow: 0 8px 24px -12px #2563eb1a; border-radius: 16px; max-width: 900px; margin-left:auto; margin-right:auto;">';
            echo '<h3 style="font-size:1.18rem; font-family:Sora,sans-serif; font-weight:700; margin-bottom:18px;">Resultado de asignacion</h3>';
            if (empty($result)) {
                echo '<p>No se proporcionaron códigos válidos.</p>';
            } else {
                echo '<div style="overflow-x:auto;">';
                echo '<table style="width:100%; border-radius:12px; background:#f8fbff; border-collapse:separate; border-spacing:0; font-size:1.08rem;">';
                echo '<thead style="background:#f1f7fd;">';
                echo '<tr>';
                echo '<th style="padding:12px 16px; font-size:0.93rem; font-weight:700; color:#2563eb; border-top-left-radius:12px;">CODIGO</th>';
                echo '<th style="padding:12px 16px; font-size:0.93rem; font-weight:700; color:#2563eb;">ESTADO</th>';
                echo '<th style="padding:12px 16px; font-size:0.93rem; font-weight:700; color:#2563eb; border-top-right-radius:12px;">MENSAJE</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($result as $row) {
                    $code = (string)($row['code'] ?? '');
                    $ok = !empty($row['ok']);
                    $estado = $ok ? 'OK' : 'ERROR';
                    $msg = (string)($row['message'] ?? '');
                    echo '<tr style="background:#fff;">';
                    echo '<td style="padding:12px 16px; font-weight:700; color:#222;">' . htmlspecialchars($code) . '</td>';
                    echo '<td style="padding:12px 16px; color:' . ($ok ? '#059669' : '#dc2626') . '; font-weight:600;">' . htmlspecialchars($estado) . '</td>';
                    echo '<td style="padding:12px 16px; color:#222;">' . htmlspecialchars($msg) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            echo '</div>';
            // Tarjeta asignaciones realizadas (más ancha y centrada)
            $grupo = $this->naGrupo->getById($groupId);
            $asignados = $grupo['asignaciones'] ?? [];
            echo '<div class="card card-soft card-md" style="margin-top: 18px; box-shadow: 0 8px 24px -12px #2563eb1a; border-radius: 16px; max-width: 900px; margin-left:auto; margin-right:auto;">';
            echo '<h3 style="font-size:1.18rem; font-family:Sora,sans-serif; font-weight:700; margin-bottom:18px;">Asignaciones realizadas</h3>';
            if (empty($asignados)) {
                echo '<p>No hay asignaciones para este grupo.</p>';
            } else {
                echo '<div style="overflow-x:auto;">';
                echo '<table style="width:100%; border-radius:12px; background:#f8fbff; border-collapse:separate; border-spacing:0; font-size:1.08rem;">';
                echo '<thead style="background:#f1f7fd;">';
                echo '<tr>';
                echo '<th style="padding:12px 16px; font-size:0.93rem; font-weight:700; color:#2563eb; border-top-left-radius:12px;">CODIGO</th>';
                echo '<th style="padding:12px 16px; font-size:0.93rem; font-weight:700; color:#2563eb;">NOMBRE</th>';
                echo '<th style="padding:12px 16px; font-size:0.93rem; font-weight:700; color:#2563eb; border-top-right-radius:12px;">FECHA</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($asignados as $row) {
                    $codigo = (string)($row['codigo_estudiante'] ?? '');
                    $nombre = trim((string)($row['apellidos'] ?? '') . ' ' . (string)($row['nombres'] ?? ''));
                    $fecha = (string)($row['fecha_asignacion'] ?? '');
                    echo '<tr style="background:#fff;">';
                    echo '<td style="padding:12px 16px; font-weight:700; color:#222;">' . htmlspecialchars($codigo) . '</td>';
                    echo '<td style="padding:12px 16px; color:#222;">' . htmlspecialchars($nombre) . '</td>';
                    echo '<td style="padding:12px 16px; color:#222;">' . htmlspecialchars($fecha) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            echo '</div>';
        }
        echo '</div>';
        return $grupos;
    }
    /**
     * Verifica si un grupo tiene capacidad disponible.
     */
    public function checkCapacity(int $groupId): bool {
        return $this->naGrupo->checkCapacity($groupId);
    }
    /**
     * Verifica si existe un estudiante por código.
     */
    public function existsStudent(string $code): bool {
        return $this->nEstudiante->existsStudent($code);
    }
    /**
     * Actualiza los datos de un grupo.
     */
    public function update(int $groupId, array $data): bool {
        return $this->naGrupo->update($groupId, $data);
    }
}
