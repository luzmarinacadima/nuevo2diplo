<?php
require_once __DIR__ . '/capa_negocio/adapter/PdfReportAdapter.php';
require_once __DIR__ . '/capa_negocio/adapter/ExcelReportAdapter.php';
require_once __DIR__ . '/capa_negocio/adapter/PdfGenerator.php';
require_once __DIR__ . '/capa_negocio/adapter/ExcelWriter.php';

echo "Probando Patron Adapter...\n\n";

$datos = [
    ['codigo' => '219001', 'nombre' => 'Juan Perez', 'estado' => 'Presente'],
    ['codigo' => '219002', 'nombre' => 'Maria Gomez', 'estado' => 'Falta']
];

// 1. Probar PDF Adapter
echo "Generando reporte PDF con Adapter:\n";
$pdfAdapter = new PdfReportAdapter(new PdfGenerator());
$resultadoPdf = $pdfAdapter->generarReporte("Lista de Asistencia", $datos);
echo $resultadoPdf . "\n\n";

// 2. Probar Excel Adapter
echo "Generando reporte Excel con Adapter:\n";
$excelAdapter = new ExcelReportAdapter(new ExcelWriter());
$resultadoExcel = $excelAdapter->generarReporte("Lista de Asistencia", $datos);
echo $resultadoExcel . "\n";

echo "\nPrueba finalizada con éxito.";
