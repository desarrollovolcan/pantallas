<?php
session_start();

// Rutas base calculadas contra el document root real
$projectRoot = realpath(__DIR__ . '/..');
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? $projectRoot);

$relativeBase = '';
if ($projectRoot && $documentRoot && str_starts_with($projectRoot, $documentRoot)) {
    $relativeBase = trim(str_replace($documentRoot, '', $projectRoot), DIRECTORY_SEPARATOR);
}

$normalizedBase = $relativeBase === ''
    ? ''
    : '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativeBase);

define('BASE_PATH', $normalizedBase);
define(
    'BASE_URL',
    sprintf(
        '%s://%s%s%s',
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $_SERVER['HTTP_HOST'] ?? 'localhost',
        BASE_PATH,
        BASE_PATH === '' ? '/' : ''
    )
);

// Configuración de la aplicación
const APP_NAME = 'Dashboard Corporativo';
const APP_VERSION = '1.0.0';

// Configuración de la base de datos
const DB_HOST = 'localhost';
const DB_NAME = 'adlinksc1_impa';
const DB_USER = 'adlinksc1_impa';
const DB_PASS = 'vorqyw-Mygkis-0cuqja';

// Configuración adicional
const SESSION_TIMEOUT = 3600; // 1 hora

// API del clima
const WEATHER_API_KEY = '5622cfa63c405ee8247fcc9bff4a1738';
const WEATHER_API_URL = 'https://api.openweathermap.org/data/2.5/weather';

// Configuración de PHP para manejo de archivos grandes
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
?>
