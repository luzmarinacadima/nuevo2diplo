<?php
require_once __DIR__ . '/IReporteGenerador.php';
require_once __DIR__ . '/ExcelWriter.php';

class ExcelReportAdapter implements IReporteGenerador {
    private ExcelWriter $excelWriter;

    public function __construct(ExcelWriter $excelWriter) {
        $this->excelWriter = $excelWriter;
    }

    public function generarReporte(string $titulo, array $datos): string {
        $this->excelWriter->createSpreadsheet();
        
        // Título centrado en la segunda columna (columna B)
        $this->excelWriter->writeRow(1, ["", strtoupper($titulo), ""]);
        $this->excelWriter->writeRow(2, ["", "", ""]); 

        if (!empty($datos)) {
            // Cabecera limpia
            $this->excelWriter->writeRow(3, ["ESTUDIANTE", "CODIGO", "ESTADO"]);

            // Filas de datos
            $rowCount = 4;
            foreach ($datos as $fila) {
                $nombreCompleto = ($fila['apellidos'] ?? '') . ' ' . ($fila['nombres'] ?? '');
                $estado = strtoupper($fila['tipo'] ?? 'PENDIENTE');
                
                $this->excelWriter->writeRow($rowCount++, [
                    $nombreCompleto,
                    $fila['codigo_estudiante'] ?? '',
                    $estado
                ]);
            }
        }
        
        return $this->excelWriter->saveXlsx();
    }
}
