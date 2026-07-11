<?php
// wishlist.php — tambah / hapus wishlist
// Disimpan di kolom `wishlist` (JSON) di tabel usertb
// PENTING: jalankan SQL ini sekali di database jika kolom belum ada:
// ALTER TABLE usertb ADD COLUMN wishlist TEXT NULL DEFAULT NULL;
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: loginuser/index.php?return=" . rawurlencode("wishlist_view.php"));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? ($_GET['action'] ?? '');

function get_wishlist(mysqli $conn, int $uid): array {
    $st = $conn->prepare("SELECT wishlist FROM usertb WHERE id=? LIMIT 1");
    $st->bind_param("i", $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row || empty($row['wishlist'])) return [];
    $d = json_decode($row['wishlist'], true);
    return is_array($d) ? $d : [];
}

function save_wishlist(mysqli $conn, int $uid, array $list): void {
    $json = json_encode(array_values($list), JSON_UNESCAPED_UNICODE);
    $st = $conn->prepare("UPDATE usertb SET wishlist=? WHERE id=?");
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

// ---- TAMBAH ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['', 'tambah'])) {
    $pid = (int)($_POST['id'] ?? 0);
    if ($pid > 0) {
        $produk = get_produk($conn, $pid);
        if ($produk) {
            $list   = get_wishlist($conn, $user_id);
            $exists = false;
            foreach ($list as $item) {
                if ((int)$item['id'] === $pid) { $exists = true; break; }
            }
            if (!$exists) {
                $list[] = ['id' => $pid, 'nama' => $produk['nama'], 'harga' => (int)$produk['harga']];
                save_wishlist($conn, $user_id, $list);
            }
        }
    }
    header("Location: wishlist_view.php?added=1");
    exit;
}

// ---- HAPUS ----
if ($action === 'hapus') {
    $pid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($pid > 0) {
        $list = get_wishlist($conn, $user_id);
        $list = array_values(array_filter($list, fn($i) => (int)$i['id'] !== $pid));
        save_wishlist($conn, $user_id, $list);
    }
    header("Location: wishlist_view.php");
    exit;
}

// ---- PINDAH KE KERANJANG ----
if ($action === 'ke_keranjang') {
    $pid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($pid > 0) {
        // Hapus dari wishlist
        $list = get_wishlist($conn, $user_id);
        $list = array_values(array_filter($list, fn($i) => (int)$i['id'] !== $pid));
        save_wishlist($conn, $user_id, $list);

        // Tambah ke keranjang
        $produk = get_produk($conn, $pid);
        if ($produk) {
            $stCart = $conn->prepare("SELECT keranjang FROM usertb WHERE id=? LIMIT 1");
            $stCart->bind_param("i", $user_id);
            $stCart->execute();
            $rowCart = $stCart->get_result()->fetch_assoc();
            $stCart->close();

            $cart = [];
            if (!empty($rowCart['keranjang'])) {
                $dc = json_decode($rowCart['keranjang'], true);
                if (is_array($dc)) $cart = $dc;
            }
            $found = false;
            foreach ($cart as &$item) {
                if ((int)$item['id'] === $pid) { $item['qty']++; $found = true; break; }
            }
            unset($item);
            if (!$found) {
                $cart[] = ['id' => $pid, 'nama' => $produk['nama'], 'harga' => (int)$produk['harga'], 'qty' => 1];
            }
            $json = json_encode(array_values($cart), JSON_UNESCAPED_UNICODE);
            $stSave = $conn->prepare("UPDATE usertb SET keranjang=? WHERE id=?");
            $stSave->bind_param("si", $json, $user_id);
            $stSave->execute();
            $stSave->close();
        }
    }
    header("Location: cart_view.php?added=1");
    exit;
}

header("Location: wishlist_view.php");
exit;