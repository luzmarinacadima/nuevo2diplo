<?php
require_once __DIR__ . '/capa_datos/DatabaseHelper.php';
$db = DatabaseHelper::getInstance()->connect();

echo "--- USUARIOS EN TABLA ---\n";
$res = $db->query('SELECT id, nombre_usuario, tipo_usuario FROM usuario');
print_r($res->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- ESTUDIANTES EN TABLA ---\n";
$res2 = $db->query('SELECT usuario_id FROM estudiante');
print_r($res2->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- QUERY LIBRES ---\n";
$sql = "SELECT u.* FROM usuario u LEFT JOIN estudiante e ON u.id = e.usuario_id WHERE e.usuario_id IS NULL AND u.tipo_usuario = 'estudiante'";
$res3 = $db->query($sql);
print_r($res3->fetchAll(PDO::FETCH_ASSOC));
