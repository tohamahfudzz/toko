<?php
// config.php
// Konfigurasi koneksi database untuk tokodb

// Aktifkan reporting agar mysqli melempar exception saat error
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'tokodb');

try {
    // Buat koneksi mysqli (object-oriented)
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Set charset agar mendukung emoji dan karakter multibahasa
    $conn->set_charset('utf8mb4');

    // Jika ingin debug singkat saat development, bisa uncomment baris berikut
    // error_log("Database connected: " . DB_NAME);

} catch (mysqli_sql_exception $e) {
    // Tangani error koneksi dengan aman
    // Jangan tampilkan detail sensitif ke user di production
    http_response_code(500);
    echo "Terjadi kesalahan koneksi database.";
    // Untuk development, Anda bisa menampilkan detail error:
    // echo "Connection failed: " . $e->getMessage();
    exit;
}
?>


