<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo base_path('assets/css/home.css'); ?>">
</head>
<body class="d-flex align-items-center justify-content-center" style="height: 100vh; background: #f8f9fa;">
    <div class="card shadow" style="min-width: 380px;">
        <div class="card-body p-4">
            <h5 class="card-title mb-4 text-center">Administración</h5>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="<?php echo base_path('login.php'); ?>">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                </div>
                <div class="mb-3">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?php echo BASE_PATH === '' ? '/' : BASE_PATH . '/'; ?>" class="text-decoration-none">Volver</a>
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
