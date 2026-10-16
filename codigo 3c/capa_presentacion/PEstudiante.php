<?php
require_once __DIR__ . '/../capa_negocio/NEstudiante.php';

class PEstudiante {
    private $codigo_estudiante;
    private $ci;
    private $estado;
    private $id;
    private $genero;
    private $nEstudiante;

    public function __construct() {
        $this->nEstudiante = new NEstudiante();
    }

    // CU2 Administrar Estudiante
    public function create(array $data): void {
        if (empty($data)) {
            $this->Registrar();
            return;
        }

        if (!$this->nEstudiante->create($data)) {
            echo '<div class="alert alert-danger">No se pudo registrar el estudiante.</div>';
        }
    }
    public function update(int $id, array $data): void {
        if (empty($data)) {
            $_GET['id'] = $id;
            $this->Modificar();
            return;
        }

        if ($id <= 0 || !$this->nEstudiante->update($id, $data)) {
            echo '<div class="alert alert-danger">No se pudo actualizar el estudiante.</div>';
        }
    }
    public function delete(int $id): void {
        if ($id <= 0) {
            $this->Eliminar();
            return;
        }

        // Log temporal para depuración en la capa de presentación
        echo '<div style="color:blue;font-weight:bold;">Intentando eliminar estudiante con usuario_id = ' . htmlspecialchars($id) . '</div>';

        if ($id <= 0 || !$this->nEstudiante->delete($id)) {
            echo '<div class="alert alert-danger">No se pudo eliminar el estudiante.</div>';
            return;
        }

        echo "<script>window.location='index.php?action=estudiantes&ntype=success&nmsg=Estudiante%20eliminado%20correctamente';</script>";
        exit;
    }
    public function getAll(): void {
        $this->ListarTabla();
    }
    public function getById(int $id): void {
        $est = $this->nEstudiante->getById($id);
        if (!$est) {
            echo '<div class="alert alert-danger">Estudiante no encontrado.</div>';
            return;
        }

        echo '<div class="card card-sm">';
        echo '<h2>Detalle de Estudiante</h2>';
        echo '<p><strong>Codigo:</strong> ' . htmlspecialchars($est['codigo_estudiante']) . '</p>';
        echo '<p><strong>CI:</strong> ' . htmlspecialchars($est['ci']) . '</p>';
        echo '<p><strong>Estado:</strong> ' . htmlspecialchars($est['estado']) . '</p>';
        echo '<a href="index.php?action=estudiantes" class="btn">Volver</a>';
        echo '</div>';
    }
    public function getByCi(string $ci): void {
        $est = $this->nEstudiante->getByCi($ci);
        if (empty($est)) {
            echo '<div class="alert alert-danger">No existe estudiante con ese CI.</div>';
            return;
        }

        $this->getById((int)$est['usuario_id']);
    }
    /**
     * Obtiene los usuarios libres para asignar como estudiantes.
     */
    public function obtenerUsuariosLibres(): array {
        return $this->nEstudiante->obtenerUsuariosLibres();
    }

    // ListarTabla(): void
    private function ListarTabla(): void {
        $stmt = $this->nEstudiante->getAll();
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<div class="card">';
        echo '<div class="panel-header">';
        echo '<h2 class="panel-title">Gestion de Estudiantes</h2>';
        echo '<a href="index.php?action=registrar_estudiante" class="btn">➕ Nuevo Estudiante</a>';
        echo '</div>';
        
        echo '<div class="table-container"><table>';
        echo '<thead><tr><th>Codigo</th><th>CI</th><th>Estado</th><th class="actions-cell">Acciones</th></tr></thead>';
        echo '<tbody>';
        foreach ($estudiantes as $est) {
            echo "<tr>
                <td><strong>{$est['codigo_estudiante']}</strong></td>
                <td>{$est['ci']}</td>
                <td><span class='status-pill'>{$est['estado']}</span></td>
                <td class='actions-cell'>
                    <a href='index.php?action=editar_estudiante&id={$est['usuario_id']}' class='btn btn-success btn-sm'>Editar</a>
                    <a href='index.php?action=eliminar_estudiante&id={$est['usuario_id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Eliminar?\")'>Borrar</a>
                </td>
            </tr>";
        }
        echo '</tbody></table></div></div>';
    }

    // Registrar(): void
    private function Registrar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'usuario_id' => $_POST['usuario_id'],
                'ci' => $_POST['ci'],
                'codigo_estudiante' => $_POST['codigo'],
                'estado' => $_POST['estado']
            ];

            if ($this->nEstudiante->create($datos)) {
                echo "<script>window.location='index.php?action=estudiantes&ntype=success&nmsg=Estudiante%20registrado%20correctamente';</script>";
                exit;
            }

            echo '<div class="alert alert-danger">Error al registrar estudiante.</div>';
        }
        $usuarios = $this->nEstudiante->obtenerUsuariosLibres();
        echo '<div class="card card-sm">';
        echo '<h2>Registrar Estudiante</h2><form method="POST">';
        echo '<div class="form-group"><label>Seleccionar Usuario Base:</label><select name="usuario_id" class="form-control">';
        foreach ($usuarios as $u) echo "<option value='{$u['id']}'>{$u['nombre_usuario']} ({$u['nombres']})</option>";
        echo '</select></div>';
        echo '<div class="form-group"><label>Cédula de Identidad (CI):</label><input type="text" name="ci" placeholder="Ej: 1234567" class="form-control"></div>';
        echo '<div class="form-group"><label>Código de Estudiante:</label><input type="text" name="codigo" placeholder="Ej: EST-001" class="form-control"></div>';
        echo '<div class="form-group"><label>Estado:</label><select name="estado" class="form-control"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>';
        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn">Guardar Registro</button>';
        echo '<a href="index.php?action=estudiantes" class="btn btn-danger">Cancelar</a>';
        echo '</div></form></div>';
    }

    // Modificar(): void
    private function Modificar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['usuario_id'];
            $datos = [
                'ci' => $_POST['ci'],
                'codigo_estudiante' => $_POST['codigo'],
                'estado' => $_POST['estado']
            ];

            if ($this->nEstudiante->update((int)$id, $datos)) {
                echo "<script>window.location='index.php?action=estudiantes&ntype=success&nmsg=Estudiante%20actualizado%20correctamente';</script>";
                exit;
            }

            echo '<div class="alert alert-danger">Error al actualizar estudiante.</div>';
        }

        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            echo '<div class="card card-sm">';
            echo '<h2>Editar Estudiante</h2><form method="POST">';
            echo "<input type='hidden' name='usuario_id' value='{$id}'>";
            echo '<div class="form-group"><label>Cédula de Identidad (CI):</label><input type="text" name="ci" placeholder="CI" class="form-control"></div>';
            echo '<div class="form-group"><label>Código de Estudiante:</label><input type="text" name="codigo" placeholder="Código Estudiante" class="form-control"></div>';
            echo '<div class="form-group"><label>Estado:</label><select name="estado" class="form-control"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>';
            echo '<div class="form-actions">';
            echo '<button type="submit" class="btn">Actualizar</button>';
            echo '<a href="index.php?action=estudiantes" class="btn btn-danger">Cancelar</a>';
            echo '</div></form></div>';
        }
    }

    // Eliminar(): void
    private function Eliminar(): void {
        if (isset($_GET['id'])) {
            if ($this->nEstudiante->delete((int)$_GET['id'])) {
                echo "<script>window.location='index.php?action=estudiantes&ntype=success&nmsg=Estudiante%20eliminado%20correctamente';</script>";
                exit;
            }

            echo '<div class="alert alert-danger">Error al eliminar estudiante.</div>';
        }
    }

}
?>