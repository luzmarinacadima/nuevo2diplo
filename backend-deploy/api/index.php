<?php
declare(strict_types=1);

session_name(getenv('SESSION_NAME') ?: 'asistenciau_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => filter_var(getenv('SESSION_SECURE') ?: '1', FILTER_VALIDATE_BOOLEAN),
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../capa_negocio/NUsuario.php';
require_once __DIR__ . '/../capa_negocio/NGrupo.php';
require_once __DIR__ . '/../capa_negocio/NMateria.php';
require_once __DIR__ . '/../capa_negocio/NEstudiante.php';
require_once __DIR__ . '/../capa_negocio/NProfesor.php';
require_once __DIR__ . '/../capa_negocio/NClase.php';
require_once __DIR__ . '/../capa_negocio/NaGrupo.php';
require_once __DIR__ . '/../capa_negocio/NAsistencia.php';

$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: ''))));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Accept, Content-Type, Authorization, X-XSRF-TOKEN');
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code($origin === '' || in_array($origin, $allowedOrigins, true) ? 204 : 403);
    exit;
}

function jsonResponse($data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400, array $errors = []): never {
    jsonResponse(['message' => $message, 'errors' => $errors], $status);
}

function body(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) fail('El cuerpo debe ser JSON.', 400);
    return $decoded;
}

function currentUser(): ?array {
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function requireAuth(): array {
    $user = currentUser();
    if ($user === null) fail('Autenticación requerida.', 401);
    return $user;
}

function requireRole(array $roles): array {
    $user = requireAuth();
    if (!in_array(strtolower((string)($user['tipo_usuario'] ?? '')), $roles, true)) {
        fail('No tiene permisos para esta operación.', 403);
    }
    return $user;
}

function publicUser(array $user): array {
    unset($user['contrasena']);
    return $user;
}

function rows($result): array {
    if ($result instanceof PDOStatement) return $result->fetchAll(PDO::FETCH_ASSOC);
    return is_array($result) ? array_values($result) : [];
}

function idFrom(string $value): int {
    if (!ctype_digit($value) || (int)$value <= 0) fail('Identificador inválido.', 422);
    return (int)$value;
}

function normalizeGroup(array $row): array {
    $row['id'] = (int)($row['id_grupo'] ?? $row['id'] ?? 0);
    return $row;
}

function normalizeClass(array $row): array {
    return $row + [
        'id' => (int)($row['id_clase'] ?? 0),
        'nombre' => $row['grupo_nombre'] ?? '',
        'fecha' => $row['dia'] ?? '',
        'hora' => $row['hora_inicio'] ?? '',
        'estado' => !empty($row['finalizada']) ? 'finalizada' : 'activa',
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $path = preg_replace('#^api/?#', '', $path);
    $segments = $path === '' ? [] : explode('/', $path);

    if ($segments === ['auth', 'me'] && $method === 'GET') {
        $user = currentUser();
        if ($user === null) fail('No hay una sesión activa.', 401);
        jsonResponse(['user' => publicUser($user)]);
    }
    if ($segments === ['auth', 'login'] && $method === 'POST') {
        $input = body();
        $username = trim((string)($input['username'] ?? ''));
        $password = (string)($input['password'] ?? '');
        if ($username === '' || $password === '') fail('Usuario y contraseña son obligatorios.', 422);
        $user = (new NUsuario())->login($username, $password);
        if ($user === []) fail('Credenciales incorrectas.', 401);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        jsonResponse(['user' => publicUser($user)]);
    }
    if ($segments === ['auth', 'logout'] && $method === 'POST') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        jsonResponse(['ok' => true]);
    }

    $user = requireAuth();
    $role = strtolower((string)$user['tipo_usuario']);

    if ($segments === ['groups'] && $method === 'GET') {
        $items = array_map('normalizeGroup', (new NGrupo())->getAll());
        jsonResponse(['data' => $items]);
    }
    if ($segments === ['groups'] && $method === 'POST') {
        requireRole(['admin']);
        $input = body();
        if (!(new NGrupo())->create($input)) fail('No se pudo crear el grupo.', 422);
        jsonResponse(['ok' => true], 201);
    }
    if (($segments[0] ?? '') === 'groups' && count($segments) === 2) {
        $id = idFrom($segments[1]);
        $service = new NGrupo();
        if ($method === 'PATCH') {
            requireRole(['admin']);
            if (!$service->update($id, body())) fail('No se pudo actualizar el grupo.', 422);
            jsonResponse(['ok' => true]);
        }
        if ($method === 'DELETE') {
            requireRole(['admin']);
            if (!$service->delete($id)) fail('No se pudo eliminar el grupo.', 409);
            jsonResponse(['ok' => true]);
        }
    }

    if ($segments === ['students', 'available'] && $method === 'GET') {
        requireRole(['admin']);
        jsonResponse(['data' => (new NEstudiante())->obtenerUsuariosLibres()]);
    }
    if ($segments === ['students', 'dashboard'] && $method === 'GET') {
        $student = requireRole(['estudiante']);
        jsonResponse(['data' => (new NAsistencia())->getDashboardEstudiante((int)$student['id'])]);
    }
    if ($segments === ['students', 'attendance'] && $method === 'POST') {
        $student = requireRole(['estudiante']);
        $input = body();
        $classId = (int)($input['clase_id'] ?? 0);
        $result = (new NAsistencia())->marcarAsistenciaEstudiante((int)$student['id'], $classId);
        if (empty($result['ok'])) fail((string)($result['message'] ?? 'No se pudo registrar la asistencia.'), 422);
        jsonResponse($result, 201);
    }

    $simpleResources = [
        'subjects' => [NMateria::class, 'admin'],
        'students' => [NEstudiante::class, 'admin'],
        'teachers' => [NProfesor::class, 'admin'],
        'users' => [NUsuario::class, 'admin'],
    ];
    foreach ($simpleResources as $resource => [$class, $writeRole]) {
        if ($segments === [$resource] && $method === 'GET') {
            $service = new $class();
            $items = rows($service->getAll());
            if ($resource === 'users') $items = array_map('publicUser', $items);
            jsonResponse(['data' => $items]);
        }
        if ($segments === [$resource] && $method === 'POST') {
            requireRole([$writeRole]);
            $service = new $class();
            if (!$service->create(body())) fail('No se pudo crear el recurso.', 422);
            jsonResponse(['ok' => true], 201);
        }
        if (($segments[0] ?? '') === $resource && count($segments) === 2) {
            $id = idFrom($segments[1]);
            $service = new $class();
            if ($method === 'GET') {
                $item = $service->getById($id);
                if (!$item) fail('Recurso no encontrado.', 404);
                jsonResponse(['data' => $resource === 'users' ? publicUser($item) : $item]);
            }
            if ($method === 'PATCH') {
                requireRole([$writeRole]);
                if (!$service->update($id, body())) fail('No se pudo actualizar el recurso.', 422);
                jsonResponse(['ok' => true]);
            }
            if ($method === 'DELETE') {
                requireRole([$writeRole]);
                if (!$service->delete($id)) fail('No se pudo eliminar el recurso.', 409);
                jsonResponse(['ok' => true]);
            }
        }
    }

    if ($segments === ['classes'] && $method === 'GET') {
        $service = new NClase();
        $items = $role === 'profesor' ? $service->getByProfesorUsuarioId((int)$user['id']) : $service->getAll();
        jsonResponse(['data' => array_map('normalizeClass', $items)]);
    }
    if ($segments === ['classes'] && $method === 'POST') {
        requireRole(['admin', 'profesor']);
        if (!(new NClase())->create(body())) fail('No se pudo crear la clase.', 422);
        jsonResponse(['ok' => true], 201);
    }
    if (($segments[0] ?? '') === 'classes' && count($segments) === 2) {
        $id = idFrom($segments[1]);
        $service = new NClase();
        if ($method === 'GET') {
            $item = $service->getClaseDetalle($id);
            if (!$item) fail('Clase no encontrada.', 404);
            jsonResponse(['data' => normalizeClass($item)]);
        }
        if ($method === 'PATCH') {
            requireRole(['admin', 'profesor']);
            if (!$service->update($id, body())) fail('No se pudo actualizar la clase.', 422);
            jsonResponse(['ok' => true]);
        }
        if ($method === 'DELETE') {
            requireRole(['admin', 'profesor']);
            if (!$service->delete($id)) fail('No se pudo eliminar la clase.', 409);
            jsonResponse(['ok' => true]);
        }
    }

    if ($segments === ['assignments'] && $method === 'GET') {
        requireRole(['admin']);
        $groupId = idFrom((string)($_GET['group_id'] ?? '0'));
        jsonResponse(['data' => (new NaGrupo())->getById($groupId)['asignaciones'] ?? []]);
    }
    if ($segments === ['assignments'] && $method === 'POST') {
        requireRole(['admin']);
        $input = body();
        $result = isset($input['codes'], $input['groupId']) || isset($input['codigos'], $input['grupo_id'])
            ? (new NaGrupo())->bulkAssign($input)
            : (new NaGrupo())->assignToGroup((string)($input['code'] ?? ''), (int)($input['group_id'] ?? 0));
        jsonResponse(['data' => $result]);
    }

    if (($segments[0] ?? '') === 'attendance' && ($segments[1] ?? '') === 'classes' && isset($segments[2])) {
        $classId = idFrom($segments[2]);
        $service = new NAsistencia();
        if (count($segments) === 3 && $method === 'GET') {
            jsonResponse(['data' => $service->getStatusBoardByClass($classId)]);
        }
        if (count($segments) === 3 && $method === 'POST') {
            requireRole(['admin', 'profesor']);
            $input = body() + ['id_clase' => $classId];
            $strategy = strtolower((string)($input['estrategia'] ?? 'manual'));
            if (!in_array($strategy, ['manual', 'tardanza', 'falta'], true)) fail('Estrategia de asistencia inválida.', 422);
            if (!$service->addAttendance($input, $strategy)) fail('No se pudo registrar la asistencia.', 422);
            jsonResponse(['ok' => true], 201);
        }
        if (($segments[3] ?? '') === 'finish' && $method === 'POST') {
            requireRole(['admin', 'profesor']);
            if (!$service->finalizar($classId)) fail('No se pudo finalizar la asistencia.', 422);
            jsonResponse(['ok' => true]);
        }
        if (($segments[3] ?? '') === 'report' && $method === 'GET') {
            requireRole(['admin', 'profesor']);
            $format = strtolower((string)($_GET['format'] ?? 'pdf'));
            if (!in_array($format, ['pdf', 'excel'], true)) fail('Formato de reporte inválido.', 422);
            $content = $service->generarReporte($classId, $format);
            if ($content === '') fail('No hay registros para generar el reporte.', 404);
            header_remove('Content-Type');
            header('Content-Type: ' . ($format === 'pdf' ? 'application/pdf' : 'application/vnd.ms-excel'));
            header('Content-Disposition: attachment; filename="reporte_asistencia_c' . $classId . '.' . ($format === 'pdf' ? 'pdf' : 'xls') . '"');
            echo $content;
            exit;
        }
    }
    fail('Ruta no encontrada.', 404);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    fail('Error interno del servidor.', 500);
}
