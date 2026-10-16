<?php
require_once __DIR__ . '/../capa_negocio/NAsistencia.php';

class PMarcarAsistencia {
    private $nAsistencia;
    private $usuarioId;

    public function __construct(int $usuarioId) {
        $this->nAsistencia = new NAsistencia();
        $this->usuarioId = $usuarioId;
    }

    public function dashboard(string $mensaje = '', string $tipo = 'info'): void {
        $datos = $this->nAsistencia->getDashboardEstudiante($this->usuarioId);
        $estudiante = $datos['estudiante'] ?? [];
        $grupos = $datos['grupos'] ?? [];
        $clases = $datos['clases'] ?? [];
        $historial = $datos['historial'] ?? [];
        $resumen = $datos['resumen'] ?? ['presente' => 0, 'tarde' => 0, 'falta' => 0];
        $grupoSeleccionado = (int)($_GET['grupo_id'] ?? 0);
        if ($grupoSeleccionado > 0) {
            $clases = array_values(array_filter($clases, static function (array $clase) use ($grupoSeleccionado): bool {
                return (int)($clase['grupo_id'] ?? 0) === $grupoSeleccionado;
            }));
            $historial = array_values(array_filter($historial, static function (array $registro) use ($grupoSeleccionado): bool {
                return (int)($registro['grupo_id'] ?? 0) === $grupoSeleccionado;
            }));
        }

        echo '<section class="card student-attendance-dashboard">';
        date_default_timezone_set('America/La_Paz');
        $horaServidor = (new \DateTimeImmutable('now', new \DateTimeZone('America/La_Paz')))->format('c');
        echo '<p style="letter-spacing:.16em;color:#087bd1;text-align:center;font-size:.78rem;">PLATAFORMA DE ASISTENCIA DIGITAL UNIVERSITARIA</p>';
        echo '<h1 style="text-align:center;color:#075aa8;margin:.25rem 0;">Mi asistencia</h1>';
        echo '<p style="text-align:center;color:#64748b;">Marca tu asistencia y consulta tu historial</p>';
        echo '<hr>';
        echo '<h2>Mis grupos</h2>';
        echo '<form method="GET" style="margin-bottom:1.5rem;">';
        echo '<input type="hidden" name="action" value="marcar_asistencia_estudiante">';
        echo '<select name="grupo_id" class="form-control" onchange="this.form.submit()">';
        echo '<option value="0">Todos mis grupos</option>';
        foreach ($grupos as $grupo) {
            $selected = $grupoSeleccionado === (int)$grupo['grupo_id'] ? ' selected' : '';
            echo '<option value="' . (int)$grupo['grupo_id'] . '"' . $selected . '>' . htmlspecialchars($grupo['materia_nombre'] . ' - ' . $grupo['grupo_nombre']) . '</option>';
        }
        echo '</select></form>';

        if ($mensaje !== '') {
            echo '<div class="alert alert-' . htmlspecialchars($tipo) . '">' . htmlspecialchars($mensaje) . '</div>';
        }
        echo '<h2>Clases disponibles</h2>';
        if (!$clases) {
            echo '<div class="empty-state"><p>No tienes clases disponibles en tus grupos asignados.</p></div>';
        }
        foreach ($clases as $clase) {
            echo '<article class="card card-sm" style="margin:1rem 0;">';
            echo '<h3>' . htmlspecialchars($clase['materia_nombre'] . ' - ' . $clase['grupo_nombre']) . '</h3>';
            echo '<p>' . htmlspecialchars($clase['dia']) . ' · ' . htmlspecialchars(substr((string)$clase['hora_inicio'], 0, 5)) . ' a ' . htmlspecialchars(substr((string)$clase['hora_fin'], 0, 5)) . '</p>';
            echo '<p>Profesor: ' . htmlspecialchars($clase['profesor_nombre'] ?? '-') . '</p>';
            if (($clase['estado_asistencia'] ?? '') !== '') {
                echo '<p><strong>Estado:</strong> ' . htmlspecialchars(ucfirst((string)$clase['estado_asistencia'])) . '</p>';
            } else {
                date_default_timezone_set('America/La_Paz');
                $zonaHoraria = new \DateTimeZone('America/La_Paz');
                $ahora = new \DateTimeImmutable('now', $zonaHoraria);
                $inicioClase = new \DateTimeImmutable($clase['dia'] . ' ' . $clase['hora_inicio'], $zonaHoraria);
                $finClase = new \DateTimeImmutable($clase['dia'] . ' ' . $clase['hora_fin'], $zonaHoraria);
                if ($ahora < $inicioClase) {
                    echo '<p style="color:#64748b;">Asistencia disponible desde las ' . htmlspecialchars($inicioClase->format('d/m/Y H:i')) . '.</p>';
                    echo '</article>';
                    continue;
                }
                if ($ahora > $finClase) {
                    echo '<p style="color:#64748b;">La asistencia cerró a las ' . htmlspecialchars($finClase->format('H:i')) . '.</p>';
                    echo '</article>';
                    continue;
                }
                $inicioFecha = new \DateTimeImmutable($clase['dia'] . ' ' . $clase['hora_inicio'], $zonaHoraria);
                $finFecha = new \DateTimeImmutable($clase['dia'] . ' ' . $clase['hora_fin'], $zonaHoraria);
                $inicio = htmlspecialchars($inicioFecha->format('c'), ENT_QUOTES, 'UTF-8');
                $fin = htmlspecialchars($finFecha->format('c'), ENT_QUOTES, 'UTF-8');
                echo '<form method="POST" class="student-attendance-form" data-start="' . $inicio . '" data-end="' . $fin . '" data-server-now="' . htmlspecialchars($horaServidor, ENT_QUOTES, 'UTF-8') . '">';
                echo '<input type="hidden" name="action" value="marcar_asistencia_estudiante">';
                echo '<input type="hidden" name="clase_id" value="' . (int)$clase['id_clase'] . '">';
                echo '<p class="attendance-countdown" style="font-size:.9rem;color:#64748b;margin:.5rem 0;"></p>';
                echo '<button type="submit" class="btn btn-primary attendance-submit" style="width:100%;margin-top:.7rem;">Marcar asistencia</button>';
                echo '</form>';
            }
            echo '</article>';
        }

        echo '<div class="grid" style="margin-top:1.5rem;">';
        echo '<div class="card card-sm"><strong>' . (int)$resumen['presente'] . '</strong><p>Presencias</p></div>';
        echo '<div class="card card-sm"><strong>' . (int)$resumen['tarde'] . '</strong><p>Atrasos</p></div>';
        echo '<div class="card card-sm"><strong>' . (int)$resumen['falta'] . '</strong><p>Faltas</p></div>';
        echo '</div>';
        echo '<h2 style="margin-top:2rem;">Historial de asistencia</h2>';
        echo '<div class="table-container"><table><thead><tr><th>Fecha</th><th>Materia / Grupo</th><th>Hora</th><th>Estado</th></tr></thead><tbody>';
        foreach ($historial as $registro) {
            echo '<tr><td>' . htmlspecialchars($registro['dia']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['materia_nombre'] . ' / ' . $registro['grupo_nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($registro['hora_marcada'] !== '' ? substr((string)$registro['hora_marcada'], 0, 5) : '--') . '</td>';
            echo '<td>' . htmlspecialchars(ucfirst((string)$registro['tipo'])) . '</td></tr>';
        }
        if (!$historial) {
            echo '<tr><td colspan="4">Aún no tienes registros de asistencia.</td></tr>';
        }
        echo '</tbody></table></div>';
        echo '<script>
        (function () {
            // El margen de 2 minutos debe coincidir con NAsistencia.php.
            var minutosTolerancia = 2;
            document.querySelectorAll(".student-attendance-form").forEach(function (form) {
                var inicio = new Date(form.getAttribute("data-start"));
                var fin = new Date(form.getAttribute("data-end"));
                var horaServidor = new Date(form.getAttribute("data-server-now"));
                var diferenciaReloj = horaServidor.getTime() - Date.now();
                var boton = form.querySelector(".attendance-submit");
                var contador = form.querySelector(".attendance-countdown");
                function actualizar() {
                    // Usa la hora del servidor para que el contador no dependa
                    // de que el reloj del equipo del estudiante esté adelantado.
                    var ahora = new Date(Date.now() + diferenciaReloj);
                    var inicioTolerancia = new Date(inicio.getTime() + minutosTolerancia * 60000);
                    var restante = Math.ceil((inicio - ahora) / 1000);
                    var restanteTolerancia = Math.ceil((inicioTolerancia - ahora) / 1000);
                    if (ahora < inicio) {
                        var minutos = Math.floor(restante / 60);
                        var segundos = restante % 60;
                        contador.textContent = "Disponible en " + minutos + ":" + String(segundos).padStart(2, "0");
                        boton.disabled = true;
                        boton.textContent = "Asistencia no iniciada";
                    } else if (ahora <= fin) {
                        boton.disabled = false;
                        boton.textContent = ahora < inicioTolerancia
                            ? "Marcar asistencia (Presente)"
                            : "Marcar asistencia (Tarde)";
                        if (ahora < inicioTolerancia) {
                            var minutosPresente = Math.floor(restanteTolerancia / 60);
                            var segundosPresente = restanteTolerancia % 60;
                            contador.textContent = "Asistencia habilitada como presente. Tiempo restante: " + minutosPresente + ":" + String(segundosPresente).padStart(2, "0");
                        } else {
                            contador.textContent = "Asistencia habilitada: se registrará como tarde";
                        }
                    } else {
                        contador.textContent = "La clase ya finalizó";
                        boton.disabled = true;
                        boton.textContent = "Asistencia cerrada";
                    }
                    }
                }
                actualizar();
                window.setInterval(actualizar, 1000);
            });
        })();
        </script></section>';
    }

    public function marcar(array $data): void {
        $resultado = $this->nAsistencia->marcarAsistenciaEstudiante(
            $this->usuarioId,
            (int)($data['clase_id'] ?? 0)
        );
        $tipo = $resultado['ok'] ? 'success' : 'danger';
        $this->dashboard((string)$resultado['message'], $tipo);
    }
}
