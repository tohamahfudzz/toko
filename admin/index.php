<?php
session_start();

// Jika ada sistem login admin, pertahankan pengaman ini.
if (empty($_SESSION['user_id'])) {
    header('Location: ../login/index.php');
    exit;
}

include "koneksi.php";

$errors = [];
$success = null;

function blob_to_webp_data_uri($blob): string {
    if (empty($blob)) {
        return '';
    }
    return 'data:image/webp;base64,' . base64_encode($blob);
}

function normalize_category_value(string $value): string {
    return trim($value);
}

function get_category_options(mysqli $conn): array {
    $defaults = [
        'ram',
        'ssd',
        'mouse',
        'monitor',
        'keyboard',
        'smartphone',
        'speaker',
        'notebook',
        'smartband',
        'motherboard',
        'motherboar',
        'processor',
        'psu',
        'aksesoris',
    ];

    $dbValues = [];
    $stmt = $conn->prepare("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori <> '' ORDER BY kategori ASC");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $kategori = trim((string)($row['kategori'] ?? ''));
                if ($kategori !== '') {
                    $dbValues[] = $kategori;
                }
            }
        }
        $stmt->close();
    }

    $merged = array_merge($defaults, $dbValues);
    $merged = array_values(array_unique(array_filter(array_map('trim', $merged), static function ($v) {
        return $v !== '';
    })));

    return $merged;
}

/**
 * Validasi file WEBP.
 * $required = true untuk upload baru.
 * $required = false untuk edit (opsional).
 */
function validate_webp_upload(?array $file, array &$errors, bool $required = true): ?string {
    if (!is_array($file)) {
        if ($required) {
            $errors[] = "File gambar tidak ditemukan.";
        }
        return null;
    }

    $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            $errors[] = "Gambar produk wajib dipilih.";
        }
        return null;
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        $errors[] = "Terjadi kesalahan saat upload gambar (kode: {$uploadError}).";
        return null;
    }

    $maxSize = 2 * 1024 * 1024; // 2MB
    if (($file['size'] ?? 0) > $maxSize) {
        $errors[] = "Ukuran file maksimal 2MB.";
        return null;
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name'] ?? '');
            finfo_close($finfo);
        }
    }

    if ($ext !== 'webp' || $mime !== 'image/webp') {
        $errors[] = "Tipe file tidak diperbolehkan. Hanya WEBP yang diizinkan.";
        return null;
    }

    $binary = file_get_contents($file['tmp_name']);
    if ($binary === false) {
        $errors[] = "Gagal membaca file gambar.";
        return null;
    }

    return $binary;
}

$self = $_SERVER['PHP_SELF'];
$kategoriOptions = get_category_options($conn);

// ---------- PROSES TAMBAH PRODUK ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    $nama = trim($_POST['nama'] ?? '');
    $harga = $_POST['harga'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori = normalize_category_value((string)($_POST['kategori'] ?? ''));

    if ($nama === '') {
        $errors[] = "Nama produk wajib diisi.";
    }
    if (!is_numeric($harga) || (int)$harga <= 0) {
        $errors[] = "Harga tidak valid.";
    }
    if ($deskripsi === '') {
        $errors[] = "Deskripsi wajib diisi.";
    }
    if ($kategori === '') {
        $errors[] = "Kategori wajib dipilih.";
    }

    $imageData = null;
    if (empty($errors)) {
        $imageData = validate_webp_upload($_FILES['gambar'] ?? null, $errors, true);
    }

    if (empty($errors)) {
        $harga = (int)$harga;

        $stmt = $conn->prepare("INSERT INTO produk (nama, harga, deskripsi, kategori, gambar) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Prepare insert gagal: " . $conn->error;
        } else {
            $gambarPlaceholder = null;
            $stmt->bind_param("sissb", $nama, $harga, $deskripsi, $kategori, $gambarPlaceholder);
            $stmt->send_long_data(4, $imageData);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: " . $self . "?uploaded=1");
                exit;
            }

            $errors[] = "Gagal menyimpan produk: " . $stmt->error;
            $stmt->close();
        }
    }
}

// ---------- PROSES HAPUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        $errors[] = "ID produk tidak valid.";
    } else {
        $stmt = $conn->prepare("DELETE FROM produk WHERE id = ?");
        if (!$stmt) {
            $errors[] = "Prepare delete gagal: " . $conn->error;
        } else {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: " . $self . "?deleted=1");
                exit;
            }
            $errors[] = "Gagal menghapus produk: " . $stmt->error;
            $stmt->close();
        }
    }
}

// ---------- AMBIL DATA UNTUK EDIT ----------
$editItem = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    if ($edit_id > 0) {
        $q = $conn->prepare("SELECT id, nama, harga, deskripsi, kategori, gambar FROM produk WHERE id = ?");
        if ($q) {
            $q->bind_param("i", $edit_id);
            $q->execute();
            $res = $q->get_result();
            $editItem = $res ? $res->fetch_assoc() : null;
            $q->close();
        } else {
            $errors[] = "Prepare select edit gagal: " . $conn->error;
        }
    } else {
        $errors[] = "ID edit tidak valid.";
    }
}

// ---------- PROSES UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $harga = $_POST['harga'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori = normalize_category_value((string)($_POST['kategori'] ?? ''));

    if ($id <= 0) {
        $errors[] = "ID produk tidak valid.";
    }
    if ($nama === '') {
        $errors[] = "Nama produk wajib diisi.";
    }
    if (!is_numeric($harga) || (int)$harga <= 0) {
        $errors[] = "Harga tidak valid.";
    }
    if ($deskripsi === '') {
        $errors[] = "Deskripsi wajib diisi.";
    }
    if ($kategori === '') {
        $errors[] = "Kategori wajib dipilih.";
    }

    $hasNewImage = false;
    $newImageData = null;

    if (empty($errors)) {
        $file = $_FILES['gambar_edit'] ?? null;
        if (is_array($file) && (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
            $newImageData = validate_webp_upload($file, $errors, false);
            if (empty($errors) && $newImageData !== null) {
                $hasNewImage = true;
            }
        }
    }

    if (empty($errors)) {
        $harga = (int)$harga;

        if ($hasNewImage) {
            $stmt = $conn->prepare("UPDATE produk SET nama = ?, harga = ?, deskripsi = ?, kategori = ?, gambar = ? WHERE id = ?");
            if (!$stmt) {
                $errors[] = "Prepare update gagal: " . $conn->error;
            } else {
                $gambarPlaceholder = null;
                $stmt->bind_param("sissbi", $nama, $harga, $deskripsi, $kategori, $gambarPlaceholder, $id);
                $stmt->send_long_data(4, $newImageData);

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: " . $self . "?updated=1");
                    exit;
                }

                $errors[] = "Gagal memperbarui produk: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("UPDATE produk SET nama = ?, harga = ?, deskripsi = ?, kategori = ? WHERE id = ?");
            if (!$stmt) {
                $errors[] = "Prepare update gagal: " . $conn->error;
            } else {
                $stmt->bind_param("sissi", $nama, $harga, $deskripsi, $kategori, $id);
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: " . $self . "?updated=1");
                    exit;
                }

                $errors[] = "Gagal memperbarui produk: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// ---------- PENCARIAN ----------
$searchQuery = trim($_GET['search'] ?? '');
$searchResults = [];

if ($searchQuery !== '') {
    $like = '%' . $searchQuery . '%';
    $q = $conn->prepare("SELECT id, nama, harga, deskripsi, kategori, gambar FROM produk WHERE nama LIKE ? ORDER BY id DESC");
    if ($q) {
        $q->bind_param("s", $like);
        $q->execute();
        $res = $q->get_result();
        while ($row = $res->fetch_assoc()) {
            $searchResults[] = $row;
        }
        $q->close();
    } else {
        $errors[] = "Prepare search gagal: " . $conn->error;
    }
}

// ---------- DAFTAR PRODUK ----------
$products = [];
$listStmt = $conn->prepare("SELECT id, nama, harga, deskripsi, kategori, gambar FROM produk ORDER BY id DESC");
if ($listStmt) {
    $listStmt->execute();
    $res = $listStmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $listStmt->close();
} else {
    $errors[] = "Prepare list gagal: " . $conn->error;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Upload Produk</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h2>Form Upload Produk</h2>

    <?php if (!empty($_GET['uploaded'])): ?>
      <p class="success">✅ Produk berhasil diupload!</p>
    <?php endif; ?>

    <?php if (!empty($_GET['updated'])): ?>
      <p class="success">✅ Produk berhasil diperbarui!</p>
    <?php endif; ?>

    <?php if (!empty($_GET['deleted'])): ?>
      <p class="success">✅ Produk berhasil dihapus!</p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="errors">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" id="uploadForm" onsubmit="return validateForm()">
      <input type="hidden" name="action" value="upload">

      <label for="nama">Nama Produk:</label>
      <input type="text" id="nama" name="nama" required placeholder="Contoh: Asus ZenBook">

      <label for="harga">Harga:</label>
      <input type="number" id="harga" name="harga" required min="1" placeholder="Masukkan harga (angka saja)">

      <label for="deskripsi">Deskripsi:</label>
      <textarea id="deskripsi" name="deskripsi" required placeholder="Deskripsi singkat produk"></textarea>

      <label for="kategori">Kategori:</label>
      <input type="text" id="kategori" name="kategori" list="kategoriOptions" required placeholder="Pilih atau ketik kategori">
      <datalist id="kategoriOptions">
        <?php foreach ($kategoriOptions as $opt): ?>
          <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"></option>
        <?php endforeach; ?>
      </datalist>

      <label for="gambar">Upload Gambar (WEBP saja, max 2MB):</label>
      <input type="file" id="gambar" name="gambar" accept="image/webp,.webp" required>
      <div id="preview" style="margin-top:8px;"></div>

      <button type="submit">🟢 Upload Produk</button>
    </form>

    <hr>

    <div style="text-align:center;color:#666;font-size:14px;">
      <div>🔎 <strong>Ikon</strong> menggunakan emoji</div>
      <div style="margin-top:6px;">📁 Gambar disimpan langsung ke kolom <code>gambar</code> (MEDIUMBLOB)</div>
      <div style="margin-top:6px;">ℹ️ Hanya file WEBP yang diterima</div>
    </div>
  </div>

  <hr>

  <h3>Cari Produk</h3>
  <form method="GET" action="">
    <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari berdasarkan nama produk" required>
    <button type="submit">🔎 Cari</button>
  </form>

  <?php if (!empty($searchResults)): ?>
    <table>
      <thead>
        <tr><th>ID</th><th>Nama</th><th>Kategori</th><th>Deskripsi</th><th>Harga</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($searchResults as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['kategori'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($r['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo number_format((int)$r['harga'], 0, ',', '.'); ?></td>
            <td>
              <a href="?edit_id=<?php echo (int)$r['id']; ?>">✏️ Edit</a>
              <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Hapus produk ini? Tindakan tidak dapat dibatalkan.');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                <button type="submit">🗑️ Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php elseif ($searchQuery !== ''): ?>
    <p>Tidak ada produk ditemukan untuk <strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
  <?php endif; ?>

  <?php if ($editItem): ?>
    <hr>
    <h3>Edit Produk ID <?php echo (int)$editItem['id']; ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?php echo (int)$editItem['id']; ?>">

      <label>Nama</label>
      <input type="text" name="nama" required value="<?php echo htmlspecialchars($editItem['nama'], ENT_QUOTES, 'UTF-8'); ?>">

      <label>Harga</label>
      <input type="number" name="harga" required min="1" value="<?php echo (int)$editItem['harga']; ?>">

      <label>Deskripsi</label>
      <textarea name="deskripsi" required><?php echo htmlspecialchars($editItem['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></textarea>

      <label>Kategori</label>
      <input type="text" name="kategori" list="kategoriOptions" required value="<?php echo htmlspecialchars($editItem['kategori'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Pilih atau ketik kategori">

      <div>
        <label>Gambar saat ini</label><br>
        <?php if (!empty($editItem['gambar'])): ?>
          <img class="thumb" src="<?php echo blob_to_webp_data_uri($editItem['gambar']); ?>" alt="gambar produk">
        <?php else: ?>
          <div class="muted">Tidak ada gambar</div>
        <?php endif; ?>
      </div>

      <label>Ganti Gambar (WEBP saja, max 2MB)</label>
      <input type="file" name="gambar_edit" accept="image/webp,.webp">

      <div class="actions">
        <button type="submit">💾 Simpan Perubahan</button>
        <a class="btn" href="<?php echo $self; ?>">Batal</a>
      </div>
    </form>
  <?php endif; ?>

  <hr style="margin:24px 0;">

  <h3>Daftar Produk</h3>
  <?php if (!empty($searchQuery)): ?>
    <p class="muted">Hasil pencarian untuk: <strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong></p>
  <?php endif; ?>

  <?php if (!empty($searchQuery) && empty($searchResults)): ?>
    <p>Tidak ada produk ditemukan.</p>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Gambar</th>
        <th>Nama</th>
        <th>Kategori</th>
        <th>Harga</th>
        <th>Deskripsi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $rowsToShow = !empty($searchQuery) ? $searchResults : $products;
      if (!empty($rowsToShow)):
          foreach ($rowsToShow as $r):
              $img = !empty($r['gambar']) ? blob_to_webp_data_uri($r['gambar']) : '';
      ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td>
            <?php if ($img): ?>
              <img src="<?php echo $img; ?>" alt="gambar" style="width:90px;height:90px;object-fit:cover;border-radius:8px;">
            <?php else: ?>
              <div class="muted">Tidak ada</div>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($r['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($r['kategori'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>Rp <?php echo number_format((int)$r['harga'], 0, ',', '.'); ?></td>
          <td><?php echo htmlspecialchars($r['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <a href="?edit_id=<?php echo (int)$r['id']; ?>">✏️ Edit</a>
            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Hapus produk ini?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit">🗑️ Hapus</button>
            </form>
          </td>
        </tr>
      <?php
          endforeach;
      else:
      ?>
        <tr><td colspan="7" style="text-align:center;">Belum ada produk.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <script src="script.js"></script>
</body>
</html>
