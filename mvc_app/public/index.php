<?php
// Front controller para el nuevo proyecto MVC
$app = require __DIR__ . '/../bootstrap.php';

$app['router']->dispatch();
