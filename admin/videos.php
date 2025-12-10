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
    $query = "SELECT COUNT(*) as count FROM videos_corporativos WHERE orden = :orden";
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
                $titulo = sanitizeInput($_POST['titulo']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $orden = (int)$_POST['orden'];
                
                // Validar que el orden no esté duplicado
                if (verificarOrdenExistente($db, $orden)) {
                    $_SESSION['error'] = 'Ya existe un video con el orden ' . $orden . '. Por favor, elija un número de orden diferente.';
                    header('Location: videos.php');
                    exit();
                }
                
                // Procesar subida de archivo
                $archivo_video = '';
                
                // Verificar si se subió un archivo
                if (!isset($_FILES['archivo_video'])) {
                    $_SESSION['error'] = 'No se detectó ningún archivo en la subida';
                    header('Location: videos.php');
                    exit();
                }
                
                // Verificar errores de subida
                $upload_error = $_FILES['archivo_video']['error'];
                if ($upload_error !== 0) {
                    $error_msg = '';
                    switch ($upload_error) {
                        case 1:
                            $error_msg = 'El archivo es demasiado grande (supera upload_max_filesize)';
                            break;
                        case 2:
                            $error_msg = 'El archivo es demasiado grande (supera MAX_FILE_SIZE)';
                            break;
                        case 3:
                            $error_msg = 'El archivo se subió parcialmente';
                            break;
                        case 4:
                            $error_msg = 'No se seleccionó ningún archivo';
                            break;
                        case 6:
                            $error_msg = 'No se encontró el directorio temporal';
                            break;
                        case 7:
                            $error_msg = 'Error al escribir el archivo al disco';
                            break;
                        case 8:
                            $error_msg = 'La subida fue detenida por una extensión PHP';
                            break;
                        default:
                            $error_msg = 'Error desconocido en la subida del archivo (código: ' . $upload_error . ')';
                    }
                    $_SESSION['error'] = $error_msg;
                    header('Location: videos.php');
                    exit();
                }
                
                // Verificar si el directorio de uploads existe
                $upload_dir = '../uploads/videos/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        $_SESSION['error'] = 'No se pudo crear el directorio de uploads: ' . $upload_dir;
                        header('Location: videos.php');
                        exit();
                    }
                }
                
                // Verificar permisos del directorio
                if (!is_writable($upload_dir)) {
                    $_SESSION['error'] = 'El directorio de uploads no tiene permisos de escritura: ' . $upload_dir;
                    header('Location: videos.php');
                    exit();
                }
                
                // Verificar extensión del archivo
                $file_extension = strtolower(pathinfo($_FILES['archivo_video']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['mp4', 'avi', 'mov', 'wmv', 'webm'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    $_SESSION['error'] = 'Formato de archivo no válido. Recibido: ' . $file_extension . '. Formatos permitidos: ' . implode(', ', $allowed_extensions);
                    header('Location: videos.php');
                    exit();
                }
                
                // Verificar tamaño del archivo (límite de 100MB)
                $max_size = 100 * 1024 * 1024; // 100MB
                if ($_FILES['archivo_video']['size'] > $max_size) {
                    $_SESSION['error'] = 'El archivo es demasiado grande. Tamaño máximo: 100MB. Tamaño actual: ' . round($_FILES['archivo_video']['size'] / (1024*1024), 2) . 'MB';
                    header('Location: videos.php');
                    exit();
                }
                
                // Generar nombre único para el archivo
                $archivo_video = uniqid() . '_' . basename($_FILES['archivo_video']['name']);
                $target_path = $upload_dir . $archivo_video;
                
                // Mover el archivo subido
                if (!move_uploaded_file($_FILES['archivo_video']['tmp_name'], $target_path)) {
                    $_SESSION['error'] = 'Error al mover el archivo subido. Verifique permisos del directorio: ' . $upload_dir;
                    header('Location: videos.php');
                    exit();
                }
                
                try {
                    $query = "INSERT INTO videos_corporativos (titulo, archivo_video, descripcion, orden) VALUES (:titulo, :archivo_video, :descripcion, :orden)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':titulo', $titulo);
                    $stmt->bindParam(':archivo_video', $archivo_video);
                    $stmt->bindParam(':descripcion', $descripcion);
                    $stmt->bindParam(':orden', $orden);
                    $stmt->execute();
                    $_SESSION['message'] = 'Video agregado exitosamente: ' . $archivo_video;
                    header('Location: videos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al agregar el video a la base de datos: ' . $e->getMessage();
                    header('Location: videos.php');
                    exit();
                }
                
            case 'edit':
                $id = (int)$_POST['id'];
                $titulo = sanitizeInput($_POST['titulo']);
                $descripcion = sanitizeInput($_POST['descripcion']);
                $orden = (int)$_POST['orden'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                // Validar que el orden no esté duplicado (excluyendo el video actual)
                if (verificarOrdenExistente($db, $orden, $id)) {
                    $_SESSION['error'] = 'Ya existe otro video con el orden ' . $orden . '. Por favor, elija un número de orden diferente.';
                    header('Location: videos.php');
                    exit();
                }
                
                // Procesar subida de archivo (opcional)
                $archivo_video = '';
                
                // Verificar si se subió un archivo nuevo
                if (isset($_FILES['archivo_video']) && $_FILES['archivo_video']['error'] === 0) {
                    // Verificar errores de subida
                    $upload_error = $_FILES['archivo_video']['error'];
                    if ($upload_error !== 0) {
                        $error_msg = '';
                        switch ($upload_error) {
                            case 1: $error_msg = 'El archivo es demasiado grande (supera upload_max_filesize)'; break;
                            case 2: $error_msg = 'El archivo es demasiado grande (supera MAX_FILE_SIZE)'; break;
                            case 3: $error_msg = 'El archivo se subió parcialmente'; break;
                            case 4: $error_msg = 'No se seleccionó ningún archivo'; break;
                            case 6: $error_msg = 'No se encontró el directorio temporal'; break;
                            case 7: $error_msg = 'Error al escribir el archivo al disco'; break;
                            case 8: $error_msg = 'La subida fue detenida por una extensión PHP'; break;
                            default: $error_msg = 'Error desconocido en la subida del archivo (código: ' . $upload_error . ')';
                        }
                        $_SESSION['error'] = $error_msg;
                        header('Location: videos.php');
                        exit();
                    }
                    
                    // Verificar si el directorio de uploads existe
                    $upload_dir = '../uploads/videos/';
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $_SESSION['error'] = 'No se pudo crear el directorio de uploads: ' . $upload_dir;
                            header('Location: videos.php');
                            exit();
                        }
                    }
                    
                    // Verificar permisos del directorio
                    if (!is_writable($upload_dir)) {
                        $_SESSION['error'] = 'El directorio de uploads no tiene permisos de escritura: ' . $upload_dir;
                        header('Location: videos.php');
                        exit();
                    }
                    
                    // Verificar extensión del archivo
                    $file_extension = strtolower(pathinfo($_FILES['archivo_video']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['mp4', 'avi', 'mov', 'wmv', 'webm'];
                    
                    if (!in_array($file_extension, $allowed_extensions)) {
                        $_SESSION['error'] = 'Formato de archivo no válido. Recibido: ' . $file_extension . '. Formatos permitidos: ' . implode(', ', $allowed_extensions);
                        header('Location: videos.php');
                        exit();
                    }
                    
                    // Verificar tamaño del archivo (límite de 100MB)
                    $max_size = 100 * 1024 * 1024; // 100MB
                    if ($_FILES['archivo_video']['size'] > $max_size) {
                        $_SESSION['error'] = 'El archivo es demasiado grande. Tamaño máximo: 100MB. Tamaño actual: ' . round($_FILES['archivo_video']['size'] / (1024*1024), 2) . 'MB';
                        header('Location: videos.php');
                        exit();
                    }
                    
                    // Generar nombre único para el archivo
                    $archivo_video = uniqid() . '_' . basename($_FILES['archivo_video']['name']);
                    $target_path = $upload_dir . $archivo_video;
                    
                    // Mover el archivo subido
                    if (!move_uploaded_file($_FILES['archivo_video']['tmp_name'], $target_path)) {
                        $_SESSION['error'] = 'Error al mover el archivo subido. Verifique permisos del directorio: ' . $upload_dir;
                        header('Location: videos.php');
                        exit();
                    }
                }
                
                try {
                    if (!empty($archivo_video)) {
                        // Actualizar con nuevo archivo
                        $query = "UPDATE videos_corporativos SET titulo = :titulo, archivo_video = :archivo_video, descripcion = :descripcion, orden = :orden, activo = :activo WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':archivo_video', $archivo_video);
                    } else {
                        // Actualizar solo metadatos
                        $query = "UPDATE videos_corporativos SET titulo = :titulo, descripcion = :descripcion, orden = :orden, activo = :activo WHERE id = :id";
                        $stmt = $db->prepare($query);
                    }
                    
                    $stmt->bindParam(':id', $id);
                    $stmt->bindParam(':titulo', $titulo);
                    $stmt->bindParam(':descripcion', $descripcion);
                    $stmt->bindParam(':orden', $orden);
                    $stmt->bindParam(':activo', $activo);
                    $stmt->execute();
                    $_SESSION['message'] = 'Video actualizado exitosamente';
                    header('Location: videos.php');
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al actualizar el video: ' . $e->getMessage();
                    header('Location: videos.php');
                    exit();
                }
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    // Primero obtener el nombre del archivo del video antes de eliminarlo
                    $query_select = "SELECT archivo_video FROM videos_corporativos WHERE id = :id";
                    $stmt_select = $db->prepare($query_select);
                    $stmt_select->bindParam(':id', $id);
                    $stmt_select->execute();
                    
                    if ($stmt_select->rowCount() > 0) {
                        $video = $stmt_select->fetch();
                        $archivo_video = $video['archivo_video'];
                        
                        // Eliminar el archivo físico si existe
                        if (!empty($archivo_video)) {
                            $upload_dir = '../uploads/videos/';
                            $file_path = $upload_dir . $archivo_video;
                            
                            if (file_exists($file_path) && is_file($file_path)) {
                                if (unlink($file_path)) {
                                    // Archivo eliminado exitosamente
                                } else {
                                    // El archivo no se pudo eliminar, pero continuamos con la eliminación del registro
                                    error_log("No se pudo eliminar el archivo: " . $file_path);
                                }
                            }
                        }
                        
                        // Ahora eliminar el registro de la base de datos
                        $query = "DELETE FROM videos_corporativos WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                        $_SESSION['message'] = 'Video eliminado exitosamente';
                        header('Location: videos.php');
                        exit();
                    } else {
                        $_SESSION['error'] = 'Video no encontrado';
                        header('Location: videos.php');
                        exit();
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Error al eliminar el video: ' . $e->getMessage();
                    header('Location: videos.php');
                    exit();
                }
        }
    }
}

// Obtener videos
try {
    $query = "SELECT * FROM videos_corporativos ORDER BY orden ASC, fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $videos = $stmt->fetchAll();
} catch (Exception $e) {
    $videos = [];
}

// Función para obtener el siguiente orden disponible
function obtenerSiguienteOrden($videos) {
    $ordenes_ocupados = array_column($videos, 'orden');
    $siguiente_orden = 1;
    
    while (in_array($siguiente_orden, $ordenes_ocupados)) {
        $siguiente_orden++;
    }
    
    return $siguiente_orden;
}

$siguiente_orden = obtenerSiguienteOrden($videos);

// Función para convertir tamaño de PHP ini a bytes (renombrada para evitar conflicto)
function convertToBytesForValidation($val) {
    // Validación inicial
    if ($val === false || $val === null || $val === '') {
        return 10485760; // Default 10MB
    }
    
    if (!is_string($val)) {
        $val = (string)$val;
    }
    
    $val = trim($val);
    if ($val === '' || strlen($val) === 0) {
        return 10485760; // Default 10MB
    }
    
    // Extraer el último carácter (unidad)
    $len = strlen($val);
    if ($len === 0) {
        return 10485760; // Default 10MB
    }
    
    $last = strtolower($val[$len - 1]);
    $num = (int)$val;
    
    // Si no hay número válido, retornar default
    if ($num <= 0) {
        return 10485760; // Default 10MB
    }
    
    // Convertir según la unidad
    switch($last) {
        case 'g':
            $num = $num * 1024 * 1024 * 1024;
            break;
        case 'm':
            $num = $num * 1024 * 1024;
            break;
        case 'k':
            $num = $num * 1024;
            break;
        default:
            // Si no hay sufijo, se asume que ya está en bytes
            break;
    }
    
    // Validar que el resultado sea válido
    return ($num > 0) ? $num : 10485760; // Default 10MB si el resultado es 0
}

// Obtener límites en bytes con manejo de errores
$max_file_size_bytes = 10485760; // Default 10MB

try {
    $upload_max_filesize_str = @ini_get('upload_max_filesize');
    $post_max_size_str = @ini_get('post_max_size');
    
    $upload_max_filesize_bytes = 10485760; // Default
    $post_max_size_bytes = 10485760; // Default
    
    if ($upload_max_filesize_str !== false && $upload_max_filesize_str !== null && $upload_max_filesize_str !== '') {
        $upload_max_filesize_bytes = convertToBytesForValidation($upload_max_filesize_str);
    }
    
    if ($post_max_size_str !== false && $post_max_size_str !== null && $post_max_size_str !== '') {
        $post_max_size_bytes = convertToBytesForValidation($post_max_size_str);
    }
    
    // Usar el menor de los dos como límite efectivo
    $calculated_max = min($upload_max_filesize_bytes, $post_max_size_bytes);
    
    // Asegurar que nunca sea 0 o inválido
    if (is_numeric($calculated_max) && $calculated_max > 0) {
        $max_file_size_bytes = $calculated_max;
    }
} catch (Exception $e) {
    // Si hay algún error, mantener el valor por defecto
    $max_file_size_bytes = 10485760; // Default 10MB
} catch (Throwable $e) {
    // Capturar cualquier error fatal también
    $max_file_size_bytes = 10485760; // Default 10MB
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Videos - Dashboard Corporativo</title>
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
        /* Z-index solo cuando el modal de loading está visible */
        #loadingModal.show {
            z-index: 10050 !important;
        }
        #loadingModal.show .modal-dialog {
            z-index: 10051 !important;
        }
        #loadingModal.show .modal-content {
            background: white !important;
            border: none !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
            border-radius: 15px !important;
            position: relative !important;
            z-index: 10052 !important;
        }
        #loadingModal .spinner-border {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .video-preview {
            width: 100%;
            height: 200px;
            background: #000;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 10px;
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
                        <a class="nav-link active" href="videos.php">
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
                        <h2><i class="fas fa-play me-2"></i>Gestión de Videos Corporativos</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                            <i class="fas fa-plus me-2"></i>Agregar Video
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
                    
                    <!-- Lista de Videos -->
                    <div class="row">
                        <?php foreach ($videos as $video): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card">
                                    <div class="video-preview">
                                        <i class="fas fa-play-circle fa-3x"></i>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($video['titulo']); ?></h5>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($video['descripcion']); ?></p>
                                        <p class="card-text text-info small">
                                            <i class="fas fa-file-video me-1"></i>
                                            <?php echo htmlspecialchars($video['archivo_video']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Orden: <?php echo $video['orden']; ?></small>
                                            <span class="badge bg-<?php echo $video['activo'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $video['activo'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editVideo(<?php echo htmlspecialchars(json_encode($video)); ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteVideo(<?php echo $video['id']; ?>, '<?php echo htmlspecialchars($video['titulo']); ?>')">
                                                <i class="fas fa-trash"></i> Eliminar
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
    
    <!-- Modal de Loading -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem; border-width: 0.4rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5 class="mt-4 mb-2">Subiendo video...</h5>
                    <p class="text-muted mb-0">Por favor, espere. Esto puede tardar unos minutos.</p>
                    <p class="text-muted small mt-2">No cierre esta ventana mientras se procesa el archivo.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Agregar Video -->
    <div class="modal fade" id="addVideoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="addVideoForm" onsubmit="return showLoadingModal(event)">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Video Corporativo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="archivo_video" class="form-label">Archivo de Video</label>
                            <input type="file" class="form-control" id="archivo_video" name="archivo_video" accept="video/*" required>
                            <div class="invalid-feedback" id="archivo_video_error"></div>
                            <div class="form-text">
                                Formatos soportados: MP4, AVI, MOV, WMV, WEBM<br>
                                Tamaño máximo: <?php echo ini_get('upload_max_filesize'); ?><br>
                                <!-- <small class="text-muted">
                                    PHP upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?> | 
                                    post_max_size: <?php echo ini_get('post_max_size'); ?>
                                </small> -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="orden" class="form-label">Orden de Reproducción</label>
                            <input type="number" class="form-control" id="orden" name="orden" value="<?php echo $siguiente_orden; ?>" min="1">
                            <div class="form-text">Sugerencia: Orden <?php echo $siguiente_orden; ?> (próximo disponible)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Video</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Video -->
    <div class="modal fade" id="editVideoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="editVideoForm" onsubmit="return showLoadingModal(event)">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Video Corporativo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="edit_titulo" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_archivo_video" class="form-label">Archivo de Video (opcional)</label>
                            <input type="file" class="form-control" id="edit_archivo_video" name="archivo_video" accept="video/*">
                            <div class="invalid-feedback" id="edit_archivo_video_error"></div>
                            <div class="form-text">
                                <strong>Límite:</strong> <?php echo ini_get('upload_max_filesize'); ?> | <strong>Formatos:</strong> MP4, AVI, MOV, WMV, WEBM<br>
                                <strong>Nota:</strong> Si no selecciona un archivo, se mantendrá el video actual
                            </div>
                            <div id="edit_current_video" class="mt-2">
                                <small class="text-muted">Video actual: <span id="edit_current_filename"></span></small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_orden" class="form-label">Orden de Reproducción</label>
                            <input type="number" class="form-control" id="edit_orden" name="orden" min="1">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_activo" name="activo" checked>
                                <label class="form-check-label" for="edit_activo">Video Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Video</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteVideoModal" tabindex="-1">
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
                        <p>¿Está seguro de que desea eliminar el video "<span id="delete_titulo"></span>"?</p>
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
        function editVideo(video) {
            document.getElementById('edit_id').value = video.id;
            document.getElementById('edit_titulo').value = video.titulo;
            document.getElementById('edit_descripcion').value = video.descripcion;
            document.getElementById('edit_orden').value = video.orden;
            document.getElementById('edit_activo').checked = video.activo == 1;
            
            // Mostrar el archivo de video actual
            document.getElementById('edit_current_filename').textContent = video.archivo_video;
            
            // Limpiar el input de archivo para que no interfiera
            document.getElementById('edit_archivo_video').value = '';
            
            new bootstrap.Modal(document.getElementById('editVideoModal')).show();
        }
        
        function deleteVideo(id, titulo) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_titulo').textContent = titulo;
            
            new bootstrap.Modal(document.getElementById('deleteVideoModal')).show();
        }
        
        // Validación en tiempo real para orden duplicado
        const ordenesOcupados = <?php echo json_encode(array_column($videos, 'orden')); ?>;
        
        function validarOrden(input, isEdit = false, currentId = null) {
            const orden = parseInt(input.value);
            const feedback = input.nextElementSibling;
            
            if (isNaN(orden) || orden < 1) {
                input.classList.remove('is-valid', 'is-invalid');
                if (feedback) feedback.textContent = '';
                return;
            }
            
            let ordenOcupado = false;
            if (isEdit && currentId) {
                // Para edición, verificar si el orden está ocupado por otro video
                ordenOcupado = ordenesOcupados.includes(orden);
            } else {
                // Para agregar, verificar si el orden está ocupado
                ordenOcupado = ordenesOcupados.includes(orden);
            }
            
            if (ordenOcupado) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                if (feedback) {
                    feedback.textContent = 'Este orden ya está ocupado por otro video';
                    feedback.className = 'form-text text-danger';
                }
            } else {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                if (feedback) {
                    feedback.textContent = 'Orden disponible';
                    feedback.className = 'form-text text-success';
                }
            }
        }
        
        // Límite máximo de tamaño de archivo en bytes (desde PHP)
        const MAX_FILE_SIZE_BYTES = <?php echo isset($max_file_size_bytes) && is_numeric($max_file_size_bytes) ? $max_file_size_bytes : 10485760; ?>;
        const MAX_FILE_SIZE_MB = (MAX_FILE_SIZE_BYTES / (1024 * 1024)).toFixed(2);
        
        // Función para formatear bytes a formato legible
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        // Función para validar el tamaño del archivo
        function validateFileSize(fileInput, errorElement) {
            if (!fileInput || !fileInput.files.length) {
                return true; // No hay archivo, no hay problema
            }
            
            const file = fileInput.files[0];
            const fileSize = file.size;
            
            if (fileSize > MAX_FILE_SIZE_BYTES) {
                fileInput.classList.add('is-invalid');
                if (errorElement) {
                    errorElement.textContent = `El archivo es demasiado grande (${formatBytes(fileSize)}). El tamaño máximo permitido es ${formatBytes(MAX_FILE_SIZE_BYTES)} (${MAX_FILE_SIZE_MB} MB).`;
                }
                return false;
            } else {
                fileInput.classList.remove('is-invalid');
                if (errorElement) {
                    errorElement.textContent = '';
                }
                return true;
            }
        }
        
        // Función para mostrar el modal de loading
        function showLoadingModal(event) {
            // Prevenir el envío por defecto para validar primero
            if (event) {
                event.preventDefault();
            }
            
            const addForm = document.getElementById('addVideoForm');
            const editForm = document.getElementById('editVideoForm');
            let isValid = true;
            let showLoading = false;
            let currentModalId = null;
            
            // Validar formulario de agregar
            if (addForm && event && addForm === event.target) {
                currentModalId = 'addVideoModal';
                const fileInput = addForm.querySelector('#archivo_video');
                const errorElement = document.getElementById('archivo_video_error');
                
                if (fileInput && fileInput.files.length > 0) {
                    isValid = validateFileSize(fileInput, errorElement);
                    showLoading = isValid;
                } else {
                    // Si es un formulario de agregar, siempre debe tener archivo
                    showLoading = false;
                }
            }
            
            // Validar formulario de editar
            if (editForm && event && editForm === event.target) {
                currentModalId = 'editVideoModal';
                const fileInput = editForm.querySelector('#edit_archivo_video');
                const errorElement = document.getElementById('edit_archivo_video_error');
                
                if (fileInput && fileInput.files.length > 0) {
                    isValid = validateFileSize(fileInput, errorElement);
                    showLoading = isValid;
                }
                // Si no hay archivo nuevo, es válido pero no mostrar loading
            }
            
            // Si la validación falló, no enviar el formulario
            if (!isValid) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                return false;
            }
            
            // Si hay archivo y la validación pasó, mostrar loading y luego enviar
            if (showLoading && currentModalId) {
                // Obtener referencias a los modales
                const loadingModalElement = document.getElementById('loadingModal');
                const formModalElement = document.getElementById(currentModalId);
                const formModal = bootstrap.Modal.getInstance(formModalElement);
                
                // Prevenir que el usuario cierre el modal de loading
                const preventClose = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                };
                loadingModalElement.addEventListener('hide.bs.modal', preventClose);
                
                // Crear o obtener instancia del modal de loading
                let loadingModal = bootstrap.Modal.getInstance(loadingModalElement);
                if (!loadingModal) {
                    loadingModal = new bootstrap.Modal(loadingModalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                }
                
                // Esperar a que se cierre el modal del formulario primero
                if (formModal) {
                    formModal.hide();
                }
                
                // Esperar a que el modal del formulario se cierre completamente
                const handleFormModalHidden = function() {
                    formModalElement.removeEventListener('hidden.bs.modal', handleFormModalHidden);
                    
                    // Ahora mostrar el modal de loading
                    loadingModal.show();
                    
                    // Forzar visibilidad del modal de loading con z-index alto
                    loadingModalElement.style.display = 'block';
                    loadingModalElement.classList.add('show');
                    loadingModalElement.setAttribute('aria-hidden', 'false');
                    loadingModalElement.setAttribute('aria-modal', 'true');
                    loadingModalElement.style.zIndex = '10050';
                    
                    // Asegurar que el contenido del modal también tenga z-index alto
                    const modalDialog = loadingModalElement.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.zIndex = '10051';
                    }
                    const modalContent = loadingModalElement.querySelector('.modal-content');
                    if (modalContent) {
                        modalContent.style.zIndex = '10052';
                    }
                    
                    // Ajustar el backdrop del loading para que esté por debajo del modal
                    setTimeout(function() {
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        if (backdrops.length > 0) {
                            // El último backdrop (el del loading) debe estar por debajo del modal
                            backdrops[backdrops.length - 1].style.zIndex = '10049';
                        }
                        
                        // Esperar un momento antes de enviar para que el usuario vea el modal
                        setTimeout(function() {
                            // Enviar el formulario
                            if (event && event.target) {
                                event.target.submit();
                            }
                        }, 300);
                    }, 50);
                };
                
                if (formModal) {
                    formModalElement.addEventListener('hidden.bs.modal', handleFormModalHidden, { once: true });
                } else {
                    // Si no hay modal del formulario, mostrar loading directamente
                    loadingModal.show();
                    loadingModalElement.style.zIndex = '10050';
                    setTimeout(function() {
                        if (event && event.target) {
                            event.target.submit();
                        }
                    }, 300);
                }
                
            } else if (!showLoading && event && event.target) {
                // No hay archivo, enviar normalmente sin modal de loading
                // Pero aún cerrar el modal del formulario si existe
                if (currentModalId) {
                    const formModal = bootstrap.Modal.getInstance(document.getElementById(currentModalId));
                    if (formModal) {
                        formModal.hide();
                    }
                }
                event.target.submit();
            }
            
            return isValid;
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
                    const videoId = document.getElementById('edit_id').value;
                    validarOrden(this, true, videoId);
                });
            }
            
            // Validación en tiempo real del tamaño del archivo al seleccionar
            const archivoVideoInput = document.getElementById('archivo_video');
            if (archivoVideoInput) {
                archivoVideoInput.addEventListener('change', function() {
                    const errorElement = document.getElementById('archivo_video_error');
                    validateFileSize(this, errorElement);
                });
            }
            
            const editArchivoVideoInput = document.getElementById('edit_archivo_video');
            if (editArchivoVideoInput) {
                editArchivoVideoInput.addEventListener('change', function() {
                    const errorElement = document.getElementById('edit_archivo_video_error');
                    validateFileSize(this, errorElement);
                });
            }
        });
    </script>
</body>
</html>
