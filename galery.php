<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Galeri Produk — Comp Store</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background:#f0f4f8; gap:0; padding-bottom:0; align-items:stretch; }

    /* ---- Navbar (identik dengan produk.php) ---- */
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
    .navbar-links a.chat-btn { background:#22c55e; color:#fff; }
    .navbar-links a.chat-btn:hover { background:#16a34a; }

    /* ---- Page wrap ---- */
    .page-wrap { max-width:1440px; margin:0 auto; padding:28px 24px 48px; width:100%; }

    /* ---- Page header (identik dengan produk.php) ---- */
    .page-header { background:#fff; border-radius:18px; padding:28px 32px; margin-bottom:24px; box-shadow:0 2px 12px rgba(0,0,0,.06); border:1px solid #e5e7eb; }
    .page-header h2 { font-size:26px; font-weight:700; color:#111827; margin-bottom:6px; }
    .page-header p { color:#6b7280; font-size:15px; }

    /* ---- Galeri grid ---- */
    .galeri-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:18px; }

    .g-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; overflow:hidden; box-shadow:0 1px 6px rgba(0,0,0,.06); transition:transform .22s,box-shadow .22s; }
    .g-card:hover { transform:translateY(-5px); box-shadow:0 10px 28px rgba(0,0,0,.10); }
    .g-card-img-wrap { width:100%; height:180px; overflow:hidden; background:#f8f9fb; }
    .g-card-img-wrap img { width:100%; height:100%; object-fit:contain; padding:12px; display:block; transition:transform .35s; }
    .g-card:hover .g-card-img-wrap img { transform:scale(1.06); }
    .g-card-body { padding:12px 14px 14px; text-align:center; }
    .g-card-cat { font-size:11.5px; color:#9ca3af; margin-bottom:4px; }
    .g-card-name { font-size:13.5px; font-weight:600; color:#111827; line-height:1.35; margin-bottom:5px; }
    .g-card-price { font-size:14px; font-weight:700; color:#1a6fe8; }

    .empty-state { grid-column:1/-1; text-align:center; padding:64px 20px; color:#6b7280; }
    .empty-state .icon { font-size:52px; margin-bottom:14px; }
    .empty-state h3 { font-size:18px; font-weight:600; color:#374151; margin-bottom:6px; }

    /* ---- Bottom nav ---- */
    .bottom-nav { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:32px; }
    .bottom-nav a { padding:10px 20px; background:#1a6fe8; color:#fff; border-radius:12px; text-decoration:none; font-weight:600; font-size:14px; transition:background .18s,transform .15s; }
    .bottom-nav a:hover { background:#1255c4; transform:translateY(-2px); }
    .bottom-nav a.wa { background:#22c55e; }
    .bottom-nav a.wa:hover { background:#16a34a; }

    /* ---- Footer ---- */
    .footer { background:#111827; color:#fff; margin-top:40px; }
    .footer-inner { max-width:1440px; margin:0 auto; padding:36px 28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:24px; }
    .footer-brand { display:flex; align-items:center; gap:14px; }
    .footer-logo { width:52px; height:52px; border-radius:14px; background:#1a6fe8; display:flex; align-items:center; justify-content:center; font-size:26px; }
    .footer-brand h2 { font-size:19px; font-weight:700; }
    .footer-brand p { color:#9ca3af; font-size:13px; margin-top:3px; }
    .footer-links { display:flex; gap:20px; flex-wrap:wrap; }
    .footer-links a { color:#d1d5db; text-decoration:none; font-size:14px; font-weight:500; }
    .footer-links a:hover { color:#fff; }
    .footer-bottom { border-top:1px solid rgba(255,255,255,.08); text-align:center; padding:16px; color:#6b7280; font-size:13px; }

    /* ---- Floating WA chat button ---- */
    .wa-float {
      position: fixed;
      bottom: 28px;
      right: 28px;
      z-index: 999;
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
    }
    .wa-float:hover .wa-float-tooltip { opacity:1; transform:translateX(0); }
    .wa-float-btn {
      width: 58px; height: 58px;
      border-radius: 50%;
      background: #22c55e;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px;
      box-shadow: 0 6px 20px rgba(34,197,94,.45);
      text-decoration: none;
      transition: transform .2s, box-shadow .2s;
      animation: wa-pulse 2.5s infinite;
    }
    .wa-float-btn:hover { transform:scale(1.1); box-shadow:0 8px 28px rgba(34,197,94,.6); }
    @keyframes wa-pulse {
      0%,100% { box-shadow:0 6px 20px rgba(34,197,94,.45); }
      50%      { box-shadow:0 6px 28px rgba(34,197,94,.75); }
    }

    @media(max-width:768px){
      .navbar-inner{padding:12px 16px}
      .navbar-brand span{display:none}
      .page-wrap{padding:16px 12px 32px}
      .page-header{padding:20px 18px;border-radius:14px}
      .page-header h2{font-size:21px}
      .galeri-grid{grid-template-columns:repeat(2,1fr);gap:12px}
      .g-card-img-wrap{height:150px}
      .footer-inner{flex-direction:column;align-items:flex-start;padding:24px 16px}
      .wa-float{bottom:20px;right:18px}
      .wa-float-btn{width:52px;height:52px;font-size:24px}
    }
    @media(max-width:480px){
      .galeri-grid{grid-template-columns:repeat(2,1fr);gap:10px}
      .g-card-img-wrap{height:130px}
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="index.php" class="navbar-brand">
      <div class="navbar-logo">🖥️</div>
      <div>
        <h1>COMP STORE</h1>
        <span>Gaming • Office • Creator</span>
      </div>
    </a>
    <div class="navbar-links">
      <a href="index.php">🏠 Beranda</a>
      <a href="produk.php">📦 Produk</a>
      <a href="galery.php" class="active">🖼️ Galeri</a>
      <a href="cart_view.php">🛒 Keranjang</a>
      <a href="https://wa.me/6281234567890" target="_blank" rel="noopener" class="chat-btn">💬 Chat Admin</a>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- Page header -->
  <div class="page-header">
    <h2>🖼️ Galeri Produk</h2>
    <p>Koleksi visual semua produk yang tersedia di Comp Store</p>
  </div>

  <!-- Grid galeri -->
  <div class="galeri-grid">
    <?php
    $conn = new mysqli("localhost","root","","tokodb");
    if ($conn->connect_error) die("Koneksi gagal: ".$conn->connect_error);

    $stmt = $conn->prepare("SELECT nama, harga, kategori, gambar FROM produk ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0):
      while($row = $result->fetch_assoc()):
        $nama   = htmlspecialchars($row['nama'],ENT_QUOTES,'UTF-8');
        $harga  = number_format((int)$row['harga'],0,',','.');
        $kat    = htmlspecialchars($row['kategori']??'',ENT_QUOTES,'UTF-8');
        $imgSrc = !empty($row['gambar'])
                  ? 'data:image/webp;base64,'.base64_encode($row['gambar'])
                  : 'background/background.png';
    ?>
    <div class="g-card">
      <div class="g-card-img-wrap">
        <img src="<?=htmlspecialchars($imgSrc,ENT_QUOTES,'UTF-8')?>" alt="<?=$nama?>" loading="lazy">
      </div>
      <div class="g-card-body">
        <?php if($kat): ?><p class="g-card-cat">🏷️ <?=$kat?></p><?php endif; ?>
        <p class="g-card-name"><?=$nama?></p>
        <p class="g-card-price">Rp <?=$harga?></p>
      </div>
    </div>
    <?php endwhile;
    else: ?>
    <div class="empty-state">
      <div class="icon">📭</div>
      <h3>Belum ada produk</h3>
      <p>Tambahkan produk melalui halaman admin.</p>
    </div>
    <?php endif;
    $stmt->close(); $conn->close(); ?>
  </div>

</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">🖥️</div>
      <div><h2>COMP STORE</h2><p>Gaming • Office • Creator</p></div>
    </div>
    <div class="footer-links">
      <a href="index.php">Beranda</a>
      <a href="produk.php">Produk</a>
      <a href="galery.php">Galeri</a>
      <a href="https://wa.me/6281234567890" target="_blank">Chat Admin</a>
    </div>
  </div>
  <div class="footer-bottom">© 2026 Comp Store • All Rights Reserved</div>
</footer>

<!-- Floating WhatsApp -->
<div class="wa-float">
  <span class="wa-float-tooltip">💬 Chat dengan Admin</span>
  <a class="wa-float-btn" href="https://wa.me/6281234567890" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="#fff">
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
  </a>
</div>

<script src="script.js" defer></script>
</body>
</html>