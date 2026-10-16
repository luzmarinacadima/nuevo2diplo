<?php
require_once __DIR__ . '/../capa_negocio/NProfesor.php';
require_once __DIR__ . '/../capa_negocio/NUsuario.php';

class PProfesor {
    // Atributos según diagrama
    private $codigo_profesor;
    private $id;
    private $nProfesor;

    public function __construct() {
        $this->nProfesor = new NProfesor();
    }

    // API del diagrama (CU3)
    public function create(array $data): void {
        if (empty($data)) {
            $this->ListarTabla();
            return;
        }

        if (!$this->nProfesor->create($data)) {
            echo '<div class="alert alert-danger">No se pudo registrar el profesor.</div>';
        }
    }

    public function update(int $id, array $data): void {
        if (empty($data)) {
            $_GET['edit_id'] = $id;
            $this->ListarTabla();
            return;
        }

        if ($id <= 0 || !$this->nProfesor->update($id, $data)) {
            echo '<div class="alert alert-danger">No se pudo actualizar el profesor.</div>';
        }
    }

    public function delete(int $id): void {
        if ($id <= 0) {
            $this->ListarTabla();
            return;
        }

        if ($id <= 0 || !$this->nProfesor->delete($id)) {
            echo '<div class="alert alert-danger">No se pudo eliminar el profesor.</div>';
            return;
        }

        header('Location: ?action=profesores');
        exit;
    }

    public function getAll(): void {
        $this->ListarTabla();
    }

    public function getById(int $id): void {
        $profesor = $this->nProfesor->getById($id);
        if (!$profesor) {
            echo '<div class="alert alert-danger">Profesor no encontrado.</div>';
            return;
        }

        echo '<div class="card card-sm">';
        echo '<h2>Detalle de Profesor</h2>';
        echo '<p><strong>Usuario ID:</strong> ' . intval($profesor['usuario_id']) . '</p>';
        echo '<p><strong>Codigo:</strong> ' . htmlspecialchars($profesor['codigo_profesor']) . '</p>';
        echo '<a href="index.php?action=profesores" class="btn">Volver</a>';
        echo '</div>';
    }

    public function checkReferences(int $id): void {
        $ok = $this->nProfesor->checkReferences($id);
        $tipo = $ok ? 'success' : 'danger';
        $msg = $ok ? 'Profesor encontrado para operar.' : 'No se encontro referencia del profesor.';
        echo '<div class="alert alert-' . $tipo . '">' . $msg . '</div>';
    }

    // Métodos según diagrama
    private function ListarTabla() {
        $errorMsg = '';

        $nUsuario = new NUsuario();
        $usuarios = $nUsuario->getAll()->fetchAll(PDO::FETCH_ASSOC);
        $usuariosById = [];
        foreach ($usuarios as $u) {
            $usuariosById[intval($u['id'])] = $u;
        }

        $profesores = $this->nProfesor->getAll();
        $profesorUsuarioIds = [];
        foreach ($profesores as $profesor) {
            $profesorUsuarioIds[intval($profesor['usuario_id'])] = true;
        }

        // Procesar edición
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profesor']) && isset($_POST['usuario_id'])) {
            $usuario_id = intval($_POST['usuario_id']);
            $codigo = trim((string)($_POST['codigo'] ?? ''));
            $nombres = trim((string)($_POST['nombres'] ?? ''));
            $apellidos = trim((string)($_POST['apellidos'] ?? ''));
            $genero = trim((string)($_POST['genero'] ?? ''));

            if ($usuario_id > 0 && $codigo !== '' && $nombres !== '' && $apellidos !== '' && $genero !== '') {
                $usuarioBase = $usuariosById[$usuario_id] ?? null;
                if ($usuarioBase !== null) {
                    $okUsuario = $nUsuario->update($usuario_id, [
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'genero' => $genero,
                        'nombre_usuario' => $usuarioBase['nombre_usuario'],
                        'contrasena' => $usuarioBase['contrasena'],
                        'tipo_usuario' => $usuarioBase['tipo_usuario']
                    ]);

                    $okProfesor = $this->nProfesor->update($usuario_id, [
                        'usuario_id' => $usuario_id,
                        'codigo_profesor' => $codigo
                    ]);

                    if ($okUsuario && $okProfesor) {
                        header('Location: ?action=profesores');
                        exit;
                    }

                    $errorMsg = 'No se pudo actualizar el profesor.';
                } else {
                    $errorMsg = 'El usuario seleccionado no existe.';
                }
            } else {
                $errorMsg = 'Complete todos los campos para editar el profesor.';
            }
        }

        // Procesar alta
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'], $_POST['nombres'], $_POST['apellidos'], $_POST['genero'], $_POST['usuario_id']) && !isset($_POST['edit_profesor'])) {
            $codigo = trim((string)$_POST['codigo']);
            $nombres = trim((string)$_POST['nombres']);
            $apellidos = trim((string)$_POST['apellidos']);
            $genero = trim((string)$_POST['genero']);
            $usuario_id = intval($_POST['usuario_id']);

            $usuarioBase = $usuariosById[$usuario_id] ?? null;
            $usuarioValido = $usuarioBase !== null
                && (string)($usuarioBase['tipo_usuario'] ?? '') === 'profesor'
                && !isset($profesorUsuarioIds[$usuario_id]);

            if ($codigo !== '' && $nombres !== '' && $apellidos !== '' && $genero !== '' && $usuarioValido) {
                $okUsuario = $nUsuario->update($usuario_id, [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'genero' => $genero,
                    'nombre_usuario' => $usuarioBase['nombre_usuario'],
                    'contrasena' => $usuarioBase['contrasena'],
                    'tipo_usuario' => $usuarioBase['tipo_usuario']
                ]);

                $okProfesor = $this->nProfesor->create([
                    'usuario_id' => $usuario_id,
                    'codigo_profesor' => $codigo
                ]);

                if ($okUsuario && $okProfesor) {
                    header('Location: ?action=profesores');
                    exit;
                }

                $errorMsg = 'No se pudo crear el profesor. Revise codigo y usuario.';
            } else {
                $errorMsg = 'Seleccione un usuario profesor disponible y complete los campos.';
            }
        }

        // Procesar eliminación
        if (isset($_GET['delete_id'])) {
            if ($this->nProfesor->delete((int)$_GET['delete_id'])) {
                header('Location: ?action=profesores');
                exit;
            }
            $errorMsg = 'No se pudo eliminar el profesor.';
        }

        // Recargar datos para vista luego de procesar acciones
        $usuarios = $nUsuario->getAll()->fetchAll(PDO::FETCH_ASSOC);
        $usuariosById = [];
        foreach ($usuarios as $u) {
            $usuariosById[intval($u['id'])] = $u;
        }

        $profesores = $this->nProfesor->getAll();
        $profesorUsuarioIds = [];
        foreach ($profesores as $profesor) {
            $profesorUsuarioIds[intval($profesor['usuario_id'])] = true;
        }

        // Detectar edición
        $edit_profesor = null;
        if (isset($_GET['edit_id'])) {
            foreach ($profesores as $profesor) {
                if (intval($profesor['usuario_id']) === intval($_GET['edit_id'])) {
                    $edit_profesor = $profesor;
                    break;
                }
            }
        }

        $usuariosDisponibles = [];
        foreach ($usuarios as $u) {
            $uid = intval($u['id']);
            $esProfesor = (string)($u['tipo_usuario'] ?? '') === 'profesor';
            $yaAsignado = isset($profesorUsuarioIds[$uid]);

            if ($esProfesor && (!$yaAsignado || ($edit_profesor && intval($edit_profesor['usuario_id']) === $uid))) {
                $usuariosDisponibles[] = $u;
            }
        }

        echo '<div class="card">';
        echo '<div class="panel-header">';
        echo '<h2 class="panel-title">Gestion de Profesores</h2>';
        echo '<a href="?action=listar" class="btn btn-outline btn-sm">Volver a Grupos</a>';
        echo '</div>';

        if ($errorMsg !== '') {
            echo '<div class="alert alert-danger">' . htmlspecialchars($errorMsg) . '</div>';
        }

        echo '<div class="card card-soft compact-card">';
        if ($edit_profesor) {
            $usuario_edit = $usuariosById[intval($edit_profesor['usuario_id'])] ?? null;

            echo '<div class="panel-subtitle">Editar Profesor</div>';
            echo '<form method="POST" class="inline-form">';
            echo '<input type="hidden" name="usuario_id" value="' . htmlspecialchars($edit_profesor['usuario_id']) . '" />';
            echo '<input class="form-control" type="text" name="codigo" placeholder="Codigo" value="' . htmlspecialchars($edit_profesor['codigo_profesor']) . '" required />';
            echo '<input class="form-control" type="text" name="nombres" placeholder="Nombres" value="' . ($usuario_edit ? htmlspecialchars($usuario_edit['nombres']) : '') . '" required />';
            echo '<input class="form-control" type="text" name="apellidos" placeholder="Apellidos" value="' . ($usuario_edit ? htmlspecialchars($usuario_edit['apellidos']) : '') . '" required />';
            echo '<select class="form-control" name="genero" required>';
            $genero_actual = $usuario_edit ? $usuario_edit['genero'] : '';
            echo '<option value="">Seleccione genero</option>';
            echo '<option value="Masculino"' . ($genero_actual == 'Masculino' ? ' selected' : '') . '>Masculino</option>';
            echo '<option value="Femenino"' . ($genero_actual == 'Femenino' ? ' selected' : '') . '>Femenino</option>';
            echo '</select>';
            echo '<button class="btn" type="submit" name="edit_profesor" value="1">Actualizar</button>';
            echo '<a href="?action=profesores" class="btn btn-outline btn-sm">Cancelar</a>';
            echo '</form>';
        } else {
            echo '<div class="panel-subtitle">Agregar Nuevo Profesor</div>';
            echo '<form method="POST" class="inline-form">';
            echo '<input class="form-control" type="text" name="codigo" placeholder="Codigo" required />';
            echo '<input class="form-control" type="text" name="nombres" placeholder="Nombres" required />';
            echo '<input class="form-control" type="text" name="apellidos" placeholder="Apellidos" required />';
            echo '<select class="form-control" name="genero" required>';
            echo '<option value="">Seleccione genero</option>';
            echo '<option value="Masculino">Masculino</option>';
            echo '<option value="Femenino">Femenino</option>';
            echo '</select>';
            echo '<select class="form-control" name="usuario_id" required>';
            echo '<option value="">Seleccione usuario</option>';
            foreach ($usuariosDisponibles as $u) {
                echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['nombre_usuario']) . ' (' . htmlspecialchars($u['nombres']) . ')</option>';
            }
            echo '</select>';
            echo '<button class="btn" type="submit">Crear</button>';
            echo '</form>';
        }
        echo '</div>';

        echo '<div class="table-container"><table>';
        echo '<thead><tr><th>Codigo</th><th>Nombres</th><th>Apellidos</th><th>Genero</th><th>Usuario</th><th class="actions-cell">Acciones</th></tr></thead>';
        echo '<tbody>';
        foreach ($profesores as $profesor) {
            $usuario = $usuariosById[intval($profesor['usuario_id'])] ?? null;
            echo '<tr>';
            echo '<td>' . htmlspecialchars($profesor['codigo_profesor']) . '</td>';
            echo '<td>' . ($usuario ? htmlspecialchars($usuario['nombres']) : '-') . '</td>';
            echo '<td>' . ($usuario ? htmlspecialchars($usuario['apellidos']) : '-') . '</td>';
            echo '<td>' . ($usuario ? htmlspecialchars($usuario['genero']) : '-') . '</td>';
            echo '<td>' . ($usuario ? htmlspecialchars($usuario['nombre_usuario']) : '-') . '</td>';
            echo '<td class="actions-cell">';
            echo '<a href="?action=profesores&edit_id=' . $profesor['usuario_id'] . '" class="btn btn-success btn-sm">Editar</a> ';
            echo '<a href="?action=profesores&delete_id=' . $profesor['usuario_id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Seguro que deseas eliminar este profesor?\');">Eliminar</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '</div>';
    }

}
