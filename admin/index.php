

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Admin Upload Produk</title>
  <link rel="stylesheet" href="style.css">
  <script src="script.js"></script>
</head>
<body>
  <div class="container">
    <h2>Form Upload Produk</h2>
    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
      <label for="nama">Nama Produk:</label>
      <input type="text" id="nama" name="nama" required>

      <label for="harga">Harga:</label>
      <input type="number" id="harga" name="harga" required>

      <label for="deskripsi">Deskripsi:</label>
      <textarea id="deskripsi" name="deskripsi" required></textarea>

      <label for="gambar">Upload Gambar:</label>
      <input type="file" id="gambar" name="gambar" accept="image/*" required>

      <button type="submit" name="submit">Upload</button>
    </form>
  </div>
</body>
</html>

<?php
include "koneksi.php";

if (isset($_POST['submit'])) {
    $nama      = $_POST['nama'];
    $harga     = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];

    // ambil data gambar
    $gambarTmp  = $_FILES['gambar']['tmp_name'];
    $gambarData = file_get_contents($gambarTmp);

    // gunakan prepared statement
    $stmt = $conn->prepare("INSERT INTO produk (nama, harga, deskripsi, gambar) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $nama, $harga, $deskripsi, $gambarData);

    if ($stmt->execute()) {
        echo "<p style='text-align:center;color:green;'>Produk berhasil diupload!</p>";
    } else {
        echo "<p style='text-align:center;color:red;'>Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
}
$conn->close();
?>
