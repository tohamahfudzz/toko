<?php
// login/login.php
session_start();

// Sesuaikan path ke config.php (misal config.php di root project)
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password harus diisi.';
    header('Location: index.php');
    exit;
}

$sql = "SELECT id, username, password FROM admintb WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $_SESSION['login_error'] = 'Terjadi kesalahan server.';
    header('Location: index.php');
    exit;
}

$stmt->bind_param('s', $username);
$stmt->execute();

// Ambil hasil dengan fallback jika get_result tidak tersedia
$row = null;
if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
} else {
    $stmt->bind_result($id, $uname, $db_password);
    if ($stmt->fetch()) {
        $row = [
            'id' => $id,
            'username' => $uname,
            'password' => $db_password
        ];
    }
}

if ($row) {
    $dbPass = $row['password'];

    // Deteksi apakah password di DB sudah hash (cek prefix umum)
    $isHashed = (strpos($dbPass, '$2y$') === 0 || strpos($dbPass, '$2a$') === 0 || strpos($dbPass, '$argon2') === 0);

    $ok = false;
    if ($isHashed) {
        // Jika sudah hash, gunakan password_verify
        if (password_verify($password, $dbPass)) {
            $ok = true;
        }
    } else {
        // Jika belum hash (plain-text), bandingkan langsung
        if ($password === $dbPass) {
            $ok = true;
        }
    }

    if ($ok) {
        // Jika login sukses, set session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];

        // Jika password masih plain-text, lakukan migrasi otomatis ke hash (opsional)
        if (!$isHashed) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admintb SET password = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param('si', $newHash, $row['id']);
                $upd->execute();
                $upd->close();
            } else {
                error_log("Update hash failed: " . $conn->error);
            }
        }

        header('Location: ../admin/index.php');
        exit;
    } else {
        error_log("Login gagal untuk user {$username}: password mismatch.");
    }
}

$_SESSION['login_error'] = 'Username atau password salah.';
header('Location: index.php');
exit;
