<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo base_path('assets/css/home.css'); ?>">
</head>
<body>
    <div class="dashboard-container">
        <a href="<?php echo base_path('login.php'); ?>" class="admin-float-button">
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
                                    <i class="fas fa-<?php echo HomeController::getWeatherIcon($location['descripcion'] ?? ''); ?> weather-icon"></i>
                                    <div class="current-temp">
                                        <?php echo $location['temperatura'] ?? '--'; ?>°C
                                    </div>
                                    <div class="current-condition">
                                        <?php echo htmlspecialchars(HomeController::limpiarDescripcion($location['descripcion'] ?? 'Sin datos')); ?>
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
