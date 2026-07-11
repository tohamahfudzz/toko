<?php
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header("Location: loginuser/index.php?return=" . rawurlencode("cart_view.php"));
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8');

$st = $conn->prepare("SELECT keranjang FROM usertb WHERE id=? LIMIT 1");
$st->bind_param("i", $user_id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

$cart = [];
if (!empty($row['keranjang'])) {
    $d = json_decode($row['keranjang'], true);
    if (is_array($d)) $cart = $d;
}

// Ambil gambar semua produk sekaligus
$gambarMap = [];
if (!empty($cart)) {
    $ids = implode(',', array_map(fn($i) => (int)$i['id'], $cart));
    $res = $conn->query("SELECT id, gambar FROM produk WHERE id IN ($ids)");
    if ($res) while ($r = $res->fetch_assoc()) $gambarMap[$r['id']] = $r['gambar'];
}

$total = array_reduce($cart, fn($carry, $i) => $carry + (int)$i['harga'] * (int)$i['qty'], 0);

$waMsg = "Halo, saya ingin memesan:\n";
foreach ($cart as $item) {
    $waMsg .= "- {$item['nama']} x{$item['qty']} = Rp " . number_format((int)$item['harga'] * (int)$item['qty'], 0, ',', '.') . "\n";
}
$waMsg .= "\nTotal: Rp " . number_format($total, 0, ',', '.');
$waNumber = '6281234567890';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Keranjang Belanja — Comp Store</title>
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

    .cart-list { display:flex; flex-direction:column; gap:12px; }
    .cart-item { background:#fff; border-radius:16px; border:1px solid #e5e7eb; box-shadow:0 1px 6px rgba(0,0,0,.05); display:flex; align-items:center; gap:16px; padding:16px 20px; flex-wrap:wrap; }
    .cart-img { width:76px; height:76px; object-fit:contain; border-radius:10px; background:#f8f9fb; padding:6px; flex-shrink:0; }
    .cart-info { flex:1; min-width:140px; }
    .cart-info h3 { font-size:15px; font-weight:600; color:#111827; margin-bottom:3px; }
    .cart-info .price { font-size:17px; font-weight:700; color:#1a6fe8; }
    .cart-info .subtotal { font-size:13px; color:#6b7280; margin-top:2px; }

    .qty-form { display:flex; align-items:center; gap:6px; }
    .qty-btn { width:32px; height:32px; border-radius:8px; border:1.5px solid #e5e7eb; background:#f9fafb; font-size:18px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#374151; transition:all .18s; line-height:1; }
    .qty-btn:hover { border-color:#1a6fe8; color:#1a6fe8; background:#eff6ff; }
    .qty-input { width:46px; height:32px; text-align:center; border:1.5px solid #e5e7eb; border-radius:8px; font-size:15px; font-weight:600; color:#111827; font-family:inherit; }
    .qty-input:focus { outline:none; border-color:#1a6fe8; }

    .btn-hapus { padding:8px 14px; background:#fff; border:1.5px solid #fecaca; border-radius:10px; color:#dc2626; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; transition:all .18s; white-space:nowrap; }
    .btn-hapus:hover { background:#fef2f2; border-color:#dc2626; }

    .cart-summary { background:#fff; border-radius:18px; border:1px solid #e5e7eb; box-shadow:0 2px 12px rgba(0,0,0,.06); padding:22px 28px; margin-top:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; }
    .summary-label { font-size:14px; color:#6b7280; margin-bottom:4px; }
    .summary-total { font-size:24px; font-weight:700; color:#111827; }
    .summary-total span { color:#1a6fe8; }
    .summary-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }

    .btn-checkout { display:inline-flex; align-items:center; gap:8px; padding:13px 22px; background:#22c55e; color:#fff; border:none; border-radius:12px; font-size:15px; font-weight:700; cursor:pointer; text-decoration:none; font-family:inherit; box-shadow:0 4px 14px rgba(34,197,94,.3); transition:background .18s,transform .15s; }
    .btn-checkout:hover { background:#16a34a; transform:translateY(-2px); }
    .btn-lanjut { display:inline-flex; align-items:center; gap:6px; padding:13px 18px; background:#fff; color:#374151; border:1.5px solid #e5e7eb; border-radius:12px; font-size:14px; font-weight:600; text-decoration:none; transition:all .18s; }
    .btn-lanjut:hover { border-color:#1a6fe8; color:#1a6fe8; background:#eff6ff; }

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
      .cart-item{padding:14px 16px;gap:12px} .cart-img{width:60px;height:60px}
      .cart-summary{flex-direction:column;align-items:flex-start}
      .footer-inner{flex-direction:column;align-items:flex-start;padding:24px 16px}
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
      <a href="wishlist_view.php">❤️ Wishlist</a>
      <a href="cart_view.php" class="active">🛒 Keranjang</a>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <div class="page-header">
    <h2>🛒 Keranjang Belanja</h2>
    <span>Halo <strong><?= $username ?></strong> — <?= count($cart) ?> produk</span>
  </div>

  <?php if (!empty($_GET['added'])): ?>
    <div class="alert-success">✅ Produk berhasil ditambahkan ke keranjang!</div>
  <?php endif; ?>

  <?php if (empty($cart)): ?>
    <div class="empty-state">
      <div class="icon">🛒</div>
      <h3>Keranjang masih kosong</h3>
      <p>Yuk tambahkan produk favoritmu!</p>
      <a href="produk.php">📦 Lihat Produk</a>
    </div>

  <?php else: ?>

    <div class="cart-list">
      <?php foreach ($cart as $item):
        $pid      = (int)$item['id'];
        $nama     = htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8');
        $harga    = (int)$item['harga'];
        $qty      = (int)$item['qty'];
        $subtotal = $harga * $qty;
        $gambar   = $gambarMap[$pid] ?? null;
        $imgSrc   = $gambar ? 'data:image/webp;base64,' . base64_encode($gambar) : 'background/background.png';
      ?>
      <div class="cart-item">
        <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nama ?>" class="cart-img">
        <div class="cart-info">
          <h3><?= $nama ?></h3>
          <div class="price">Rp <?= number_format($harga, 0, ',', '.') ?></div>
          <div class="subtotal">Subtotal: Rp <?= number_format($subtotal, 0, ',', '.') ?></div>
        </div>

        <!-- Update qty -->
        <form method="POST" action="cart.php" class="qty-form">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $pid ?>">
          <button type="button" class="qty-btn" onclick="changeQty(this,-1)">−</button>
          <input type="number" name="qty" value="<?= $qty ?>" min="1" max="99" class="qty-input" onchange="this.form.submit()">
          <button type="button" class="qty-btn" onclick="changeQty(this,1)">+</button>
        </form>

        <!-- Hapus -->
        <a href="cart.php?action=hapus&id=<?= $pid ?>"
           class="btn-hapus"
           onclick="return confirm('Hapus <?= addslashes($nama) ?> dari keranjang?')">🗑️ Hapus</a>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Summary -->
    <div class="cart-summary">
      <div>
        <div class="summary-label">Total Belanja (<?= count($cart) ?> item)</div>
        <div class="summary-total">Rp <span><?= number_format($total, 0, ',', '.') ?></span></div>
      </div>
      <div class="summary-actions">
        <a href="produk.php" class="btn-lanjut">← Lanjut Belanja</a>
        <a href="https://wa.me/<?= $waNumber ?>?text=<?= rawurlencode($waMsg) ?>"
           target="_blank" rel="noopener" class="btn-checkout">
          🟢 Checkout via WhatsApp
        </a>
      </div>
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
      <a href="wishlist_view.php">Wishlist</a>
    </div>
  </div>
  <div class="footer-bottom">© 2026 Comp Store • All Rights Reserved</div>
</footer>

<script>
function changeQty(btn, delta) {
  const form  = btn.closest('form');
  const input = form.querySelector('.qty-input');
  input.value = Math.max(1, Math.min(99, parseInt(input.value) + delta));
  form.submit();
}
</script>
</body>
</html>