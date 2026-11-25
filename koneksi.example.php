<?php
// Salin file ini menjadi koneksi.php dan sesuaikan kredensial database Anda
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$db   = getenv('DB_NAME') ?: "login";
$port = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    $conn = @mysqli_connect($host, $user, $pass, null, $port);
}
