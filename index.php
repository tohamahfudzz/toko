<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Store</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  Comp Store
  <!-- Tombol Hamburger -->
  <img src="icon/hamburger.png" alt="Menu" class="hamburger" onclick="toggleSidebar()">
</header>

<!-- Slideshow -->
<div class="slideshow-container">
  <?php
    $conn = new mysqli("localhost", "root", "", "tokodb");
    if ($conn->connect_error) {
      die("Koneksi gagal: " . htmlspecialchars($conn->connect_error));
    }

    // hanya ambil 3 gambar untuk slideshow
    $sql = "SELECT gambar FROM produk LIMIT 3";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        $gambar_base64 = base64_encode($row['gambar']);
        echo "<img class='slides fade' src='data:image/jpeg;base64,".$gambar_base64."' alt='Gambar toko'>";
      }
    } else {
      echo "<p style='text-align:center;'>Belum ada gambar di gallery.</p>";
    }
  ?>
</div>

<div class="dot-container">
  <span class="dot"></span> 
  <span class="dot"></span> 
  <span class="dot"></span> 
</div>

<!-- Produk -->
<section class="produk-container">
  <?php
    $sql_produk = "SELECT * FROM produk LIMIT 4"; 
    $result_produk = $conn->query($sql_produk);

    if ($result_produk && $result_produk->num_rows > 0) {
      while($row = $result_produk->fetch_assoc()) {
        // sanitasi output
        $nama = htmlspecialchars($row['nama']);
        $harga = number_format((int)$row['harga'],0,',','.');
        $deskripsi = htmlspecialchars($row['deskripsi']);
        $wa_message = urlencode("Halo, saya ingin membeli produk: ".$nama);
        $gambar_base64 = base64_encode($row['gambar']);

        echo "
        <div class='card'>
          <img src='data:image/jpeg;base64,$gambar_base64' class='gambar-produk'>
          <h3>$nama</h3>
          <p class='harga'>Rp $harga</p>
          <p class='deskripsi'>$deskripsi</p>
          <a class='btn-beli' href='https://wa.me/6281234567890?text=$wa_message' target='_blank'>Beli Produk Ini</a>
        </div>
        ";
      }
    } else {
      echo "<p style='text-align:center;'>Belum ada produk tersedia.</p>";
    }
    $conn->close();
  ?>
</section>

<!-- Tombol lihat semua produk -->
<div class="lihat-semua">
  <a href="produk.php" class="btn-lihat">Lihat Semua Produk</a>
</div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
  <button class="menu-btn">Akun</button>
  <button class="menu-btn">Informasi</button>
</div>

<script src="script.js"></script>
</body>
</html>
