<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tokodb');
define('DB_USER', 'root');
define('DB_PASS', '');

session_start();
$isLoggedIn = !empty($_SESSION['user_id']);

try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi database gagal: ".htmlspecialchars($e->getMessage()));
}

function normalizeKategoriText($text): string {
    $text = html_entity_decode((string)$text, ENT_QUOTES|ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}\p{C}\s]+/u', '', $text);
    return mb_strtolower($text, 'UTF-8');
}

function blob_to_data_uri($blob, $mime='image/webp'): string {
    if ($blob === null || $blob === '') return '';
    return "data:".$mime.";base64,".base64_encode($blob);
}

function kategori_to_emoji($kategori): string {
    $map = ['ram'=>'🧠','ssd'=>'💾','mouse'=>'🖱️','monitor'=>'🖥️','keyboard'=>'⌨️',
            'smartphone'=>'📱','speaker'=>'🔊','notebook'=>'💻','smartband'=>'⌚',
            'motherboard'=>'🧩','motherboar'=>'🧩','processor'=>'⚙️','psu'=>'🔌','aksesoris'=>'🎒'];
    $k = normalizeKategoriText($kategori);
    return $map[$k] ?? '🏷️';
}

function get_badge($index) {
    $badges = [
        0 => ['label'=>'🏆 Best Seller','class'=>'badge-bestseller'],
        1 => ['label'=>'✨ New',         'class'=>'badge-new'],
        2 => ['label'=>'🔥 Promo',       'class'=>'badge-promo'],
    ];
    return $badges[$index] ?? null;
}

$wa_number = '6281234567890';

try {
    $stmtCats = $pdo->prepare("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori <> '' ORDER BY kategori ASC");
    $stmtCats->execute();
    $rawCategories = $stmtCats->fetchAll(PDO::FETCH_COLUMN);
    $categories = array_values(array_unique(array_filter(array_map('trim', $rawCategories))));
} catch (Exception $e) { $categories = []; }

$selectedKategori  = isset($_GET['kategori']) ? trim((string)$_GET['kategori']) : '';
$normalizedKategori = $selectedKategori !== '' ? normalizeKategoriText($selectedKategori) : '';

try {
    $stmtProduk = $pdo->prepare("SELECT id, nama, harga, deskripsi, gambar, kategori FROM produk ORDER BY id DESC LIMIT 24");
    $stmtProduk->execute();
    $allProduk = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);
    if ($normalizedKategori !== '') {
        $produkList = array_values(array_filter($allProduk, function($p) use ($normalizedKategori) {
            return normalizeKategoriText($p['kategori'] ?? '') === $normalizedKategori;
        }));
    } else {
        $produkList = $allProduk;
    }
} catch (Exception $e) { $allProduk = []; $produkList = []; }

$slideFiles = [];
$slideDir = __DIR__.DIRECTORY_SEPARATOR.'slideshow';
if (is_dir($slideDir)) {
    $files = glob($slideDir.DIRECTORY_SEPARATOR.'*.{webp,jpg,jpeg,png}', GLOB_BRACE);
    if ($files) {
        usort($files, fn($a,$b) => strnatcmp(basename($a), basename($b)));
        foreach ($files as $f) $slideFiles[] = 'slideshow/'.basename($f);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comp Store – Gaming • Office • Creator</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* ---- Floating WhatsApp Chat ---- */
    .wa-float {
      position: fixed;
      bottom: 28px;
      right: 28px;
      z-index: 998;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
    }
    .wa-float-tooltip {
      background: #111827;
      color: #fff;
      font-size: 13px;
      font-weight: 600;
      padding: 8px 14px;
      border-radius: 10px;
      white-space: nowrap;
      opacity: 0;
      transform: translateX(10px);
      transition: opacity .2s, transform .2s;
      pointer-events: none;
      box-shadow: 0 4px 14px rgba(0,0,0,.18);
    }
    .wa-float:hover .wa-float-tooltip { opacity:1; transform:translateX(0); }
    .wa-float-btn {
      width: 60px; height: 60px;
      border-radius: 50%;
      background: #22c55e;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 6px 22px rgba(34,197,94,.5);
      text-decoration: none;
      transition: transform .2s, box-shadow .2s;
      animation: wa-pulse 2.5s infinite;
    }
    .wa-float-btn:hover { transform:scale(1.12); box-shadow:0 8px 30px rgba(34,197,94,.65); }
    @keyframes wa-pulse {
      0%,100% { box-shadow:0 6px 22px rgba(34,197,94,.5); }
      50%      { box-shadow:0 6px 32px rgba(34,197,94,.8); }
    }
    @media(max-width:768px){
      .wa-float { bottom:20px; right:16px; }
      .wa-float-btn { width:52px; height:52px; }
    }
  </style>
</head>
<body data-logged-in="<?= $isLoggedIn ? '1' : '0' ?>">

<!-- Header -->
<header class="main-header">
  <div class="logo-section">
    <div class="logo-icon">🖥️</div>
    <div class="logo-text">
      <h1>COMP STORE</h1>
      <span>Gaming • Office • Creator</span>
    </div>
  </div>
  <button id="hamburgerBtn" class="hamburger" aria-label="Buka menu">☰</button>
</header>

<!-- Slideshow -->
<div class="slideshow-wrapper" aria-roledescription="carousel" aria-label="Promosi utama">
  <div class="slideshow-inner">
    <button class="prev" aria-label="Sebelumnya" id="prevBtn">❮</button>
    <div class="slideshow-container" aria-live="polite">
      <?php if (!empty($slideFiles)): ?>
        <?php foreach ($slideFiles as $idx => $path): ?>
          <div class="slide-item<?= $idx===0?' show':'' ?>">
            <img class="slides" src="<?= htmlspecialchars($path) ?>" alt="Slide <?= $idx+1 ?>" loading="lazy">
            <div class="slide-overlay">
              <h2>Upgrade PC Impianmu</h2>
              <p>Gaming • Office • Creator</p>
              <a href="#produk">Belanja Sekarang</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="slide-item show">
          <img class="slides" src="background/background.png" alt="Banner toko" loading="lazy">
          <div class="slide-overlay">
            <h2>Upgrade PC Impianmu</h2>
            <p>Gaming • Office • Creator</p>
            <a href="#produk">Belanja Sekarang</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <button class="next" aria-label="Berikutnya" id="nextBtn">❯</button>
  </div>
  <div class="dot-container" role="tablist" aria-label="Kontrol slideshow">
    <?php
    $dotCount = max(1, count($slideFiles));
    for ($i=0; $i<$dotCount; $i++)
      echo '<button class="dot'.($i===0?' active':'').'" role="tab" aria-selected="'.($i===0?'true':'false').'" aria-label="Slide '.($i+1).'"></button>';
    ?>
  </div>
</div>

<!-- Kategori -->
<section class="kategori" aria-label="Filter kategori">
  <div class="section-header" style="padding:0;">
    <div>
      <h2 class="section-title">Kategori Produk</h2>
      <p class="section-subtitle">Temukan komponen sesuai kebutuhanmu</p>
    </div>
  </div>
  <div class="kategori-grid" role="list">
    <a href="index.php" class="kategori-item <?= $selectedKategori===''?'active':'' ?>" role="listitem">
      🏠 <span>Semua</span>
    </a>
    <?php foreach ($categories as $cat):
      $catEsc  = htmlspecialchars($cat);
      $catUrl  = 'index.php?kategori='.rawurlencode($cat);
      $isActive= ($normalizedKategori!=='' && normalizeKategoriText($cat)===$normalizedKategori);
      $emoji   = kategori_to_emoji($cat);
    ?>
      <a href="<?= $catUrl ?>" class="kategori-item <?= $isActive?'active':'' ?>" role="listitem">
        <?= $emoji ?> <span><?= $catEsc ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Produk -->
<section class="produk-wrapper" id="produk" aria-label="Daftar produk">
  <div class="section-header">
    <div>
      <h2 class="section-title">Produk Pilihan</h2>
      <p class="section-subtitle">Komponen original dengan garansi resmi</p>
    </div>
    <a href="produk.php" class="btn-lihat">📦 Lihat Semua</a>
  </div>
  <div class="produk-container">
    <?php if (!empty($produkList)): ?>
      <?php foreach ($produkList as $idx => $p):
        $id      = (int)$p['id'];
        $nama    = htmlspecialchars($p['nama']);
        $harga   = number_format((int)$p['harga'],0,',','.');
        $deskripsi= htmlspecialchars($p['deskripsi']);
        $kategori= htmlspecialchars($p['kategori']??'');
        $imgSrc  = $p['gambar'] ? blob_to_data_uri($p['gambar']) : 'background/background.png';
        $waMsg   = rawurlencode("Halo, saya ingin membeli produk: ".$p['nama']);
        $badge   = get_badge($idx);
      ?>
        <article class="card" aria-labelledby="produk-<?= $id ?>">
          <?php if ($badge): ?>
            <span class="card-badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
          <?php endif; ?>
          <img src="<?= htmlspecialchars($imgSrc,ENT_QUOTES,'UTF-8') ?>" alt="Gambar <?= $nama ?>" class="gambar-produk" loading="lazy">
          <div class="card-body">
            <p class="kategori-label"><?= kategori_to_emoji($kategori) ?> <?= $kategori ?></p>
            <h3 id="produk-<?= $id ?>"><?= $nama ?></h3>
            <div class="card-rating">
              <span class="stars">⭐⭐⭐⭐⭐</span>
              <span class="rating-val">4.9</span>
            </div>
            <p class="harga">Rp <?= $harga ?></p>
            <p class="deskripsi"><?= $deskripsi ?></p>
            <div class="card-info">
              <span class="info-tag">✅ Garansi Resmi</span>
              <span class="info-tag">📦 Ready Stock</span>
            </div>
          </div>
          <a class="btn-beli" href="https://wa.me/<?= htmlspecialchars($wa_number) ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener noreferrer">
            🟢 Beli via WhatsApp
          </a>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-center" style="grid-column:1/-1;padding:48px 20px;color:#777;">Belum ada produk untuk kategori ini.</p>
    <?php endif; ?>
  </div>
</section>

<!-- Sidebar -->
<div id="sidebar" class="sidebar" aria-hidden="true">
  <button id="closeSidebarBtn" class="close-btn" aria-label="Tutup menu">✕</button>
  <div class="sidebar-logo">
    <div class="sidebar-icon">🖥️</div>
    <h2>COMP STORE</h2>
    <span>Gaming • Office • Creator</span>
  </div>
  <nav class="sidebar-nav" aria-label="Menu utama">
    <a href="index.php"  class="menu-btn">🏠 Beranda</a>
    <a href="produk.php" class="menu-btn">📦 Semua Produk</a>
    <a href="galery.php" class="menu-btn">🖼️ Galeri</a>
    <a href="https://wa.me/<?= htmlspecialchars($wa_number) ?>" target="_blank" rel="noopener" class="menu-btn">💬 Chat Admin</a>
    <button id="akunBtn" class="menu-btn" type="button">👤 Akun</button>
  </nav>
  <div id="accountInfo" class="account-info" aria-live="polite" style="display:none;padding:12px;">
    <strong>Username</strong>
    <div id="usernameDisplay" style="margin-top:6px;font-weight:600;"></div>
  </div>
  <div class="sidebar-footer">Version 1.0</div>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-content">
    <div class="footer-brand">
      <div class="footer-logo">🖥️</div>
      <div>
        <h2>COMP STORE</h2>
        <p>Gaming • Office • Creator</p>
      </div>
    </div>
    <div class="footer-menu">
      <a href="index.php">Beranda</a>
      <a href="produk.php">Produk</a>
      <a href="galery.php">Galeri</a>
      <a href="https://wa.me/<?= htmlspecialchars($wa_number) ?>" target="_blank">Chat Admin</a>
    </div>
  </div>
  <div class="footer-bottom">© 2026 Comp Store • All Rights Reserved</div>
</footer>

<!-- Floating WhatsApp Chat Button -->
<div class="wa-float">
  <span class="wa-float-tooltip">💬 Chat dengan Admin</span>
  <a class="wa-float-btn"
     href="https://wa.me/<?= htmlspecialchars($wa_number) ?>"
     target="_blank" rel="noopener noreferrer"
     aria-label="Chat Admin via WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="#fff">
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
  </a>
</div>

<script src="script.js" defer></script>
</body>
</html>

