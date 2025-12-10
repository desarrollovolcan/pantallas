<?php
require_once '../config/config.php';
requireAuth();

$database = new Database();
$db = $database->getConnection();

// Recuperar mensajes de la sesión si existen (después de redirect)
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
// Limpiar mensajes de la sesión después de leerlos
unset($_SESSION['message'], $_SESSION['error']);

// Función helper para verificar si el orden ya existe
function verificarOrdenExistente($db, $orden, $excluir_id = null) {
    $query = "SELECT COUNT(*) as count FROM ubicaciones_clima WHERE orden = :orden";
    if ($excluir_id !== null) {
        $query .= " AND id != :excluir_id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':orden', $orden);
    if ($excluir_id !== null) {
        $stmt->bindParam(':excluir_id', $excluir_id);
    }
    $stmt->execute();
    
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nombre = sanitizeInput($_POST['nombre']);
                $codigo_ciudad = sanitizeInput($_POST['codigo_ciudad']);
                $latitud = (float)$_POST['latitud'];
                $longitud = (float)$_POST['longitud'];
                $orden = (int)$_POST['orden'];
                
                // Validar que el orden no esté duplicado
                if (verificarOrdenExistente($db, $orden)) {
                    $_SESSION['error'] = 'Ya existe una ubicación con el orden ' . $orden . '. Por favor, elija un número de orden diferente.';
                    header('Location: clima.php');
                    exit();
                }
                
                try {
                    $query = "INSERT INTO ubicaciones_clima (nombre, codigo_ciudad, latitud, longitud, orden) VALUES (:nombre, :codigo_ciudad, :latitud, :longitud, :orden)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':codigo_ciudad', $codigo_ciudad);
                    $stmt->bindParam(':latitud', $latitud);
                    $stmt->bindParam(':longitud', $longitud);
                    $stmt->bindParam(':orden', $orden);
                    $stmt->execute();
                    $_SESSION['message'] = 'Ubicación agregada exitosamente';
                    header('Location: clima.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al agregar la ubicación';
                    header('Location: clima.php');
                    exit();
                }
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nombre = sanitizeInput($_POST['nombre']);
                $codigo_ciudad = sanitizeInput($_POST['codigo_ciudad']);
                $latitud = (float)$_POST['latitud'];
                $longitud = (float)$_POST['longitud'];
                $orden = (int)$_POST['orden'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                // Validar que el orden no esté duplicado (excluyendo la ubicación actual)
                if (verificarOrdenExistente($db, $orden, $id)) {
                    $_SESSION['error'] = 'Ya existe otra ubicación con el orden ' . $orden . '. Por favor, elija un número de orden diferente.';
                    header('Location: clima.php');
                    exit();
                }
                
                try {
                    $query = "UPDATE ubicaciones_clima SET nombre = :nombre, codigo_ciudad = :codigo_ciudad, latitud = :latitud, longitud = :longitud, orden = :orden, activo = :activo WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':codigo_ciudad', $codigo_ciudad);
                    $stmt->bindParam(':latitud', $latitud);
                    $stmt->bindParam(':longitud', $longitud);
                    $stmt->bindParam(':orden', $orden);
                    $stmt->bindParam(':activo', $activo);
                    $stmt->execute();
                    $_SESSION['message'] = 'Ubicación actualizada exitosamente';
                    header('Location: clima.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar la ubicación';
                    header('Location: clima.php');
                    exit();
                }
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $query = "DELETE FROM ubicaciones_clima WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    $_SESSION['message'] = 'Ubicación eliminada exitosamente';
                    header('Location: clima.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al eliminar la ubicación';
                    header('Location: clima.php');
                    exit();
                }
                
            case 'update_weather':
                try {
                    // Actualizar datos de clima para todas las ubicaciones activas
                    $query = "SELECT * FROM ubicaciones_clima WHERE activo = 1";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $ubicaciones = $stmt->fetchAll();
                    
                    foreach ($ubicaciones as $ubicacion) {
                        // Obtener datos reales de la API de OpenWeatherMap
                        $weatherData = getWeatherData($ubicacion['latitud'], $ubicacion['longitud'], $ubicacion['nombre']);
                        
                        // Eliminar datos anteriores
                        $deleteQuery = "DELETE FROM datos_clima WHERE ubicacion_id = :ubicacion_id";
                        $deleteStmt = $db->prepare($deleteQuery);
                        $deleteStmt->bindParam(':ubicacion_id', $ubicacion['id']);
                        $deleteStmt->execute();
                        
                        // Insertar nuevos datos reales
                        // Guardar descripción con código main para mapeo de íconos, pero oculto
                        $descripcion_con_codigo = $weatherData['descripcion'];
                        if (!empty($weatherData['weather_main'])) {
                            $descripcion_con_codigo .= ' [main:' . $weatherData['weather_main'] . ']';
                        }
                        
                        $insertQuery = "INSERT INTO datos_clima (ubicacion_id, temperatura, descripcion, humedad, velocidad_viento, fecha_actualizacion) VALUES (:ubicacion_id, :temperatura, :descripcion, :humedad, :velocidad_viento, NOW())";
                        $insertStmt = $db->prepare($insertQuery);
                        $insertStmt->bindParam(':ubicacion_id', $ubicacion['id']);
                        $insertStmt->bindParam(':temperatura', $weatherData['temperatura']);
                        $insertStmt->bindParam(':descripcion', $descripcion_con_codigo);
                        $insertStmt->bindParam(':humedad', $weatherData['humedad']);
                        $insertStmt->bindParam(':velocidad_viento', $weatherData['velocidad_viento']);
                        $insertStmt->execute();
                    }
                    
                    $_SESSION['message'] = 'Datos de clima actualizados exitosamente';
                    header('Location: clima.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar los datos de clima';
                    header('Location: clima.php');
                    exit();
                }
        }
    }
}

// Función helper para limpiar descripción visible (remover código [main:...])
function limpiarDescripcion($descripcion) {
    if (empty($descripcion)) {
        return $descripcion;
    }
    return preg_replace('/\s*\[main:[^\]]+\]/i', '', $descripcion);
}

// Obtener ubicaciones con datos de clima
try {
    $query = "SELECT u.*, d.temperatura, d.descripcion, d.humedad, d.velocidad_viento, d.fecha_actualizacion 
              FROM ubicaciones_clima u 
              LEFT JOIN datos_clima d ON u.id = d.ubicacion_id 
              ORDER BY u.orden ASC, u.nombre ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $ubicaciones = $stmt->fetchAll();
    // Nota: Mantenemos el código [main:...] en los datos para el mapeo de íconos
    // Se limpiará solo al mostrar en pantalla
} catch (Exception $e) {
    $ubicaciones = [];
}

// Función para obtener el siguiente orden disponible
function obtenerSiguienteOrden($ubicaciones) {
    $ordenes_ocupados = array_column($ubicaciones, 'orden');
    $siguiente_orden = 1;
    
    while (in_array($siguiente_orden, $ordenes_ocupados)) {
        $siguiente_orden++;
    }
    
    return $siguiente_orden;
}

$siguiente_orden = obtenerSiguienteOrden($ubicaciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clima - Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .weather-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
        }
        .weather-icon {
            font-size: 3rem;
            color: #74b9ff;
        }
        .temperature {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3436;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4><i class="fas fa-building me-2"></i>Dashboard</h4>
                        <small class="text-light">Panel de Administración</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Inicio
                        </a>
                        <a class="nav-link" href="videos.php">
                            <i class="fas fa-play me-2"></i>Videos Corporativos
                        </a>
                        <a class="nav-link active" href="clima.php">
                            <i class="fas fa-cloud-sun me-2"></i>Clima
                        </a>
                        <a class="nav-link" href="cumpleanos.php">
                            <i class="fas fa-birthday-cake me-2"></i>Cumpleaños
                        </a>
                        <a class="nav-link" href="eventos.php">
                            <i class="fas fa-calendar-alt me-2"></i>Eventos
                        </a>
                        <a class="nav-link" href="campanas.php">
                            <i class="fas fa-qrcode me-2"></i>Campañas QR
                        </a>
                        <hr class="my-3">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-eye me-2"></i>Ver Dashboard
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-cloud-sun me-2"></i>Gestión de Clima</h2>
                        <div>
                            <button class="btn btn-success me-2" onclick="updateWeather()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar Clima
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                <i class="fas fa-plus me-2"></i>Agregar Ubicación
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Lista de Ubicaciones -->
                    <div class="row">
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="weather-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($ubicacion['nombre']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($ubicacion['codigo_ciudad']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $ubicacion['activo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $ubicacion['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($ubicacion['temperatura']): ?>
                                        <div class="text-center mb-3">
                                            <div class="weather-icon">
                                                <i class="fas fa-<?php echo getWeatherIcon($ubicacion['descripcion']); ?>"></i>
                                            </div>
                                            <div class="temperature"><?php echo $ubicacion['temperatura']; ?>°C</div>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars(limpiarDescripcion($ubicacion['descripcion'])); ?></p>
                                            <small class="text-muted">
                                                Humedad: <?php echo $ubicacion['humedad']; ?>% | 
                                                Viento: <?php echo $ubicacion['velocidad_viento']; ?> km/h
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center mb-3">
                                            <div class="weather-icon">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                            <p class="text-muted">Sin datos de clima</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Orden: <?php echo $ubicacion['orden']; ?></small>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editLocation(<?php echo htmlspecialchars(json_encode($ubicacion)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteLocation(<?php echo $ubicacion['id']; ?>, '<?php echo htmlspecialchars($ubicacion['nombre']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Agregar Ubicación -->
    <div class="modal fade" id="addLocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Ubicación de Clima</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Ubicación</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="codigo_ciudad" class="form-label">Código de Ciudad</label>
                            <input type="text" class="form-control" id="codigo_ciudad" name="codigo_ciudad" required>
                            <div class="form-text">Formato: Ciudad,País (ej: Santiago,CL)</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="latitud" class="form-label">Latitud</label>
                                    <input type="number" step="any" class="form-control" id="latitud" name="latitud" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="longitud" class="form-label">Longitud</label>
                                    <input type="number" step="any" class="form-control" id="longitud" name="longitud" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="orden" class="form-label">Orden de Visualización</label>
                            <input type="number" class="form-control" id="orden" name="orden" value="<?php echo $siguiente_orden; ?>" min="1">
                            <div class="form-text">Sugerencia: Orden <?php echo $siguiente_orden; ?> (próximo disponible)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Ubicación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Ubicación -->
    <div class="modal fade" id="editLocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Ubicación de Clima</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre de la Ubicación</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_codigo_ciudad" class="form-label">Código de Ciudad</label>
                            <input type="text" class="form-control" id="edit_codigo_ciudad" name="codigo_ciudad" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_latitud" class="form-label">Latitud</label>
                                    <input type="number" step="any" class="form-control" id="edit_latitud" name="latitud" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_longitud" class="form-label">Longitud</label>
                                    <input type="number" step="any" class="form-control" id="edit_longitud" name="longitud" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_orden" class="form-label">Orden de Visualización</label>
                            <input type="number" class="form-control" id="edit_orden" name="orden" min="1">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_activo" name="activo" checked>
                                <label class="form-check-label" for="edit_activo">Ubicación Activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Ubicación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteLocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de que desea eliminar la ubicación "<span id="delete_nombre"></span>"?</p>
                        <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Formulario oculto para actualizar clima -->
    <form id="updateWeatherForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_weather">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editLocation(location) {
            document.getElementById('edit_id').value = location.id;
            document.getElementById('edit_nombre').value = location.nombre;
            document.getElementById('edit_codigo_ciudad').value = location.codigo_ciudad;
            document.getElementById('edit_latitud').value = location.latitud;
            document.getElementById('edit_longitud').value = location.longitud;
            document.getElementById('edit_orden').value = location.orden;
            document.getElementById('edit_activo').checked = location.activo == 1;
            
            new bootstrap.Modal(document.getElementById('editLocationModal')).show();
        }
        
        function deleteLocation(id, nombre) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nombre').textContent = nombre;
            
            new bootstrap.Modal(document.getElementById('deleteLocationModal')).show();
        }
        
        function updateWeather() {
            document.getElementById('updateWeatherForm').submit();
        }
        
        // Validación en tiempo real para orden duplicado
        const ordenesOcupados = <?php echo json_encode(array_column($ubicaciones, 'orden')); ?>;
        
        function validarOrden(input, isEdit = false, currentId = null) {
            const orden = parseInt(input.value);
            const feedback = input.nextElementSibling;
            
            if (isNaN(orden) || orden < 1) {
                input.classList.remove('is-valid', 'is-invalid');
                if (feedback && feedback.classList.contains('form-text')) {
                    feedback.textContent = '';
                    feedback.className = 'form-text';
                }
                return;
            }
            
            let ordenOcupado = false;
            if (isEdit && currentId) {
                // Para edición, verificar si el orden está ocupado por otra ubicación
                ordenOcupado = ordenesOcupados.includes(orden);
            } else {
                // Para agregar, verificar si el orden está ocupado
                ordenOcupado = ordenesOcupados.includes(orden);
            }
            
            if (ordenOcupado) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                if (feedback && feedback.classList.contains('form-text')) {
                    feedback.textContent = 'Este orden ya está ocupado por otra ubicación';
                    feedback.className = 'form-text text-danger';
                }
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                if (feedback && feedback.classList.contains('form-text')) {
                    feedback.textContent = 'Orden disponible';
                    feedback.className = 'form-text text-success';
                }
            }
        }
        
        // Agregar event listeners cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            const ordenInput = document.getElementById('orden');
            if (ordenInput) {
                ordenInput.addEventListener('input', function() {
                    validarOrden(this, false);
                });
            }
            
            const editOrdenInput = document.getElementById('edit_orden');
            if (editOrdenInput) {
                editOrdenInput.addEventListener('input', function() {
                    const locationId = document.getElementById('edit_id').value;
                    validarOrden(this, true, locationId);
                });
            }
        });
    </script>
</body>
</html>

<?php
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
?>
