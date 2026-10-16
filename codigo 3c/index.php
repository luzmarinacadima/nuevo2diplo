<?php
session_start();
require_once __DIR__ . '/capa_presentacion/PGrupo.php';
require_once __DIR__ . '/capa_presentacion/PMateria.php';
require_once __DIR__ . '/capa_presentacion/PClase.php';
require_once __DIR__ . '/capa_presentacion/PAsistencia.php';
require_once __DIR__ . '/capa_presentacion/PMarcarAsistencia.php';
require_once __DIR__ . '/capa_presentacion/PEstudiante.php';
require_once __DIR__ . '/capa_presentacion/PaGrupo.php';
require_once __DIR__ . '/capa_presentacion/PUsuario.php';
require_once __DIR__ . '/capa_negocio/NClase.php';

if (!isset($_SESSION['logueado']) || !$_SESSION['logueado']) {
    $pUsuario = new PUsuario();
    $pUsuario->login('', '');
    exit;
}

function obtenerDatosUsuario($nombre_usuario) {
    require_once __DIR__ . '/capa_negocio/NUsuario.php';
    $nUsuario = new NUsuario();
    $usuarios = $nUsuario->getAll();
    foreach ($usuarios as $usuario) {
        if ($usuario['nombre_usuario'] === $nombre_usuario) {
            return $usuario;
        }
    }
    return null;
}

$usuario = isset($_SESSION['usuario']) ? obtenerDatosUsuario($_SESSION['usuario']) : null;
$rol = strtolower((string)($usuario['tipo_usuario'] ?? ''));
$usuarioId = intval($usuario['id'] ?? 0);
$nombreCompleto = $usuario ? $usuario['nombres'] . ' ' . $usuario['apellidos'] : 'Usuario';
$tipo = $usuario ? ucfirst((string)$usuario['tipo_usuario']) : '';

$cardsByRole = [
    'admin' => [
        ['action' => 'listar', 'icon' => '👥', 'title' => 'Grupos', 'desc' => 'Gestion de grupos y horarios'],
        ['action' => 'materias', 'icon' => '📚', 'title' => 'Materias', 'desc' => 'Catalogo de asignaturas'],
        ['action' => 'clases', 'icon' => '🗓️', 'title' => 'Clases', 'desc' => 'Programacion de clases y toma de asistencia', 'featured' => true],
        ['action' => 'estudiantes', 'icon' => '👨‍🎓', 'title' => 'Estudiantes', 'desc' => 'Registro y control de alumnos'],
        ['action' => 'registrar_estudiantes_grupo', 'icon' => '🧾', 'title' => 'Asignar a un Grupo estudiantes', 'desc' => 'Asignar a un Grupo estudiantes'],
        ['action' => 'profesores', 'icon' => '👨‍🏫', 'title' => 'Profesores', 'desc' => 'Administracion de docentes'],
        ['action' => 'usuarios', 'icon' => '🔐', 'title' => 'Usuarios', 'desc' => 'Administracion de cuentas']
    ],
    'profesor' => [
        ['action' => 'dashboard', 'icon' => '👥', 'title' => 'Mis Grupos', 'desc' => 'Vista no disponible en este build'],
        ['action' => 'clases', 'icon' => '📝', 'title' => 'Mis Clases', 'desc' => 'Clases creadas para tus grupos', 'featured' => true],
        ['action' => 'registrar_clase', 'icon' => '➕', 'title' => 'Crear Clase', 'desc' => 'Registrar una clase para tus grupos']
    ],
    'estudiante' => [
        ['action' => 'marcar_asistencia_estudiante', 'icon' => '✅', 'title' => 'Mi asistencia', 'desc' => 'Marca asistencia y consulta tu historial']
    ]
];

$allowedActions = [
    'admin' => [
        'dashboard', 'listar', 'materias', 'estudiantes',
        'registrar_estudiante', 'editar_estudiante', 'eliminar_estudiante',
        'registrar_estudiantes_grupo',
        'clases', 'registrar_clase', 'crear_clase', 'marcar_asistencia', 'eliminar_clase',
        'reporte_asistencia_pdf', 'estado_asistencia_tiempo_real', 'finalizar_asistencia',
        'usuarios', 'registrar_usuario', 'editar_usuario', 'eliminar_usuario',
        'profesores', 'editar_profesor', 'eliminar_profesor',
        'registrar', 'crear_grupo', 'editar', 'eliminar',
        
    ],
    'profesor' => [
        'dashboard',
        'clases', 'registrar_clase', 'crear_clase',
        'marcar_asistencia', 'eliminar_clase',
        'reporte_asistencia_pdf', 'estado_asistencia_tiempo_real',
        'finalizar_asistencia'
    ],
    'estudiante' => [
        'dashboard', 'marcar_asistencia_estudiante'
    ]
];

$requestedAction = isset($_GET['action']) ? (string)$_GET['action'] : '';
if ($requestedAction === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestedAction = (string)$_POST['action'];
}
if ($requestedAction === '') {
    $requestedAction = 'dashboard';
}

$action = $requestedAction;

$notifyMessage = trim((string)($_GET['nmsg'] ?? ''));
$notifyType = strtolower(trim((string)($_GET['ntype'] ?? 'info')));
if (!in_array($notifyType, ['success', 'danger', 'warning', 'info'], true)) {
    $notifyType = 'info';
}

$accesoDenegado = false;
$accionesPermitidas = $allowedActions[$rol] ?? ['dashboard'];
if (!in_array($action, $accionesPermitidas, true)) {
    $accesoDenegado = true;
    $action = 'dashboard';
}

if ($rol === 'profesor' && in_array($action, ['marcar_asistencia', 'eliminar_clase', 'reporte_asistencia_pdf', 'estado_asistencia_tiempo_real', 'finalizar_asistencia'], true)) {
    $idClase = isset($_GET['clase_id'])
        ? intval($_GET['clase_id'])
        : intval($_POST['clase_id'] ?? ($_POST['id_clase'] ?? 0));
    $nClaseAuth = new NClase();
    $clase = $nClaseAuth->getClaseDetalle($idClase);
    if ($idClase <= 0 || !$clase || !isset($clase['profesor_id']) || $clase['profesor_id'] != $usuarioId) {
        $accesoDenegado = true;
        $action = 'dashboard';
    }
}

$pGrupo = new PGrupo();
$pMateria = new PMateria();
$pClase = new PClase();
$pAsistencia = new PAsistencia();
$pMarcarAsistencia = new PMarcarAsistencia($usuarioId);
$pEstudiante = new PEstudiante();
$pAsignacion = new PaGrupo();
$pUsuario = new PUsuario();

if ($requestedAction === 'reporte_asistencia_pdf') {
    if ($accesoDenegado) {
        http_response_code(403);
        echo 'No tiene permisos para generar este reporte.';
        exit;
    }

    $claseIdReporte = isset($_GET['clase_id'])
        ? intval($_GET['clase_id'])
        : intval($_POST['clase_id'] ?? 0);
    $reporteContenido = $pAsistencia->generarReporte($claseIdReporte);
    if ($reporteContenido === '') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'No se pudo generar el reporte solicitado.';
        exit;
    }

    $format = $_GET['format'] ?? 'pdf';
    $ext = ($format === 'excel') ? 'xls' : 'pdf';
    $mime = ($format === 'excel') ? 'application/vnd.ms-excel' : 'application/pdf';

    $filename = 'reporte_asistencia_c' . intval($claseIdReporte) . '_' . date('Ymd_His') . '.' . $ext;
    if (ob_get_level() > 0) {
        @ob_end_clean();
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($reporteContenido));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $reporteContenido;
    exit;
}

if ($requestedAction === 'finalizar_asistencia') {
    if ($accesoDenegado) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'No tiene permisos para finalizar esta asistencia.';
        exit;
    }

    $claseIdFinalizar = isset($_GET['clase_id'])
        ? intval($_GET['clase_id'])
        : intval($_POST['clase_id'] ?? 0);
    
    $estrategia = isset($_GET['estrategia']) ? $_GET['estrategia'] : (isset($_POST['estrategia']) ? $_POST['estrategia'] : 'manual');

    if ($claseIdFinalizar <= 0) {
        header('Location: index.php?action=clases&ntype=danger&nmsg=' . urlencode('Clase invalida para finalizar.'));
        exit;
    }

    $ok = $pAsistencia->finalizar($claseIdFinalizar, $estrategia);
    if ($ok) {
        header('Location: index.php?action=clases&ntype=success&nmsg=' . urlencode('Asistencia finalizada.'));
        exit;
    }

    header('Location: index.php?action=marcar_asistencia&clase_id=' . intval($claseIdFinalizar) . '&ntype=danger&nmsg=' . urlencode('No se pudo finalizar la asistencia.'));
    exit;
}

if ($requestedAction === 'estado_asistencia_tiempo_real') {
    if ($accesoDenegado) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'message' => 'No autorizado']);
        exit;
    }

    $claseIdEstado = isset($_GET['clase_id'])
        ? intval($_GET['clase_id'])
        : intval($_POST['clase_id'] ?? 0);

    // Usar PAsistencia para obtener el estado de asistencia en JSON
    header('Content-Type: application/json; charset=UTF-8');
    if ($claseIdEstado <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Clase inválida']);
        exit;
    }
    $statusBoard = $pAsistencia->getStatusBoardByClass($claseIdEstado);
    $pendientes = $pAsistencia->getPendientesByClass($claseIdEstado);
    echo json_encode([
        'ok' => true,
        'statusBoard' => $statusBoard,
        'pendientes' => $pendientes
    ]);
    exit;
}


$cards = $cardsByRole[$rol] ?? $cardsByRole['estudiante'];
$dashboardGridClass = 'grid dashboard-grid';
if ($rol === 'admin') {
    $dashboardGridClass .= ' dashboard-grid--admin';
}

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Asistencia Universitaria</title>
    <script>
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
    </script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="brand">
                <div class="brand-logo" aria-hidden="true"></div>
                <div>
                    <h1>AsistencIAU</h1>
                    <p>Control académico de clases y asistencia</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>';

if ($notifyMessage !== '') {
    echo '<div id="server-notify" data-type="' . htmlspecialchars($notifyType, ENT_QUOTES, 'UTF-8') . '" data-message="' . htmlspecialchars($notifyMessage, ENT_QUOTES, 'UTF-8') . '" hidden></div>';
}

echo '
        <div class="user-strip">
            <div class="user-strip-left">
                <div class="user-avatar">
                    ' . strtoupper(substr($nombreCompleto, 0, 1)) . '
                </div>
                <div>
                    <div class="user-name">' . htmlspecialchars($nombreCompleto) . '</div>
                    <div class="user-role">' . htmlspecialchars($tipo) . '</div>
                </div>
            </div>
            <div class="user-strip-actions">
                <button type="button" id="theme-toggle" class="btn btn-outline btn-sm theme-toggle">Modo oscuro</button>
                <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesion</a>
            </div>
        </div>

        <div class="' . htmlspecialchars($dashboardGridClass, ENT_QUOTES, 'UTF-8') . '">';

foreach ($cards as $card) {
    $cardClass = !empty($card['featured']) ? 'stat-card stat-card--featured' : 'stat-card';
    echo '<a href="index.php?action=' . urlencode((string)$card['action']) . '" class="' . $cardClass . '">';
    echo '<span class="icon">' . $card['icon'] . '</span>';
    echo '<h3>' . htmlspecialchars((string)$card['title']) . '</h3>';
    echo '<p>' . htmlspecialchars((string)$card['desc']) . '</p>';
    echo '</a>';
}

echo '</div>';

echo '<div class="main-content">';

if ($accesoDenegado) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a esa opcion.</div>';
}

switch ($action) {
                case 'registrar_clase':
                    $pClase->create([]);
                    break;
            case 'crear_clase':
                $pClase->create($_POST);
                break;
        case 'clases':
            if ($rol === 'profesor') {
                $pClase->getByProfesorUsuarioId($usuarioId);
            } else {
                $pClase->getAll();
            }
            break;
    case 'dashboard':
        if ($rol === 'estudiante') {
            $pMarcarAsistencia->dashboard();
            break;
        }
        $mensajeRol = 'Seleccione una opcion del panel para continuar.';
        if ($rol === 'profesor') {
            $mensajeRol = 'Accede a tus grupos y crea clases segun tu asignacion.';
        } elseif ($rol === 'estudiante') {
            $mensajeRol = 'Revisa tus grupos y coordina con el docente el registro de asistencia.';
        }

        echo '<div class="card empty-state">';
        echo '<span class="empty-state-icon">🎯</span>';
        echo '<h2>Bienvenido al sistema</h2>';
        echo '<p>' . htmlspecialchars($mensajeRol) . '</p>';
        echo '</div>';
        break;

    case 'marcar_asistencia_estudiante':
        if ($rol !== 'estudiante') {
            echo '<div class="alert alert-danger">No tiene permisos para acceder a esta opción.</div>';
            break;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pMarcarAsistencia->marcar($_POST);
        } else {
            $pMarcarAsistencia->dashboard();
        }
        break;


    case 'listar':
        $pGrupo->getAll();
        break;
    case 'materias':
        $pMateria->getAll();
        break;
    case 'estudiantes':
        $pEstudiante->getAll();
        break;
    case 'registrar_estudiantes_grupo':
        $pAsignacion->getAll();
        break;
    case 'registrar_estudiante':
        $pEstudiante->create([]);
        break;
    case 'editar_estudiante':
        // Mostrar formulario de edición o procesar actualización
        $idEst = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            // Procesar actualización
            $pEstudiante->update(intval($_POST['id']), $_POST);
        } else {
            // Mostrar formulario de edición
            $pEstudiante->update($idEst, []);
        }
        break;
    case 'eliminar_estudiante':
        $pEstudiante->delete(isset($_GET['id']) ? intval($_GET['id']) : 0);
        break;
    case 'marcar_asistencia':
        $idClase = isset($_GET['clase_id']) ? intval($_GET['clase_id']) : (isset($_POST['id_clase']) ? intval($_POST['id_clase']) : 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_estudiante'], $_POST['tipo'])) {
            $pAsistencia->addAttendance($_POST);
        }
        $pAsistencia->getStatusBoardByClass($idClase);
        break;
    case 'reporte_asistencia_pdf':
        echo '<div class="alert alert-danger">No se pudo generar el reporte PDF.</div>';
        break;
    case 'estado_asistencia_tiempo_real':
        echo '<div class="alert alert-danger">No se pudo consultar el estado en tiempo real.</div>';
        break;
    case 'eliminar_clase':
        $pClase->delete(isset($_GET['clase_id']) ? intval($_GET['clase_id']) : 0);
        break;
    case 'usuarios':
        $pUsuario->getAll();
        break;
    case 'registrar_usuario':
        $pUsuario->create([]);
        break;
    case 'editar_usuario':
        $pUsuario->update(isset($_GET['id']) ? intval($_GET['id']) : 0, []);
        break;
    case 'eliminar_usuario':
        $pUsuario->delete(isset($_GET['id']) ? intval($_GET['id']) : 0);
        break;
    case 'profesores':
        require_once __DIR__ . '/capa_presentacion/PProfesor.php';
        $pProfesor = new PProfesor();
        $pProfesor->getAll();
        break;
    case 'editar_profesor':
        require_once __DIR__ . '/capa_presentacion/PProfesor.php';
        $pProfesor = new PProfesor();
        $pProfesor->update(isset($_GET['id']) ? intval($_GET['id']) : 0, []);
        break;
    case 'eliminar_profesor':
        require_once __DIR__ . '/capa_presentacion/PProfesor.php';
        $pProfesor = new PProfesor();
        $pProfesor->delete(isset($_GET['id']) ? intval($_GET['id']) : 0);
        break;
    case 'registrar':
    case 'crear_grupo':
        $pGrupo->create([]);
        break;
    case 'editar':
        if (isset($_GET['id']) && isset($_GET['from']) && $_GET['from'] === 'materias') {
            $pMateria->getAll();
        } else {
            $pGrupo->update(isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0, []);
        }
        break;
    case 'eliminar':
        if (isset($_GET['id']) && isset($_GET['from']) && $_GET['from'] === 'materias') {
            $pMateria->delete(intval($_GET['id']));
        } else {
            $pGrupo->delete(isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0);
        }
        break;
    default:
        echo '<div class="alert alert-danger">Accion no valida.</div>';
        break;
}

echo '</div> <!-- close main-content -->
    </div> <!-- close container -->
    <script>
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

        (function () {
            var stack = document.getElementById("toast-stack");
            if (!stack) {
                return;
            }

            function normalizeType(type) {
                var t = String(type || "info").toLowerCase();
                if (t === "success" || t === "danger" || t === "warning" || t === "info") {
                    return t;
                }
                return "info";
            }

            function showToast(message, type, duration) {
                var text = String(message || "").trim();
                if (!text) {
                    return;
                }

                var safeType = normalizeType(type);
                var lifetime = Number(duration || 4500);
                var toast = document.createElement("div");
                toast.className = "toast toast--" + safeType;
                toast.setAttribute("role", "status");
                toast.innerHTML = "<span class=\"toast-dot\"></span><span class=\"toast-text\"></span><button type=\"button\" class=\"toast-close\" aria-label=\"Cerrar\">x</button>";

                var textEl = toast.querySelector(".toast-text");
                if (textEl) {
                    textEl.textContent = text;
                }

                var closeBtn = toast.querySelector(".toast-close");
                var closed = false;
                function closeToast() {
                    if (closed) {
                        return;
                    }
                    closed = true;
                    toast.classList.add("toast-hide");
                    window.setTimeout(function () {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 220);
                }

                if (closeBtn) {
                    closeBtn.addEventListener("click", closeToast);
                }

                stack.appendChild(toast);
                window.setTimeout(closeToast, Math.max(1800, lifetime));
            }

            window.asistenciaNotify = showToast;

            var serverNotify = document.getElementById("server-notify");
            if (serverNotify) {
                var sType = serverNotify.getAttribute("data-type") || "info";
                var sMsg = serverNotify.getAttribute("data-message") || "";
                showToast(sMsg, sType, 5200);

                if (window.history && typeof window.history.replaceState === "function") {
                    try {
                        var url = new URL(window.location.href);
                        url.searchParams.delete("nmsg");
                        url.searchParams.delete("ntype");
                        var clean = url.pathname;
                        if (url.searchParams.toString() !== "") {
                            clean += "?" + url.searchParams.toString();
                        }
                        clean += url.hash;
                        window.history.replaceState({}, document.title, clean);
                    } catch (e) {
                    }
                }
            }

            var alertNodes = document.querySelectorAll(".main-content .alert");
            var seen = Object.create(null);
            alertNodes.forEach(function (node) {
                if (!node || node.id === "rt-pending-msg") {
                    return;
                }
                var style = window.getComputedStyle(node);
                if (style && style.display === "none") {
                    return;
                }

                var msg = (node.textContent || "").trim();
                if (!msg) {
                    return;
                }

                var type = "info";
                if (node.classList.contains("alert-success")) {
                    type = "success";
                } else if (node.classList.contains("alert-danger")) {
                    type = "danger";
                } else if (node.classList.contains("alert-warning")) {
                    type = "warning";
                }

                var key = type + "::" + msg;
                if (seen[key]) {
                    return;
                }
                seen[key] = true;
                showToast(msg, type, 4200);
            });
        })();
    </script>
</body>
</html>';
?>