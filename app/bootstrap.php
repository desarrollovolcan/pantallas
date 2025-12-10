<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Core/Helpers.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/Core/' . $class . '.php',
        __DIR__ . '/Controllers/' . $class . '.php',
        __DIR__ . '/Models/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
?>
