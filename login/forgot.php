<?php
session_start();
require_once __DIR__ . '/../config.php';

date_default_timezone_set('Asia/Jakarta');

$error = '';
$success = '';
$step = 1;

$otp_ttl = 300;      // 5 menit
$max_attempts = 5;   // batas percobaan OTP

function normalize_whatsapp_number(string $nomor): string
{
    $nomor = preg_replace('/\D+/', '', $nomor);

    if (str_starts_with($nomor, '0')) {
        $nomor = '62' . substr($nomor, 1);
    } elseif (str_starts_with($nomor, '8')) {
        $nomor = '62' . $nomor;
    }

    return $nomor;
}

function send_otp_whatsapp(string $target, string $pesan): bool
{
    $token = 'XA7aSUMfdTgu7fXWBGBS';

    if ($token === '' || $token === 'XA7aSUMfdTgu7fXWBGBS') {
        return false;
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $target,
            'message' => $pesan,
        ],
        CURLOPT_HTTPHEADER => [
            "Authorization: $token"
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    return $err === '';
}

function clear_reset_session(): void
{
    unset(
        $_SESSION['reset_username'],
        $_SESSION['reset_otp_hash'],
        $_SESSION['reset_otp_expired'],
        $_SESSION['reset_otp_attempts']
    );
}

if (isset($_SESSION['reset_username'], $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expired'])) {
    $step = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'kirim_kode') {
        $username = trim((string)($_POST['username'] ?? ''));

        if ($username === '') {
            $error = 'Username wajib diisi.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT username, nomor FROM admintb WHERE username = ? LIMIT 1"
            );

            if (!$stmt) {
                $error = 'Gagal menyiapkan query.';
            } else {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = $result ? mysqli_fetch_assoc($result) : null;
                mysqli_stmt_close($stmt);

                if ($user) {
                    $nomor = normalize_whatsapp_number((string)$user['nomor']);
                    $kode = (string) random_int(100000, 999999);

                    $_SESSION['reset_username'] = $username;
                    $_SESSION['reset_otp_hash'] = password_hash($kode, PASSWORD_DEFAULT);
                    $_SESSION['reset_otp_expired'] = time() + $otp_ttl;
                    $_SESSION['reset_otp_attempts'] = 0;

                    $pesan = "Kode reset password admin kamu: {$kode}\n\nKode ini berlaku 5 menit.";

                    if (send_otp_whatsapp($nomor, $pesan)) {
                        $success = 'Kode OTP sudah dikirim ke WhatsApp.';
                        $step = 2;
                    } else {
                        $error = 'Gagal mengirim OTP. Cek token Fonnte atau koneksi server.';
                        clear_reset_session();
                    }
                } else {
                    $error = 'Username admin tidak ditemukan.';
                }
            }
        }
    }

    if ($action === 'verifikasi_kode') {
        if (!isset($_SESSION['reset_username'], $_SESSION['reset_otp_hash'], $_SESSION['reset_otp_expired'])) {
            $error = 'Sesi reset tidak ditemukan. Silakan mulai ulang.';
            $step = 1;
        } else {
            if (time() > (int)$_SESSION['reset_otp_expired']) {
                $error = 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode.';
                clear_reset_session();
                $step = 1;
            } else {
                $kode_input = trim((string)($_POST['kode'] ?? ''));

                if ($kode_input === '') {
                    $error = 'Kode OTP wajib diisi.';
                    $step = 2;
                } else {
                    $_SESSION['reset_otp_attempts'] = (int)($_SESSION['reset_otp_attempts'] ?? 0) + 1;

                    if ($_SESSION['reset_otp_attempts'] > $max_attempts) {
                        $error = 'Percobaan OTP terlalu banyak. Silakan kirim kode baru.';
                        clear_reset_session();
                        $step = 1;
                    } else {
                        $valid = password_verify($kode_input, (string)$_SESSION['reset_otp_hash']);

                        if ($valid) {
                            $_SESSION['otp_verified'] = true;
                            $step = 3;
                        } else {
                            $error = 'Kode OTP salah.';
                            $step = 2;
                        }
                    }
                }
            }
        }
    }

    if ($action === 'reset_password') {
        if (
            !isset($_SESSION['reset_username'], $_SESSION['otp_verified']) ||
            empty($_SESSION['otp_verified'])
        ) {
            $error = 'Silakan verifikasi kode OTP terlebih dahulu.';
            $step = 1;
        } else {
            $password_baru = (string)($_POST['password_baru'] ?? '');
            $konfirmasi = (string)($_POST['konfirmasi_password'] ?? '');

            if (strlen($password_baru) < 8) {
                $error = 'Password baru minimal 8 karakter.';
                $step = 3;
            } elseif ($password_baru !== $konfirmasi) {
                $error = 'Konfirmasi password tidak cocok.';
                $step = 3;
            } else {
                $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                $username = (string)$_SESSION['reset_username'];

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE admintb SET password = ? WHERE username = ? LIMIT 1"
                );

                if (!$stmt) {
                    $error = 'Gagal menyiapkan update password.';
                    $step = 3;
                } else {
                    mysqli_stmt_bind_param($stmt, "ss", $hash_baru, $username);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if ($ok) {
                        clear_reset_session();
                        unset($_SESSION['otp_verified']);
                        $success = 'Password admin berhasil diubah. Kamu akan diarahkan ke halaman login.';
                        header("refresh:2;url=index.php");
                    } else {
                        $error = 'Password gagal diperbarui.';
                        $step = 3;
                    }
                }
            }
        }
    }
}

if (isset($_SESSION['otp_verified']) && !empty($_SESSION['otp_verified'])) {
    $step = 3;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password Admin</title>
    <style>
        :root{
            --primary:#0b74de;
            --primary-dark:#0058cc;
            --accent:#25D366;
            --bg:#f4f7fb;
            --text:#111827;
            --muted:#6b7280;
            --card:#ffffff;
            --border:#e5e7eb;
            --danger:#dc2626;
            --success:#16a34a;
            --shadow:0 20px 60px rgba(15,23,42,.12);
            --radius:22px;
        }

        *{box-sizing:border-box}
        body{
            margin:0;
            min-height:100vh;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
            background:linear-gradient(135deg,#eaf2ff 0%,#f8fbff 45%,#eef6ff 100%);
            color:var(--text);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
        }

        .wrap{
            width:100%;
            max-width:980px;
            display:grid;
            grid-template-columns:1.05fr .95fr;
            background:rgba(255,255,255,.7);
            backdrop-filter:blur(14px);
            border:1px solid rgba(255,255,255,.55);
            border-radius:28px;
            overflow:hidden;
            box-shadow:var(--shadow);
        }

        .brand{
            padding:42px 38px;
            background:linear-gradient(145deg,var(--primary) 0%,#0f5dbb 55%,#0a49a3 100%);
            color:#fff;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            gap:24px;
        }

        .brand-top{
            display:flex;
            flex-direction:column;
            gap:18px;
        }

        .brand-badge{
            width:70px;
            height:70px;
            border-radius:20px;
            background:rgba(255,255,255,.16);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:34px;
            box-shadow:inset 0 1px 0 rgba(255,255,255,.18);
        }

        .brand h1{
            margin:0;
            font-size:34px;
            line-height:1.1;
        }

        .brand p{
            margin:0;
            color:rgba(255,255,255,.82);
            font-size:15px;
            line-height:1.7;
            max-width:420px;
        }

        .steps{
            display:flex;
            flex-direction:column;
            gap:14px;
        }

        .step-item{
            display:flex;
            gap:12px;
            align-items:flex-start;
            background:rgba(255,255,255,.10);
            border:1px solid rgba(255,255,255,.14);
            border-radius:16px;
            padding:14px 16px;
        }

        .step-num{
            width:28px;
            height:28px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            background:rgba(255,255,255,.18);
            font-weight:700;
            flex:0 0 auto;
        }

        .step-item strong{
            display:block;
            margin-bottom:4px;
        }

        .step-item span{
            color:rgba(255,255,255,.8);
            font-size:13px;
            line-height:1.5;
        }

        .panel{
            padding:42px 38px;
            background:rgba(255,255,255,.92);
        }

        .panel-header{
            margin-bottom:24px;
        }

        .panel-header h2{
            margin:0 0 8px;
            font-size:28px;
        }

        .panel-header p{
            margin:0;
            color:var(--muted);
            font-size:14px;
            line-height:1.6;
        }

        .alert{
            padding:14px 16px;
            border-radius:14px;
            margin-bottom:18px;
            font-size:14px;
            line-height:1.6;
        }

        .alert.error{
            background:#fef2f2;
            color:var(--danger);
            border:1px solid #fecaca;
        }

        .alert.success{
            background:#f0fdf4;
            color:var(--success);
            border:1px solid #bbf7d0;
        }

        .stepper{
            display:flex;
            gap:10px;
            margin-bottom:22px;
            flex-wrap:wrap;
        }

        .chip{
            padding:9px 12px;
            border-radius:999px;
            background:#f3f4f6;
            color:#4b5563;
            font-size:13px;
            font-weight:600;
        }

        .chip.active{
            background:rgba(11,116,222,.12);
            color:var(--primary);
        }

        form{
            display:flex;
            flex-direction:column;
            gap:16px;
        }

        .field{
            display:flex;
            flex-direction:column;
            gap:7px;
        }

        label{
            font-size:14px;
            font-weight:700;
            color:#374151;
        }

        input{
            width:100%;
            padding:14px 15px;
            border:1.8px solid var(--border);
            border-radius:14px;
            font-size:15px;
            outline:none;
            transition:.2s ease;
            background:#fff;
        }

        input:focus{
            border-color:rgba(11,116,222,.7);
            box-shadow:0 0 0 4px rgba(11,116,222,.08);
        }

        .hint{
            font-size:12px;
            color:var(--muted);
            line-height:1.5;
        }

        .row{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }

        .btn{
            appearance:none;
            border:none;
            border-radius:14px;
            padding:14px 16px;
            font-size:15px;
            font-weight:800;
            cursor:pointer;
            transition:.2s ease;
        }

        .btn-primary{
            background:var(--primary);
            color:#fff;
        }

        .btn-primary:hover{
            background:var(--primary-dark);
            transform:translateY(-1px);
        }

        .btn-secondary{
            background:#eef2ff;
            color:#1e3a8a;
        }

        .btn-secondary:hover{
            background:#e0e7ff;
        }

        .footer-link{
            margin-top:8px;
            text-align:center;
            font-size:14px;
            color:var(--muted);
        }

        .footer-link a{
            color:var(--primary);
            text-decoration:none;
            font-weight:700;
        }

        .footer-link a:hover{text-decoration:underline}

        .small-note{
            margin-top:12px;
            font-size:12px;
            color:var(--muted);
            line-height:1.6;
        }

        @media (max-width:900px){
            .wrap{
                grid-template-columns:1fr;
            }

            .brand{
                padding:30px 24px;
            }

            .panel{
                padding:30px 24px;
            }
        }

        @media (max-width:560px){
            body{padding:14px}
            .brand h1{font-size:28px}
            .panel-header h2{font-size:24px}
            .row{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="brand">
            <div class="brand-top">
                <div class="brand-badge">🔐</div>
                <div>
                    <h1>Lupa Password Admin</h1>
                    <p>
                        Reset password admin lewat verifikasi WhatsApp. Setelah kode OTP benar,
                        kamu bisa langsung membuat password baru dan kembali login.
                    </p>
                </div>
            </div>

            <div class="steps">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <div>
                        <strong>Masukkan username</strong>
                        <span>Sistem akan mencari akun admin di tabel admintb.</span>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-num">2</div>
                    <div>
                        <strong>Verifikasi OTP</strong>
                        <span>Kode dikirim ke nomor WhatsApp yang tersimpan.</span>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-num">3</div>
                    <div>
                        <strong>Buat password baru</strong>
                        <span>Password baru langsung di-hash lalu disimpan.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Reset Password</h2>
                <p>Ikuti langkah berikut sampai selesai. Setelah berhasil, kamu akan kembali ke halaman login.</p>
            </div>

            <div class="stepper">
                <div class="chip <?= $step === 1 ? 'active' : '' ?>">1. Username</div>
                <div class="chip <?= $step === 2 ? 'active' : '' ?>">2. OTP</div>
                <div class="chip <?= $step === 3 ? 'active' : '' ?>">3. Password Baru</div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <form method="post">
                    <input type="hidden" name="action" value="kirim_kode">

                    <div class="field">
                        <label for="username">Username Admin</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan username admin" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Kirim Kode WhatsApp</button>
                </form>
            <?php endif; ?>

            <?php if ($step === 2): ?>
                <form method="post">
                    <input type="hidden" name="action" value="verifikasi_kode">

                    <div class="field">
                        <label for="kode">Kode OTP</label>
                        <input type="text" id="kode" name="kode" inputmode="numeric" maxlength="6" placeholder="Masukkan 6 digit kode" required>
                        <div class="hint">
                            Kode berlaku 5 menit dan maksimal <?= (int)$max_attempts ?> kali percobaan.
                        </div>
                    </div>

                    <div class="row">
                        <button type="submit" class="btn btn-primary">Verifikasi Kode</button>
                        <a href="index.php" class="btn btn-secondary" style="text-align:center; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Batal</a>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($step === 3): ?>
                <form method="post">
                    <input type="hidden" name="action" value="reset_password">

                    <div class="field">
                        <label for="password_baru">Password Baru</label>
                        <input type="password" id="password_baru" name="password_baru" placeholder="Minimal 8 karakter" required>
                    </div>

                    <div class="field">
                        <label for="konfirmasi_password">Konfirmasi Password</label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
                </form>
            <?php endif; ?>

            <div class="footer-link">
                <a href="index.php">Kembali ke login</a>
            </div>

            <div class="small-note">
                Password disimpan memakai <code>password_hash()</code> dan data diambil dari tabel <b>admintb</b>.
            </div>
        </section>
    </div>
</body>
</html>