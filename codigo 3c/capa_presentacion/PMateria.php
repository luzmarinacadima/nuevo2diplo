<?php
require_once __DIR__ . '/../capa_negocio/NMateria.php';

class PMateria {
    private $id;
    private $nombre;
    private $nMateria;

    public function __construct() {
        $this->nMateria = new NMateria();
    }

    // API del diagrama (CU4)
    public function create(array $data): void {
        if (empty($data)) {
            $this->Registrar();
            return;
        }

        if (!$this->nMateria->create($data)) {
            echo '<div class="alert alert-danger">No se pudo registrar la materia.</div>';
        }
    }

    public function update(int $id, array $data): void {
        if (empty($data)) {
            $_GET['id'] = $id;
            $this->Modificar();
            return;
        }

        if ($id <= 0 || !$this->nMateria->update($id, $data)) {
            echo '<div class="alert alert-danger">No se pudo actualizar la materia.</div>';
        }
    }

    public function delete(int $id): void {
        if ($id <= 0) {
            $this->Eliminar();
            return;
        }

        if ($id <= 0 || !$this->nMateria->delete($id)) {
            // Mensaje mejorado: explicar posible causa
            echo '<div class="alert alert-danger">No se pudo eliminar la materia. Asegúrese de que no tenga grupos asociados.</div>';
            return;
        }

        header('Location: ?action=materias&ntype=success&nmsg=Materia%20eliminada%20correctamente');
        exit;
    }

    public function getAll(): void {
        $this->ListarTabla();
    }

    public function getById(int $id): void {
        $materia = $this->nMateria->getById($id);
        if (!$materia) {
            echo '<div class="alert alert-danger">Materia no encontrada.</div>';
            return;
        }

        echo '<div class="card card-sm">';
        echo '<h2>Detalle de Materia</h2>';
        echo '<p><strong>ID:</strong> ' . intval($materia['id']) . '</p>';
        echo '<p><strong>Nombre:</strong> ' . htmlspecialchars($materia['nombre']) . '</p>';
        echo '<a href="index.php?action=materias" class="btn">Volver</a>';
        echo '</div>';
    }

    public function checkReferences(int $id): void {
        $ok = $this->nMateria->checkReferences($id);
        $tipo = $ok ? 'success' : 'danger';
        $msg = $ok
            ? 'La materia no tiene referencias y puede eliminarse.'
            : 'La materia tiene referencias activas o no existe.';
        echo '<div class="alert alert-' . $tipo . '">' . $msg . '</div>';
    }
//fin cu4 more private methods 
    private function ListarTabla() { //1 CREAR
        $errorMsg = '';

        // --- PROCESAMIENTO DE ACCIONES ---
        // Crear
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre']) && !isset($_POST['edit_id'])) {
            $nombre = trim($_POST['nombre']);
            if ($nombre !== '') { //meth 3 create
                if ($this->nMateria->create(['nombre' => $nombre])) {
                    header('Location: ?action=materias&ntype=success&nmsg=Materia%20creada%20correctamente');
                    exit;
                }

                $errorMsg = 'No se pudo crear la materia. Verifique que el nombre no este repetido.';
            } else {
                $errorMsg = 'El nombre de la materia es obligatorio.';
            }
        }
        // Editar
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
            $id = intval($_POST['edit_id']);
            $nombre = trim($_POST['edit_nombre']);
            if ($nombre !== '') {
                if ($this->nMateria->update($id, ['nombre' => $nombre])) {
                    header('Location: ?action=materias&ntype=success&nmsg=Materia%20actualizada%20correctamente');
                    exit;
                }

                $errorMsg = 'No se pudo actualizar la materia. Verifique que exista y que el nombre no este repetido.';
            } else {
                $errorMsg = 'El nombre de la materia es obligatorio.';
            }
        }
        // Eliminar
        if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($this->nMateria->delete($id)) {
                header('Location: ?action=materias&ntype=success&nmsg=Materia%20eliminada%20correctamente');
                exit;
            }

            $errorMsg = 'No se pudo eliminar la materia. Es posible que tenga grupos asociados.';
        }
        $materias = $this->nMateria->getAll();
        $edit_id = isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id']) ? intval($_GET['id']) : null;

        echo '<div class="card">';
        echo '<div class="panel-header">';
        echo '<h2 class="panel-title">Gestion de Materias</h2>'; //botn VOLVER  A GRUPOS
        echo '<a href="?action=listar" class="btn btn-outline btn-sm">Volver a Grupos</a>';
        echo '</div>';

        if ($errorMsg !== '') {
            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMsg) . '</div>';
        }
        //boton 2 Crear
        echo '<div class="card card-soft compact-card">';
        echo '<div class="panel-subtitle">Registrar nueva materia</div>';
        echo '<form method="POST" class="inline-form">';
        echo '<input class="form-control" type="text" name="nombre" placeholder="Nombre de la materia" required />';
        echo '<button type="submit" class="btn">Crear</button>';
        echo '</form>';
        echo '</div>';

        echo '<div class="table-container"><table>';
        echo '<thead><tr><th>ID</th><th>Nombre</th><th class="actions-cell">Acciones</th></tr></thead>';
        echo '<tbody>';
        foreach ($materias as $materia) {
            $idMateria = intval($materia['id']);
            echo '<tr>';
            echo '<td>' . $idMateria . '</td>';

            if ($edit_id === $idMateria) {
                $formId = 'edit_materia_' . $idMateria;
                echo '<td>';
                echo '<form id="' . $formId . '" method="POST">';
                echo '<input type="hidden" name="edit_id" value="' . $idMateria . '" />';
                echo '<input class="form-control" type="text" name="edit_nombre" value="' . htmlspecialchars($materia['nombre']) . '" required />';
                echo '</form>';
                echo '</td>';
                echo '<td class="actions-cell">';
                echo '<button type="submit" form="' . $formId . '" class="btn btn-success btn-sm">Guardar</button> ';
                echo '<a href="?action=materias" class="btn btn-outline btn-sm">Cancelar</a>';
                echo '</td>';
            } else {
                echo '<td>' . htmlspecialchars($materia['nombre']) . '</td>';
                echo '<td class="actions-cell">'; //boton EDITAR, ELIMINAR
                echo '<a href="?action=editar&id=' . $idMateria . '&from=materias" class="btn btn-success btn-sm">Editar</a> ';
                echo '<a href="?action=eliminar&id=' . $idMateria . '&from=materias" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Seguro que deseas eliminar esta materia?\');">Eliminar</a>';
                echo '</td>';
            }

            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '</div>';
    }

    private function Registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
            if ($this->nMateria->create(['nombre' => $nombre])) {
                echo '<p>Materia registrada exitosamente</p>';
            } else {
                echo '<p>Error al registrar la materia</p>';
            }
        }
        
        echo '<form method="POST">';
        echo '<label>Nombre de la Materia:</label>';
        echo '<input type="text" name="nombre" required>';
        echo '<button type="submit">Registrar</button>';
        echo '</form>';
    }

    private function Modificar() {
        if (!isset($_GET['id'])) {
            echo '<p>ID no especificado</p>';
            return;
        }
        $id = intval($_GET['id']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
            if ($this->nMateria->update($id, ['nombre' => $nombre])) {
                header('Location: ?action=materias&ntype=success&nmsg=Materia%20actualizada%20correctamente');
                exit;
            } else {
                echo '<p>Error al actualizar la materia</p>';
            }
        }
    }

    private function Eliminar() {
        if (!isset($_GET['id'])) {
            echo '<p>ID no especificado</p>';
            return;
        }
        
        $id = intval($_GET['id']);
        
        if ($this->nMateria->delete($id)) {
            echo '<p>Materia eliminada exitosamente</p>';
        } else {
            echo '<p>Error al eliminar la materia o la materia tiene referencias</p>';
        }
    }
}
