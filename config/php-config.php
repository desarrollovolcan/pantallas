<?php
/**
 * Configuración de PHP para el Dashboard Corporativo
 * Este archivo establece los límites de subida de archivos
 */

// Configurar límites de subida de archivos
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
ini_set('memory_limit', '256M');

// Verificar que los cambios se aplicaron
function checkUploadLimits() {
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');
    $memory = ini_get('memory_limit');
    
    return [
        'upload_max_filesize' => $upload_max,
        'post_max_size' => $post_max,
        'memory_limit' => $memory
    ];
}

// Función para convertir límites a bytes
function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int)$value;
    
    switch($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}
?>
