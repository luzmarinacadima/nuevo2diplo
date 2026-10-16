<?php

interface IReporteGenerador {
    /**
     * @param string $titulo
     * @param array $datos
     * @return string (Retorna el contenido del archivo como string o binario)
     */
    public function generarReporte(string $titulo, array $datos): string;
}
