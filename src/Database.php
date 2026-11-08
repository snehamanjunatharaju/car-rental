<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$databaseHost = getenv('DB_HOST') ?: 'localhost';
$databaseUser = getenv('DB_USER') ?: 'root';
$databasePassword = getenv('DB_PASSWORD');
$databaseName = getenv('DB_NAME') ?: 'car_rental';
$databasePort = getenv('DB_PORT') ?: '3306';

if ($databasePassword === false) {
    $databasePassword = '';
}

if (!ctype_digit($databasePort) || (int) $databasePort < 1 || (int) $databasePort > 65535) {
    throw new RuntimeException('DB_PORT must be a valid TCP port.');
}

$db = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName, (int) $databasePort);
$db->set_charset('utf8mb4');
