<?php
require_once __DIR__ . '/../capa_negocio/NGrupo.php';
require_once __DIR__ . '/../capa_negocio/NMateria.php';
require_once __DIR__ . '/../capa_negocio/NProfesor.php';

class PGrupo {
    private $id;
    private $nombre;
    private $capacidad_maxima;
    private $id_materia;
    private $id_profesor;
    private $capacidad_actual;

    private $nGrupo;
    private $nMateria;
    private $nProfesor;

    public function __construct() {
        $this->nGrupo = new NGrupo();
        $this->nMateria = new NMateria();
        $this->nProfesor = new NProfesor();
    }

    // API del diagrama (CU5)
    public function create(array $data): void {
        $materias = $this->nMateria->getAll();
        $profesores = $this->nProfesor->getAll();

        if (!empty($data)) {
            if (!$this->nGrupo->create($data)) {
                echo '<div class="alert alert-danger">No se pudo registrar el grupo.</div>';
            }
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigoProfesor = trim($_POST['profesor_codigo_profesor'] ?? '');
            $profesorId = 0;
            foreach ($profesores as $profesor) {
                if ((string)($profesor['codigo_profesor'] ?? '') === $codigoProfesor) {
                    $profesorId = intval($profesor['profesor_id'] ?? 0);
                    break;
                }
            }

            $datos = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'capacidad_maxima' => intval($_POST['capacidad_maxima'] ?? 0),
                'materia_id' => intval($_POST['id_materia'] ?? 0),
                'profesor_id' => $profesorId,
                'capacidad_actual' => 0
            ];

            if ($datos['nombre'] !== '' && $datos['capacidad_maxima'] > 0 && $datos['materia_id'] > 0 && $datos['profesor_id'] > 0) {
                if ($this->nGrupo->create($datos)) {
                    echo "<script>window.location='index.php?action=listar&ntype=success&nmsg=Grupo%20registrado%20correctamente';</script>";
                    exit;
                }
            }

            echo '<div class="alert alert-danger">No se pudo registrar el grupo.</div>';
        }

        echo '<div class="card card-md">';
        echo '<h2>Crear Grupo</h2>';
        echo '<form method="POST">';
        echo '<div class="form-group"><label>Nombre:</label><input type="text" class="form-control" name="nombre" required></div>';
        echo '<div class="form-group"><label>Capacidad maxima:</label><input type="number" class="form-control" name="capacidad_maxima" min="1" required value="30"></div>';

        echo '<div class="form-group"><label>Materia:</label><select name="id_materia" class="form-control" required>';
        echo '<option value="">Seleccione...</option>';
        foreach ($materias as $materia) {
            echo '<option value="' . intval($materia['id']) . '">' . htmlspecialchars((string)$materia['nombre']) . '</option>';
        }
        echo '</select></div>';

        echo '<div class="form-group"><label>Profesor:</label><select name="profesor_codigo_profesor" class="form-control" required>';
        echo '<option value="">Seleccione...</option>';
        foreach ($profesores as $profesor) {
            $codigo = (string)($profesor['codigo_profesor'] ?? '');
            echo '<option value="' . htmlspecialchars($codigo) . '">' . htmlspecialchars($codigo) . '</option>';
        }
        echo '</select></div>';

        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn">Guardar</button>';
        echo '<a href="index.php?action=listar" class="btn btn-danger">Cancelar</a>';
        echo '</div>';
        echo '</form></div>';
    }

    public function update(int $id, array $data): void {
        if (!empty($data)) {
            if ($id <= 0 || !$this->nGrupo->update($id, $data)) {
                echo '<div class="alert alert-danger">No se pudo actualizar el grupo.</div>';
            }
            return;
        }

        $idGrupo = $id > 0 ? $id : intval($_GET['id_grupo'] ?? 0);
        if ($idGrupo <= 0) {
            echo '<div class="alert alert-danger">ID de grupo no especificado.</div>';
            return;
        }

        $grupo = $this->nGrupo->getById($idGrupo);
        if (!$grupo) {
            echo '<div class="alert alert-danger">Grupo no encontrado.</div>';
            return;
        }

        $materias = $this->nMateria->getAll();
        $profesores = $this->nProfesor->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigoProfesor = trim($_POST['profesor_codigo_profesor'] ?? '');
            $profesorId = 0;
            foreach ($profesores as $profesor) {
                if ((string)($profesor['codigo_profesor'] ?? '') === $codigoProfesor) {
                    $profesorId = intval($profesor['profesor_id'] ?? 0);
                    break;
                }
            }

            $datos = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'capacidad_maxima' => intval($_POST['capacidad_maxima'] ?? 0),
                'materia_id' => intval($_POST['id_materia'] ?? 0),
                'profesor_id' => $profesorId
            ];

            if ($datos['nombre'] !== '' && $datos['capacidad_maxima'] > 0 && $datos['materia_id'] > 0 && $datos['profesor_id'] > 0) {
                if ($this->nGrupo->update($idGrupo, $datos)) {
                    echo "<script>window.location='index.php?action=listar&ntype=success&nmsg=Grupo%20actualizado%20correctamente';</script>";
                    exit;
                }
            }
            echo '<div class="alert alert-danger">No se pudo actualizar el grupo.</div>';
            $grupo = array_merge($grupo, $datos);
        }

        echo '<div class="card card-md">';
        echo '<h2>Editar Grupo</h2>';
        echo '<form method="POST">';
        echo '<input type="hidden" name="id_grupo" value="' . intval($idGrupo) . '">';

        echo '<div class="form-group"><label>Nombre:</label><input type="text" class="form-control" name="nombre" required value="' . htmlspecialchars((string)($grupo['nombre'] ?? '')) . '"></div>';
        echo '<div class="form-group"><label>Capacidad maxima:</label><input type="number" class="form-control" name="capacidad_maxima" min="1" required value="' . intval($grupo['capacidad_maxima'] ?? 30) . '"></div>';

        echo '<div class="form-group"><label>Materia:</label><select name="id_materia" class="form-control" required>';
        echo '<option value="">Seleccione...</option>';
        foreach ($materias as $materia) {
            $selected = (intval($grupo['materia_id'] ?? 0) === intval($materia['id'])) ? ' selected' : '';
            echo '<option value="' . intval($materia['id']) . '"' . $selected . '>' . htmlspecialchars((string)$materia['nombre']) . '</option>';
        }
        echo '</select></div>';

        echo '<div class="form-group"><label>Profesor:</label><select name="profesor_codigo_profesor" class="form-control" required>';
        echo '<option value="">Seleccione...</option>';
        $grupoProfesorId = intval($grupo['profesor_id'] ?? 0);
        foreach ($profesores as $profesor) {
            $profesorId = intval($profesor['profesor_id'] ?? 0);
            $codigo = (string)($profesor['codigo_profesor'] ?? '');
            $selected = ($grupoProfesorId > 0 && $grupoProfesorId === $profesorId) ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($codigo) . '"' . $selected . '>' . htmlspecialchars($codigo) . '</option>';
        }
        echo '</select></div>';

        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn">Guardar</button>';
        echo '<a href="index.php?action=listar" class="btn btn-danger">Cancelar</a>';
        echo '</div>';
        echo '</form></div>';
    }

    public function delete(int $id): void {
        $idGrupo = $id > 0 ? $id : intval($_GET['id_grupo'] ?? 0);
        if ($idGrupo <= 0) {
            echo '<div class="alert alert-danger">ID de grupo no especificado.</div>';
            return;
        }

        if ($this->nGrupo->delete($idGrupo)) {
            echo "<script>window.location='index.php?action=listar&ntype=success&nmsg=Grupo%20eliminado%20correctamente';</script>";
            exit;
        }

        echo '<div class="alert alert-danger">No se pudo eliminar el grupo.</div>';
    }

    public function getAll(): array {
        $grupos = $this->nGrupo->getAll();
        $materias = $this->nMateria->getAll();
        $profesores = $this->nProfesor->getAll();

        $materiaById = [];
        foreach ($materias as $materia) {
            $materiaById[intval($materia['id'])] = (string)$materia['nombre'];
        }

        $profesorCodigoById = [];
        foreach ($profesores as $profesor) {
            $profesorCodigoById[intval($profesor['profesor_id'] ?? 0)] = (string)($profesor['codigo_profesor'] ?? '');
        }

        echo '<div class="card">';
        echo '<div class="panel-header">';
        echo '<h2 class="panel-title">Gestion de Grupos</h2>';
        echo '<a href="index.php?action=registrar" class="btn">➕ Nuevo Grupo</a>';
        echo '</div>';

        echo '<div class="table-container"><table>';
        echo '<thead><tr><th>ID</th><th>Nombre</th><th>Capacidad</th><th>Materia</th><th>Profesor</th><th class="actions-cell">Acciones</th></tr></thead>';
        echo '<tbody>';
        foreach ($grupos as $grupo) {
            $materiaNombre = $materiaById[intval($grupo['materia_id'] ?? 0)] ?? 'N/D';
            $profCodigo = $profesorCodigoById[intval($grupo['profesor_id'] ?? 0)] ?? '';
            $profCodigo = $profCodigo !== '' ? $profCodigo : 'N/D';

            echo '<tr>';
            echo '<td>' . intval($grupo['id_grupo']) . '</td>';
            echo '<td>' . htmlspecialchars((string)$grupo['nombre']) . '</td>';
            echo '<td>' . intval($grupo['capacidad_actual']) . ' / ' . intval($grupo['capacidad_maxima']) . '</td>';
            echo '<td>' . htmlspecialchars($materiaNombre) . '</td>';
            echo '<td>' . htmlspecialchars($profCodigo) . '</td>';
            echo '<td class="actions-cell">';
            echo '<a class="btn btn-success btn-sm" href="index.php?action=editar&id_grupo=' . intval($grupo['id_grupo']) . '">Editar</a> ';
            echo '<a class="btn btn-danger btn-sm" href="index.php?action=eliminar&id_grupo=' . intval($grupo['id_grupo']) . '" onclick="return confirm(\'¿Eliminar este grupo?\')">Eliminar</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '</div>';

        return $grupos;
    }

    public function getById(int $id): array {
        $grupo = $this->nGrupo->getById($id);
        if (!$grupo) {
            echo '<div class="alert alert-danger">Grupo no encontrado.</div>';
            return [];
        }

        echo '<div class="card card-md">';
        echo '<h2>Detalle del Grupo</h2>';
        echo '<p><strong>ID:</strong> ' . intval($grupo['id_grupo']) . '</p>';
        echo '<p><strong>Nombre:</strong> ' . htmlspecialchars((string)($grupo['nombre'] ?? '')) . '</p>';
        echo '<p><strong>Capacidad:</strong> ' . intval($grupo['capacidad_actual'] ?? 0) . ' / ' . intval($grupo['capacidad_maxima'] ?? 0) . '</p>';
        echo '<p><strong>Materia ID:</strong> ' . intval($grupo['materia_id'] ?? 0) . '</p>';
        echo '<p><strong>Profesor ID:</strong> ' . intval($grupo['profesor_id'] ?? 0) . '</p>';
        echo '<a href="index.php?action=listar" class="btn">Volver</a>';
        echo '</div>';

        return $grupo;
    }
}
?>