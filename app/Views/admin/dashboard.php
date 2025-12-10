<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Dashboard Corporativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/home.css">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Panel de Administración</h2>
            <small class="text-muted">Versión <?php echo APP_VERSION; ?></small>
        </div>
        <div>
            <a href="/" class="btn btn-outline-secondary me-2">Ver panel público</a>
            <a href="/logout" class="btn btn-danger">Cerrar sesión</a>
        </div>
    </div>

    <div class="row g-3">
        <?php
        $labels = [
            'videos' => ['icon' => 'fa-play', 'label' => 'Videos activos'],
            'ubicaciones' => ['icon' => 'fa-cloud-sun', 'label' => 'Ubicaciones clima'],
            'cumpleanos' => ['icon' => 'fa-birthday-cake', 'label' => 'Cumpleaños'],
            'eventos' => ['icon' => 'fa-calendar-alt', 'label' => 'Eventos'],
            'campañas' => ['icon' => 'fa-qrcode', 'label' => 'Campañas QR']
        ];
        ?>
        <?php foreach ($labels as $key => $meta): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1"><?php echo $meta['label']; ?></p>
                            <h3 class="mb-0"><?php echo $stats[$key] ?? 0; ?></h3>
                        </div>
                        <div class="text-primary fs-2">
                            <i class="fas <?php echo $meta['icon']; ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
