<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/app-config.php';

$databaseHost = ($appConfig['DB_HOST'] ?? '') ?: (getenv('DB_HOST') ?: 'localhost');
$databaseUser = ($appConfig['DB_USER'] ?? '') ?: (getenv('DB_USER') ?: 'root');
$databaseName = ($appConfig['DB_NAME'] ?? '') ?: (getenv('DB_NAME') ?: 'car_rental');
$databasePort = (string) (($appConfig['DB_PORT'] ?? '') ?: (getenv('DB_PORT') ?: '3306'));
$databasePassword = $appConfig['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

if ($databasePassword === false) {
    $databasePassword = '';
}

if (!ctype_digit($databasePort) || (int) $databasePort < 1 || (int) $databasePort > 65535) {
    throw new RuntimeException('DB_PORT must be a valid TCP port.');
}

$db = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName, (int) $databasePort);
$db->set_charset('utf8mb4');
