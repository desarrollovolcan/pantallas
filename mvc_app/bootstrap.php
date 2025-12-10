<?php
use App\Core\Database;
use App\Core\Installer;
use App\Core\Router;
use App\Controllers\HomeController;

require __DIR__ . '/src/Core/Helpers.php';

// Simple PSR-4 like autoloader for the App namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_readable($file)) {
        require $file;
    }
});

$config = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';

date_default_timezone_set($config['timezone'] ?? 'UTC');

try {
    $database = new Database($dbConfig);
    $installer = new Installer(
        $database,
        __DIR__ . '/config/.installed'
    );

    $installerMessage = $installer->install();

    $container = [
        'config' => $config,
        'db' => $database,
        'installerMessage' => $installerMessage,
    ];

    $router = new Router($container);
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/status', [HomeController::class, 'status']);

    return [
        'router' => $router,
        'container' => $container,
    ];
} catch (\Throwable $exception) {
    http_response_code(500);
    $message = $exception->getMessage();
    echo "<h1>Error al iniciar la aplicación</h1><p>{$message}</p>";
    exit;
}
