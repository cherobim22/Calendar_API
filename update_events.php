<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header('Content-Type: application/json');
error_reporting(E_ALL);

require_once "GDGoogleClient.php";

$gd_client = new GDGoogleCLient;

$gd_client->setToken($_POST);
$events = $gd_client->updateEvents($_POST);

echo(json_encode($events));