<?php
require_once 'config/config.php';
// Obtener datos para el dashboard
$database = new Database();
$db = $database->getConnection();

// Obtener videos corporativos
try {
    $query = "SELECT * FROM videos_corporativos WHERE activo = 1 ORDER BY orden ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $videos = $stmt->fetchAll();
} catch (Exception $e) {
    $videos = [];
}

// Obtener datos de clima
try {
    $query = "SELECT u.*, d.temperatura, d.descripcion, d.humedad, d.velocidad_viento 
              FROM ubicaciones_clima u 
              LEFT JOIN datos_clima d ON u.id = d.ubicacion_id 
              WHERE u.activo = 1 
              ORDER BY u.orden ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $ubicaciones_clima = $stmt->fetchAll();
    // Nota: Mantenemos el código [main:...] en los datos para el mapeo de íconos
    // Se limpiará solo al mostrar en pantalla
} catch (Exception $e) {
    $ubicaciones_clima = [];
}

// Función helper para limpiar descripción visible (remover código [main:...])
function limpiarDescripcion($descripcion) {
    if (empty($descripcion)) {
        return $descripcion;
    }
    return preg_replace('/\s*\[main:[^\]]+\]/i', '', $descripcion);
}

// Función para obtener el icono del clima basado en descripción de la API
function getWeatherIcon($descripcion) {
    if (empty($descripcion)) {
        return 'cloud-sun';
    }
    
    $descripcion_lower = strtolower(trim($descripcion));
    
    // Extraer el código main si está presente en el formato "[main:codigo]"
    $weatherMain = null;
    if (preg_match('/\[main:([^\]]+)\]/', $descripcion_lower, $matches)) {
        $weatherMain = trim($matches[1]);
    }
    
    // Primero intentar usar el código principal de la API (más confiable)
    if (!empty($weatherMain)) {
        switch ($weatherMain) {
            case 'clear':
                return 'sun';
            case 'clouds':
                return 'cloud';
            case 'rain':
            case 'drizzle':
                return 'cloud-rain';
            case 'thunderstorm':
                return 'bolt';
            case 'snow':
                return 'snowflake';
            case 'mist':
            case 'fog':
            case 'haze':
            case 'dust':
            case 'sand':
                return 'smog';
            default:
                break; // Continuar con el mapeo por descripción
        }
    }
    
    // Fallback: mapeo por descripción en español/inglés
    // Cielo despejado / Clear
    if (strpos($descripcion_lower, 'despejado') !== false || 
        strpos($descripcion_lower, 'clear') !== false || 
        strpos($descripcion_lower, 'soleado') !== false ||
        strpos($descripcion_lower, 'sunny') !== false) {
        return 'sun';
    }
    
    // Nubes / Clouds
    if (strpos($descripcion_lower, 'nube') !== false || 
        strpos($descripcion_lower, 'cloud') !== false ||
        strpos($descripcion_lower, 'nublado') !== false ||
        strpos($descripcion_lower, 'overcast') !== false) {
        return 'cloud';
    }
    
    // Lluvia / Rain / Drizzle
    if (strpos($descripcion_lower, 'lluvia') !== false || 
        strpos($descripcion_lower, 'rain') !== false || 
        strpos($descripcion_lower, 'llovizna') !== false ||
        strpos($descripcion_lower, 'drizzle') !== false ||
        strpos($descripcion_lower, 'chubasco') !== false ||
        strpos($descripcion_lower, 'shower') !== false) {
        return 'cloud-rain';
    }
    
    // Tormenta / Thunderstorm
    if (strpos($descripcion_lower, 'tormenta') !== false || 
        strpos($descripcion_lower, 'thunder') !== false || 
        strpos($descripcion_lower, 'storm') !== false ||
        strpos($descripcion_lower, 'rayo') !== false ||
        strpos($descripcion_lower, 'bolt') !== false ||
        strpos($descripcion_lower, 'lightning') !== false) {
        return 'bolt';
    }
    
    // Nieve / Snow
    if (strpos($descripcion_lower, 'nieve') !== false || 
        strpos($descripcion_lower, 'snow') !== false ||
        strpos($descripcion_lower, 'nevando') !== false) {
        return 'snowflake';
    }
    
    // Niebla / Mist / Fog / Haze
    if (strpos($descripcion_lower, 'niebla') !== false || 
        strpos($descripcion_lower, 'mist') !== false || 
        strpos($descripcion_lower, 'fog') !== false ||
        strpos($descripcion_lower, 'haze') !== false ||
        strpos($descripcion_lower, 'bruma') !== false ||
        strpos($descripcion_lower, 'dust') !== false ||
        strpos($descripcion_lower, 'sand') !== false) {
        return 'smog';
    }
    
    // Por defecto
    return 'cloud-sun';
}

// Obtener próximos cumpleaños (máximo 3)
// Función helper para calcular días hasta el cumpleaños
function getDaysUntilBirthday($fecha_nacimiento) {
    $fecha_actual = new DateTime();
    $fecha_nac = new DateTime($fecha_nacimiento);
    
    // Obtener el próximo cumpleaños
    $cumpleanos_este_año = new DateTime($fecha_actual->format('Y') . '-' . $fecha_nac->format('m-d'));
    
    if ($cumpleanos_este_año < $fecha_actual) {
        // Si el cumpleaños de este año ya pasó, usar el del próximo año
        $cumpleanos_este_año->add(new DateInterval('P1Y'));
    }
    
    $diferencia = $fecha_actual->diff($cumpleanos_este_año);
    return $diferencia->days;
}

try {
    // Obtener todos los cumpleaños activos
    $query = "SELECT *, 
              DATE_FORMAT(fecha_nacimiento, '%d') as dia,
              DATE_FORMAT(fecha_nacimiento, '%m') as mes,
              DATE_FORMAT(fecha_nacimiento, '%M') as mes_nombre
              FROM cumpleanos 
              WHERE activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $todos_cumpleanos = $stmt->fetchAll();
    
    // Calcular días restantes para cada cumpleaños y ordenar
    foreach ($todos_cumpleanos as &$cumpleano) {
        $cumpleano['dias_restantes'] = getDaysUntilBirthday($cumpleano['fecha_nacimiento']);
    }
    unset($cumpleano);
    
    // Ordenar por días restantes (ascendente) y luego por día del mes
    usort($todos_cumpleanos, function($a, $b) {
        if ($a['dias_restantes'] == $b['dias_restantes']) {
            return $a['dia'] - $b['dia'];
        }
        return $a['dias_restantes'] - $b['dias_restantes'];
    });
    
    // Tomar solo los primeros 3
    $cumpleanos = array_slice($todos_cumpleanos, 0, 3);
} catch (Exception $e) {
    $cumpleanos = [];
}

// Obtener eventos activos
try {
    $query = "SELECT *, 
              DATE_FORMAT(fecha_evento, '%d/%m/%Y %H:%i') as fecha_formateada,
              DATEDIFF(fecha_evento, NOW()) as dias_restantes
              FROM eventos 
              WHERE activo = 1
              ORDER BY fecha_creacion DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $eventos = $stmt->fetchAll();
} catch (Exception $e) {
    $eventos = [];
}

// Obtener campaña QR principal activa
try {
    $query = "SELECT * FROM campanas_qr 
              WHERE activo = 1 AND activa_principal = 1 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $campana_principal = $stmt->fetch();
} catch (Exception $e) {
    $campana_principal = null;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, rgb(175, 111, 203) 0%, rgb(242, 193, 153) 100%);
            --card-gradient: linear-gradient(140deg, rgba(255, 255, 255, 0.94), rgba(255, 255, 255, 0.82));
            --accent-blue: #3c5bff;
            --accent-purple: #7b2ff7;
            --accent-orange: #ff7a2f;
            --text-primary: #1b1b35;
            --text-secondary: #5a5c74;
        }
        body {
            background: var(--bg-gradient);
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Sin scroll */
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .dashboard-container {
            position: relative;
            width: min(98vw, 1860px);
            height: min(96vh, 1040px);
            padding: 24px;
            border-radius: 30px;
            background: rgba(13, 18, 43, 0.12);
            box-shadow: 0 28px 60px rgba(10, 10, 40, 0.22), inset 0 0 0 5px rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(18px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .admin-float-button {
            position: absolute;
            top: 24px;
            right: 32px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(14px);
            color: white;
            padding: 12px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.26);
            font-size: 1.1rem;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.18);
        }
        
        .admin-float-button:hover {
            background: rgba(255, 255, 255, 0.26);
            transform: translateY(-3px);
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 0.75fr 2.2fr;
            gap: 24px;
            flex: 1;
            min-height: 0;
            margin-top: 14px;
        }
        
        /* Columna izquierda */
        .left-column {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            gap: 20px;
            min-height: 0;
        }
        
        .left-column > * {
            flex-shrink: 0;
        }
        
        .left-column > .weather-section {
            flex: 0 0 auto;
        }
        
        .left-column > .birthdays-section {
            flex: 0 0 auto;
        }
        
        .logo-section {
            background: transparent;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 12px 24px rgba(23, 32, 60, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 184px;
            flex: 0 0 auto;
        }
        
        .logo-section img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .glass-panel {
            background: var(--card-gradient);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 22px 38px rgba(17, 25, 40, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.65);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .glass-panel.alt {
            background: linear-gradient(150deg, rgba(255, 255, 255, 0.88), rgba(255, 255, 255, 0.7) 70%, rgba(255, 255, 255, 0.55));
        }

        .weather-section {
            position: relative;
            background: #ffffff !important;
        }
        
        .weather-section.glass-panel {
            background: #ffffff !important;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .birthdays-section {
            background: linear-gradient(180deg, rgb(74, 130, 173) 0%, rgb(18, 22, 81) 100%);
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 30px 52px rgba(13, 28, 100, 0.32);
            color: #ffffff;
        }
        
        .birthdays-section .section-title {
            color: #ffffff;
            font-size: 2.025rem;
        }
        
        .birthdays-section .section-title i {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            padding: 0;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .birthdays-section .section-title i::before {
            display: block;
        }

        .birthdays-section .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .weather-cards-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            height: 100%;
        }
        
        .weather-card {
            text-align: center;
            padding: 20px 16px;
            transition: opacity 0.5s ease-in-out;
            border-radius: 18px;
            background: linear-gradient(500deg, rgba(248, 247, 253, 0.9), rgba(237, 236, 251, 1));
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .current-weather {
            text-align: center;
            margin-bottom: 0;
            padding: 30px 24px;
            transition: opacity 0.5s ease-in-out;
            border-radius: 18px;
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.78), rgba(220, 226, 255, 0.62));
            border: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow: inset 0 18px 32px rgba(255, 255, 255, 0.22), 0 18px 32px rgba(35, 36, 84, 0.18);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .weather-card .weather-icon,
        .current-weather .weather-icon {
            font-size: 2.2rem;
            color: rgb(48, 82, 130)
            margin-bottom: 10px;
            display: block;
            transition: all 0.5s ease-in-out;
        }
        
        .weather-card .current-temp,
        .current-weather .current-temp {
            font-size: 2.4rem;
            font-weight: 700;
            color: rgb(48, 82, 130);
            margin-bottom: 4px;
            line-height: 1;
            transition: all 0.5s ease-in-out;
        }
        
        .weather-card .current-condition,
        .current-weather .current-condition {
            font-size: 0.95rem;
            color: rgb(48, 82, 130);
            font-weight: 500;
            margin-bottom: 0;
            transition: all 0.5s ease-in-out;
        }
        
        .weather-card .current-location,
        .current-weather .current-location {
            font-size: 1.2rem;
            color: rgb(48, 82, 130);
            font-weight: 500;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin-bottom: 12px;
            transition: all 0.5s ease-in-out;
        }
        
        .weather-icon {
            font-size: 2.9rem;
            color: rgb(48, 82, 130);
            margin-bottom: 13px;
            display: block;
            transition: all 0.5s ease-in-out;
        }
        
        .current-temp {
            font-size: 3.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            line-height: 1;
            transition: all 0.5s ease-in-out;
        }
        
        .current-condition {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0;
            transition: all 0.5s ease-in-out;
        }
        
        .current-location {
            font-size: 1rem;
            color: rgba(0, 0, 0, 0.52);
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin-bottom: 16px;
            transition: all 0.5s ease-in-out;
        }
        
        #events-container {
            transition: opacity 0.5s ease-in-out;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            position: relative;
        }
        
        .events-grid::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 1px;
            background: #a0c4ff;
            transform: translateX(-50%);
        }
        
        .events-grid > .event-card:first-child {
            padding-right: 24px;
        }
        
        .events-grid > .event-card:last-child {
            padding-left: 24px;
        }
        
        /* Indicador de progreso circular para clima */
        .weather-section {
            position: relative; /* Para posicionar el spinner */
        }
        
        .weather-progress-container {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            z-index: 10;
        }
        
        .weather-progress-circle {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg); /* Empezar desde arriba */
        }
        
        .progress-bg {
            stroke: rgba(102, 126, 234, 0.2);
            stroke-width: 2;
        }
        
        .weather-progress {
            stroke: #667eea;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-dasharray: 85; /* Circunferencia del círculo (2 * π * 13.5) */
            stroke-dashoffset: 85; /* Empieza sin mostrar */
            transition: stroke-dashoffset 0.1s linear;
        }
        
        /* Indicador de progreso circular para eventos */
        .events-section {
            position: relative; /* Para posicionar el spinner */
        }
        
        .events-progress-container {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            z-index: 10;
        }
        
        .events-progress-circle {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .events-progress-bg {
            stroke: rgba(102, 126, 234, 0.2);
            stroke-width: 2;
        }
        
        .events-progress {
            stroke: #667eea;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-dasharray: 85; /* Circunferencia del círculo (2 * π * 13.5) */
            stroke-dashoffset: 85;
            transition: stroke-dashoffset 0.1s linear;
        }
        
        
        .right-column {
            display: grid;
            grid-template-rows: 2.8fr 1fr;
            gap: 20px;
            min-height: 0;
        }
        
        .video-section {
            background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.98) 0%, rgba(222, 229, 255, 0.82) 70%, rgba(211, 222, 255, 0.65) 100%);
            border-radius: 28px;
            padding: 0;
            box-shadow: 0 30px 56px rgba(15, 24, 54, 0.24);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            min-height: 0;
        }
        
        .events-section {
            background: #ffffff;
            border-radius: 24px;
            padding: 16px 20px;
            box-shadow: 0 12px 24px rgba(23, 32, 60, 0.14);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .qr-section {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 24px;
            padding: 16px 20px;
            box-shadow: 0 26px 44px rgba(0, 0, 0, 0.32);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.35);
            display: flex;
            flex-direction: column;
        }
        
        .qr-campaign {
            transition: opacity 0.5s ease-in-out; /* Transición suave para rotación */
            text-align: center;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 0.01em;
        }
        
        .events-section .section-title,
        .qr-section .section-title {
            margin-bottom: 8px;
            font-size: 1.2rem;
        }
        
        .section-title i {
            font-size: 2rem;
            color: inherit;
            background: rgba(255, 255, 255, 0.18);
            padding: 10px;
            border-radius: 14px;
        }
        
        .video-player {
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            border-radius: 22px;
            overflow: hidden;
            position: relative;
            min-height: 0;
        }
        
        .video-player video {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Mantiene la proporción del video sin recortar */
            max-height: 100%; /* No se desborda del contenedor */
        }
        
        .video-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            text-align: center;
        }
        
        
        .event-card {
            background: transparent;
            border-radius: 0;
            padding: 0;
            margin-bottom: 0;
            border: none;
            box-shadow: none;
        }
        
        .event-title {
            font-weight: 200;
            color: rgb(48, 82, 130);
            margin-bottom: 0px;
            letter-spacing: 0;
            font-size: 1.5rem;
            padding:  0px 50px 0px 50px;
        }
        
        .event-date {
            color: rgb(48, 82, 130);
            font-size: 0.9rem;
            margin-bottom: 20px;
            font-weight: 900;
            padding:  0px 50px 0px 50px;
        }
        
        .event-description {
            color: rgb(48, 82, 130);
            font-size: 0.85rem;
            font-weight: 400;
            line-height: 1.4;
            padding:  0px 50px 0px 50px;
        }
        
        .event-countdown {
            display: flex;
            gap: 10px;
        }
        
        .countdown-box {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: white;
            padding: 10px 12px;
            border-radius: 12px;
            text-align: center;
            min-width: 58px;
        }
        
        .countdown-number {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .countdown-label {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        .qr-campaign {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 0;
            background: transparent;
            border-radius: 0;
            margin-bottom: 0;
            border: none;
        }
        
        .qr-code {
            margin: 0;
            flex-shrink: 0;
        }
        
        .qr-code img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 0;
            box-shadow: none;
            background: #2d2d2d;
            padding: 8px;
            margin: 0;
        }
        
        .qr-section .section-title {
            color: #ffffff;
        }
        
        .qr-section .section-title i {
            color: rgba(255, 255, 255, 0.88);
            background: rgba(255, 255, 255, 0.22);
        }
        
        .qr-campaign-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
        }
        
        .qr-title {
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            font-size: 1.1rem;
            text-align: left;
        }
        
        .qr-description {
            color: #ffffff;
            font-size: 1rem;
            font-weight: 500;
            text-align: left;
            line-height: 1.4;
        }
        
        .birthday-item {
            display: flex;
            align-items: center;
            padding: 2px 20px;
            background: transparent;
            border-radius: 0;
            margin-bottom: 12px;
            border: none;
            box-shadow: none;
        }
        
        .birthday-date {
            background: rgb(91, 193, 249);
            color: #ffffff;
            border-radius: 50%;
            margin-right: 20px;
            width: 85px;
            height: 85px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: none;
            flex-shrink: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .birthday-date .text-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        
        .birthday-date .text-center > div:first-child {
            font-size: 2.1rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1;
            color: #ffffff;
            margin-bottom: -10px;
        }
        
        .birthday-date .text-center small {
            font-size: 1.7rem;
            font-weight: 900;
            letter-spacing: 0em;
            color: #ffffff;
            text-transform: uppercase;
            opacity: 1;
        }
        
        .birthday-info {
            flex: 1;
        }
        
        .birthday-info h6 {
            margin: 0;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0;
            margin-bottom: 0px;
            font-size: 1.45rem;
            line-height: 1;
        }
        
        .birthday-info small {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 400;
            opacity: 1;
        }
        
        @media (max-width: 1366px) {
            .dashboard-container {
                width: 94vw;
                height: 92vh;
                padding: 22px;
            }
            .main-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                overflow-y: auto;
            }
            .right-column {
                grid-template-rows: auto auto;
            }
        }
        .events-title {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1;
            color: rgb(48, 82, 130);
        }
        .qr-title {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1;
            color: #fff;
        }
        .weather-title {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1;
            color: rgb(48, 82, 130);
        }
        .birthdays-title {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1;
            color: #fff;
        }
        .birthday-info .birthday-cargo {
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 0;
            line-height: 1;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <a href="login.php" class="admin-float-button">
            <i class="fas fa-cog"></i>
        </a>
        <div class="main-grid">
            <!-- Columna Izquierda -->
            <div class="left-column">
                <!-- Logo Section -->
                <div class="logo-section">
                    <img src="uploads/logo/logo.png" alt="Logo" onerror="this.style.display='none'">
                </div>
                
                <!-- Weather Section -->
                <div class="glass-panel weather-section">
                    <!-- Indicador de progreso circular para clima -->
                    <div class="weather-progress-container">
                        <svg class="weather-progress-circle" width="30" height="30">
                            <circle class="progress-bg" cx="15" cy="15" r="13.5" fill="none"/>
                            <circle class="progress-bar weather-progress" cx="15" cy="15" r="13.5" fill="none"/>
                        </svg>
                    </div>
                    
                    <div class="section-title">
                        <i class="fas fa-cloud-sun" style="color: rgb(48, 82, 130);"></i>
                        <h4 class="weather-title">Clima Regional</h4>
                    </div>
                    
                    <?php if (!empty($ubicaciones_clima)): ?>
                        <div class="weather-cards-container">
                            <?php 
                            // Mostrar las primeras 2 ubicaciones
                            $weather_locations = array_slice($ubicaciones_clima, 0, 2);
                            foreach ($weather_locations as $location): 
                            ?>
                                <div class="weather-card">
                                    <div class="current-location">
                                        <?php echo htmlspecialchars($location['nombre']); ?>
                                    </div>
                                    <i class="fas fa-<?php echo getWeatherIcon($location['descripcion'] ?? ''); ?> weather-icon"></i>
                                    <div class="current-temp">
                                        <?php echo $location['temperatura'] ?? '--'; ?>°C
                                    </div>
                                    <div class="current-condition">
                                        <?php echo htmlspecialchars(limpiarDescripcion($location['descripcion'] ?? 'Sin datos')); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-cloud-sun fa-3x mb-3"></i>
                            <p>Sin datos de clima disponibles</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Birthdays Section -->
                <div class="glass-panel birthdays-section">
                    <div class="section-title">
                        <i class="fas fa-birthday-cake"></i>
                        <h4 class="birthdays-title">Cumpleaños</h4>
                    </div>
                    
                    <?php if (!empty($cumpleanos)): ?>
                        <?php foreach ($cumpleanos as $cumpleano): ?>
                            <div class="birthday-item">
                                <div class="birthday-date">
                                    <div class="text-center">
                                        <div><?php echo $cumpleano['dia']; ?></div>
                                        <small><?php echo strtoupper(substr($cumpleano['mes_nombre'], 0, 3)); ?></small>
                                    </div>
                                </div>
                                <div class="birthday-info">
                                    <h6><?php echo htmlspecialchars($cumpleano['nombre'] . ' ' . $cumpleano['apellido']); ?></h6>
                                    <small class="birthday-cargo"><?php echo htmlspecialchars($cumpleano['cargo'] ?? ''); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-birthday-cake fa-3x mb-3"></i>
                            <p>No hay cumpleaños próximos</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Columna Derecha -->
            <div class="right-column">
                <div class="video-section">
                    <div class="video-player">
                        <?php if (!empty($videos)): ?>
                            <?php 
                            $video_archivo = $videos[0]['archivo_video'];
                            $video_path = "uploads/videos/" . $video_archivo;
                            ?>
                            <?php if (file_exists($video_path)): ?>
                                <video 
                                    id="current-video"
                                    width="100%" 
                                    height="100%" 
                                    autoplay 
                                    muted 
                                    controls
                                    onended="rotateVideo()">
                                    <source src="<?php echo $video_path; ?>" type="video/mp4">
                                    Tu navegador no soporta el elemento video.
                                </video>
                            <?php else: ?>
                                <div class="video-placeholder">
                                    <i class="fas fa-video fa-3x"></i>
                                    <h4>Video: <?php echo htmlspecialchars($videos[0]['titulo']); ?></h4>
                                    <p>Archivo de video no encontrado: <?php echo $video_archivo; ?></p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="video-placeholder">
                                <i class="fas fa-play-circle fa-3x"></i>
                                <h4>Sin videos disponibles</h4>
                                <p>Configure videos corporativos desde el panel de administración</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="bottom-grid">
                    <!-- Events Section - 3 columnas -->
                    <div class="events-section">
                        <!-- Indicador de progreso circular para eventos -->
                        <div class="events-progress-container">
                            <svg class="events-progress-circle" width="30" height="30">
                                <circle class="events-progress-bg" cx="15" cy="15" r="13.5" fill="none"/>
                                <circle class="events-progress" cx="15" cy="15" r="13.5" fill="none" 
                                        stroke-linecap="round" stroke-dasharray="85" stroke-dashoffset="85"/>
                            </svg>
                        </div>
                        
                        <div class="section-title">
                            <i class="fas fa-calendar-alt" style="color: rgb(48, 82, 130);"></i>
                            <h4 class="events-title">Próximos Eventos</h4>
                        </div>
                        
                        <div id="events-container" class="events-grid">
                            <?php if (!empty($eventos)): ?>
                                <?php 
                                // Mostrar solo los primeros 2 eventos en 2 columnas
                                $eventos_iniciales = array_slice($eventos, 0, 2);
                                foreach ($eventos_iniciales as $evento): 
                                ?>
                                    <div class="event-card">
                                        <div class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></div>
                                        <div class="event-date"><?php echo $evento['fecha_formateada']; ?></div>
                                        <div class="event-description"><?php echo htmlspecialchars($evento['descripcion']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                                    <p>No hay eventos próximos</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- QR Campaigns Section - 1 columna -->
                    <div class="qr-section glass-panel">
                        <div class="section-title">
                            <i class="fas fa-qrcode"></i>
                            <h4 class="qr-title">Encuestas</h4>
                        </div>
                        
                        <?php if ($campana_principal): ?>
                            <div class="qr-campaign">
                                <div class="qr-code">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($campana_principal['url']); ?>" 
                                         alt="Código QR para <?php echo htmlspecialchars($campana_principal['titulo']); ?>"
                                         class="img-fluid">
                                </div>
                                <div class="qr-campaign-content">
                                    <div class="qr-title"><?php echo htmlspecialchars($campana_principal['titulo']); ?></div>
                                    <div class="qr-description"><?php echo htmlspecialchars($campana_principal['descripcion'] ?? 'Sin descripción'); ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-qrcode fa-3x mb-3"></i>
                                <p>No hay campaña principal configurada</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            let currentVideoIndex = 0;
            const videos = <?php echo !empty($videos) ? json_encode($videos) : '[]'; ?>;
            
            
            // Rotar videos automáticamente cuando terminen
            window.rotateVideo = function() {
                console.log('Rotando video...', 'Videos disponibles:', videos.length);
                if (videos.length > 1) {
                    currentVideoIndex = (currentVideoIndex + 1) % videos.length;
                    const currentVideo = videos[currentVideoIndex];
                    console.log('Cambiando a video:', currentVideoIndex, currentVideo.titulo);
                    
                    const videoElement = document.getElementById('current-video');
                    
                    if (videoElement && currentVideo) {
                        const newVideoPath = `uploads/videos/${currentVideo.archivo_video}`;
                        console.log('Nuevo video path:', newVideoPath);
                        videoElement.src = newVideoPath;
                        videoElement.load(); // Recargar el video
                        
                        // Forzar reproducción
                        videoElement.play().catch(function(error) {
                            console.log('Error al reproducir video:', error);
                        });
                    }
                } else {
                    console.log('Solo hay un video o no hay videos disponibles');
                }
            };
            
            // Rotar ubicaciones de clima automáticamente
            <?php if (!empty($ubicaciones_clima) && count($ubicaciones_clima) > 1): ?>
            let currentWeatherIndex = 0;
            const weatherLocations = <?php echo json_encode($ubicaciones_clima); ?>;
            
            function rotateWeather() {
                currentWeatherIndex = (currentWeatherIndex + 1) % weatherLocations.length;
                const currentLocation = weatherLocations[currentWeatherIndex];
                
                console.log('Rotando clima a:', currentLocation.nombre, 'Index:', currentWeatherIndex);
                
                // Obtener todas las tarjetas de clima
                const weatherCards = document.querySelectorAll('.weather-card');
                
                if (weatherCards.length > 0) {
                    // Rotar entre las tarjetas visibles
                    weatherCards.forEach((card, index) => {
                        const locationIndex = (currentWeatherIndex + index) % weatherLocations.length;
                        const location = weatherLocations[locationIndex];
                        
                        const tempElement = card.querySelector('.current-temp');
                        const conditionElement = card.querySelector('.current-condition');
                        const locationElement = card.querySelector('.current-location');
                        const weatherIcon = card.querySelector('.weather-icon');
                        
                        // Fade out
                        card.style.opacity = '0';
                        
                        setTimeout(() => {
                            if (tempElement) tempElement.textContent = (location.temperatura || '--') + '°C';
                            // Limpiar descripción para mostrar (remover código [main:...])
                            const descripcionLimpia = (location.descripcion || 'Sin datos').replace(/\s*\[main:[^\]]+\]/gi, '');
                            if (conditionElement) conditionElement.textContent = descripcionLimpia;
                            if (locationElement) locationElement.textContent = location.nombre;
                            
                            if (weatherIcon) {
                                const iconClass = getWeatherIconClass(location.descripcion || '');
                                weatherIcon.className = 'fas fa-' + iconClass + ' weather-icon';
                            }
                            
                            // Fade in
                            card.style.opacity = '1';
                        }, 250);
                    });
                } else {
                    // Fallback para el formato anterior
                    const weatherContainer = document.querySelector('.current-weather');
                    const tempElement = document.querySelector('.current-temp');
                    const conditionElement = document.querySelector('.current-condition');
                    const locationElement = document.querySelector('.current-location');
                    const weatherIcon = document.querySelector('.current-weather .weather-icon');
                    
                    if (weatherContainer) weatherContainer.style.opacity = '0';
                    
                    setTimeout(() => {
                        if (tempElement) tempElement.textContent = (currentLocation.temperatura || '--') + '°C';
                        // Limpiar descripción para mostrar (remover código [main:...])
                        const descripcionLimpia = (currentLocation.descripcion || 'Sin datos').replace(/\s*\[main:[^\]]+\]/gi, '');
                        if (conditionElement) conditionElement.textContent = descripcionLimpia;
                        if (locationElement) locationElement.textContent = currentLocation.nombre;
                        
                        if (weatherIcon) {
                            const iconClass = getWeatherIconClass(currentLocation.descripcion || '');
                            weatherIcon.className = 'fas fa-' + iconClass + ' weather-icon';
                        }
                        
                        if (weatherContainer) weatherContainer.style.opacity = '1';
                    }, 250);
                }
            }
            
            // Función helper para obtener la clase del icono
            function getWeatherIconClass(descripcion) {
                if (!descripcion) return 'cloud-sun';
                
                const desc = descripcion.toLowerCase().trim();
                
                // Extraer el código main si está presente en el formato "[main:codigo]"
                let weatherMain = null;
                const mainMatch = desc.match(/\[main:([^\]]+)\]/);
                if (mainMatch) {
                    weatherMain = mainMatch[1].trim();
                }
                
                // Primero intentar usar el código principal de la API (más confiable)
                if (weatherMain) {
                    switch (weatherMain) {
                        case 'clear':
                            return 'sun';
                        case 'clouds':
                            return 'cloud';
                        case 'rain':
                        case 'drizzle':
                            return 'cloud-rain';
                        case 'thunderstorm':
                            return 'bolt';
                        case 'snow':
                            return 'snowflake';
                        case 'mist':
                        case 'fog':
                        case 'haze':
                        case 'dust':
                        case 'sand':
                            return 'smog';
                        default:
                            break; // Continuar con el mapeo por descripción
                    }
                }
                
                // Fallback: mapeo por descripción en español/inglés
                // Cielo despejado / Clear
                if (desc.includes('despejado') || desc.includes('clear') || desc.includes('soleado') || desc.includes('sunny')) {
                    return 'sun';
                }
                
                // Nubes / Clouds
                if (desc.includes('nube') || desc.includes('cloud') || desc.includes('nublado') || desc.includes('overcast')) {
                    return 'cloud';
                }
                
                // Lluvia / Rain / Drizzle
                if (desc.includes('lluvia') || desc.includes('rain') || desc.includes('llovizna') || desc.includes('drizzle') || desc.includes('chubasco') || desc.includes('shower')) {
                    return 'cloud-rain';
                }
                
                // Tormenta / Thunderstorm
                if (desc.includes('tormenta') || desc.includes('thunder') || desc.includes('storm') || desc.includes('rayo') || desc.includes('bolt') || desc.includes('lightning')) {
                    return 'bolt';
                }
                
                // Nieve / Snow
                if (desc.includes('nieve') || desc.includes('snow') || desc.includes('nevando')) {
                    return 'snowflake';
                }
                
                // Niebla / Mist / Fog / Haze
                if (desc.includes('niebla') || desc.includes('mist') || desc.includes('fog') || desc.includes('haze') || desc.includes('bruma') || desc.includes('dust') || desc.includes('sand')) {
                    return 'smog';
                }
                
                // Por defecto
                return 'cloud-sun';
            }
            
            // Función para manejar el indicador de progreso del clima
            function startWeatherProgress() {
                const progressBar = document.querySelector('.weather-progress');
                const circumference = 85; // 2 * π * 13.5
                let timeElapsed = 0;
                const totalTime = 5; // 5 segundos
                
                // Reiniciar el progreso completamente
                progressBar.style.transition = 'none';
                progressBar.style.strokeDasharray = circumference;
                progressBar.style.strokeDashoffset = circumference;
                
                // Forzar un reflow
                progressBar.offsetHeight;
                
                // Restaurar la transición suave
                setTimeout(() => {
                    progressBar.style.transition = 'stroke-dashoffset 0.1s linear';
                }, 50);
                
                // Animar el progreso
                const progressInterval = setInterval(() => {
                    timeElapsed += 0.1;
                    const progress = (timeElapsed / totalTime) * 100;
                    const offset = circumference - (progress / 100) * circumference;
                    
                    if (timeElapsed >= totalTime) {
                        progressBar.style.strokeDashoffset = 0;
                        clearInterval(progressInterval);
                    } else {
                        progressBar.style.strokeDashoffset = offset;
                    }
                }, 100);
            }
            
            // Cambiar clima cada 5 segundos
            setInterval(() => {
                rotateWeather();
                startWeatherProgress();
            }, 5000);
            <?php endif; ?>
            
            // Rotar eventos automáticamente
            <?php if (!empty($eventos) && count($eventos) > 2): ?>
            let currentEventIndex = 0;
            const allEvents = <?php echo json_encode($eventos); ?>;
            
            function rotateEvents() {
                const eventsContainer = document.getElementById('events-container');
                
                // Calcular los índices de los próximos 2 eventos
                const startIndex = currentEventIndex;
                const endIndex = Math.min(startIndex + 2, allEvents.length);
                
                // Si llegamos al final, volver al inicio
                if (endIndex === allEvents.length) {
                    currentEventIndex = 0;
                } else {
                    currentEventIndex = endIndex;
                }
                
                // Obtener los próximos 2 eventos
                const nextEvents = [];
                for (let i = 0; i < 2; i++) {
                    const eventIndex = (currentEventIndex + i) % allEvents.length;
                    nextEvents.push(allEvents[eventIndex]);
                }
                
                console.log('Rotando eventos. Índice actual:', currentEventIndex, 'Eventos:', nextEvents.map(e => e.titulo));
                
                // Crear el HTML para los nuevos eventos (sin countdown)
                let eventsHTML = '';
                nextEvents.forEach(evento => {
                    eventsHTML += `
                        <div class="event-card">
                            <div class="event-title">${evento.titulo}</div>
                            <div class="event-date">${evento.fecha_formateada}</div>
                            <div class="event-description">${evento.descripcion}</div>
                        </div>
                    `;
                });
                
                // Aplicar transición fade
                eventsContainer.style.opacity = '0';
                
                setTimeout(() => {
                    eventsContainer.innerHTML = eventsHTML;
                    eventsContainer.style.opacity = '1';
                }, 250);
            }
            
            // Función para manejar el indicador de progreso de eventos
            function startEventsProgress() {
                const progressBar = document.querySelector('.events-progress');
                const circumference = 85; // 2 * π * 13.5
                let timeElapsed = 0;
                const totalTime = 5; // 5 segundos
                
                // Reiniciar el progreso
                progressBar.style.transition = 'none';
                progressBar.style.strokeDashoffset = circumference;
                
                // Forzar un reflow
                progressBar.offsetHeight;
                
                // Restaurar la transición suave
                setTimeout(() => {
                    progressBar.style.transition = 'stroke-dashoffset 0.1s linear';
                }, 50);
                
                // Animar el progreso
                const progressInterval = setInterval(() => {
                    timeElapsed += 0.1;
                    const progress = (timeElapsed / totalTime) * 100;
                    const offset = circumference - (progress / 100) * circumference;
                    
                    if (timeElapsed >= totalTime) {
                        progressBar.style.strokeDashoffset = 0;
                        clearInterval(progressInterval);
                    } else {
                        progressBar.style.strokeDashoffset = offset;
                    }
                }, 100);
            }
            
            // Cambiar eventos cada 5 segundos
            setInterval(() => {
                rotateEvents();
                startEventsProgress();
            }, 5000);
            <?php endif; ?>
            
            // Función para iniciar ambos spinners sincronizados
            function startBothSpinners() {
                <?php if (!empty($ubicaciones_clima)): ?>
                startWeatherProgress();
                <?php endif; ?>
                <?php if (!empty($eventos) && count($eventos) > 2): ?>
                startEventsProgress();
                <?php endif; ?>
            }
            
            // Iniciar ambos spinners cuando la página cargue
            document.addEventListener('DOMContentLoaded', function() {
                startBothSpinners();
            });
            
        </script>
    </body>
</html>
