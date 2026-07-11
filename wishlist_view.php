<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: loginuser/index.php?return=" . rawurlencode("wishlist-view.php"));
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8');

$st = $conn->prepare("SELECT wishlist FROM usertb WHERE id=? LIMIT 1");
$st->bind_param("i", $user_id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

$list = [];
if (!empty($row['wishlist'])) {
    $d = json_decode($row['wishlist'], true);
    if (is_array($d)) $list = $d;
}

// Ambil gambar
$gambarMap = [];
if (!empty($list)) {
    $ids = implode(',', array_map(fn($i) => (int)$i['id'], $list));
    $res = $conn->query("SELECT id, gambar FROM produk WHERE id IN ($ids)");
    if ($res) while ($r = $res->fetch_assoc()) $gambarMap[$r['id']] = $r['gambar'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wishlist — Comp Store</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background:#f0f4f8; gap:0; padding-bottom:0; align-items:stretch; }

    .navbar { width:100%; background:#fff; border-bottom:1px solid #e5e7eb; box-shadow:0 1px 8px rgba(0,0,0,.07); position:sticky; top:0; z-index:100; }
    .navbar-inner { max-width:1440px; margin:0 auto; padding:14px 28px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .navbar-brand { display:flex; align-items:center; gap:12px; text-decoration:none; color:#111827; }
    .navbar-logo { width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#1a6fe8,#1255c4); display:flex; align-items:center; justify-content:center; font-size:22px; color:#fff; flex-shrink:0; }
    .navbar-brand h1 { font-size:18px; font-weight:700; }
    .navbar-brand span { font-size:12px; color:#6b7280; display:block; }
    .navbar-links { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
    .navbar-links a { padding:8px 14px; border-radius:10px; text-decoration:none; color:#374151; font-size:14px; font-weight:500; transition:background .18s,color .18s; }
    .navbar-links a:hover { background:#eff6ff; color:#1a6fe8; }
    .navbar-links a.active { background:#1a6fe8; color:#fff; }

    .page-wrap { max-width:900px; margin:0 auto; padding:28px 20px 60px; width:100%; }

    .page-header { background:#fff; border-radius:18px; padding:22px 28px; margin-bottom:20px; box-shadow:0 2px 12px rgba(0,0,0,.06); border:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .page-header h2 { font-size:22px; font-weight:700; color:#111827; }
    .page-header span { font-size:14px; color:#6b7280; }

    .alert-success { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; padding:12px 18px; border-radius:12px; margin-bottom:16px; font-size:14px; font-weight:600; }

    .empty-state { background:#fff; border-radius:18px; border:1px solid #e5e7eb; text-align:center; padding:56px 20px; }
    .empty-state .icon { font-size:52px; margin-bottom:12px; }
    .empty-state h3 { font-size:18px; font-weight:600; color:#374151; margin-bottom:8px; }
    .empty-state p { color:#6b7280; margin-bottom:20px; }
    .empty-state a { display:inline-block; padding:11px 24px; background:#1a6fe8; color:#fff; border-radius:12px; text-decoration:none; font-weight:600; }

    .wish-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }

    .wish-card { background:#fff; border-radius:16px; border:1px solid #e5e7eb; box-shadow:0 1px 6px rgba(0,0,0,.05); overflow:hidden; display:flex; flex-direction:column; transition:transform .22s,box-shadow .22s; }
    .wish-card:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.10); }

    .wish-img { width:100%; height:180px; object-fit:contain; background:#f8f9fb; padding:16px; display:block; transition:transform .32s; }
    .wish-card:hover .wish-img { transform:scale(1.04); }

    .wish-body { padding:14px 16px 8px; flex:1; }
    .wish-name { font-size:14.5px; font-weight:600; color:#111827; line-height:1.35; margin-bottom:5px; }
    .wish-price { font-size:18px; font-weight:700; color:#1a6fe8; }

    .wish-actions { display:flex; gap:8px; padding:10px 16px 14px; }
    .btn-ke-keranjang { flex:1; padding:9px 0; background:#1a6fe8; color:#fff; border:none; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; text-align:center; transition:background .18s,transform .15s; font-family:inherit; }
    .btn-ke-keranjang:hover { background:#1255c4; transform:translateY(-1px); }
    .btn-hapus-wish { padding:9px 12px; background:#fff; border:1.5px solid #fecaca; border-radius:10px; color:#dc2626; font-size:13px; font-weight:600; cursor:pointer; transition:all .18s; white-space:nowrap; font-family:inherit; }
    .btn-hapus-wish:hover { background:#fef2f2; border-color:#dc2626; }

    .footer { background:#111827; color:#fff; margin-top:40px; width:100%; }
    .footer-inner { max-width:1440px; margin:0 auto; padding:32px 28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; }
    .footer-brand { display:flex; align-items:center; gap:14px; }
    .footer-logo { width:50px; height:50px; border-radius:14px; background:#1a6fe8; display:flex; align-items:center; justify-content:center; font-size:24px; }
    .footer-brand h2 { font-size:18px; font-weight:700; }
    .footer-brand p { color:#9ca3af; font-size:13px; margin-top:2px; }
    .footer-links { display:flex; gap:18px; flex-wrap:wrap; }
    .footer-links a { color:#d1d5db; text-decoration:none; font-size:14px; }
    .footer-links a:hover { color:#fff; }
    .footer-bottom { border-top:1px solid rgba(255,255,255,.08); text-align:center; padding:14px; color:#6b7280; font-size:13px; }

    @media(max-width:768px){
      .navbar-inner{padding:12px 16px} .navbar-brand span{display:none}
      .page-wrap{padding:16px 14px 40px}
      .wish-grid{grid-template-columns:repeat(2,1fr);gap:12px}
      .wish-img{height:150px}
      .footer-inner{flex-direction:column;align-items:flex-start;padding:24px 16px}
    }
    @media(max-width:380px){
      .wish-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="index.php" class="navbar-brand">
      <div class="navbar-logo">🖥️</div>
      <div><h1>COMP STORE</h1><span>Gaming • Office • Creator</span></div>
    </a>
    <div class="navbar-links">
      <a href="index.php">🏠 Beranda</a>
      <a href="produk.php">📦 Produk</a>
      <a href="galery.php">🖼️ Galeri</a>
      <a href="wishlist_view.php" class="active">❤️ Wishlist</a>
      <a href="cart_view.php">🛒 Keranjang</a>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <div class="page-header">
    <h2>❤️ Wishlist</h2>
    <span>Halo <strong><?= $username ?></strong> — <?= count($list) ?> produk tersimpan</span>
  </div>

  <?php if (!empty($_GET['added'])): ?>
    <div class="alert-success">✅ Produk ditambahkan ke wishlist!</div>
  <?php endif; ?>

  <?php if (empty($list)): ?>
    <div class="empty-state">
      <div class="icon">❤️</div>
      <h3>Wishlist masih kosong</h3>
      <p>Simpan produk favoritmu di sini!</p>
      <a href="produk.php">📦 Lihat Produk</a>
    </div>

  <?php else: ?>
    <div class="wish-grid">
      <?php foreach ($list as $item):
        $pid    = (int)$item['id'];
        $nama   = htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8');
        $harga  = number_format((int)$item['harga'], 0, ',', '.');
        $gambar = $gambarMap[$pid] ?? null;
        $imgSrc = $gambar ? 'data:image/webp;base64,' . base64_encode($gambar) : 'background/background.png';
      ?>
      <div class="wish-card">
        <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nama ?>" class="wish-img" loading="lazy">
        <div class="wish-body">
          <div class="wish-name"><?= $nama ?></div>
          <div class="wish-price">Rp <?= $harga ?></div>
        </div>
        <div class="wish-actions">
          <!-- Pindah ke keranjang -->
          <a href="wishlist.php?action=ke_keranjang&id=<?= $pid ?>" class="btn-ke-keranjang">🛒 Beli</a>
          <!-- Hapus dari wishlist -->
          <button class="btn-hapus-wish"
            onclick="if(confirm('Hapus dari wishlist?')) window.location='wishlist.php?action=hapus&id=<?= $pid ?>'">
            🗑️
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:24px;text-align:center;">
      <a href="produk.php" style="display:inline-block;padding:11px 24px;background:#1a6fe8;color:#fff;border-radius:12px;text-decoration:none;font-weight:600;">← Lanjut Belanja</a>
    </div>
  <?php endif; ?>

</div>

<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand"><div class="footer-logo">🖥️</div><div><h2>COMP STORE</h2><p>Gaming • Office • Creator</p></div></div>
    <div class="footer-links">
      <a href="index.php">Beranda</a>
      <a href="produk.php">Produk</a>
      <a href="galery.php">Galeri</a>
      <a href="cart_view.php">Keranjang</a>
    </div>
  </div>
  <div class="footer-bottom">© 2026 Comp Store • All Rights Reserved</div>
</footer>

</body>
</html>