<?php
require_once 'config/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es solicitud de recuperación de contraseña
    if (isset($_POST['forgot_password'])) {
        $usuario = sanitizeInput($_POST['usuario_forgot']);
        
        if (empty($usuario)) {
            $error = 'Por favor, ingrese su nombre de usuario';
        } else {
            $result = resetAdminPassword($usuario);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
    $usuario = sanitizeInput($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    
    if (empty($usuario) || empty($contrasena)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, usuario, contrasena, nombre FROM administradores WHERE usuario = :usuario AND activo = 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch();
                
                // Verificar contraseña usando hash
                if (verifyPassword($contrasena, $admin['contrasena'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_usuario'] = $admin['usuario'];
                    $_SESSION['admin_nombre'] = $admin['nombre'];
                    $_SESSION['last_activity'] = time();
                    
                    // Debug: verificar que la sesión se creó
                    error_log("Sesión creada para usuario: " . $admin['usuario']);
                    
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    $error = 'Credenciales incorrectas';
                }
            } else {
                $error = 'Credenciales incorrectas';
            }
        } catch (Exception $e) {
            $error = 'Error del sistema. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-building fa-3x mb-3"></i>
                <h2>Dashboard Corporativo</h2>
                <p class="mb-0">Acceso de Administrador</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">
                            <i class="fas fa-user me-2"></i>Usuario
                        </label>
                        <input type="text" class="form-control" id="usuario" name="usuario" 
                               value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">
                            <i class="fas fa-lock me-2"></i>Contraseña
                        </label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                        <i class="fas fa-key me-2"></i>Olvidé mi contraseña
                    </button>
                </div>
                
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Credenciales por defecto: admin / admin123
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Olvidé mi contraseña -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="forgot_password" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-key me-2"></i>Recuperar Contraseña
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Ingrese su nombre de usuario para generar una nueva contraseña. Se enviará por correo electrónico a: <strong>sergioortegac@gmail.com</strong></p>
                        <div class="mb-3">
                            <label for="usuario_forgot" class="form-label">
                                <i class="fas fa-user me-2"></i>Usuario
                            </label>
                            <input type="text" class="form-control" id="usuario_forgot" name="usuario_forgot" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Generar Nueva Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
