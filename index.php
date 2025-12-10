<?php
require_once __DIR__ . '/app/bootstrap.php';

$database = new Database();

$videoModel = new VideoModel($database);
$weatherModel = new WeatherModel($database);
$birthdayModel = new BirthdayModel($database);
$eventModel = new EventModel($database);
$campaignModel = new CampaignModel($database);

$videos = $videoModel->getActive();
$ubicaciones_clima = $weatherModel->getActiveLocations();
$cumpleanos = $birthdayModel->getUpcoming();
$eventos = $eventModel->getActive();
$campana_principal = $campaignModel->getPrincipal();

require __DIR__ . '/app/Views/home/index.php';
