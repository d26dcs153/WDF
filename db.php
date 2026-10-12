<?php
mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'studenthub';

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('A database error occurred. Please try again later.');
}

$conn->set_charset('utf8mb4');
?>