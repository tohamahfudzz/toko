<?php
// cart.php — tambah / hapus / update keranjang
// Disimpan di kolom `keranjang` (JSON) di tabel usertb
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: loginuser/index.php?return=" . rawurlencode("cart_view.php"));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? ($_GET['action'] ?? '');

function get_cart(mysqli $conn, int $uid): array {
    $st = $conn->prepare("SELECT keranjang FROM usertb WHERE id=? LIMIT 1");
    $st->bind_param("i", $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row || empty($row['keranjang'])) return [];
    $d = json_decode($row['keranjang'], true);
    return is_array($d) ? $d : [];
}

function save_cart(mysqli $conn, int $uid, array $cart): void {
    $json = json_encode(array_values($cart), JSON_UNESCAPED_UNICODE);
    $st = $conn->prepare("UPDATE usertb SET keranjang=? WHERE id=?");
    $st->bind_param("si", $json, $uid);
    $st->execute();
    $st->close();
}

function get_produk(mysqli $conn, int $pid): ?array {
    $st = $conn->prepare("SELECT id, nama, harga FROM produk WHERE id=? LIMIT 1");
    $st->bind_param("i", $pid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ?: null;
}

// ---- TAMBAH (POST tanpa action / action=tambah) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['', 'tambah'])) {
    $pid = (int)($_POST['id'] ?? 0);
    if ($pid > 0) {
        $produk = get_produk($conn, $pid);
        if ($produk) {
            $cart  = get_cart($conn, $user_id);
            $found = false;
            foreach ($cart as &$item) {
                if ((int)$item['id'] === $pid) { $item['qty']++; $found = true; break; }
            }
            unset($item);
            if (!$found) {
                $cart[] = ['id' => $pid, 'nama' => $produk['nama'], 'harga' => (int)$produk['harga'], 'qty' => 1];
            }
            save_cart($conn, $user_id, $cart);
        }
    }
    header("Location: cart_view.php?added=1");
    exit;
}

// ---- HAPUS ----
if ($action === 'hapus') {
    $pid  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($pid > 0) {
        $cart = get_cart($conn, $user_id);
        $cart = array_values(array_filter($cart, fn($i) => (int)$i['id'] !== $pid));
        save_cart($conn, $user_id, $cart);
    }
    header("Location: cart_view.php");
    exit;
}

// ---- UPDATE QTY ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $pid = (int)($_POST['id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($pid > 0) {
        $cart = get_cart($conn, $user_id);
        foreach ($cart as &$item) {
            if ((int)$item['id'] === $pid) { $item['qty'] = $qty; break; }
        }
        unset($item);
        save_cart($conn, $user_id, $cart);
    }
    header("Location: cart_view.php");
    exit;
}

header("Location: cart_view.php");
exit;