<?php
/**
 * Script de Verificación Final de Casos de Uso (Arquitectura 3 Capas)
 */
require_once __DIR__ . '/capa_datos/DatabaseHelper.php';
require_once __DIR__ . '/capa_negocio/NClase.php';
require_once __DIR__ . '/capa_negocio/NAsistencia.php';
require_once __DIR__ . '/capa_negocio/NEstudiante.php';
require_once __DIR__ . '/capa_negocio/NGrupo.php';
require_once __DIR__ . '/capa_negocio/NMateria.php';

function test($name, $callback) {
    try {
        $result = $callback();
        echo "✅ [SUCCESS] $name\n";
    } catch (Exception $e) {
        echo "❌ [ERROR] $name: " . $e->getMessage() . "\n";
    }
}

echo "=== INICIANDO VERIFICACIÓN FINAL DEL PROYECTO ===\n\n";

// 1. Verificar Conexión
test("Conexión a Base de Datos", function() {
    $db = DatabaseHelper::getInstance()->connect();
    if (!$db) throw new Exception("No se pudo conectar");
    return true;
});

// 2. Verificar CU2: Estudiantes
test("CU2: Obtener Estudiantes (Negocio -> Datos)", function() {
    $nEst = new NEstudiante();
    $stmt = $nEst->getAll();
    if (!is_object($stmt)) throw new Exception("El retorno no es un objeto PDOStatement");
    return true;
});

// 3. Verificar CU3: Materias
test("CU3: Obtener Materias", function() {
    $nMateria = new NMateria();
    $materias = $nMateria->getAll();
    if (!is_array($materias)) throw new Exception("Fallo al obtener materias");
    return true;
});

// 4. Verificar CU6: Registro de Clases
test("CU6A: Generar Código de Clase (Negocio)", function() {
    $nClase = new NClase();
    $code = $nClase->generateClassCode();
    if (empty($code) || strlen($code) < 5) throw new Exception("Código generado inválido: $code");
    return true;
});

test("CU6B: Registro de Asistencia (Flujo)", function() {
    $nClase = new NClase();
    // Intentar registrar con datos ficticios para probar la lógica (debe fallar grácilmente o funcionar si existen)
    // El método debería devolver booleano
    $res = $nClase->markAttendance([
        'estudiante_codigo_estudiante' => 'EST-999',
        'clase_id' => 999,
        'tipo' => 'Presente'
    ]);
    // Si no existe el estudiante devuelve false, lo cual es correcto operativamente
    if (!is_bool($res)) throw new Exception("markAttendance no devolvió un booleano");
    return true;
});

test("CU7A: Validar payload de asistencia", function() {
    $nAsistencia = new NAsistencia();
    $ok = $nAsistencia->validateAttendance([
        'clase_id' => 1,
        'estudiante_codigo_estudiante' => 'ESTU001',
        'tipo' => 'Presente'
    ]);
    if (!is_bool($ok)) throw new Exception("validateAttendance no devolvió booleano");
    return true;
});

test("CU7B: Consulta asistencia por clase", function() {
    $nAsistencia = new NAsistencia();
    $rows = $nAsistencia->getByClass(1);
    if (!is_array($rows)) throw new Exception("getByClass no devolvió array");
    return true;
});

// 5. Verificar Integridad de Archivos
test("Estructura de Carpetas (3 Capas)", function() {
    if (!is_dir('capa_datos')) throw new Exception("Falta capa_datos");
    if (!is_dir('capa_negocio')) throw new Exception("Falta capa_negocio");
    if (!is_dir('capa_presentacion')) throw new Exception("Falta capa_presentacion");
    return true;
});

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
