<?php
// account.php (perbaikan)
session_start();

// Jika belum login, redirect ke halaman login dengan parameter return
if (empty($_SESSION['user_id'])) {
    // Ambil current request URI sebagai return target
    $current = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/account.php';
    // rawurlencode lebih cocok untuk path/URI
    $return = rawurlencode($current);

    // NOTE: sesuaikan path login dengan struktur server Anda.
    // Hindari spasi di nama folder; contoh: /pemrograman-web/...
    $loginPath = 'loginuser/index.php';

    header("Location: {$loginPath}?return={$return}");
    exit;
}

// user sudah login: tampilkan halaman akun
require_once __DIR__ . '/config.php';

// Contoh: ketika menggunakan nilai return setelah login, validasi asalnya
// (contoh sederhana: hanya izinkan path internal yang dimulai dengan '/')
function is_safe_return($r) {
    $decoded = rawurldecode($r);
    return (strpos($decoded, '/') === 0);
}

// Lanjutkan menampilkan profil, keranjang, dll.
?>
