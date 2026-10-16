<?php
require_once __DIR__ . '/../capa_negocio/NUsuario.php';

class PUsuario {
    // Atributos según diagrama procedimental
    private $nombres;
    private $apellidos;
    private $genero;
    private $nombre_usuario;
    private $contrasena;
    private $tipo_usuario;
    private $id;
    private $nUsuario;

    public function __construct() {
        $this->nUsuario = new NUsuario();
    }

    // CU1 Administrar Usuarios
    // Crea un usuario (CU1)
    public function create(array $data): void {
        if (empty($data)) {
            $this->Registrar();
            return;
        }

        if (!$this->nUsuario->create($data)) {
            echo '<div class="alert alert-danger">No se pudo registrar el usuario.</div>';
        }
    }

    // Actualiza un usuario (CU1)
    public function update(int $id, array $data): void {
        if (empty($data)) {
            $_GET['id'] = $id;
            $this->Modificar();
            return;
        }

        if ($id <= 0 || !$this->nUsuario->update($id, $data)) {
            echo '<div class="alert alert-danger">No se pudo actualizar el usuario.</div>';
        }
    }

    // Elimina un usuario (CU1)
    public function delete(int $id): void {
        if ($id <= 0) {
            $this->Eliminar();
            return;
        }

        if (!$this->nUsuario->checkReferences($id) || !$this->nUsuario->delete($id)) {
            echo '<div class="alert alert-danger">No se pudo eliminar el usuario.</div>';
            return;
        }

        echo "<script>window.location='index.php?action=usuarios&ntype=success&nmsg=Usuario%20eliminado%20correctamente';</script>";
        exit;
    }

    // Muestra todos los usuarios (CU1)
    public function getAll(): void {
        $this->ListarTabla();
    }

    // Muestra detalle de usuario por ID (CU1)
    public function getById(int $id): void {
        $usuario = $this->nUsuario->getById($id);
        if (!$usuario) {
            echo '<div class="alert alert-danger">Usuario no encontrado.</div>';
            return;
        }

        echo '<div class="card card-sm">';
        echo '<h2>Detalle de Usuario</h2>';
        echo '<p><strong>ID:</strong> ' . intval($usuario['id']) . '</p>';
        echo '<p><strong>Nombres:</strong> ' . htmlspecialchars($usuario['nombres']) . '</p>';
        echo '<p><strong>Apellidos:</strong> ' . htmlspecialchars($usuario['apellidos']) . '</p>';
        echo '<p><strong>Usuario:</strong> ' . htmlspecialchars($usuario['nombre_usuario']) . '</p>';
        echo '<p><strong>Tipo:</strong> ' . htmlspecialchars($usuario['tipo_usuario']) . '</p>';
        echo '<a href="index.php?action=usuarios" class="btn">Volver</a>';
        echo '</div>';
    }

    // Realiza login de usuario (CU1)
    public function login(string $username, string $password): void {
        if ($username === '' && $password === '') {
            $this->onProcesoLogin();
            return;
        }

        if (empty($this->nUsuario->login($username, $password))) {
            echo '<div class="alert alert-danger">Credenciales incorrectas.</div>';
            return;
        }

        $_SESSION['usuario'] = $username;
        $_SESSION['logueado'] = true;
    }

    // Verifica si un usuario puede eliminarse (CU1)
    public function checkReferences(int $id): void {
        $ok = $this->nUsuario->checkReferences($id);
        $tipo = $ok ? 'success' : 'danger';
        $msg = $ok
            ? 'El usuario no tiene referencias y puede eliminarse.'
            : 'El usuario tiene referencias o no existe.';
        echo '<div class="alert alert-' . $tipo . '">' . $msg . '</div>';
    }

    // fin cu1

    /** metodos internos
     * Muestra la tabla de todos los usuarios
     */
    private function ListarTabla() {
        $usuarios = $this->nUsuario->getAll();

        echo '<div class="card">';
        echo '<div class="panel-header">';
        echo '<h2 class="panel-title">Gestion de Usuarios</h2>';
        echo '<a href="index.php?action=registrar_usuario" class="btn">➕ Nuevo Usuario</a>';
        echo '</div>';

        echo '<div class="table-container"><table>';
        echo '<thead><tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Usuario</th><th>Tipo</th><th class="actions-cell">Acciones</th></tr></thead>';
        echo '<tbody>';
        foreach ($usuarios as $usuario) {
            echo '<tr>';
            echo '<td>' . $usuario['id'] . '</td>';
            echo '<td>' . htmlspecialchars($usuario['nombres']) . '</td>';
            echo '<td>' . htmlspecialchars($usuario['apellidos']) . '</td>';
            echo '<td>' . htmlspecialchars($usuario['nombre_usuario']) . '</td>';
            echo '<td>' . htmlspecialchars($usuario['tipo_usuario']) . '</td>';
            echo '<td class="actions-cell">';
            echo '<a class="btn btn-success btn-sm" href="index.php?action=editar_usuario&id=' . $usuario['id'] . '">Editar</a> ';
            echo '<a class="btn btn-danger btn-sm" href="index.php?action=eliminar_usuario&id=' . $usuario['id'] . '" onclick="return confirm(\'¿Eliminar este usuario?\')">Eliminar</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '</div>';
    }

    /**
     * Muestra formulario y registra un nuevo usuario
     */
    private function Registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombres' => isset($_POST['nombres']) ? $_POST['nombres'] : '',
                'apellidos' => isset($_POST['apellidos']) ? $_POST['apellidos'] : '',
                'genero' => isset($_POST['genero']) ? $_POST['genero'] : '',
                'nombre_usuario' => isset($_POST['nombre_usuario']) ? $_POST['nombre_usuario'] : '',
                'contrasena' => isset($_POST['contrasena']) ? $_POST['contrasena'] : '',
                'tipo_usuario' => isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : ''
            ];
            // Asegurarse de que no se envíe el campo id
            if (isset($datos['id'])) {
                unset($datos['id']);
            }
            if ($this->nUsuario->create($datos)) {
                echo "<script>window.location='index.php?action=usuarios&ntype=success&nmsg=Usuario%20registrado%20correctamente';</script>";
                exit;
            } else {
                echo '<div class="alert alert-danger">Error al registrar el usuario.</div>';
            }
        }

        echo '<div class="card card-sm">';
        echo '<h2>Registrar Nuevo Usuario</h2>';
        echo '<form method="POST">';
        echo '<div class="form-group"><label>Nombres:</label><input class="form-control" type="text" name="nombres" required></div>';
        echo '<div class="form-group"><label>Apellidos:</label><input class="form-control" type="text" name="apellidos" required></div>';
        echo '<div class="form-group"><label>Género:</label><input class="form-control" type="text" name="genero"></div>';
        echo '<div class="form-group"><label>Nombre de Usuario:</label><input class="form-control" type="text" name="nombre_usuario" required></div>';
        echo '<div class="form-group"><label>Contraseña:</label><input class="form-control" type="password" name="contrasena" required></div>';
        echo '<div class="form-group"><label>Tipo de Usuario:</label>';
        echo '<select class="form-control" name="tipo_usuario" required>';
        echo '<option value="profesor">Profesor</option>';
        echo '<option value="estudiante">Estudiante</option>';
        echo '<option value="admin">Admin</option>';
        echo '</select></div>';
        echo '<div class="form-actions">';
        echo '<button class="btn" type="submit">Registrar</button>';
        echo '<a class="btn btn-danger" href="index.php?action=usuarios">Cancelar</a>';
        echo '</div>';
        echo '</form></div>';
    }

    /**
     * Muestra formulario y modifica un usuario existente
     */
    private function Modificar() {
        if (!isset($_GET['id'])) {
            echo '<p>ID no especificado</p>';
            return;
        }
        
        $id = intval($_GET['id']);
        $usuarioActual = $this->nUsuario->getById($id);
        if (!$usuarioActual) {
            echo '<div class="alert alert-danger">Usuario no encontrado</div>';
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'nombres' => isset($_POST['nombres']) ? $_POST['nombres'] : '',
                'apellidos' => isset($_POST['apellidos']) ? $_POST['apellidos'] : '',
                'genero' => isset($_POST['genero']) ? $_POST['genero'] : '',
                'nombre_usuario' => isset($_POST['nombre_usuario']) ? $_POST['nombre_usuario'] : '',
                'contrasena' => isset($_POST['contrasena']) ? $_POST['contrasena'] : '',
                'tipo_usuario' => isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : ''
            ];

            if ($this->nUsuario->update($id, $datos)) {
                echo "<script>window.location='index.php?action=usuarios&ntype=success&nmsg=Usuario%20actualizado%20correctamente';</script>";
                exit;
            } else {
                echo '<div class="alert alert-danger">Error al actualizar el usuario.</div>';
            }
        }

        echo '<div class="card card-sm">';
        echo '<h2>Modificar Usuario (ID: ' . $id . ')</h2>';
        echo '<form method="POST">';
        echo '<div class="form-group"><label>Nombres:</label><input class="form-control" type="text" name="nombres" value="' . htmlspecialchars($usuarioActual['nombres']) . '" required></div>';
        echo '<div class="form-group"><label>Apellidos:</label><input class="form-control" type="text" name="apellidos" value="' . htmlspecialchars($usuarioActual['apellidos']) . '" required></div>';
        echo '<div class="form-group"><label>Género:</label><input class="form-control" type="text" name="genero" value="' . htmlspecialchars($usuarioActual['genero']) . '"></div>';
        echo '<div class="form-group"><label>Nombre de Usuario:</label><input class="form-control" type="text" name="nombre_usuario" value="' . htmlspecialchars($usuarioActual['nombre_usuario']) . '" required></div>';
        echo '<div class="form-group"><label>Contraseña:</label><input class="form-control" type="password" name="contrasena" value="' . htmlspecialchars($usuarioActual['contrasena']) . '" required></div>';
        echo '<div class="form-group"><label>Tipo de Usuario:</label>';
        echo '<select class="form-control" name="tipo_usuario" required>';
        echo '<option value="profesor"' . ($usuarioActual['tipo_usuario'] === 'profesor' ? ' selected' : '') . '>Profesor</option>';
        echo '<option value="estudiante"' . ($usuarioActual['tipo_usuario'] === 'estudiante' ? ' selected' : '') . '>Estudiante</option>';
        echo '<option value="admin"' . ($usuarioActual['tipo_usuario'] === 'admin' ? ' selected' : '') . '>Admin</option>';
        echo '</select></div>';
        echo '<div class="form-actions">';
        echo '<button class="btn" type="submit">Actualizar</button>';
        echo '<a class="btn btn-danger" href="index.php?action=usuarios">Cancelar</a>';
        echo '</div>';
        echo '</form></div>';
    }

    /**
     * Elimina un usuario
     */
    private function Eliminar() {
        if (!isset($_GET['id'])) {
            echo '<p>ID no especificado</p>';
            return;
        }
        
        $id = intval($_GET['id']);
        
        if ($this->nUsuario->checkReferences($id)) {
            if ($this->nUsuario->delete($id)) {
                echo "<script>window.location='index.php?action=usuarios&ntype=success&nmsg=Usuario%20eliminado%20correctamente';</script>";
                exit;
            }
            echo '<div class="alert alert-danger">Error al eliminar el usuario.</div>';
        } else {
            echo '<div class="alert alert-danger">El usuario tiene referencias y no puede ser eliminado.</div>';
        }
        echo '<div class="form-actions"><a class="btn btn-outline" href="index.php?action=usuarios">Volver</a></div>';
    }

    /**
     * Procesa el login del usuario
     */
    private function onProcesoLogin() {
        $mensaje = '';

        $notifyMsg = trim((string)($_GET['nmsg'] ?? ''));
        $notifyType = strtolower(trim((string)($_GET['ntype'] ?? 'info')));
        if (!in_array($notifyType, ['success', 'danger', 'warning', 'info'], true)) {
            $notifyType = 'info';
        }

        if ($notifyMsg !== '') {
            $mensaje = '<div class="alert alert-' . $notifyType . '">' . htmlspecialchars($notifyMsg) . '</div>';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = isset($_POST['nombre_usuario']) ? $_POST['nombre_usuario'] : '';
            $pass = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
            if (!empty($this->nUsuario->login((string)$usuario, (string)$pass))) {
                $_SESSION['usuario'] = $usuario;
                $_SESSION['logueado'] = true;
                $mensaje = '<div class="alert alert-success">Login exitoso. Redirigiendo...</div>';
                header('Refresh: 2; URL=index.php?ntype=success&nmsg=Inicio%20de%20sesion%20exitoso');
            } else {
                $mensaje = '<div class="alert alert-danger">Usuario o contrasena incorrectos.</div>';
            }
        }

        echo '<!DOCTYPE html>';
        echo '<html lang="es">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Login - Sistema de Asistencia</title>';
        echo '<script>
            (function () {
                try {
                    var key = "asistenciau-theme";
                    var saved = localStorage.getItem(key);
                    if (saved === "dark" || saved === "light") {
                        document.documentElement.setAttribute("data-theme", saved);
                    } else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
                        document.documentElement.setAttribute("data-theme", "dark");
                    }
                } catch (e) {
                }
            })();
        </script>';
        echo '<link rel="stylesheet" href="style.css">';
        echo '</head>';
        echo '<body class="login-page">';
        echo '<div class="login-wrap">';
        echo '<div class="login-card">';
        echo '<div class="login-top-actions">';
        echo '<button type="button" id="theme-toggle" class="btn btn-outline btn-sm theme-toggle">Modo oscuro</button>';
        echo '</div>';
        echo '<div class="login-brand">';
        echo '<span class="login-logo" aria-hidden="true"></span>';
        echo '<span class="login-brand-text">AsistencIAU</span>';
        echo '</div>';
        echo '<div class="login-subtitle">Inicia sesion para gestionar clases y asistencia</div>';
        echo $mensaje;
        echo '<form method="POST" autocomplete="on" class="login-form">';
        echo '<div class="input-group">';
        echo '<label for="nombre_usuario"><span class="login-icon">👤</span>Usuario</label>';
        echo '<input class="form-control" type="text" id="nombre_usuario" name="nombre_usuario" required autocomplete="username" placeholder="Tu usuario">';
        echo '</div>';
        echo '<div class="input-group">';
        echo '<label for="contrasena"><span class="login-icon">🔑</span>Contrasena</label>';
        echo '<input class="form-control" type="password" id="contrasena" name="contrasena" required autocomplete="current-password" placeholder="Tu contrasena">';
        echo '</div>';
        echo '<button class="btn btn-block" type="submit">Entrar al sistema</button>';
        echo '</form>';
        echo '<div class="login-note">Sistema de Asistencia Universitaria</div>';
        echo '</div>';
        echo '</div>';
        echo '<script>
            (function () {
                var key = "asistenciau-theme";
                var btn = document.getElementById("theme-toggle");

                function preferredTheme() {
                    try {
                        var saved = localStorage.getItem(key);
                        if (saved === "dark" || saved === "light") {
                            return saved;
                        }
                    } catch (e) {
                    }
                    if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
                        return "dark";
                    }
                    return "light";
                }

                function applyTheme(theme) {
                    document.documentElement.setAttribute("data-theme", theme);
                    if (btn) {
                        btn.textContent = theme === "dark" ? "Modo claro" : "Modo oscuro";
                    }
                }

                applyTheme(preferredTheme());

                if (btn) {
                    btn.addEventListener("click", function () {
                        var current = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
                        var next = current === "dark" ? "light" : "dark";
                        applyTheme(next);
                        try {
                            localStorage.setItem(key, next);
                        } catch (e) {
                        }
                    });
                }
            })();
        </script>';
        echo '</body></html>';
    }
}
