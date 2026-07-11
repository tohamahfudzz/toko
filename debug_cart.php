<?php
// debug_cart.php — HAPUS FILE INI SETELAH SELESAI DEBUG
session_start();
require_once __DIR__ . '/config.php';

echo "<h2>Debug Cart & Wishlist</h2>";

// 1. Cek session
echo "<h3>1. Session</h3>";
echo "user_id: " . ($_SESSION['user_id'] ?? '<b style=color:red>TIDAK ADA</b>') . "<br>";
echo "username: " . ($_SESSION['username'] ?? '<b style=color:red>TIDAK ADA</b>') . "<br>";

if (empty($_SESSION['user_id'])) {
    echo "<p style='color:red'><b>❌ Kamu belum login! Session user_id kosong.</b></p>";
    echo "<p>Pastikan sudah login via loginuser/index.php sebelum test ini.</p>";
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 2. Cek koneksi DB
echo "<h3>2. Koneksi DB</h3>";
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ Koneksi gagal: " . $conn->connect_error . "</p>";
    exit;
}
echo "✅ Koneksi OK<br>";

// 3. Cek struktur tabel usertb
echo "<h3>3. Kolom di tabel usertb</h3>";
$res = $conn->query("SHOW COLUMNS FROM usertb");
$cols = [];
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
    echo "- " . $r['Field'] . " (" . $r['Type'] . ")<br>";
}

$hasKeranjang = in_array('keranjang', $cols);
$hasWishlist  = in_array('wishlist', $cols);
echo "<br>Kolom keranjang: " . ($hasKeranjang ? "✅ Ada" : "❌ TIDAK ADA") . "<br>";
echo "Kolom wishlist: "  . ($hasWishlist  ? "✅ Ada" : "❌ TIDAK ADA — jalankan ALTER TABLE dulu") . "<br>";

// 4. Cek data user saat ini
echo "<h3>4. Data user ID $user_id</h3>";
$st = $conn->prepare("SELECT id, username, keranjang, wishlist FROM usertb WHERE id=? LIMIT 1");
$st->bind_param("i", $user_id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if (!$row) {
    echo "<p style='color:red'>❌ User ID $user_id tidak ditemukan di usertb!</p>";
    exit;
}

echo "ID: " . $row['id'] . "<br>";
echo "Username: " . htmlspecialchars($row['username']) . "<br>";
echo "Keranjang (raw): <code>" . htmlspecialchars($row['keranjang'] ?? 'NULL') . "</code><br>";
echo "Wishlist (raw): <code>"  . htmlspecialchars($row['wishlist']  ?? 'NULL') . "</code><br>";

// 5. Coba simpan test ke keranjang
echo "<h3>5. Test simpan keranjang</h3>";
$testJson = json_encode([['id'=>1,'nama'=>'Test Produk','harga'=>100000,'qty'=>1]]);
$st2 = $conn->prepare("UPDATE usertb SET keranjang=? WHERE id=?");
$st2->bind_param("si", $testJson, $user_id);
$ok = $st2->execute();
$affected = $st2->affected_rows;
$st2->close();

echo "Execute: " . ($ok ? "✅ OK" : "❌ GAGAL — " . $conn->error) . "<br>";
echo "Affected rows: $affected<br>";

if ($affected === 0) {
    echo "<p style='color:orange'>⚠️ 0 rows affected — kemungkinan WHERE id=$user_id tidak cocok dengan data di tabel.</p>";
}

// 6. Baca ulang untuk konfirmasi
$st3 = $conn->prepare("SELECT keranjang FROM usertb WHERE id=? LIMIT 1");
$st3->bind_param("i", $user_id);
$st3->execute();
$row3 = $st3->get_result()->fetch_assoc();
$st3->close();
echo "Keranjang setelah disimpan: <code>" . htmlspecialchars($row3['keranjang'] ?? 'NULL') . "</code><br>";

// 7. Bersihkan test data
$conn->prepare("UPDATE usertb SET keranjang=NULL WHERE id=?")->bind_param("i",$user_id);

echo "<hr><p style='color:green'><b>✅ Debug selesai. Lihat hasil di atas untuk menemukan masalah.</b></p>";
echo "<p style='color:red'><b>⚠️ HAPUS file debug_cart.php ini setelah selesai!</b></p>";
?>