<?php
require_once __DIR__ . '/capa_datos/DatabaseHelper.php';

$db = DatabaseHelper::getInstance()->connect();

echo "=== CLASES ===\n";
$q1 = $db->query("SELECT id_clase, grupo_id, dia, hora_inicio, hora_fin, codigo FROM clase ORDER BY id_clase DESC");
print_r($q1->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== GRUPOS ===\n";
$q2 = $db->query("SELECT id_grupo, nombre, capacidad_actual, capacidad_maxima FROM grupo ORDER BY id_grupo DESC LIMIT 10");
print_r($q2->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== ASIGNACIONES ===\n";
$q3 = $db->query("SELECT estudiante_id, grupo_id, fecha_asignacion FROM asignacion ORDER BY fecha_asignacion DESC");
print_r($q3->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== ESTUDIANTES ===\n";
$q4 = $db->query("SELECT e.codigo_estudiante, e.usuario_id, e.estado, u.nombres, u.apellidos FROM estudiante e JOIN usuario u ON u.id = e.usuario_id ORDER BY e.codigo_estudiante");
print_r($q4->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== PENDIENTES POR CLASE ===\n";
$sqlPend = "
SELECT
    c.id_clase,
    c.grupo_id,
    e.codigo_estudiante,
    u.nombres,
    u.apellidos
FROM clase c
INNER JOIN asignacion a ON a.grupo_id = c.grupo_id
INNER JOIN estudiante e ON e.estudiante_id = a.estudiante_id
INNER JOIN usuario u ON u.id = e.usuario_id
LEFT JOIN asistencia asi
    ON asi.clase_id = c.id_clase
   AND asi.estudiante_codigo_estudiante = e.codigo_estudiante
WHERE asi.id_asistencia IS NULL
ORDER BY c.id_clase DESC, u.apellidos, u.nombres
";
$q5 = $db->query($sqlPend);
print_r($q5->fetchAll(PDO::FETCH_ASSOC));
