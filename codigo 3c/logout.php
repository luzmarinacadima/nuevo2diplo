<?php
session_start();
// Destruir la sesión y redirigir al login
session_unset();
session_destroy();
header('Location: index.php?ntype=info&nmsg=Sesion%20cerrada%20correctamente');
exit;
