<?php
// register/register.php
session_start();

// Sesuaikan path jika config.php berada di lokasi lain
require_once __DIR__ . '/config.php';

// Ambil input dan sanitasi dasar
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = $_POST['password'] ?? '';
$nomor    = isset($_POST['nomor']) ? trim($_POST['nomor']) : '';

// Validasi server-side
if ($username === '' || $password === '' || $nomor === '') {
    header("Location: index.php?status=gagal");
    exit;
}

// Validasi nomor WA: harus +62 diikuti angka
if (!preg_match('/^\+62[0-9]{6,15}$/', $nomor)) {
    header("Location: index.php?status=gagal&pesan=invalid_nomor");
    exit;
}

// Pastikan username unik di tabel usertb
// Gunakan prepared statement untuk mencegah SQL injection
$stmt = $conn->prepare("SELECT id FROM usertb WHERE username = ? LIMIT 1");
if (!$stmt) {
    header("Location: index.php?status=gagal");
    exit;
}
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: index.php?status=gagal&pesan=username_sudah_ada");
    exit;
}
$stmt->close();

// Hash password sebelum disimpan
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Insert user baru (kolom keranjang dibiarkan kosong / default)
$ins = $conn->prepare("INSERT INTO usertb (username, password, nomor) VALUES (?, ?, ?)");
if (!$ins) {
    header("Location: index.php?status=gagal");
    exit;
}
$ins->bind_param('sss', $username, $hashed, $nomor);
$ok = $ins->execute();
$ins->close();

if ($ok) {
    header("Location: index.php?status=sukses");
    exit;
} else {
    header("Location: index.php?status=gagal");
    exit;
}
