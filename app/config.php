<?php
session_start();

// Configuración de la aplicación
const APP_NAME = 'Dashboard Corporativo';
const APP_VERSION = '1.0.0';

// Base URL dinámica para funcionar en subdirectorios
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$computedBasePath = rtrim(dirname($scriptPath), '/');

// Normalizar base path
if ($computedBasePath === '.' || $computedBasePath === '/') {
    $computedBasePath = '';
}

define('BASE_PATH', $computedBasePath);
define(
    'BASE_URL',
    sprintf(
        '%s://%s%s%s',
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $_SERVER['HTTP_HOST'] ?? 'localhost',
        $computedBasePath,
        $computedBasePath === '' ? '/' : ''
    )
);

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
