<?php
// config.php
// Koneksi MySQLi ke database tokodb

$servername = "localhost";
$db_username = "root";
$db_password = "";
$database   = "tokodb";

$conn = new mysqli($servername, $db_username, $db_password, $database);

// Set charset
if ($conn->connect_error) {
    // Jangan tampilkan detail sensitif di production
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
