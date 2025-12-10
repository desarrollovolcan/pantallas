<?php
require_once '../config/config.php';
requireAuth();

// Verificar timeout de sesión
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}
$_SESSION['last_activity'] = time();

// Obtener estadísticas
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Contar registros
    $stats = [];
    
    $queries = [
        'videos' => "SELECT COUNT(*) as total FROM videos_corporativos WHERE activo = 1",
        'ubicaciones' => "SELECT COUNT(*) as total FROM ubicaciones_clima WHERE activo = 1",
        'cumpleanos' => "SELECT COUNT(*) as total FROM cumpleanos WHERE activo = 1",
        'eventos' => "SELECT COUNT(*) as total FROM eventos WHERE activo = 1",
        'campañas' => "SELECT COUNT(*) as total FROM campanas_qr WHERE activo = 1"
    ];
    
    foreach ($queries as $key => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats[$key] = $stmt->fetch()['total'];
    }
    
} catch (Exception $e) {
    $stats = ['videos' => 0, 'ubicaciones' => 0, 'cumpleanos' => 0, 'eventos' => 0, 'campañas' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Dashboard Corporativo</title>
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
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .stat-icon.videos { background: linear-gradient(45deg, #ff6b6b, #ee5a24); }
        .stat-icon.clima { background: linear-gradient(45deg, #74b9ff, #0984e3); }
        .stat-icon.cumpleanos { background: linear-gradient(45deg, #fdcb6e, #e17055); }
        .stat-icon.eventos { background: linear-gradient(45deg, #6c5ce7, #a29bfe); }
        .stat-icon.campañas { background: linear-gradient(45deg, #00b894, #00cec9); }
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <div>
                            <h2>Bienvenido, <?php echo $_SESSION['admin_nombre']; ?></h2>
                            <p class="text-muted mb-0">Panel de administración del Dashboard Corporativo</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Último acceso: <?php echo date('d/m/Y H:i'); ?></small>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon videos me-3">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['videos']; ?></h3>
                                        <p class="text-muted mb-0">Videos Activos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon clima me-3">
                                        <i class="fas fa-cloud-sun"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['ubicaciones']; ?></h3>
                                        <p class="text-muted mb-0">Ubicaciones de Clima</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon cumpleanos me-3">
                                        <i class="fas fa-birthday-cake"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['cumpleanos']; ?></h3>
                                        <p class="text-muted mb-0">Cumpleaños</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon eventos me-3">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['eventos']; ?></h3>
                                        <p class="text-muted mb-0">Eventos Activos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon campañas me-3">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats['campañas']; ?></h3>
                                        <p class="text-muted mb-0">Campañas QR</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accesos Rápidos -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Accesos Rápidos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <a href="videos.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-plus me-2"></i>Agregar Video
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="clima.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-map-marker-alt me-2"></i>Gestionar Ubicaciones
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="cumpleanos.php" class="btn btn-outline-warning w-100">
                                                <i class="fas fa-user-plus me-2"></i>Agregar Cumpleaños
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="eventos.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-calendar-plus me-2"></i>Crear Evento
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
