<?php
// loginuser/index.php
session_start();

$isLoggedIn  = !empty($_SESSION['user_id']);
$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Sanitasi return URL
$rawReturn  = $_GET['return'] ?? '';
$parsed     = parse_url($rawReturn);
$returnPath = '';
if ($parsed !== false && isset($parsed['path'])) {
    $returnPath = $parsed['path'];
    if (isset($parsed['query'])) $returnPath .= '?' . $parsed['query'];
}
$returnPath = $returnPath !== '' ? $returnPath : '/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — Comp Store</title>
  <link rel="stylesheet" href="style.css">
</head>
<body data-logged-in="<?= $isLoggedIn ? '1' : '0' ?>">

  <div class="container" role="main">
    <!-- Logo area -->
    <div class="logo-area">
      <div class="logo-icon">🖥️</div>
      <h2>Selamat Datang</h2>
      <p class="subtitle">Masuk ke akun Comp Store kamu</p>
    </div>

    <?php if ($login_error): ?>
      <div class="errors" role="alert">
        <ul><li><?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8') ?></li></ul>
      </div>
    <?php endif; ?>

    <form id="loginForm" action="login.php" method="post" onsubmit="return validasi();">
      <input type="hidden" name="return" value="<?= htmlspecialchars($returnPath, ENT_QUOTES, 'UTF-8') ?>">

      <label for="username">Username</label>
      <input type="text" id="username" name="username" autocomplete="username" placeholder="Masukkan username">

      <label for="password">Password</label>
      <div class="input-group">
        <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password">
        <button type="button" id="toggleBtn" aria-label="Tampilkan password">👁</button>
      </div>

      <button type="submit" id="submitBtn">🔐 Login</button>
    </form>

    <hr>

    <div class="auth-links">
      <a href="forgot.php">Lupa Password?</a>
      &nbsp;·&nbsp;
      <a href="../register/index.php">Daftar Akun Baru</a>
    </div>

    <div style="text-align:center; margin-top:16px; font-size:13px; color:#9ca3af;">
      <a href="../index.php" style="color:#6b7280;">← Kembali ke Toko</a>
    </div>
  </div>

<script>
  document.getElementById('toggleBtn').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const isHidden = pw.type === 'password';
    pw.type = isHidden ? 'text' : 'password';
    this.textContent = isHidden ? '🙈' : '👁';
    this.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
  });

  function validasi() {
    const user = document.getElementById('username').value.trim();
    const pass = document.getElementById('password').value.trim();
    if (!user || !pass) {
      alert('Username dan Password harus diisi!');
      return false;
    }
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Sedang masuk...';
    setTimeout(() => { btn.disabled = false; btn.textContent = '🔐 Login'; }, 4000);
    return true;
  }

  // Redirect jika sudah login
  (function () {
    const isLoggedIn = document.body.getAttribute('data-logged-in') === '1';
    const params = new URLSearchParams(window.location.search);
    const returnPath = params.get('return');
    if (isLoggedIn && returnPath) {
      try {
        const u = new URL(returnPath, window.location.origin);
        if (u.origin === window.location.origin) window.location.replace(u.pathname + (u.search || ''));
      } catch (e) {}
    }
  })();
</script>

</body>
</html>
