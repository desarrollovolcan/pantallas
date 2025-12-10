<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? $appName) ?></title>
    <style>
        :root { color-scheme: light dark; font-family: system-ui, -apple-system, sans-serif; }
        body { margin: 0; padding: 2rem; background: #0e1726; color: #f8f9fc; }
        header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 1.5rem; }
        nav a { color: #8ed0ff; margin-right: 1rem; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        .card { background: #111d35; border: 1px solid #1e2e4f; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.6rem; border-bottom: 1px solid #1e2e4f; text-align: left; }
        th { color: #8ed0ff; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.08em; }
        .badge { display: inline-block; padding: 0.35rem 0.65rem; background: #1e2e4f; border-radius: 999px; font-size: 0.8rem; }
        .muted { color: #9fb0c8; }
    </style>
</head>
<body>
<header>
    <div>
        <h1 style="margin:0;"><?= htmlspecialchars($appName) ?></h1>
        <p class="muted" style="margin:0;">Plantilla MVC ligera con instalación automática</p>
    </div>
    <nav>
        <a href="/">Inicio</a>
        <a href="/status">/status</a>
    </nav>
</header>

<main>
    <?php if (!empty($installerMessage)): ?>
        <div class="card" style="border-color:#2e7d32; background: #0f2b18;">
            <strong>Setup listo:</strong> <?= htmlspecialchars($installerMessage) ?>
        </div>
    <?php endif; ?>

    <?php include $viewFile; ?>
</main>
</body>
</html>
