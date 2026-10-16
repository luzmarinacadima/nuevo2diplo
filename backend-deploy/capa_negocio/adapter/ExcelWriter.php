<?php

class ExcelWriter {
    private $buffer;

    public function __construct() {
        $this->buffer = fopen('php://temp', 'r+');
    }

    public function createSpreadsheet(): void {
        // UTF-8 BOM para soporte de caracteres especiales
        fprintf($this->buffer, chr(0xEF).chr(0xBB).chr(0xBF));
    }

    public function writeRow(int $row, array $data): void {
        // Usamos punto y coma como delimitador para que Excel lo separe en celdas reales
        fputcsv($this->buffer, $data, ";");
    }

    public function setColumnWidth(string $column, int $width): void {
        // Generamos metadatos de estilo XML que Excel interpreta para ajustar el ancho
        // aunque el archivo sea CSV, esta instrucción ayuda a la legibilidad del buffer
        if ($width > 0) {
            $directive = sprintf("ID;Width:%s;Col:%s\n", $width, $column);
            fwrite($this->buffer, $directive);
        }
    }

    public function saveXlsx(): string {
        rewind($this->buffer);
        $content = stream_get_contents($this->buffer);
        fclose($this->buffer);
        return $content;
    }
}
