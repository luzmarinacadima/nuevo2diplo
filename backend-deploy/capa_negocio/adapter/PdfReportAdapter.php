<?php
require_once __DIR__ . '/IReporteGenerador.php';
require_once __DIR__ . '/PdfGenerator.php';

class PdfReportAdapter implements IReporteGenerador {
    private PdfGenerator $pdfGen;

    public function __construct(PdfGenerator $pdfGen) {
        $this->pdfGen = $pdfGen;
    }

    public function generarReporte(string $titulo, array $datos): string {
        $html = "<h1>" . strtoupper(htmlspecialchars($titulo)) . "</h1>";
        $html .= "<table border='1'>";
        
        // Nueva disposición: ESTUDIANTE, CODIGO, ESTADO
        $html .= "<tr><th>ESTUDIANTE</th><th>CODIGO</th><th>ESTADO</th></tr>";

        if (!empty($datos)) {
            // Filas
            foreach ($datos as $fila) {
                $nombreCompleto = ($fila['apellidos'] ?? '') . ' ' . ($fila['nombres'] ?? '');
                $estado = strtoupper($fila['tipo'] ?? 'PENDIENTE');

                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($nombreCompleto) . "</td>";
                $html .= "<td>" . htmlspecialchars($fila['codigo_estudiante'] ?? '') . "</td>";
                $html .= "<td>" . htmlspecialchars($estado) . "</td>";
                $html .= "</tr>";
            }
        }
        $html .= "</table>";

        $this->pdfGen->loadHtml($html);
        $this->pdfGen->setPaper("A4", "portrait");
        $this->pdfGen->render();
        return $this->pdfGen->output();
    }
}
