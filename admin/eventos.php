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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $titulo = sanitizeInput($_POST['titulo']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $ubicacion = sanitizeInput($_POST['ubicacion']);
                $tipo_evento = sanitizeInput($_POST['tipo_evento']);
                $fecha_evento = $_POST['fecha_evento'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                try {
                    $query = "INSERT INTO eventos (titulo, descripcion, ubicacion, tipo_evento, fecha_evento, activo) VALUES (:titulo, :descripcion, :ubicacion, :tipo_evento, :fecha_evento, :activo)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':titulo', $titulo);
                    $stmt->bindParam(':descripcion', $descripcion);
                    $stmt->bindParam(':ubicacion', $ubicacion);
                    $stmt->bindParam(':tipo_evento', $tipo_evento);
                    $stmt->bindParam(':fecha_evento', $fecha_evento);
                    $stmt->bindParam(':activo', $activo);
                    $stmt->execute();
                    $_SESSION['message'] = 'Evento agregado exitosamente';
                    header('Location: eventos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al agregar el evento';
                    header('Location: eventos.php');
                    exit();
                }
                
            case 'edit':
                $id = (int)$_POST['id'];
                $titulo = sanitizeInput($_POST['titulo']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $ubicacion = sanitizeInput($_POST['ubicacion']);
                $tipo_evento = sanitizeInput($_POST['tipo_evento']);
                $fecha_evento = $_POST['fecha_evento'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                try {
                    $query = "UPDATE eventos SET titulo = :titulo, descripcion = :descripcion, ubicacion = :ubicacion, tipo_evento = :tipo_evento, fecha_evento = :fecha_evento, activo = :activo WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->bindParam(':titulo', $titulo);
                    $stmt->bindParam(':descripcion', $descripcion);
                    $stmt->bindParam(':ubicacion', $ubicacion);
                    $stmt->bindParam(':tipo_evento', $tipo_evento);
                    $stmt->bindParam(':fecha_evento', $fecha_evento);
                    $stmt->bindParam(':activo', $activo);
                    $stmt->execute();
                    $_SESSION['message'] = 'Evento actualizado exitosamente';
                    header('Location: eventos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar el evento';
                    header('Location: eventos.php');
                    exit();
                }
                
            case 'toggle_active':
                $id = (int)$_POST['id'];
                try {
                    $query = "UPDATE eventos SET activo = NOT activo WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    $_SESSION['message'] = 'Estado del evento actualizado exitosamente';
                    header('Location: eventos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar el estado del evento';
                    header('Location: eventos.php');
                    exit();
                }
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $query = "DELETE FROM eventos WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    $message = 'Evento eliminado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al eliminar el evento';
                }
                break;
        }
    }
}

// Obtener eventos
try {
    $query = "SELECT *, 
              DATE_FORMAT(fecha_evento, '%d/%m/%Y %H:%i') as fecha_formateada,
              DATEDIFF(fecha_evento, NOW()) as dias_restantes
              FROM eventos 
              ORDER BY fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $eventos = $stmt->fetchAll();
} catch (Exception $e) {
    $eventos = [];
}

// Función para obtener el icono del tipo de evento
function getEventIcon($tipo_evento) {
    switch (strtolower($tipo_evento)) {
        case 'celebración':
        case 'celebracion':
            return 'fas fa-glass-cheers';
        case 'reunión':
        case 'reunion':
            return 'fas fa-users';
        case 'capacitación':
        case 'capacitacion':
            return 'fas fa-graduation-cap';
        case 'conferencia':
            return 'fas fa-microphone';
        case 'taller':
            return 'fas fa-tools';
        default:
            return 'fas fa-calendar-alt';
    }
}

// Función para obtener el color del tipo de evento
function getEventColor($tipo_evento) {
    switch (strtolower($tipo_evento)) {
        case 'celebración':
        case 'celebracion':
            return 'success';
        case 'reunión':
        case 'reunion':
            return 'primary';
        case 'capacitación':
        case 'capacitacion':
            return 'warning';
        case 'conferencia':
            return 'info';
        case 'taller':
            return 'secondary';
        default:
            return 'dark';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos - Dashboard Corporativo</title>
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
        .event-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            border-left: 4px solid #6c5ce7;
        }
        .event-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .countdown {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1rem;
        }
        .countdown-number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .countdown-label {
            font-size: 0.8rem;
            opacity: 0.9;
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
                        <a class="nav-link" href="clima.php">
                            <i class="fas fa-cloud-sun me-2"></i>Clima
                        </a>
                        <a class="nav-link" href="cumpleanos.php">
                            <i class="fas fa-birthday-cake me-2"></i>Cumpleaños
                        </a>
                        <a class="nav-link active" href="eventos.php">
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
                        <h2><i class="fas fa-calendar-alt me-2"></i>Gestión de Eventos</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus me-2"></i>Crear Evento
                        </button>
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
                    
                    <!-- Próximo Evento Principal -->
                    <?php 
                    $proximo_evento = null;
                    foreach ($eventos as $evento) {
                        if ($evento['dias_restantes'] >= 0 && $evento['activo']) {
                            $proximo_evento = $evento;
                            break;
                        }
                    }
                    
                    if ($proximo_evento): 
                    ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Próximo Evento</h5>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h4><?php echo htmlspecialchars($proximo_evento['titulo']); ?></h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($proximo_evento['descripcion']); ?></p>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                            <span><?php echo $proximo_evento['fecha_formateada']; ?></span>
                                        </div>
                                        <div class="d-flex align-items-center mt-1">
                                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                            <span><?php echo htmlspecialchars($proximo_evento['ubicacion']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="countdown">
                                            <div class="countdown-number"><?php echo $proximo_evento['dias_restantes']; ?></div>
                                            <div class="countdown-label">DÍAS RESTANTES</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Lista de Eventos -->
                    <div class="row">
                        <?php foreach ($eventos as $evento): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="event-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="event-icon text-<?php echo getEventColor($evento['tipo_evento']); ?>">
                                            <i class="<?php echo getEventIcon($evento['tipo_evento']); ?>"></i>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $evento['activo'] ? 'success' : 'secondary'; ?> mb-2" 
                                                  style="cursor: pointer;" 
                                                  onclick="toggleActive(<?php echo $evento['id']; ?>)"
                                                  title="Clic para cambiar estado">
                                                <?php echo $evento['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($evento['descripcion']); ?></p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                            <small><?php echo htmlspecialchars($evento['ubicacion']); ?></small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tag me-2 text-primary"></i>
                                            <small><?php echo htmlspecialchars($evento['tipo_evento']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end align-items-center">
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editEvent(<?php echo htmlspecialchars(json_encode($evento)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteEvent(<?php echo $evento['id']; ?>, '<?php echo htmlspecialchars($evento['titulo']); ?>')">
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
    
    <!-- Modal Agregar Evento -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Evento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título del Evento</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
                        </div>
                        <div class="mb-3">
                            <label for="tipo_evento" class="form-label">Tipo de Evento</label>
                            <select class="form-select" id="tipo_evento" name="tipo_evento" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="Celebración">Celebración</option>
                                <option value="Reunión">Reunión</option>
                                <option value="Capacitación">Capacitación</option>
                                <option value="Conferencia">Conferencia</option>
                                <option value="Taller">Taller</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_evento" class="form-label">Fecha y Hora del Evento</label>
                            <input type="datetime-local" class="form-control" id="fecha_evento" name="fecha_evento" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" checked>
                                <label class="form-check-label" for="activo">
                                    Evento Activo
                                </label>
                                <div class="form-text">Los eventos activos se mostrarán en el dashboard</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Evento -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Evento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título del Evento</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="edit_ubicacion" name="ubicacion" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tipo_evento" class="form-label">Tipo de Evento</label>
                            <select class="form-select" id="edit_tipo_evento" name="tipo_evento" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="Celebración">Celebración</option>
                                <option value="Reunión">Reunión</option>
                                <option value="Capacitación">Capacitación</option>
                                <option value="Conferencia">Conferencia</option>
                                <option value="Taller">Taller</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_fecha_evento" class="form-label">Fecha y Hora del Evento</label>
                            <input type="datetime-local" class="form-control" id="edit_fecha_evento" name="fecha_evento" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_activo" name="activo" value="1">
                                <label class="form-check-label" for="edit_activo">
                                    Evento Activo
                                </label>
                                <div class="form-text">Los eventos activos se mostrarán en el dashboard</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1">
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
                        <p>¿Está seguro de que desea eliminar el evento "<span id="delete_titulo"></span>"?</p>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEvent(event) {
            document.getElementById('edit_id').value = event.id;
            document.getElementById('edit_titulo').value = event.titulo || '';
            document.getElementById('edit_descripcion').value = event.descripcion || '';
            document.getElementById('edit_ubicacion').value = event.ubicacion || '';
            document.getElementById('edit_tipo_evento').value = event.tipo_evento || '';
            
            // Formatear la fecha para datetime-local (YYYY-MM-DDTHH:MM)
            if (event.fecha_evento) {
                const fecha = new Date(event.fecha_evento);
                const fechaFormateada = fecha.toISOString().slice(0, 16);
                document.getElementById('edit_fecha_evento').value = fechaFormateada;
            }
            
            // Marcar el checkbox de activo
            document.getElementById('edit_activo').checked = event.activo == 1;
            
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }
        
        function toggleActive(id) {
            if (confirm('¿Estás seguro de que quieres cambiar el estado de este evento?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteEvent(id, titulo) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_titulo').textContent = titulo;
            
            new bootstrap.Modal(document.getElementById('deleteEventModal')).show();
        }
    </script>
</body>
</html>
