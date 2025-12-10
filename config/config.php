<?php
/**
 * Configuración general del sistema
 */
session_start();

// Configuración de la aplicación
define('APP_NAME', 'Dashboard Corporativo');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'corporativo_dashboard');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Configuración de la API del clima (OpenWeatherMap)
define('WEATHER_API_KEY', '5622cfa63c405ee8247fcc9bff4a1738'); // Reemplazar con tu API key
define('WEATHER_API_URL', 'https://api.openweathermap.org/data/2.5/weather');

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos

// Incluir configuración de PHP para límites de archivos
require_once 'php-config.php';

// Incluir archivos necesarios
require_once 'database.php';

/**
 * Función para verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_usuario']);
}

/**
 * Función para redirigir si no está autenticado
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Función para generar hash de contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Función para verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Función para sanitizar entrada
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Función para generar respuesta JSON
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Función para generar una nueva contraseña aleatoria
 */
function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
    $password = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $max)];
    }
    return $password;
}

/**
 * Función para recuperar contraseña y enviarla por email
 */
function resetAdminPassword($usuario) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Buscar el administrador por usuario
        $query = "SELECT id, nombre, email FROM administradores WHERE usuario = :usuario AND activo = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Usuario no encontrado o inactivo'];
        }
        
        $admin = $stmt->fetch();
        
        // Generar nueva contraseña
        $nuevaContrasena = generateRandomPassword(12);
        $hashContrasena = hashPassword($nuevaContrasena);
        
        // Actualizar contraseña en la base de datos
        $updateQuery = "UPDATE administradores SET contrasena = :contrasena WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':contrasena', $hashContrasena);
        $updateStmt->bindParam(':id', $admin['id']);
        
        if (!$updateStmt->execute()) {
            return ['success' => false, 'message' => 'Error al actualizar la contraseña'];
        }
        
        // Enviar correo electrónico
        $to = 'sergioortegac@gmail.com';
        $subject = 'Recuperación de Contraseña - Dashboard Corporativo';
        $message = "Estimado/a " . htmlspecialchars($admin['nombre']) . ",\n\n";
        $message .= "Se ha generado una nueva contraseña para tu cuenta de administrador.\n\n";
        $message .= "Usuario: " . htmlspecialchars($usuario) . "\n";
        $message .= "Nueva contraseña: " . $nuevaContrasena . "\n\n";
        $message .= "Por favor, cambia esta contraseña después de iniciar sesión por seguridad.\n\n";
        $message .= "Si no solicitaste este cambio, contacta al administrador del sistema.\n\n";
        $message .= "Saludos,\nSistema de Dashboard Corporativo";
        
        $headers = "From: noreply@dashboard.local\r\n";
        $headers .= "Reply-To: noreply@dashboard.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $mailSent = mail($to, $subject, $message, $headers);
        
        if ($mailSent) {
            return ['success' => true, 'message' => 'Nueva contraseña generada y enviada por correo electrónico'];
        } else {
            // La contraseña ya se actualizó, pero el correo falló
            return ['success' => true, 'message' => 'Nueva contraseña generada. Error al enviar correo: ' . $nuevaContrasena];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()];
    }
}

/**
 * Función para obtener datos de clima desde OpenWeatherMap API
 */
function getWeatherData($lat, $lon, $cityName = '') {
    $apiKey = WEATHER_API_KEY;
    $url = WEATHER_API_URL . "?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric&lang=es";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        
        if ($data && isset($data['main']) && isset($data['weather'][0])) {
            // Obtener el código del clima (más confiable que la descripción)
            $weatherMain = isset($data['weather'][0]['main']) ? strtolower($data['weather'][0]['main']) : '';
            $weatherId = isset($data['weather'][0]['id']) ? (int)$data['weather'][0]['id'] : 0;
            $description = isset($data['weather'][0]['description']) ? $data['weather'][0]['description'] : '';
            
            // Guardar la descripción limpia (sin el código) pero incluir el código main separado
            // El código main se guarda en la descripción en formato oculto para uso interno
            $descripcion_limpia = ucfirst($description);
            
            return [
                'temperatura' => round($data['main']['temp']),
                'descripcion' => $descripcion_limpia,
                'humedad' => $data['main']['humidity'],
                'velocidad_viento' => round($data['wind']['speed'] * 3.6), // Convertir m/s a km/h
                'ciudad' => $cityName ?: $data['name'],
                'weather_main' => $weatherMain, // Código principal del clima (clear, clouds, rain, etc.) - solo para uso interno
                'weather_id' => $weatherId, // ID numérico del clima
                'error' => false
            ];
        }
    }
    
    // En caso de error, retornar datos simulados realistas
    return getRealisticWeatherData($lat, $lon, $cityName);
}

/**
 * Función para generar datos de clima simulados pero realistas
 */
function getRealisticWeatherData($lat, $lon, $cityName = '') {
    $hora = (int)date('H');
    $mes = (int)date('n');
    
    // Ajustar temperatura base según la estación (hemisferio norte)
    $tempBase = 15;
    if ($mes >= 12 || $mes <= 2) { // Invierno
        $tempBase = 8;
    } elseif ($mes >= 3 && $mes <= 5) { // Primavera
        $tempBase = 15;
    } elseif ($mes >= 6 && $mes <= 8) { // Verano
        $tempBase = 25;
    } else { // Otoño
        $tempBase = 18;
    }
    
    // Variación por hora del día
    $variacionHora = 0;
    if ($hora >= 6 && $hora <= 8) { // Amanecer
        $variacionHora = -3;
    } elseif ($hora >= 12 && $hora <= 16) { // Mediodía
        $variacionHora = 5;
    } elseif ($hora >= 20 && $hora <= 22) { // Atardecer
        $variacionHora = 2;
    } elseif ($hora >= 23 || $hora <= 5) { // Noche
        $variacionHora = -5;
    }
    
    // Variación por ubicación (simulada)
    $variacionLugar = sin($lat) * 5 + cos($lon) * 3;
    
    $temperatura = round($tempBase + $variacionHora + $variacionLugar + rand(-2, 2));
    
    // Condiciones climáticas basadas en temperatura y hora
    $condiciones = [];
    if ($temperatura > 25) {
        $condiciones = ['Soleado', 'Parcialmente nublado', 'Despejado'];
    } elseif ($temperatura > 15) {
        $condiciones = ['Parcialmente nublado', 'Nublado', 'Soleado'];
    } elseif ($temperatura > 5) {
        $condiciones = ['Nublado', 'Lluvia ligera', 'Parcialmente nublado'];
    } else {
        $condiciones = ['Nublado', 'Lluvia', 'Niebla'];
    }
    
    $descripcion = $condiciones[array_rand($condiciones)];
    
    // Humedad basada en condiciones
    $humedad = 50;
    if (strpos($descripcion, 'Lluvia') !== false) {
        $humedad = rand(70, 90);
    } elseif (strpos($descripcion, 'Nublado') !== false) {
        $humedad = rand(60, 80);
    } else {
        $humedad = rand(30, 60);
    }
    
    // Viento basado en condiciones
    $velocidad_viento = rand(5, 15);
    if (strpos($descripcion, 'Lluvia') !== false) {
        $velocidad_viento = rand(15, 25);
    }
    
    return [
        'temperatura' => $temperatura,
        'descripcion' => $descripcion,
        'humedad' => $humedad,
        'velocidad_viento' => $velocidad_viento,
        'ciudad' => $cityName ?: 'Ubicación',
        'error' => true // Marcar como simulado
    ];
}
?>
