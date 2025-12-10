<?php
require_once __DIR__ . '/app/bootstrap.php';

$database = new Database();
$homeController = new HomeController(
    new VideoModel($database),
    new WeatherModel($database),
    new BirthdayModel($database),
    new EventModel($database),
    new CampaignModel($database)
);

$homeController->index();
