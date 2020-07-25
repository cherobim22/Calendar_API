<?php
require_once "GDGoogleClient.php";
header('Content-Type: application/json');

$gd_client = new GDGoogleCLient;

$url_acesso = $gd_client->getAuthUrl();

//echo(json_encode(['auth_url' => $url_acesso]));
echo implode(['auth_url' => $url_acesso]);
