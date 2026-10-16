<?php
require_once __DIR__ . '/../../capa_datos/lib/fpdf.php';

class PdfGenerator {
    private $pdf;
    private $html = "";
    private $paperSize = "A4";
    private $orientation = "P";

    public function __construct() {
        $this->pdf = new \FPDF();
    }

    public function loadHtml(string $html): void {
        $this->html = $html;
    }

    public function setPaper(string $size, string $orientation): void {
        $this->paperSize = (strtoupper($size) === 'A4') ? 'A4' : 'Letter';
        $this->orientation = (strtolower($orientation) === 'landscape') ? 'L' : 'P';
    }

    public function render(): void {
        if (empty($this->html)) return;

        // Configuración real del objeto FPDF basada en setPaper
        $this->pdf = new \FPDF($this->orientation, 'mm', $this->paperSize);
        $this->pdf->AddPage();
        
        $html = $this->html;
        
        // Extraer título
        if (preg_match('/<h1>(.*?)<\/h1>/', $html, $matches)) {
            $this->pdf->SetFont('Arial', 'B', 18);
            $this->pdf->SetTextColor(33, 97, 140);
            $this->pdf->Cell(0, 15, utf8_decode($matches[1]), 0, 1, 'C');
            $this->pdf->Ln(5);
        }

        // Configuración de tabla: ESTUDIANTE (100), CODIGO (40), ESTADO (45)
        $w = [100, 40, 45]; 
        
        // Extraer celdas
        preg_match_all('/<th>(.*?)<\/th>|<td>(.*?)<\/td>/', $html, $matches);
        $allCells = array_map(fn($m1, $m2) => !empty($m1) ? $m1 : $m2, $matches[1], $matches[2]);
        
        if (!empty($allCells)) {
            // Cabecera
            $this->pdf->SetFillColor(33, 97, 140);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFont('Arial', 'B', 12);
            for ($i = 0; $i < 3; $i++) {
                $this->pdf->Cell($w[$i], 12, utf8_decode($allCells[$i]), 1, 0, 'C', true);
            }
            $this->pdf->Ln();

            // Datos
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont('Arial', '', 11);
            $fill = false;
            
            for ($i = 3; $i < count($allCells); $i += 3) {
                if ($i + 2 >= count($allCells)) break;

                $this->pdf->SetFillColor($fill ? 245 : 255, $fill ? 247 : 255, $fill ? 254 : 255);
                
                $nombreCompleto = $allCells[$i];
                $codigo = $allCells[$i+1];
                $estado = strtoupper(trim($allCells[$i+2]));

                $this->pdf->Cell($w[0], 10, utf8_decode($nombreCompleto), 1, 0, 'C', true);
                $this->pdf->Cell($w[1], 10, utf8_decode($codigo), 1, 0, 'C', true);
                
                // Color según estado
                if ($estado === 'PRESENTE') {
                    $this->pdf->SetTextColor(40, 167, 69);
                } elseif ($estado === 'TARDE') {
                    $this->pdf->SetTextColor(255, 128, 0); // Naranja para Tarde
                } elseif ($estado === 'FALTA' || $estado === 'AUSENTE') {
                    $this->pdf->SetTextColor(220, 53, 69);
                } elseif ($estado === 'LICENCIA') {
                    $this->pdf->SetTextColor(40, 116, 166); // Color para Licencia
                }
                
                $this->pdf->SetFont('Arial', 'B', 11);
                $this->pdf->Cell($w[2], 10, utf8_decode($estado), 1, 1, 'C', true);
                
                $this->pdf->SetTextColor(0, 0, 0);
                $this->pdf->SetFont('Arial', '', 11);
                $fill = !$fill;
            }
        }
    }

    public function output(): string {
        return $this->pdf->Output('S'); 
    }
}
