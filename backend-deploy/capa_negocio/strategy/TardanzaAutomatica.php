<?php
require_once __DIR__ . '/IEstrategiaAsistencia.php';
// 🔥 CONTROL DE RUTA LOCAL: Subir 2 niveles para llegar a capa_datos desde strategy
require_once __DIR__ . '/../../capa_datos/DAsistencia.php';

class TardanzaAutomatica implements IEstrategiaAsistencia {
    
    /**
     * Aplica la política de Tardanza Automática evaluando la hora local de Bolivia.
     */
    public function aplicarAlgoritmo(array $asistencia, array $claseDetalle, DAsistencia $dAsistencia): bool {
        // 1. FORZAR LA ZONA HORARIA DE BOLIVIA (Evita que el reloj de XAMPP rompa el cálculo)
        date_default_timezone_set('America/La_Paz');

        // 2. Extraer metadatos de control y limpiar cadenas
        $horaInicio = $claseDetalle['hora_inicio'] ?? '00:00:00';
        $tipoOriginal = strtolower(trim($asistencia['tipo'] ?? ($asistencia['estado'] ?? 'presente')));
        
        $tiempoActual = time();
        $hoy = date('Y-m-d');
        $tiempoInicio = strtotime("$hoy $horaInicio");
        
        // 3. Calcular el desfase temporal real
        $diferenciaSegundos = $tiempoActual - $tiempoInicio;
        $diferenciaMinutos = $diferenciaSegundos / 60;
        $estadoCalculado = $tipoOriginal;

        // Si se marca "presente" pero ya pasaron los minutos de tolerancia, mutamos a "tarde"
        if ($tipoOriginal === 'presente' && $diferenciaMinutos > 1) {
            $estadoCalculado = 'tarde';
        }

        // 4. Persistencia directa en la base de datos
        return $dAsistencia->addAttendance([
            'id_clase' => $asistencia['id_clase'],
            'codigo_estudiante' => trim($asistencia['codigo_estudiante']),
            'tipo' => $estadoCalculado
        ]);
    }
}