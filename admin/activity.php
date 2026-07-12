<?php
// file: activity_list.php
session_start();

// Pastikan user sudah login (opsional)
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

// Sesuaikan path jika koneksi.php berada di folder lain
require_once __DIR__ . '/koneksi.php';

// Ambil data activity
$sql = "SELECT id, aktor, aksi, waktu FROM historytb ORDER BY waktu DESC";
$result = $conn->query($sql);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar Activity</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; color:#222; padding:20px; }
    .container { max-width:1000px; margin:0 auto; background:#fff; padding:18px; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    table { width:100%; border-collapse:collapse; margin-top:12px; }
    th, td { padding:10px 12px; border:1px solid #e6e6e6; text-align:left; vertical-align:middle; }
    th { background:#fafafa; font-weight:600; }
    tr:nth-child(even) td { background:#fbfbfb; }
    .meta { color:#666; font-size:13px; margin-bottom:8px; }
    .actions { text-align:right; margin-bottom:8px; }
    a.button { display:inline-block; padding:8px 12px; background:#1976d2; color:#fff; text-decoration:none; border-radius:4px; }
    .empty { padding:18px; text-align:center; color:#666; }
    .nowrap { white-space:nowrap; }
  </style>
</head>
<body>
  <div class="container">
    <div class="actions">
      <a class="button" href="index.php">← Kembali ke Dashboard</a>
    </div>

    <h3>Daftar Activity</h3>
    <div class="meta">Menampilkan log aktivitas terbaru. User: <strong><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></strong></div>

    <?php if ($result && $result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="width:180px;">Aktor</th>
            <th>Aksi</th>
            <th style="width:180px;">Waktu</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="nowrap"><?php echo (int)$row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['aktor'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($row['aksi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="nowrap"><?php echo htmlspecialchars($row['waktu'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty">Belum ada activity yang tercatat.</div>
    <?php endif; ?>

  </div>
</body>
</html>
<?php
// Bebaskan resource
if ($result) $result->free();
$conn->close();
?>
