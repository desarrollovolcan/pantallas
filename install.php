<?php
/**
 * Script de Instalación - Dashboard Corporativo
 * Este archivo ayuda a configurar la base de datos y verificar requisitos
 */

// Verificar si ya está instalado
if (file_exists('config/installed.txt')) {
    die('El sistema ya está instalado. Elimine el archivo config/installed.txt para reinstalar.');
}

$errors = [];
$success = [];

// Verificar extensiones PHP
$required_extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "Extensión PHP requerida: $ext";
    }
}

// Verificar permisos de escritura
$writable_dirs = ['config'];
foreach ($writable_dirs as $dir) {
    if (!is_writable($dir)) {
        $errors[] = "Directorio no escribible: $dir";
    }
}

// Procesar formulario de instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? 'corporativo_dashboard';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    
    try {
        // Probar conexión
        $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        $pdo->exec("USE `$dbname`");
        
        // Leer y ejecutar script SQL
        $sql = file_get_contents('database.sql');
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(--|\#)/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Actualizar archivo de configuración
        $config_content = file_get_contents('config/database.php');
        $config_content = str_replace("'localhost'", "'$host'", $config_content);
        $config_content = str_replace("'corporativo_dashboard'", "'$dbname'", $config_content);
        $config_content = str_replace("'root'", "'$username'", $config_content);
        $config_content = str_replace("''", "'$password'", $config_content);
        
        file_put_contents('config/database.php', $config_content);
        
        // Crear archivo de instalación completada
        file_put_contents('config/installed.txt', date('Y-m-d H:i:s'));
        
        $success[] = 'Instalación completada exitosamente!';
        $success[] = 'Puede acceder al dashboard en: <a href="index.php">Dashboard</a>';
        $success[] = 'Panel de administración: <a href="login.php">Login</a>';
        $success[] = 'Credenciales: admin / password';
        
    } catch (Exception $e) {
        $errors[] = 'Error de conexión: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        .install-body {
            padding: 30px;
        }
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .requirement-item:last-child {
            border-bottom: none;
        }
        .requirement-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .requirement-icon.success {
            background: #d4edda;
            color: #155724;
        }
        .requirement-icon.error {
            background: #f8d7da;
            color: #721c24;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-install {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 600;
            width: 100%;
            color: white;
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <i class="fas fa-building fa-3x mb-3"></i>
            <h2>Dashboard Corporativo</h2>
            <p class="mb-0">Instalación del Sistema</p>
        </div>
        
        <div class="install-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Errores Encontrados:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Instalación Exitosa:</h5>
                    <ul class="mb-0">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Verificación de Requisitos -->
                <div class="mb-4">
                    <h5><i class="fas fa-list-check me-2"></i>Verificación de Requisitos</h5>
                    
                    <div class="requirement-item">
                        <div class="requirement-icon <?php echo extension_loaded('pdo') ? 'success' : 'error'; ?>">
                            <i class="fas fa-<?php echo extension_loaded('pdo') ? 'check' : 'times'; ?>"></i>
                        </div>
                        <div>
                            <strong>PDO Extension</strong>
                            <br><small>Requerida para conexión a base de datos</small>
                        </div>
                    </div>
                    
                    <div class="requirement-item">
                        <div class="requirement-icon <?php echo extension_loaded('pdo_mysql') ? 'success' : 'error'; ?>">
                            <i class="fas fa-<?php echo extension_loaded('pdo_mysql') ? 'check' : 'times'; ?>"></i>
                        </div>
                        <div>
                            <strong>PDO MySQL Extension</strong>
                            <br><small>Requerida para MySQL</small>
                        </div>
                    </div>
                    
                    <div class="requirement-item">
                        <div class="requirement-icon <?php echo extension_loaded('json') ? 'success' : 'error'; ?>">
                            <i class="fas fa-<?php echo extension_loaded('json') ? 'check' : 'times'; ?>"></i>
                        </div>
                        <div>
                            <strong>JSON Extension</strong>
                            <br><small>Requerida para procesamiento de datos</small>
                        </div>
                    </div>
                    
                    <div class="requirement-item">
                        <div class="requirement-icon <?php echo is_writable('config') ? 'success' : 'error'; ?>">
                            <i class="fas fa-<?php echo is_writable('config') ? 'check' : 'times'; ?>"></i>
                        </div>
                        <div>
                            <strong>Permisos de Escritura</strong>
                            <br><small>Directorio config debe ser escribible</small>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de Instalación -->
                <?php if (empty($errors)): ?>
                    <form method="POST">
                        <h5><i class="fas fa-database me-2"></i>Configuración de Base de Datos</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="host" class="form-label">Servidor MySQL</label>
                                <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dbname" class="form-label">Nombre de Base de Datos</label>
                                <input type="text" class="form-control" id="dbname" name="dbname" value="corporativo_dashboard" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Usuario MySQL</label>
                                <input type="text" class="form-control" id="username" name="username" value="root" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña MySQL</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> La instalación creará automáticamente la base de datos y las tablas necesarias.
                        </div>
                        
                        <button type="submit" class="btn btn-install">
                            <i class="fas fa-rocket me-2"></i>Instalar Sistema
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Corrija los errores antes de continuar con la instalación.</strong>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
