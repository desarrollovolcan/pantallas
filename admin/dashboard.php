<?php
require_once __DIR__ . '/../app/bootstrap.php';

$database = new Database();
$controller = new DashboardController(new AdminModel($database));
$controller->index();
