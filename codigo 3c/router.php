<?php
// Router para el servidor embebido de PHP.
// Permite servir archivos estáticos (CSS/JS/imagenes) directamente.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path && $path !== '/') {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/index.php';
