<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Produk</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  Daftar Produk
</header>

<section class="produk-container">
  <?php
    $conn = new mysqli("localhost", "root", "", "tokodb");
    if ($conn->connect_error) {
      die("Koneksi gagal: " . $conn->connect_error);
    }

    $sql_produk = "SELECT * FROM produk"; // tampilkan semua produk
    $result_produk = $conn->query($sql_produk);

    if ($result_produk->num_rows > 0) {
      while($row = $result_produk->fetch_assoc()) {
        $nama = $row['nama'];
        $harga = number_format($row['harga'],0,',','.');
        $deskripsi = $row['deskripsi'];
        $wa_message = urlencode("Halo, saya ingin membeli produk: ".$nama);

        // konversi blob ke base64
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

<!-- Tombol navigasi -->
<div class="lihat-semua">
  <a href="index.php" class="btn-lihat">Halaman Utama</a>
  <a href="galery.php" class="btn-lihat">Galery</a>
</div>

</body>
</html>