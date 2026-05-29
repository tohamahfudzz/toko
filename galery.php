<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Galery Produk</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  Galery Produk
</header>

<section class="produk-container">
  <?php
    $conn = new mysqli("localhost", "root", "", "tokodb");
    if ($conn->connect_error) {
      die("Koneksi gagal: " . $conn->connect_error);
    }

    $sql = "SELECT nama, gambar FROM produk"; // ambil nama + gambar saja
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        $nama = $row['nama'];
        $gambar_base64 = base64_encode($row['gambar']);

        echo "
        <div class='card'>
          <img src='data:image/jpeg;base64,$gambar_base64' class='gambar-produk'>
          <h3>$nama</h3>
        </div>
        ";
      }
    } else {
      echo "<p style='text-align:center;'>Belum ada gambar produk tersedia.</p>";
    }
    $conn->close();
  ?>
</section>

<!-- Tombol navigasi -->
<div class="lihat-semua">
  <a href="index.php" class="btn-lihat">Halaman Utama</a>
  <a href="produk.php" class="btn-lihat">Produk</a>
</div>

</body>
</html>