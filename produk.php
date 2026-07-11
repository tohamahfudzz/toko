<?php
session_start();
require_once __DIR__ . '/config.php';

$q        = trim($_GET['q'] ?? '');
$kategori = $_GET['kategori'] ?? '';
$sort     = ($_GET['sort'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$perPage  = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

$whereClauses = []; $params = []; $types = '';
if ($q !== '')        { $whereClauses[] = "nama LIKE ?";               $params[] = "%{$q}%"; $types .= 's'; }
if ($kategori !== '') { $whereClauses[] = "LOWER(kategori)=LOWER(?)";  $params[] = $kategori; $types .= 's'; }
$whereSql = $whereClauses ? 'WHERE '.implode(' AND ', $whereClauses) : '';

// Count total
$sc = $conn->prepare("SELECT COUNT(*) AS total FROM produk $whereSql");
if (!empty($params)) { $b=[&$types]; foreach($params as &$p) $b[]=&$p; call_user_func_array([$sc,'bind_param'],$b); }
$sc->execute();
$totalItems = (int)$sc->get_result()->fetch_assoc()['total'];
$sc->close();
$totalPages = max(1, (int)ceil($totalItems / $perPage));

// Fetch produk
$stmt = $conn->prepare("SELECT * FROM produk $whereSql ORDER BY harga $sort LIMIT ? OFFSET ?");
$tL = $types.'ii'; $pL = array_merge($params, [$perPage, $offset]);
$b = [&$tL]; foreach($pL as &$p) $b[]=&$p;
call_user_func_array([$stmt,'bind_param'], $b);
$stmt->execute();
$result = $stmt->get_result();

$isLoggedIn = !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Produk — Comp Store</title>
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

    .page-wrap { max-width:1440px; margin:0 auto; padding:28px 24px 48px; width:100%; }

    .page-header { background:#fff; border-radius:18px; padding:28px 32px; margin-bottom:24px; box-shadow:0 2px 12px rgba(0,0,0,.06); border:1px solid #e5e7eb; }
    .page-header h2 { font-size:26px; font-weight:700; color:#111827; margin-bottom:6px; }
    .page-header p { color:#6b7280; font-size:15px; }

    .search-filter-bar { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; align-items:center; }
    .search-filter-bar input[type="text"] { flex:1 1 220px; padding:10px 16px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:14px; font-family:inherit; background:#f9fafb; color:#111827; transition:border-color .2s,box-shadow .2s; }
    .search-filter-bar input[type="text"]:focus { outline:none; border-color:#1a6fe8; box-shadow:0 0 0 3px rgba(26,111,232,.1); background:#fff; }
    .search-filter-bar select { padding:10px 14px; border:1.5px solid #e5e7eb; border-radius:10px; font-size:14px; font-family:inherit; background:#f9fafb; color:#111827; cursor:pointer; }
    .search-filter-bar select:focus { outline:none; border-color:#1a6fe8; }
    .btn-filter { padding:10px 20px; background:#1a6fe8; color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit; transition:background .18s,transform .15s; }
    .btn-filter:hover { background:#1255c4; transform:translateY(-1px); }

    .produk-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px; }

    .p-card { background:#fff; border-radius:16px; border:1px solid #e5e7eb; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 1px 6px rgba(0,0,0,.06); transition:transform .22s,box-shadow .22s; position:relative; }
    .p-card:hover { transform:translateY(-5px); box-shadow:0 12px 32px rgba(0,0,0,.10); }
    .p-card-badge { position:absolute; top:12px; left:12px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; color:#fff; }
    .badge-bs{background:#f59e0b} .badge-new{background:#8b5cf6} .badge-hot{background:#ef4444}
    .p-card-img { width:100%; height:200px; object-fit:contain; background:#f8f9fb; padding:18px; display:block; transition:transform .32s; }
    .p-card:hover .p-card-img { transform:scale(1.04); }
    .p-card-body { padding:14px 18px 8px; display:flex; flex-direction:column; gap:5px; flex:1; }
    .p-card-cat { font-size:12.5px; color:#6b7280; }
    .p-card-name { font-size:15.5px; font-weight:600; color:#111827; line-height:1.35; }
    .p-card-rating { display:flex; align-items:center; gap:6px; font-size:13px; color:#4b5563; }
    .p-card-price { font-size:21px; font-weight:700; color:#1a6fe8; margin-top:2px; }
    .p-card-desc { font-size:13px; color:#6b7280; line-height:1.5; flex:1; }
    .p-card-tags { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
    .p-card-tag { font-size:11.5px; font-weight:600; background:#f3f4f6; color:#374151; padding:5px 9px; border-radius:999px; }

    .btn-wa { display:block; margin:14px 18px; padding:12px; background:#22c55e; color:#fff; text-align:center; text-decoration:none; border-radius:12px; font-weight:600; font-size:14px; box-shadow:0 3px 10px rgba(34,197,94,.25); transition:background .18s,transform .15s; }
    .btn-wa:hover { background:#16a34a; transform:translateY(-2px); }

    .p-card-actions { display:flex; gap:8px; padding:0 18px 16px; }
    .p-card-actions button { flex:1; padding:9px 0; border:1.5px solid #e5e7eb; border-radius:10px; background:#fff; color:#374151; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; transition:border-color .18s,color .18s,background .18s; }
    .p-card-actions button:hover { border-color:#1a6fe8; color:#1a6fe8; background:#eff6ff; }
    /* Wishlist button merah saat hover */
    .p-card-actions .btn-wish:hover { border-color:#f87171; color:#dc2626; background:#fef2f2; }

    /* Login prompt jika belum login */
    .login-notice { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:12px 18px; margin-bottom:20px; font-size:14px; color:#92400e; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .login-notice a { color:#1a6fe8; font-weight:600; text-decoration:none; }
    .login-notice a:hover { text-decoration:underline; }

    .empty-state { grid-column:1/-1; text-align:center; padding:64px 20px; color:#6b7280; }
    .empty-state .icon { font-size:52px; margin-bottom:14px; }
    .empty-state h3 { font-size:18px; font-weight:600; color:#374151; margin-bottom:6px; }

    .pagination-wrap { display:flex; justify-content:center; gap:6px; flex-wrap:wrap; margin-top:32px; }
    .pagination-wrap a { display:inline-flex; align-items:center; justify-content:center; min-width:38px; height:38px; padding:0 10px; border:1.5px solid #e5e7eb; border-radius:10px; text-decoration:none; color:#374151; font-weight:600; font-size:14px; background:#fff; transition:all .18s; }
    .pagination-wrap a:hover { border-color:#1a6fe8; color:#1a6fe8; background:#eff6ff; }
    .pagination-wrap a.active { background:#1a6fe8; color:#fff; border-color:#1a6fe8; box-shadow:0 3px 10px rgba(26,111,232,.28); }

    .footer { background:#111827; color:#fff; margin-top:40px; width:100%; }
    .footer-inner { max-width:1440px; margin:0 auto; padding:36px 28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:24px; }
    .footer-brand { display:flex; align-items:center; gap:14px; }
    .footer-logo { width:52px; height:52px; border-radius:14px; background:#1a6fe8; display:flex; align-items:center; justify-content:center; font-size:26px; }
    .footer-brand h2 { font-size:19px; font-weight:700; }
    .footer-brand p { color:#9ca3af; font-size:13px; margin-top:3px; }
    .footer-links { display:flex; gap:20px; flex-wrap:wrap; }
    .footer-links a { color:#d1d5db; text-decoration:none; font-size:14px; font-weight:500; }
    .footer-links a:hover { color:#fff; }
    .footer-bottom { border-top:1px solid rgba(255,255,255,.08); text-align:center; padding:16px; color:#6b7280; font-size:13px; }

    @media(max-width:768px){
      .navbar-inner{padding:12px 16px} .navbar-brand span{display:none}
      .page-wrap{padding:16px 12px 32px}
      .page-header{padding:20px 18px;border-radius:14px} .page-header h2{font-size:21px}
      .produk-grid{grid-template-columns:repeat(2,1fr);gap:12px}
      .p-card-img{height:160px;padding:12px} .p-card-name{font-size:14px} .p-card-price{font-size:18px}
      .footer-inner{flex-direction:column;align-items:flex-start;padding:24px 16px}
    }
    @media(max-width:480px){
      .produk-grid{grid-template-columns:1fr}
      .search-filter-bar{flex-direction:column;align-items:stretch}
      .btn-filter{width:100%}
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
      <a href="produk.php" class="active">📦 Produk</a>
      <a href="galery.php">🖼️ Galeri</a>
      <a href="wishlist_view.php">❤️ Wishlist</a>
      <a href="cart_view.php">🛒 Keranjang</a>
      <a href="https://wa.me/6281234567890" target="_blank" rel="noopener">💬 Chat</a>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <div class="page-header">
    <h2>📦 Semua Produk</h2>
    <p>Temukan komponen komputer terbaik untukmu</p>
    <form method="GET" action="produk.php" class="search-filter-bar">
      <input type="text" name="q" placeholder="🔎 Cari produk..."
             value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
      <select name="kategori">
        <option value="">🏷️ Semua Kategori</option>
        <?php
        $kategoriList = ['RAM','SSD','Mouse','Monitor','Keyboard','Smartphone','Speaker','Notebook','Smartband','Motherboard','Processor','PSU','Aksesoris'];
        foreach ($kategoriList as $k) {
          $sel = (strtolower($kategori) === strtolower($k)) ? 'selected' : '';
          echo "<option value=\"{$k}\" {$sel}>{$k}</option>";
        }
        ?>
      </select>
      <select name="sort">
        <option value="asc"  <?= (($_GET['sort'] ?? 'asc') === 'asc')  ? 'selected':'' ?>>💰 Termurah</option>
        <option value="desc" <?= (($_GET['sort'] ?? '') === 'desc') ? 'selected':'' ?>>💎 Termahal</option>
      </select>
      <button type="submit" class="btn-filter">⚙️ Filter</button>
    </form>
  </div>

  <?php if (!$isLoggedIn): ?>
  <div class="login-notice">
    ⚠️ <span>Kamu belum login. <a href="loginuser/index.php?return=<?= rawurlencode('produk.php') ?>">Login sekarang</a> untuk bisa menambahkan produk ke keranjang & wishlist.</span>
  </div>
  <?php endif; ?>

  <div class="produk-grid">
    <?php
    $badges = [['🏆 Best Seller','badge-bs'],['✨ New','badge-new'],['🔥 Promo','badge-hot']];
    if ($result->num_rows > 0):
      $idx = 0;
      while ($row = $result->fetch_assoc()):
        $pid    = (int)$row['id'];
        $nama   = htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8');
        $harga  = number_format((int)$row['harga'], 0, ',', '.');
        $desk   = htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8');
        $kat    = htmlspecialchars($row['kategori'] ?? '', ENT_QUOTES, 'UTF-8');
        $waMsg  = rawurlencode("Halo, saya ingin membeli produk: " . $row['nama']);
        $badge  = $badges[$idx] ?? null;
        $imgSrc = !empty($row['gambar']) ? 'data:image/webp;base64,'.base64_encode($row['gambar']) : 'background/background.png';
    ?>
    <div class="p-card">
      <?php if ($badge): ?><span class="p-card-badge <?= $badge[1] ?>"><?= $badge[0] ?></span><?php endif; ?>
      <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nama ?>" class="p-card-img" loading="lazy">
      <div class="p-card-body">
        <?php if ($kat): ?><p class="p-card-cat">🏷️ <?= $kat ?></p><?php endif; ?>
        <h3 class="p-card-name"><?= $nama ?></h3>
        <div class="p-card-rating"><span>⭐⭐⭐⭐⭐</span><span style="font-weight:700">4.9</span></div>
        <p class="p-card-price">Rp <?= $harga ?></p>
        <p class="p-card-desc"><?= $desk ?></p>
        <div class="p-card-tags">
          <span class="p-card-tag">✅ Garansi Resmi</span>
          <span class="p-card-tag">📦 Ready Stock</span>
        </div>
      </div>
      <a class="btn-wa" href="https://wa.me/6281234567890?text=<?= $waMsg ?>" target="_blank" rel="noopener">🟢 Beli via WhatsApp</a>

      <?php if ($isLoggedIn): ?>
      <div class="p-card-actions">
        <form method="POST" action="cart.php" style="flex:1;margin:0;">
          <input type="hidden" name="id" value="<?= $pid ?>">
          <button type="submit">🛒 Keranjang</button>
        </form>
        <form method="POST" action="wishlist.php" style="flex:1;margin:0;">
          <input type="hidden" name="id" value="<?= $pid ?>">
          <button type="submit" class="btn-wish">❤️ Wishlist</button>
        </form>
      </div>
      <?php else: ?>
      <div class="p-card-actions">
        <a href="loginuser/index.php?return=<?= rawurlencode('produk.php') ?>" style="flex:1;padding:9px 0;border:1.5px solid #e5e7eb;border-radius:10px;background:#f9fafb;color:#6b7280;font-size:13px;font-weight:600;text-align:center;text-decoration:none;">🔐 Login untuk beli</a>
      </div>
      <?php endif; ?>
    </div>
    <?php $idx++; endwhile;
    else: ?>
    <div class="empty-state">
      <div class="icon">🔍</div>
      <h3>Produk tidak ditemukan</h3>
      <p>Coba kata kunci atau filter yang berbeda.</p>
      <a href="produk.php" style="display:inline-block;margin-top:16px;padding:10px 20px;background:#1a6fe8;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">Lihat Semua</a>
    </div>
    <?php endif;
    $stmt->close(); ?>
  </div>

  <div class="pagination-wrap">
    <?php
    $bp = $_GET;
    for ($i = 1; $i <= $totalPages; $i++) {
      $bp['page'] = $i;
      $url = 'produk.php?' . http_build_query($bp);
      $act = ($i === $page) ? 'active' : '';
      echo "<a class='$act' href='" . htmlspecialchars($url) . "'>$i</a>";
    }
    ?>
  </div>

</div>

<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand"><div class="footer-logo">🖥️</div><div><h2>COMP STORE</h2><p>Gaming • Office • Creator</p></div></div>
    <div class="footer-links">
      <a href="index.php">Beranda</a>
      <a href="produk.php">Produk</a>
      <a href="galery.php">Galeri</a>
      <a href="cart_view.php">Keranjang</a>
      <a href="wishlist_view.php">Wishlist</a>
    </div>
  </div>
  <div class="footer-bottom">© 2026 Comp Store • All Rights Reserved</div>
</footer>

</body>
</html>