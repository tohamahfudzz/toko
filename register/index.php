<?php
// register/index.php
// Menampilkan form registrasi dan pesan status
$status = $_GET['status'] ?? '';
$pesan  = $_GET['pesan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <center>
 

    <?php if ($status === 'sukses'): ?>
      <div style="color: green; background:#eaffea; padding:10px; border:1px solid green; width:60%; margin:8px auto;">
        Registrasi berhasil! Silakan <a href="../loginuser/index.php">login</a>.
      </div>
    <?php elseif ($status === 'gagal'): ?>
      <div style="color: red; background:#ffeaea; padding:10px; border:1px solid red; width:60%; margin:8px auto;">
        <?php if ($pesan === 'username_sudah_ada'): ?>
          Username sudah digunakan!
        <?php elseif ($pesan === 'invalid_nomor'): ?>
          Format nomor WA tidak valid. Gunakan format +62xxxxxxxxxxx tanpa spasi.
        <?php else: ?>
          Registrasi gagal. Silakan coba lagi.
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </center>

  <div class="register" style="max-width:420px;margin:18px auto;padding:18px;border-radius:8px;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
      <h2>HALAMAN REGISTER</h2>
  <form action="register.php" method="post" onsubmit="return validasi();">
      <div>
        <label>Username:</label>
        <input type="text" name="username" id="username" required>
      </div>

      <div style="margin-top:10px;">
        <label>Password:</label>
        <div style="display:flex;gap:8px;align-items:center;">
          <input type="password" name="password" id="password" required style="flex:1;">
          <button type="button" onclick="togglePassword()" aria-label="Tampilkan password">👁</button>
        </div>
      </div>

      <div style="margin-top:10px;">
        <label>Nomor whatsapp dengan format +62 tanpa spasi dan -:</label>
        <input type="text" name="nomor" id="nomor" placeholder="+6281234567890" required>
      </div>

      <div style="margin-top:14px;">
        <input type="submit" value="Register" class="tombol">
      </div>
    </form>
  </div>

<script>
function togglePassword() {
  var input = document.getElementById("password");
  input.type = input.type === "password" ? "text" : "password";
}

function validasi() {
  var username = document.getElementById("username").value.trim();
  var password = document.getElementById("password").value;
  var nomor = document.getElementById("nomor").value.trim();

  if (!username || !password || !nomor) {
    alert("Semua field harus diisi!");
    return false;
  }

  // Validasi nomor WA: harus mulai dengan +62 dan hanya angka setelahnya
  var re = /^\+62[0-9]{6,15}$/;
  if (!re.test(nomor)) {
    alert("Nomor WA tidak valid. Gunakan format +62 diikuti angka, tanpa spasi.");
    return false;
  }

  return true;
}
</script>

</body>
</html>
