<?php
/**
 * Script de Instalación para Servidor Apache
 * Dashboard Corporativo - Sistema de Pantallas de Gestión
 */

// Verificar si ya está instalado
if (file_exists('config/installed.txt')) {
    die('<h1>❌ El sistema ya está instalado</h1><p>Para reinstalar, elimine el archivo config/installed.txt</p>');
}

$errors = [];
$success = [];

// Procesar formulario de instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? 'corporativo_dashboard';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    $base_url = $_POST['base_url'] ?? 'http://localhost/';
    
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
        
        // Actualizar archivos de configuración
        updateConfigFiles($host, $dbname, $username, $password, $base_url);
        
        // Crear directorios necesarios
        createDirectories();
        
        // Crear archivo de instalación completada
        file_put_contents('config/installed.txt', date('Y-m-d H:i:s'));
        
        $success[] = '✅ Instalación completada exitosamente!';
        $success[] = '🌐 Dashboard: <a href="index.php">Acceder al Dashboard</a>';
        $success[] = '🔐 Panel Admin: <a href="login.php">Login Administrador</a>';
        $success[] = '👤 Usuario: admin | 🔑 Contraseña: 123456';
        
    } catch (Exception $e) {
        $errors[] = '❌ Error de conexión: ' . $e->getMessage();
    }
}

function updateConfigFiles($host, $dbname, $username, $password, $base_url) {
    // Actualizar config/database.php
    $database_content = file_get_contents('config/database.php');
    $database_content = str_replace("'localhost'", "'$host'", $database_content);
    $database_content = str_replace("'corporativo_dashboard'", "'$dbname'", $database_content);
    $database_content = str_replace("'root'", "'$username'", $database_content);
    $database_content = str_replace("''", "'$password'", $database_content);
    file_put_contents('config/database.php', $database_content);
    
    // Actualizar config/config.php
    $config_content = file_get_contents('config/config.php');
    $config_content = preg_replace("/define\('BASE_URL', '[^']*'\);/", "define('BASE_URL', '$base_url');", $config_content);
    file_put_contents('config/config.php', $config_content);
}

function createDirectories() {
    $directories = ['uploads', 'uploads/videos', 'uploads/images', 'uploads/temp'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 600px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="logo">
                <h1 class="h3 mb-0">🏢 Dashboard Corporativo</h1>
                <p class="text-muted">Instalación del Sistema</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5>❌ Errores encontrados:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php foreach ($success as $msg): ?>
                        <p class="mb-1"><?= $msg ?></p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="host" class="form-label">Servidor MySQL</label>
                            <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dbname" class="form-label">Nombre de Base de Datos</label>
                            <input type="text" class="form-control" id="dbname" name="dbname" value="corporativo_dashboard" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Usuario MySQL</label>
                            <input type="text" class="form-control" id="username" name="username" value="root" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña MySQL</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="base_url" class="form-label">URL Base del Proyecto</label>
                        <input type="url" class="form-control" id="base_url" name="base_url" value="http://localhost/" required>
                        <div class="form-text">URL completa donde estará instalado el proyecto</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            🚀 Instalar Sistema
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="alert alert-info">
                    <h6>📋 Requisitos del Sistema:</h6>
                    <ul class="mb-0">
                        <li>✅ Apache con mod_rewrite habilitado</li>
                        <li>✅ PHP 7.4 o superior</li>
                        <li>✅ MySQL 5.7 o superior</li>
                        <li>✅ Extensiones: PDO, PDO_MySQL, JSON</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
