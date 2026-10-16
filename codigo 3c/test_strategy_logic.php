<?php
require_once __DIR__ . '/capa_negocio/strategy/TardanzaAutomatica.php';

$strategy = new TardanzaAutomatica();

// Simular clase que empezó hace 2 minutos (120 segundos)
$horaInicio = date('H:i:s', time() - 120); 

$data = [
    'hora_inicio' => $horaInicio,
    'estudiantes' => [
        ['codigo_estudiante' => 'EST001', 'tipo' => 'Presente']
    ]
];

echo "Hora inicio simulación: $horaInicio\n";
echo "Hora actual servidor: " . date('H:i:s') . "\n";

$resultado = $strategy->aplicarAlgoritmo($data);

echo "Resultado: " . $resultado[0]['tipo'] . "\n";

if ($resultado[0]['tipo'] === 'Tarde') {
    echo "DEBUG: La estrategia FUNCIONA correctamente para > 1 min.\n";
} else {
    echo "DEBUG: La estrategia NO cambió el estado.\n";
}
