<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "stokflow_db";
$port = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli(
        $host,
        $username,
        $password,
        $database,
        $port
    );

    $koneksi->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit("Koneksi database gagal. Periksa konfigurasi MySQL.");
}
