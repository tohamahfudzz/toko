<?php
// register/index.php
$status = $_GET['status'] ?? '';
$pesan  = $_GET['pesan']  ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar Akun — Comp Store</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="container" role="main">

    <!-- Logo area -->
    <div class="logo-area">
      <div class="logo-icon">🖥️</div>
      <h2>Buat Akun Baru</h2>
      <p class="subtitle">Bergabung dengan komunitas Comp Store</p>
    </div>

    <?php if ($status === 'sukses'): ?>
      <div class="success">
        ✅ Registrasi berhasil! <a href="../loginuser/index.php">Klik di sini untuk login</a>.
      </div>
    <?php elseif ($status === 'gagal'): ?>
      <div class="errors">
        <ul>
          <?php if ($pesan === 'username_sudah_ada'): ?>
            <li>Username sudah digunakan, coba yang lain.</li>
          <?php elseif ($pesan === 'invalid_nomor'): ?>
            <li>Format nomor WhatsApp tidak valid. Gunakan format <strong>+62xxx</strong> tanpa spasi.</li>
          <?php else: ?>
            <li>Registrasi gagal. Silakan coba lagi.</li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="register.php" method="post" onsubmit="return validasi();">

      <label for="username">Username</label>
      <input type="text" id="username" name="username" required placeholder="Buat username unik">

      <label for="password">Password</label>
      <div class="input-group">
        <input type="password" id="password" name="password" required placeholder="Min. 8 karakter">
        <button type="button" onclick="togglePassword()" aria-label="Tampilkan password">👁</button>
      </div>

      <label for="nomor">Nomor WhatsApp</label>
      <input type="text" id="nomor" name="nomor" placeholder="+6281234567890" required>
      <p style="font-size:12.5px; color:#9ca3af; margin-top:5px;">Format: +62 diikuti angka, tanpa spasi atau tanda -</p>

      <button type="submit">✅ Daftar Sekarang</button>
    </form>

    <hr>

    <div class="auth-links">
      Sudah punya akun? <a href="../loginuser/index.php">Login di sini</a>
    </div>

    <div style="text-align:center; margin-top:14px; font-size:13px; color:#9ca3af;">
      <a href="../index.php" style="color:#6b7280;">← Kembali ke Toko</a>
    </div>
  </div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  input.type = input.type === 'password' ? 'text' : 'password';
}

function validasi() {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  const nomor    = document.getElementById('nomor').value.trim();

  if (!username || !password || !nomor) {
    alert('Semua field harus diisi!');
    return false;
  }
  if (password.length < 8) {
    alert('Password minimal 8 karakter.');
    return false;
  }
  const re = /^\+62[0-9]{6,15}$/;
  if (!re.test(nomor)) {
    alert('Nomor WA tidak valid. Gunakan format +62 diikuti angka tanpa spasi.');
    return false;
  }
  return true;
}
</script>

</body>
</html>
