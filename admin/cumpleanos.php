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
                $nombre = sanitizeInput($_POST['nombre']);
                $apellido = sanitizeInput($_POST['apellido']);
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $cargo = sanitizeInput($_POST['cargo']);
                $departamento = sanitizeInput($_POST['departamento']);
                
                try {
                    $query = "INSERT INTO cumpleanos (nombre, apellido, fecha_nacimiento, cargo, departamento) VALUES (:nombre, :apellido, :fecha_nacimiento, :cargo, :departamento)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':apellido', $apellido);
                    $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                    $stmt->bindParam(':cargo', $cargo);
                    $stmt->bindParam(':departamento', $departamento);
                    $stmt->execute();
                    $_SESSION['message'] = 'Cumpleaños agregado exitosamente';
                    header('Location: cumpleanos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al agregar el cumpleaños';
                    header('Location: cumpleanos.php');
                    exit();
                }
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nombre = sanitizeInput($_POST['nombre']);
                $apellido = sanitizeInput($_POST['apellido']);
                $fecha_nacimiento = $_POST['fecha_nacimiento'];
                $cargo = sanitizeInput($_POST['cargo']);
                $departamento = sanitizeInput($_POST['departamento']);
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                try {
                    $query = "UPDATE cumpleanos SET nombre = :nombre, apellido = :apellido, fecha_nacimiento = :fecha_nacimiento, cargo = :cargo, departamento = :departamento, activo = :activo WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':apellido', $apellido);
                    $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
                    $stmt->bindParam(':cargo', $cargo);
                    $stmt->bindParam(':departamento', $departamento);
                    $stmt->bindParam(':activo', $activo);
                    $stmt->execute();
                    $_SESSION['message'] = 'Cumpleaños actualizado exitosamente';
                    header('Location: cumpleanos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar el cumpleaños';
                    header('Location: cumpleanos.php');
                    exit();
                }
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $query = "DELETE FROM cumpleanos WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    $_SESSION['message'] = 'Cumpleaños eliminado exitosamente';
                    header('Location: cumpleanos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al eliminar el cumpleaños';
                    header('Location: cumpleanos.php');
                    exit();
                }
        }
    }
}

// Obtener cumpleaños
try {
    // Obtener todos los cumpleaños
    $query = "SELECT *, 
              DATE_FORMAT(fecha_nacimiento, '%d') as dia,
              DATE_FORMAT(fecha_nacimiento, '%m') as mes,
              DATE_FORMAT(fecha_nacimiento, '%M') as mes_nombre
              FROM cumpleanos";
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
    
    $cumpleanos = $todos_cumpleanos;
} catch (Exception $e) {
    $cumpleanos = [];
}

// Función para obtener el próximo cumpleaños
function getNextBirthday($fecha_nacimiento) {
    $fecha_actual = new DateTime();
    $fecha_nacimiento = new DateTime($fecha_nacimiento);
    
    $cumpleanos_este_año = new DateTime($fecha_actual->format('Y') . '-' . $fecha_nacimiento->format('m-d'));
    
    if ($cumpleanos_este_año < $fecha_actual) {
        $cumpleanos_este_año->add(new DateInterval('P1Y'));
    }
    
    return $cumpleanos_este_año;
}

// Función para calcular días hasta el cumpleaños
function getDaysUntilBirthday($fecha_nacimiento) {
    $proximo_cumpleanos = getNextBirthday($fecha_nacimiento);
    $fecha_actual = new DateTime();
    $diferencia = $fecha_actual->diff($proximo_cumpleanos);
    return $diferencia->days;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cumpleaños - Dashboard Corporativo</title>
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
        .birthday-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            border-left: 4px solid #fdcb6e;
        }
        .birthday-date {
            background: linear-gradient(45deg, #fdcb6e, #e17055);
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .days-until {
            background: #e17055;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
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
                        <a class="nav-link active" href="cumpleanos.php">
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
                        <h2><i class="fas fa-birthday-cake me-2"></i>Gestión de Cumpleaños</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBirthdayModal">
                            <i class="fas fa-plus me-2"></i>Agregar Cumpleaños
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
                    
                    <!-- Próximos Cumpleaños (máximo 3) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Próximos Cumpleaños (Dashboard)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                $proximos_cumpleanos = array_slice($cumpleanos, 0, 3);
                                foreach ($proximos_cumpleanos as $cumpleano): 
                                    $dias_restantes = getDaysUntilBirthday($cumpleano['fecha_nacimiento']);
                                ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="birthday-date me-3">
                                                <div class="text-center">
                                                    <div><?php echo $cumpleano['dia']; ?></div>
                                                    <small><?php echo strtoupper(substr($cumpleano['mes_nombre'], 0, 3)); ?></small>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($cumpleano['nombre'] . ' ' . $cumpleano['apellido']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($cumpleano['cargo']); ?></small>
                                                <br>
                                                <span class="days-until">
                                                    <?php echo $dias_restantes; ?> días
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista Completa de Cumpleaños -->
                    <div class="row">
                        <?php foreach ($cumpleanos as $cumpleano): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="birthday-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="birthday-date me-3">
                                                <div class="text-center">
                                                    <div><?php echo $cumpleano['dia']; ?></div>
                                                    <small><?php echo strtoupper(substr($cumpleano['mes_nombre'], 0, 3)); ?></small>
                                                </div>
                                            </div>
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($cumpleano['nombre'] . ' ' . $cumpleano['apellido']); ?></h5>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($cumpleano['cargo']); ?></p>
                                                <small class="text-muted"><?php echo htmlspecialchars($cumpleano['departamento']); ?></small>
                                            </div>
                                        </div>
                                        <span class="badge bg-<?php echo $cumpleano['activo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $cumpleano['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="days-until">
                                            <?php echo getDaysUntilBirthday($cumpleano['fecha_nacimiento']); ?> días restantes
                                        </span>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editBirthday(<?php echo htmlspecialchars(json_encode($cumpleano)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteBirthday(<?php echo $cumpleano['id']; ?>, '<?php echo htmlspecialchars($cumpleano['nombre'] . ' ' . $cumpleano['apellido']); ?>')">
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
    
    <!-- Modal Agregar Cumpleaños -->
    <div class="modal fade" id="addBirthdayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Cumpleaños</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                        </div>
                        <div class="mb-3">
                            <label for="cargo" class="form-label">Cargo</label>
                            <input type="text" class="form-control" id="cargo" name="cargo" required>
                        </div>
                        <div class="mb-3">
                            <label for="departamento" class="form-label">Departamento</label>
                            <input type="text" class="form-control" id="departamento" name="departamento" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Cumpleaños</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Cumpleaños -->
    <div class="modal fade" id="editBirthdayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Cumpleaños</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_apellido" class="form-label">Apellido</label>
                                    <input type="text" class="form-control" id="edit_apellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="edit_fecha_nacimiento" name="fecha_nacimiento" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_cargo" class="form-label">Cargo</label>
                            <input type="text" class="form-control" id="edit_cargo" name="cargo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_departamento" class="form-label">Departamento</label>
                            <input type="text" class="form-control" id="edit_departamento" name="departamento" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_activo" name="activo" checked>
                                <label class="form-check-label" for="edit_activo">Cumpleaños Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Cumpleaños</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteBirthdayModal" tabindex="-1">
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
                        <p>¿Está seguro de que desea eliminar el cumpleaños de "<span id="delete_nombre"></span>"?</p>
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
        function editBirthday(birthday) {
            document.getElementById('edit_id').value = birthday.id;
            document.getElementById('edit_nombre').value = birthday.nombre;
            document.getElementById('edit_apellido').value = birthday.apellido;
            document.getElementById('edit_fecha_nacimiento').value = birthday.fecha_nacimiento;
            document.getElementById('edit_cargo').value = birthday.cargo;
            document.getElementById('edit_departamento').value = birthday.departamento;
            document.getElementById('edit_activo').checked = birthday.activo == 1;
            
            new bootstrap.Modal(document.getElementById('editBirthdayModal')).show();
        }
        
        function deleteBirthday(id, nombre) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nombre').textContent = nombre;
            
            new bootstrap.Modal(document.getElementById('deleteBirthdayModal')).show();
        }
    </script>
</body>
</html>
