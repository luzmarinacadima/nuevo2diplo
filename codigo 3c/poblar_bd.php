<?php
require_once __DIR__ . '/capa_datos/DatabaseHelper.php';

try {
    $db = DatabaseHelper::getInstance()->connect();
    $sql = file_get_contents(__DIR__ . '/bd_asistencia.sql');
    
    // Eliminar comandos de base de datos que no funcionan dentro de PDO (CREATE DATABASE, etc)
    // El script ya está pensado para correr dentro de la BD 'asistenciau'
    $sql = preg_replace('/CREATE DATABASE .*?;/i', '', $sql);
    
    echo "Poblando base de datos...\n";
    $db->exec($sql);
    echo "¡Base de datos poblada exitosamente!\n";
    
} catch (Exception $e) {
    echo "Error al poblar la base de datos: " . $e->getMessage() . "\n";
}
?>