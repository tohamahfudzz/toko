<?php
// login/index.php
session_start();

// Ambil pesan error dari session (jika ada) lalu hapus
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Halaman Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="container" role="main" aria-labelledby="login-heading">
    <h2 id="login-heading">HALAMAN LOGIN</h2>

    <?php if ($login_error): ?>
      <div class="errors" role="alert" aria-live="polite">
        <ul><li><?php echo htmlspecialchars($login_error); ?></li></ul>
      </div>
    <?php endif; ?>

    <form id="loginForm" action="login.php" method="post" onsubmit="return validasi();">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username" />

      <label for="password">Password</label>
      <div class="input-group" style="margin-top:5px;">
        <input type="password" id="password" name="password" autocomplete="current-password" />
        <button type="button" id="toggleBtn" aria-label="Tampilkan password" aria-pressed="false">👁</button>
      </div>

      <button type="submit" id="submitBtn">Login</button>

      <hr />

      <div style="text-align:center; margin-top:8px;">
        <a href="forgot.php">Lupa Password?</a>
      </div>
    </form>
  </div>

<script>
  // Toggle visibility password
  document.getElementById('toggleBtn').addEventListener('click', function () {
    const pw = document.getElementById('password');
    if (pw.type === 'password') {
      pw.type = 'text';
      this.textContent = '🙈';
      this.setAttribute('aria-pressed', 'true');
    } else {
      pw.type = 'password';
      this.textContent = '👁';
      this.setAttribute('aria-pressed', 'false');
    }
  });

  // Simple client-side validation
  function validasi() {
    const user = document.getElementById('username').value.trim();
    const pass = document.getElementById('password').value.trim();

    if (user === '' || pass === '') {
      // tampilkan pesan error sederhana tanpa reload
      alert('Username dan Password harus diisi!');
      return false;
    }

    // Optional: disable submit button to prevent double submit
    document.getElementById('submitBtn').disabled = true;
    return true;
  }
</script>

</body>
</html>
