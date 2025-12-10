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
                $url_destino = sanitizeInput($_POST['url_destino']);
                $activa_principal = isset($_POST['activa_principal']) ? 1 : 0;
                
                // Validaciones específicas
                if (empty($titulo)) {
                    $_SESSION['error'] = 'El título es obligatorio';
                    header('Location: campanas.php');
                    exit();
                } elseif (empty($url_destino)) {
                    $_SESSION['error'] = 'La URL de destino es obligatoria';
                    header('Location: campanas.php');
                    exit();
                } elseif (!filter_var($url_destino, FILTER_VALIDATE_URL)) {
                    $_SESSION['error'] = 'La URL de destino no tiene un formato válido';
                    header('Location: campanas.php');
                    exit();
                } else {
                    try {
                        // Si se marca como principal, desactivar todas las demás
                        if ($activa_principal) {
                            $updateQuery = "UPDATE campanas_qr SET activa_principal = 0";
                            $updateStmt = $db->prepare($updateQuery);
                            $updateStmt->execute();
                        }
                        
                        $query = "INSERT INTO campanas_qr (titulo, descripcion, url, activa_principal) VALUES (:titulo, :descripcion, :url, :activa_principal)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':titulo', $titulo);
                        $stmt->bindParam(':descripcion', $descripcion);
                        $stmt->bindParam(':url', $url_destino);
                        $stmt->bindParam(':activa_principal', $activa_principal);
                        $stmt->execute();
                        $_SESSION['message'] = 'Campaña QR agregada exitosamente';
                        header('Location: campanas.php');
                        exit();
                    } catch (PDOException $e) {
                        // Mensajes de error específicos basados en el código de error
                        $error_msg = '';
                        if ($e->getCode() == 23000) {
                            $error_msg = 'Ya existe una campaña con ese título. Por favor, elija un título diferente.';
                        } elseif ($e->getCode() == 42000) {
                            $error_msg = 'Error en la estructura de la base de datos. Contacte al administrador del sistema.';
                        } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $error_msg = 'Ya existe una campaña con esos datos. Por favor, verifique la información.';
                        } elseif (strpos($e->getMessage(), 'Data too long') !== false) {
                            $error_msg = 'Algunos campos son demasiado largos. Por favor, reduzca el texto.';
                        } else {
                            $error_msg = 'Error al agregar la campaña QR: ' . $e->getMessage();
                        }
                        $_SESSION['error'] = $error_msg;
                        header('Location: campanas.php');
                        exit();
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Error inesperado al agregar la campaña QR: ' . $e->getMessage();
                        header('Location: campanas.php');
                        exit();
                    }
                }
                
            case 'edit':
                $id = (int)$_POST['id'];
                $titulo = sanitizeInput($_POST['titulo']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $url_destino = sanitizeInput($_POST['url_destino']);
                $activa_principal = isset($_POST['activa_principal']) ? 1 : 0;
                
                // Validaciones específicas
                if (empty($titulo)) {
                    $_SESSION['error'] = 'El título es obligatorio';
                    header('Location: campanas.php');
                    exit();
                } elseif (empty($url_destino)) {
                    $_SESSION['error'] = 'La URL de destino es obligatoria';
                    header('Location: campanas.php');
                    exit();
                } elseif (!filter_var($url_destino, FILTER_VALIDATE_URL)) {
                    $_SESSION['error'] = 'La URL de destino no tiene un formato válido';
                    header('Location: campanas.php');
                    exit();
                } else {
                    try {
                        // Si se marca como principal, desactivar todas las demás
                        if ($activa_principal) {
                            $updateQuery = "UPDATE campanas_qr SET activa_principal = 0 WHERE id != :id";
                            $updateStmt = $db->prepare($updateQuery);
                            $updateStmt->bindParam(':id', $id);
                            $updateStmt->execute();
                        }
                        
                        $query = "UPDATE campanas_qr SET titulo = :titulo, descripcion = :descripcion, url = :url, activa_principal = :activa_principal WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':titulo', $titulo);
                        $stmt->bindParam(':descripcion', $descripcion);
                        $stmt->bindParam(':url', $url_destino);
                        $stmt->bindParam(':activa_principal', $activa_principal);
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                        $_SESSION['message'] = 'Campaña QR actualizada exitosamente';
                        header('Location: campanas.php');
                        exit();
                    } catch (PDOException $e) {
                        // Mensajes de error específicos basados en el código de error
                        $error_msg = '';
                        if ($e->getCode() == 23000) {
                            $error_msg = 'Ya existe una campaña con ese título. Por favor, elija un título diferente.';
                        } elseif ($e->getCode() == 42000) {
                            $error_msg = 'Error en la estructura de la base de datos. Contacte al administrador del sistema.';
                        } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $error_msg = 'Ya existe una campaña con esos datos. Por favor, verifique la información.';
                        } elseif (strpos($e->getMessage(), 'Data too long') !== false) {
                            $error_msg = 'Algunos campos son demasiado largos. Por favor, reduzca el texto.';
                        } else {
                            $error_msg = 'Error al actualizar la campaña QR: ' . $e->getMessage();
                        }
                        $_SESSION['error'] = $error_msg;
                        header('Location: campanas.php');
                        exit();
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Error inesperado al actualizar la campaña QR: ' . $e->getMessage();
                        header('Location: campanas.php');
                        exit();
                    }
                }
                
            case 'toggle_active':
                $id = (int)$_POST['id'];
                try {
                    // Solo activar campañas inactivas (ya que solo ellas tienen el botón ACTIVAR)
                    // Desactivar todas las campañas y quitarles el estado principal
                    $deactivateQuery = "UPDATE campanas_qr SET activo = 0, activa_principal = 0";
                    $deactivateStmt = $db->prepare($deactivateQuery);
                    $deactivateStmt->execute();
                    
                    // Activar solo esta campaña y hacerla principal
                    $activateQuery = "UPDATE campanas_qr SET activo = 1, activa_principal = 1 WHERE id = :id";
                    $activateStmt = $db->prepare($activateQuery);
                    $activateStmt->bindParam(':id', $id);
                    $activateStmt->execute();
                    
                    $_SESSION['message'] = 'Campaña activada exitosamente. Las demás campañas han sido desactivadas.';
                    header('Location: campanas.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar el estado de la campaña';
                    header('Location: campanas.php');
                    exit();
                }
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $query = "DELETE FROM campanas_qr WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                    $_SESSION['message'] = 'Campaña QR eliminada exitosamente';
                    header('Location: campanas.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al eliminar la campaña QR';
                    header('Location: campanas.php');
                    exit();
                }
        }
    }
}

// Función para generar código QR (usando API externa)
function generateQRCode($url) {
    // En un entorno de producción, sería mejor usar una librería local como phpqrcode
    // Por ahora usamos una API externa simple
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
    return $qr_url;
}


// Obtener todas las campañas
try {
    $query = "SELECT *, 
              CASE WHEN activo = 1 THEN 'Activa' ELSE 'Inactiva' END as estado_campana 
              FROM campanas_qr 
              ORDER BY activo DESC, activa_principal DESC, fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $campanas = $stmt->fetchAll();
} catch (Exception $e) {
    $campanas = [];
}

// Función para obtener el icono del tipo de campaña
function getCampaignIcon($tipo_campana) {
    if (empty($tipo_campana)) {
        return 'fas fa-qrcode';
    }
    switch (strtolower($tipo_campana)) {
        case 'encuesta':
            return 'fas fa-poll';
        case 'informativo':
            return 'fas fa-info-circle';
        case 'promocional':
            return 'fas fa-bullhorn';
        case 'evento':
            return 'fas fa-calendar-alt';
        case 'formulario':
            return 'fas fa-file-alt';
        default:
            return 'fas fa-qrcode';
    }
}

// Función para obtener el color del tipo de campaña
function getCampaignColor($tipo_campana) {
    if (empty($tipo_campana)) {
        return 'dark';
    }
    switch (strtolower($tipo_campana)) {
        case 'encuesta':
            return 'primary';
        case 'informativo':
            return 'info';
        case 'promocional':
            return 'warning';
        case 'evento':
            return 'success';
        case 'formulario':
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
    <title>Gestión de Campañas QR - Dashboard Corporativo</title>
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
        .campaign-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            border-left: 4px solid #00b894;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }
        .qr-code img {
            max-width: 100%;
            max-height: 100%;
        }
        .campaign-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link" href="eventos.php">
                            <i class="fas fa-calendar-alt me-2"></i>Eventos
                        </a>
                        <a class="nav-link active" href="campanas.php">
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
                        <h2><i class="fas fa-qrcode me-2"></i>Gestión de Campañas QR</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCampaignModal">
                            <i class="fas fa-plus me-2"></i>Crear Campaña QR
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
                    
                    <!-- Lista de Campañas -->
                    <div class="row">
                        <?php foreach ($campanas as $campana): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="campaign-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="campaign-icon text-<?php echo getCampaignColor($campana['tipo_campana'] ?? null); ?>">
                                            <i class="<?php echo getCampaignIcon($campana['tipo_campana'] ?? null); ?>"></i>
                                        </div>
                                        <div class="text-end">
                        <?php if (!$campana['activo']): ?>
                            <span class="badge bg-primary mb-2" 
                                  style="cursor: pointer;" 
                                  onclick="toggleActive(<?php echo $campana['id']; ?>)"
                                  title="Clic para activar campaña">
                                ACTIVAR
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success mb-2">
                                Activa
                            </span>
                            <?php if ($campana['activa_principal']): ?>
                                <br>
                                <span class="badge bg-warning mb-2">
                                    <i class="fas fa-star me-1"></i>Principal
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                                            <br>
                                            <span class="badge bg-<?php 
                                                echo $campana['estado_campana'] == 'Activa' ? 'primary' : 
                                                    ($campana['estado_campana'] == 'Programada' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo $campana['estado_campana']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <h5 class="card-title"><?php echo htmlspecialchars($campana['titulo']); ?></h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($campana['descripcion']); ?></p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-link me-2 text-primary"></i>
                                            <small class="text-truncate"><?php echo htmlspecialchars($campana['url'] ?? 'Sin URL'); ?></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-tag me-2 text-primary"></i>
                                            <small><?php echo htmlspecialchars($campana['tipo_campana'] ?? 'General'); ?></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-calendar me-2 text-primary"></i>
                                            <small><?php 
                                                $fecha_creacion = $campana['fecha_creacion'] ?? date('Y-m-d');
                                                echo date('d/m/Y', strtotime($fecha_creacion));
                                            ?></small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-sort-numeric-up me-2 text-primary"></i>
                                            <small>ID: <?php echo $campana['id'] ?? 'N/A'; ?></small>
                                        </div>
                                    </div>
                                    
                                    <!-- Código QR -->
                                    <div class="d-flex justify-content-center mb-3">
                                        <div class="qr-code">
                                            <?php if (!empty($campana['url'])): ?>
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($campana['url']); ?>" 
                                                     alt="Código QR para <?php echo htmlspecialchars($campana['titulo']); ?>"
                                                     class="img-fluid">
                                            <?php else: ?>
                                                <i class="fas fa-qrcode fa-3x text-muted"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button class="btn btn-sm btn-outline-info" onclick="previewQR(<?php echo $campana['id']; ?>)">
                                            <i class="fas fa-eye"></i> Ver QR
                                        </button>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCampaign(<?php echo htmlspecialchars(json_encode($campana)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCampaign(<?php echo $campana['id']; ?>, '<?php echo htmlspecialchars($campana['titulo']); ?>')">
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
    
    <!-- Modal Agregar Campaña -->
    <div class="modal fade" id="addCampaignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Campaña QR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título de la Campaña</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="url_destino" class="form-label">URL de Destino</label>
                            <input type="url" class="form-control" id="url_destino" name="url_destino" required>
                            <div class="form-text">La URL a la que dirigirá el código QR</div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activa_principal" name="activa_principal">
                                <label class="form-check-label" for="activa_principal">Campaña Principal</label>
                                <div class="form-text">Solo una campaña puede ser principal a la vez. Esta se mostrará en el dashboard.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Campaña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Campaña -->
    <div class="modal fade" id="editCampaignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Campaña QR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título de la Campaña</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_url_destino" class="form-label">URL de Destino</label>
                            <input type="url" class="form-control" id="edit_url_destino" name="url_destino" required>
                            <div class="form-text">La URL a la que dirigirá el código QR</div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_activa_principal" name="activa_principal">
                                <label class="form-check-label" for="edit_activa_principal">Campaña Principal</label>
                                <div class="form-text">Solo una campaña puede ser principal a la vez. Esta se mostrará en el dashboard.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Campaña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteCampaignModal" tabindex="-1">
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
                        <p>¿Está seguro de que desea eliminar la campaña "<span id="delete_titulo"></span>"?</p>
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
    
    <!-- Modal Ver QR -->
    <div class="modal fade" id="viewQRModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Código QR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qr_preview" class="mb-3">
                        <!-- QR code will be loaded here -->
                    </div>
                    <button class="btn btn-primary" onclick="downloadQR()">
                        <i class="fas fa-download me-2"></i>Descargar QR
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCampaign(campaign) {
            document.getElementById('edit_id').value = campaign.id || '';
            document.getElementById('edit_titulo').value = campaign.titulo || '';
            document.getElementById('edit_descripcion').value = campaign.descripcion || '';
            document.getElementById('edit_url_destino').value = campaign.url || '';
            document.getElementById('edit_activa_principal').checked = campaign.activa_principal == 1;
            
            new bootstrap.Modal(document.getElementById('editCampaignModal')).show();
        }
        
        function toggleActive(id) {
            if (confirm('¿Estás seguro de que quieres activar esta campaña? Las demás campañas serán desactivadas.')) {
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
        
        function deleteCampaign(id, titulo) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_titulo').textContent = titulo;
            
            new bootstrap.Modal(document.getElementById('deleteCampaignModal')).show();
        }
        
        function previewQR(id) {
            // En una implementación real, esto cargaría el QR desde la base de datos
            document.getElementById('qr_preview').innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=example" class="img-fluid" alt="QR Code">';
            
            new bootstrap.Modal(document.getElementById('viewQRModal')).show();
        }
        
        function downloadQR() {
            // Función para descargar el código QR
            const qrImg = document.querySelector('#qr_preview img');
            if (qrImg) {
                const link = document.createElement('a');
                link.href = qrImg.src;
                link.download = 'qr-code.png';
                link.click();
            }
        }
        
    </script>
</body>
</html>
