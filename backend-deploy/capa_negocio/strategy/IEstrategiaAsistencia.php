<?php
// 🔥 CORREGIDO: Subir 2 niveles para llegar a la raíz e ingresar a capa_datos
require_once __DIR__ . '/../../capa_datos/DAsistencia.php';

interface IEstrategiaAsistencia {
    public function aplicarAlgoritmo(array $asistencia, array $claseDetalle, DAsistencia $dAsistencia): bool;
}