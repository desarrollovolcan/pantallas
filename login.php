<?php
require_once __DIR__ . '/app/bootstrap.php';

$database = new Database();
$authController = new AuthController(new AdminModel($database));

$authController->login();
