<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$sql = file_get_contents(__DIR__ . '/install.sql');
if ($sql === false) {
    fwrite(STDERR, "No se pudo leer install.sql.\n");
    exit(1);
}

$body = preg_replace('/^CREATE DATABASE[^;]*;\R^USE [^;]*;\R/m', '', $sql, 1, $removed);
if ($body === null || $removed !== 1 || preg_match('/^\s*(?:CREATE\s+(?:DATABASE|SCHEMA)|USE)\b/im', $body)) {
    fwrite(STDERR, "El instalador cambió. Revisá sus instrucciones de esquema antes de exportarlo.\n");
    exit(1);
}

$start = strpos($body, 'SET NAMES utf8mb4;');
if ($start === false) {
    fwrite(STDERR, "No se encontró el inicio del esquema integrado.\n");
    exit(1);
}

echo "-- SEDEMA | Instalación limpia en la base seleccionada por el usuario.\n";
echo "-- Seleccioná la base vacía asignada por el hosting antes de ejecutar.\n";
echo substr($body, $start);
