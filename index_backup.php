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
} catch (Exception $e) {
    $ubicaciones_clima = [];
}

// Obtener próximos cumpleaños (máximo 3)
try {
    $query = "SELECT *, 
              DATE_FORMAT(fecha_nacimiento, '%d') as dia,
              DATE_FORMAT(fecha_nacimiento, '%m') as mes,
              DATE_FORMAT(fecha_nacimiento, '%M') as mes_nombre,
              CASE 
                  WHEN DATE_FORMAT(fecha_nacimiento, '%m-%d') >= DATE_FORMAT(NOW(), '%m-%d') 
                  THEN DATE_FORMAT(fecha_nacimiento, '%m-%d')
                  ELSE DATE_FORMAT(DATE_ADD(fecha_nacimiento, INTERVAL 1 YEAR), '%m-%d')
              END as proximo_cumpleanos
              FROM cumpleanos 
              WHERE activo = 1
              ORDER BY proximo_cumpleanos ASC, dia ASC
              LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $cumpleanos = $stmt->fetchAll();
} catch (Exception $e) {
    $cumpleanos = [];
}

// Obtener próximos eventos
try {
    $query = "SELECT *, 
              DATE_FORMAT(fecha_evento, '%d/%m/%Y %H:%i') as fecha_formateada,
              DATEDIFF(fecha_evento, NOW()) as dias_restantes
              FROM eventos 
              WHERE activo = 1 AND fecha_evento > NOW()
              ORDER BY fecha_evento ASC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $eventos = $stmt->fetchAll();
} catch (Exception $e) {
    $eventos = [];
}

// Obtener campañas QR activas
try {
    $query = "SELECT * FROM campañas_qr 
              WHERE activo = 1 
              AND fecha_inicio <= CURDATE() 
              AND fecha_fin >= CURDATE()
              ORDER BY orden ASC
              LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $campanas = $stmt->fetchAll();
} catch (Exception $e) {
    $campanas = [];
}

// Función para obtener el icono del clima
function getWeatherIcon($descripcion) {
    $descripcion = strtolower($descripcion);
    
    if (strpos($descripcion, 'soleado') !== false) {
        return 'fas fa-sun';
    } elseif (strpos($descripcion, 'nublado') !== false) {
        return 'fas fa-cloud';
    } elseif (strpos($descripcion, 'lluvia') !== false) {
        return 'fas fa-cloud-rain';
    } elseif (strpos($descripcion, 'nieve') !== false) {
        return 'fas fa-snowflake';
    } elseif (strpos($descripcion, 'tormenta') !== false) {
        return 'fas fa-bolt';
    } else {
        return 'fas fa-cloud-sun';
    }
}

// Función para calcular días hasta el cumpleaños
function getDaysUntilBirthday($fecha_nacimiento) {
    $fecha_actual = new DateTime();
    $fecha_nacimiento = new DateTime($fecha_nacimiento);
    
    $cumpleanos_este_año = new DateTime($fecha_actual->format('Y') . '-' . $fecha_nacimiento->format('m-d'));
    
    if ($cumpleanos_este_año < $fecha_actual) {
        $cumpleanos_este_año->add(new DateInterval('P1Y'));
    }
    
    $diferencia = $fecha_actual->diff($cumpleanos_este_año);
    return $diferencia->days;
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            padding: 20px;
            min-height: 100vh;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px 30px;
            margin-bottom: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
        }
        
        .time-display {
            font-size: 1.5rem;
            font-weight: 300;
            font-family: 'Courier New', monospace;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: auto auto auto auto;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        
        .video-section {
            background: #2d3436;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            grid-row: 1;
            display: flex;
            flex-direction: column;
            height: 300px; /* Tamaño fijo horizontal */
        }
        
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            grid-row: 2 / 5;
        }
        
        .weather-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            grid-row: 1 / 3;
        }
        
        .birthdays-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            grid-row: 3;
        }
        
        .events-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            grid-row: 2;
        }
        
        .qr-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            grid-row: 3;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: #2d3436;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .video-section .section-title {
            color: white;
        }
        
        .current-weather {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .current-temp {
            font-size: 3rem;
            font-weight: 300;
            color: #74b9ff;
            margin: 10px 0;
        }
        
        .current-condition {
            font-size: 1.2rem;
            color: #636e72;
            margin-bottom: 10px;
        }
        
        .current-location {
            font-size: 1rem;
            color: #636e72;
        }
        
        .weather-forecast {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .forecast-day {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        
        .forecast-day:hover {
            transform: translateY(-5px);
        }
        
        .forecast-day-name {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 10px;
        }
        
        .forecast-icon {
            font-size: 2rem;
            color: #74b9ff;
            margin: 10px 0;
        }
        
        .forecast-temp {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3436;
        }
        
        .birthday-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .birthday-item:hover {
            transform: translateX(5px);
        }
        
        .birthday-date {
            background: linear-gradient(45deg, #fdcb6e, #e17055);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .birthday-info h6 {
            margin: 0;
            color: #2d3436;
        }
        
        .birthday-info small {
            color: #636e72;
        }
        
        .video-player {
            flex: 1;
            background: #000;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .video-placeholder {
            text-align: center;
        }
        
        .video-placeholder i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .video-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .video-info h4 {
            color: white;
            margin: 0;
        }
        
        .video-info p {
            color: rgba(255, 255, 255, 0.8);
            margin: 5px 0;
        }
        
        .event-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #6c5ce7;
            transition: transform 0.3s;
        }
        
        .event-card:hover {
            transform: translateY(-3px);
        }
        
        .event-title {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 5px;
        }
        
        .event-date {
            color: #6c5ce7;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .event-description {
            color: #636e72;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .event-countdown {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .countdown-box {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            text-align: center;
            min-width: 80px;
        }
        
        .countdown-number {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .countdown-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .qr-campaign {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .qr-campaign:hover {
            transform: translateY(-3px);
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
        }
        
        .qr-code img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .qr-title {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 5px;
        }
        
        .qr-description {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .admin-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-3px);
        }
        
        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto auto auto;
            }
            
            .weather-section { grid-row: 1; }
            .birthdays-section { grid-row: 2; }
            .video-section { grid-row: 3; }
            .events-section { grid-row: 4; }
            .qr-section { grid-row: 5; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-building me-2"></i>Dashboard Corporativo</h1>
            <div class="time-display" id="currentTime"></div>
        </div>
        
        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Video Section - Tamaño fijo horizontal -->
            <div class="video-section">
                <div class="section-title">
                    <i class="fas fa-play"></i>
                    Video Corporativo
                </div>
                
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
                                loop
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
                
                <div class="video-controls">
                    <div class="video-info">
                        <?php if (!empty($videos)): ?>
                            <h4><?php echo htmlspecialchars($videos[0]['titulo']); ?></h4>
                            <p><?php echo htmlspecialchars($videos[0]['descripcion']); ?></p>
                        <?php else: ?>
                            <h4>Sin videos</h4>
                            <p>Agregue videos desde el panel de administración</p>
                        <?php endif; ?>
                    </div>
                    <div class="video-status">
                        <span class="badge bg-danger">EN VIVO</span>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Grid - Eventos y Encuestas en la misma línea -->
            <div class="bottom-grid">
                <!-- Events Section - 3 columnas -->
                <div class="events-section">
                    <div class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        Próximos Eventos
                    </div>
                    
                    <?php if (!empty($eventos)): ?>
                        <?php foreach ($eventos as $evento): ?>
                            <div class="event-card">
                                <div class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></div>
                                <div class="event-date"><?php echo $evento['fecha_formateada']; ?></div>
                                <div class="event-description"><?php echo htmlspecialchars($evento['descripcion']); ?></div>
                                
                                <?php if ($evento['dias_restantes'] > 0): ?>
                                    <div class="event-countdown">
                                        <div class="countdown-box">
                                            <div class="countdown-number"><?php echo $evento['dias_restantes']; ?></div>
                                            <div class="countdown-label">DIAS</div>
                                        </div>
                                        <div class="countdown-box">
                                            <div class="countdown-number">09</div>
                                            <div class="countdown-label">HRS</div>
                                        </div>
                                        <div class="countdown-box">
                                            <div class="countdown-number">24</div>
                                            <div class="countdown-label">MIN</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                            <p>No hay eventos próximos</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- QR Campaigns Section - 1 columna -->
                <div class="qr-section">
                    <div class="section-title">
                        <i class="fas fa-qrcode"></i>
                        Encuestas
                    </div>
                    
                    <?php if (!empty($campanas)): ?>
                        <?php foreach ($campanas as $campana): ?>
                            <div class="qr-campaign">
                                <div class="qr-code">
                                    <?php if ($campana['codigo_qr']): ?>
                                        <img src="<?php echo htmlspecialchars($campana['codigo_qr']); ?>" alt="Código QR">
                                    <?php else: ?>
                                        <i class="fas fa-qrcode fa-3x text-muted"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="qr-title"><?php echo htmlspecialchars($campana['titulo']); ?></div>
                                <div class="qr-description"><?php echo htmlspecialchars($campana['descripcion']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-qrcode fa-3x mb-3"></i>
                            <p>No hay campañas QR activas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Weather Section -->
            <div class="weather-section">
                <div class="section-title">
                    <i class="fas fa-cloud-sun"></i>
                    Clima Regional
                </div>
                
                <?php if (!empty($ubicaciones_clima)): ?>
                    <?php 
                    $current_location = $ubicaciones_clima[0]; // Primera ubicación como principal
                    ?>
                    <div class="current-weather">
                        <div class="current-temp">
                            <?php echo $current_location['temperatura'] ?? '--'; ?>°
                        </div>
                        <div class="current-condition">
                            <?php echo htmlspecialchars($current_location['descripcion'] ?? 'Sin datos'); ?>
                        </div>
                        <div class="current-location">
                            <?php echo htmlspecialchars($current_location['nombre']); ?>
                        </div>
                    </div>
                    
                    <div class="weather-forecast">
                        <?php foreach (array_slice($ubicaciones_clima, 0, 4) as $index => $ubicacion): ?>
                            <div class="forecast-day">
                                <div class="forecast-day-name">
                                    <?php 
                                    $days = ['Hoy', 'Mañana', 'Jueves', 'Viernes'];
                                    echo $days[$index] ?? 'Día';
                                    ?>
                                </div>
                                <div class="forecast-icon">
                                    <i class="<?php echo getWeatherIcon($ubicacion['descripcion'] ?? 'soleado'); ?>"></i>
                                </div>
                                <div class="forecast-temp">
                                    <?php echo $ubicacion['temperatura'] ?? '--'; ?>°
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
            <div class="birthdays-section">
                <div class="section-title">
                    <i class="fas fa-birthday-cake"></i>
                    Próximos Cumpleaños
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
                                <small><?php echo htmlspecialchars($cumpleano['mes_nombre']); ?></small>
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
            <!-- Weather Section -->
            <div class="weather-section">
                <div class="section-title">
                    <i class="fas fa-cloud-sun"></i>
                    Clima
                </div>
                
                <?php if (!empty($ubicaciones_clima)): ?>
                    <?php 
                    $current_location = $ubicaciones_clima[0]; // Primera ubicación como principal
                    ?>
                    <div class="current-weather">
                        <div class="current-temp">
                            <?php echo $current_location['temperatura'] ?? '--'; ?>°
                        </div>
                        <div class="current-condition">
                            <?php echo htmlspecialchars($current_location['descripcion'] ?? 'Sin datos'); ?>
                        </div>
                        <div class="current-location">
                            <?php echo htmlspecialchars($current_location['nombre']); ?>
                        </div>
                    </div>
                    
                    <div class="weather-forecast">
                        <?php foreach (array_slice($ubicaciones_clima, 0, 4) as $index => $ubicacion): ?>
                            <div class="forecast-day">
                                <div class="forecast-day-name">
                                    <?php 
                                    $days = ['Hoy', 'Mañana', 'Jueves', 'Viernes'];
                                    echo $days[$index] ?? 'Día';
                                    ?>
                                </div>
                                <div class="forecast-icon">
                                    <i class="<?php echo getWeatherIcon($ubicacion['descripcion'] ?? 'soleado'); ?>"></i>
                                </div>
                                <div class="forecast-temp">
                                    <?php echo $ubicacion['temperatura'] ?? '--'; ?>°
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
            <div class="birthdays-section">
                <div class="section-title">
                    <i class="fas fa-birthday-cake"></i>
                    Cumpleaños
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
                                <small><?php echo htmlspecialchars($cumpleano['mes_nombre']); ?></small>
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
            
            <!-- Video Section -->
            <div class="video-section">
                <div class="section-title">
                    <i class="fas fa-play"></i>
                    Video Corporativo
                </div>
                
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
                                loop
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
                
                <div class="video-controls">
                    <div class="video-info">
                        <?php if (!empty($videos)): ?>
                            <h4><?php echo htmlspecialchars($videos[0]['titulo']); ?></h4>
                            <p><?php echo htmlspecialchars($videos[0]['descripcion']); ?></p>
                        <?php else: ?>
                            <h4>Sin videos</h4>
                            <p>Agregue videos desde el panel de administración</p>
                        <?php endif; ?>
                    </div>
                    <div class="video-status">
                        <span class="badge bg-danger">EN VIVO</span>
                    </div>
                </div>
            </div>
            
            <!-- Events Section -->
            <div class="events-section">
                <div class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Próximos Eventos
                </div>
                
                <?php if (!empty($eventos)): ?>
                    <?php foreach ($eventos as $evento): ?>
                        <div class="event-card">
                            <div class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></div>
                            <div class="event-date"><?php echo $evento['fecha_formateada']; ?></div>
                            <div class="event-description"><?php echo htmlspecialchars($evento['descripcion']); ?></div>
                            
                            <?php if ($evento['dias_restantes'] > 0): ?>
                                <div class="event-countdown">
                                    <div class="countdown-box">
                                        <div class="countdown-number"><?php echo $evento['dias_restantes']; ?></div>
                                        <div class="countdown-label">DIAS</div>
                                    </div>
                                    <div class="countdown-box">
                                        <div class="countdown-number">09</div>
                                        <div class="countdown-label">HRS</div>
                                    </div>
                                    <div class="countdown-box">
                                        <div class="countdown-number">24</div>
                                        <div class="countdown-label">MIN</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <p>No hay eventos próximos</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- QR Campaigns Section -->
            <div class="qr-section">
                <div class="section-title">
                    <i class="fas fa-qrcode"></i>
                    Encuestas
                </div>
                
                <?php if (!empty($campanas)): ?>
                    <?php foreach ($campanas as $campana): ?>
                        <div class="qr-campaign">
                            <div class="qr-code">
                                <?php if ($campana['codigo_qr']): ?>
                                    <img src="<?php echo htmlspecialchars($campana['codigo_qr']); ?>" alt="Código QR">
                                <?php else: ?>
                                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div class="qr-title"><?php echo htmlspecialchars($campana['titulo']); ?></div>
                            <div class="qr-description"><?php echo htmlspecialchars($campana['descripcion']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-qrcode fa-3x mb-3"></i>
                        <p>No hay campañas QR activas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Admin Link -->
        <a href="login.php" class="admin-link">
            <i class="fas fa-cog me-2"></i>Administrar
        </a>
    </div>
    
    <script>
        let currentVideoIndex = 0;
        const videos = <?php echo !empty($videos) ? json_encode($videos) : '[]'; ?>;
        
        // Actualizar tiempo en tiempo real
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        // Actualizar cada segundo
        setInterval(updateTime, 1000);
        updateTime();
        
        // Rotar videos automáticamente cuando terminen
        function rotateVideo() {
            if (videos.length > 1) {
                currentVideoIndex = (currentVideoIndex + 1) % videos.length;
                const currentVideo = videos[currentVideoIndex];
                
                const videoElement = document.getElementById('current-video');
                const videoTitle = document.querySelector('.video-info h4');
                const videoDescription = document.querySelector('.video-info p');
                
                if (videoElement && currentVideo) {
                    const newVideoPath = `uploads/videos/${currentVideo.archivo_video}`;
                    videoElement.src = newVideoPath;
                    videoElement.load(); // Recargar el video
                }
                
                if (videoTitle && videoDescription) {
                    videoTitle.textContent = currentVideo.titulo;
                    videoDescription.textContent = currentVideo.descripcion;
                }
            }
        }
        
        // Rotar ubicaciones de clima automáticamente
        <?php if (!empty($ubicaciones_clima) && count($ubicaciones_clima) > 1): ?>
        let currentWeatherIndex = 0;
        const weatherLocations = <?php echo json_encode($ubicaciones_clima); ?>;
        
        function rotateWeather() {
            currentWeatherIndex = (currentWeatherIndex + 1) % weatherLocations.length;
            const currentLocation = weatherLocations[currentWeatherIndex];
            
            const tempElement = document.querySelector('.current-temp');
            const conditionElement = document.querySelector('.current-condition');
            const locationElement = document.querySelector('.current-location');
            
            if (tempElement && conditionElement && locationElement) {
                tempElement.textContent = (currentLocation.temperatura || '--') + '°';
                conditionElement.textContent = currentLocation.descripcion || 'Sin datos';
                locationElement.textContent = currentLocation.nombre;
            }
        }
        
        // Cambiar ubicación cada 15 segundos
        setInterval(rotateWeather, 15000);
        <?php endif; ?>
        
        // Auto-refresh de la página cada 5 minutos
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
