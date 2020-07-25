<?php
echo('<pre>');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "GDGoogleClient.php";

$gd_client = new GDGoogleCLient;
$gd_client->setCode($_GET['code']);
$token = $gd_client->getToken();
print_r($token);