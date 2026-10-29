<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$msg = '';
$err = '';

// ============ PROSES FORM ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

        // ---------- HAPUS ----------
    if ($action === 'hapus') {
        $id = (int)$_POST['id'];
        
        // Cek apakah sarana ini sudah ada riwayat booking-nya
        $cek_booking = $conn->query("SELECT id FROM booking WHERE sarana_id = $id LIMIT 1");
        
        if ($cek_booking->num_rows > 0) {
            // Jika ada, tolak hapus
            flash('err', 'Gagal menghapus! Sarana ini sudah memiliki riwayat pemesanan. Silakan ubah status menjadi "Maintenance" jika ingin menonaktifkannya.');
        } else {
            // Jika belum ada booking, izinkan hapus
            $s = $conn->query("SELECT gambar, galeri FROM sarana WHERE id=$id")->fetch_assoc();
            if ($s) {
                hapusGambar($s['gambar']);
                foreach (getGaleri($s['galeri']) as $g) hapusGambar($g);
                $conn->query("DELETE FROM sarana WHERE id=$id");
                flash('msg', 'Sarana berhasil dihapus secara permanen.');
            }
        }
        redirect(BASE_URL . '/pages/admin/kelola_sarana.php');
    }

    // ---------- TAMBAH / EDIT ----------
    $id          = (int)($_POST['id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $jenis       = trim($_POST['jenis'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $kelengkapan = trim($_POST['kelengkapan'] ?? '');
    $harga       = (float)($_POST['harga_per_jam'] ?? 0);
    $status      = $_POST['status'] ?? 'tersedia';

    if ($nama === '' || $harga <= 0) {
        flash('err', 'Nama dan harga wajib diisi dengan benar.');
        redirect(BASE_URL . '/pages/admin/kelola_sarana.php' . ($id ? "?edit=$id" : ''));
    }

    // Upload foto utama
    $gambarBaru = uploadGambar($_FILES['gambar'] ?? [], 'sarana');

    // Upload galeri (multiple)
    $galeriBaru = [];
    if (!empty($_FILES['galeri']['name'][0])) {
        $count = count($_FILES['galeri']['name']);
        for ($i = 0; $i < $count; $i++) {
            $f = [
                'name'     => $_FILES['galeri']['name'][$i],
                'type'     => $_FILES['galeri']['type'][$i],
                'tmp_name' => $_FILES['galeri']['tmp_name'][$i],
                'error'    => $_FILES['galeri']['error'][$i],
                'size'     => $_FILES['galeri']['size'][$i],
            ];
            $up = uploadGambar($f, 'galeri');
            if ($up) $galeriBaru[] = $up;
        }
    }

    // ---------- TAMBAH BARU ----------
    if ($id === 0) {
        $slug = buatSlugUnik($conn, $nama);
        $galeriJson = !empty($galeriBaru) ? json_encode($galeriBaru) : null;

        $stmt = $conn->prepare("INSERT INTO sarana 
            (nama, slug, jenis, deskripsi, kelengkapan, harga_per_jam, gambar, galeri, status) 
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssdsss',
            $nama, $slug, $jenis, $deskripsi, $kelengkapan, $harga,
            $gambarBaru, $galeriJson, $status
        );
        $stmt->execute();
        flash('msg', 'Sarana baru berhasil ditambahkan.');
    } 
    // ---------- EDIT ----------
    else {
        $old = $conn->query("SELECT * FROM sarana WHERE id=$id")->fetch_assoc();
        if (!$old) redirect(BASE_URL . '/pages/admin/kelola_sarana.php');

        $slug = buatSlugUnik($conn, $nama, $id);

        $gambarFinal = $old['gambar'];
        if ($gambarBaru) {
            hapusGambar($old['gambar']);
            $gambarFinal = $gambarBaru;
        }

        $galeriLama = getGaleri($old['galeri']);
        $hapusGaleri = $_POST['hapus_galeri'] ?? [];
        foreach ($galeriLama as $g) {
            if (in_array($g, $hapusGaleri)) hapusGambar($g);
        }
        $galeriSisa = array_values(array_diff($galeriLama, $hapusGaleri));
        $galeriFinal = array_merge($galeriSisa, $galeriBaru);
        $galeriJson = !empty($galeriFinal) ? json_encode($galeriFinal) : null;

        $stmt = $conn->prepare("UPDATE sarana SET 
            nama=?, slug=?, jenis=?, deskripsi=?, kelengkapan=?, 
            harga_per_jam=?, gambar=?, galeri=?, status=? 
            WHERE id=?");
        $stmt->bind_param('sssssdsssi',
            $nama, $slug, $jenis, $deskripsi, $kelengkapan, $harga,
            $gambarFinal, $galeriJson, $status, $id
        );
        $stmt->execute();
        flash('msg', 'Sarana berhasil diperbarui.');
    }
    redirect(BASE_URL . '/pages/admin/kelola_sarana.php');
}

// ============ DATA UNTUK TAMPILAN ============
$edit = null;
if (!empty($_GET['edit'])) {
    $edit = $conn->query("SELECT * FROM sarana WHERE id=" . (int)$_GET['edit'])->fetch_assoc();
}

$list = $conn->query("SELECT * FROM sarana ORDER BY created_at DESC");

ob_start();
?>

<div class="welcome">
  <h1>Kelola Fasilitas</h1>
  <p>Kelola sarana olahraga, foto, dan informasi kelengkapan</p>
</div>

<?php if ($m = flash('msg')): ?>
  <div class="alert alert-success"><?= e($m) ?></div>
<?php endif; ?>
<?php if ($m = flash('err')): ?>
  <div class="alert alert-danger"><?= e($m) ?></div>
<?php endif; ?>

<!-- FORM TAMBAH/EDIT -->
<div class="chart-card" style="margin-bottom:20px;">
  <h3 style="margin-top:0;">
    <?= $edit ? '✏️ Edit Sarana: ' . e($edit['nama']) : '➕ Tambah Sarana Baru' ?>
  </h3>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'tambah' ?>">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group">
        <label>Nama Sarana *</label>
        <input type="text" name="nama" class="form-control" required
               value="<?= e($edit['nama'] ?? '') ?>" placeholder="cth: Lapangan Futsal A">
      </div>
      <div class="form-group">
        <label>Jenis *</label>
        <input type="text" name="jenis" class="form-control" required
               value="<?= e($edit['jenis'] ?? '') ?>" placeholder="cth: Futsal">
      </div>
    </div>

    <div class="form-group">
      <label>Deskripsi Singkat</label>
      <textarea name="deskripsi" class="form-control" rows="2"
                placeholder="Deskripsi singkat yang tampil di halaman utama"><?= e($edit['deskripsi'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>Kelengkapan / Fasilitas <small style="color:#64748b;">(satu per baris, akan tampil sebagai list)</small></label>
      <textarea name="kelengkapan" class="form-control" rows="6"
                placeholder="Rumput sintetis standar FIFA&#10;Lampu sorot LED&#10;Ruang ganti + shower&#10;Toilet bersih&#10;Parkir luas"><?= e($edit['kelengkapan'] ?? '') ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
      <div class="form-group">
        <label>Harga per Jam (Rp) *</label>
        <input type="number" name="harga_per_jam" class="form-control" required min="0" step="1000"
               value="<?= e($edit['harga_per_jam'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="tersedia"    <?= ($edit['status']??'')==='tersedia'?'selected':'' ?>>Tersedia</option>
          <option value="maintenance" <?= ($edit['status']??'')==='maintenance'?'selected':'' ?>>Maintenance</option>
        </select>
      </div>
      <div class="form-group">
        <label>Foto Utama <?= $edit ? '(biarkan kosong jika tidak ganti)' : '*' ?></label>
        <input type="file" name="gambar" class="form-control" accept="image/*">
      </div>
    </div>

    <?php if ($edit && !empty($edit['gambar'])): ?>
      <div style="margin-bottom:12px;">
        <small style="color:#64748b;">Foto saat ini:</small><br>
        <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($edit['gambar']) ?>"
             style="max-width:200px;border-radius:8px;margin-top:6px;">
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label>Galeri Foto Tambahan (bisa pilih beberapa sekaligus)</label>
      <input type="file" name="galeri[]" class="form-control" accept="image/*" multiple>
    </div>

    <?php if ($edit && ($gal = getGaleri($edit['galeri']))): ?>
      <div style="margin-bottom:16px;">
        <small style="color:#64748b;">Galeri saat ini (centang untuk hapus):</small>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
          <?php foreach ($gal as $g): ?>
            <label style="cursor:pointer;text-align:center;">
              <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($g) ?>"
                   style="width:100px;height:80px;object-fit:cover;border-radius:6px;border:2px solid #e2e8f0;">
              <div style="font-size:.8rem;margin-top:4px;">
                <input type="checkbox" name="hapus_galeri[]" value="<?= e($g) ?>"> Hapus
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <button class="btn btn-primary"><?= $edit ? '💾 Simpan Perubahan' : '➕ Tambah Sarana' ?></button>
    <?php if ($edit): ?>
      <a href="kelola_sarana.php" class="btn btn-danger">Batal</a>
    <?php endif; ?>
  </form>
</div>

<!-- TABEL DAFTAR SARANA -->
<div class="chart-card">
  <h3 style="margin-top:0;">
    📋 Daftar Sarana 
    <span style="font-weight:400;color:#64748b;font-size:.9rem;">(<?= $list->num_rows ?> data)</span>
  </h3>

  <?php if ($list->num_rows === 0): ?>
    <div class="donut-empty">Belum ada sarana. Tambahkan di atas.</div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table" style="box-shadow:none;border:1px solid #e2e8f0;font-size:.88rem;">
        <thead>
          <tr>
            <th>Foto</th>
            <th>Nama</th>
            <th>Jenis</th>
            <th>Harga/Jam</th>
            <th>Status</th>
            <th style="text-align:right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          // Reset pointer
          $list->data_seek(0);
          while ($s = $list->fetch_assoc()): 
          ?>
            <tr>
              <td>
                <?php if ($s['gambar']): ?>
                  <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($s['gambar']) ?>"
                       style="width:80px;height:60px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                  <span style="color:#94a3b8;font-size:.85rem;">—</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= e($s['nama']) ?></strong><br>
                <small style="color:#64748b;"><?= e($s['slug']) ?></small>
              </td>
              <td><span class="badge badge-info"><?= e($s['jenis']) ?></span></td>
              <td>Rp <?= number_format($s['harga_per_jam'],0,',','.') ?></td>
              <td>
                <span class="badge badge-<?= $s['status']==='tersedia'?'success':'warning' ?>">
                  <?= e($s['status']) ?>
                </span>
              </td>
              <td style="text-align:right;white-space:nowrap;">
                <a href="<?= BASE_URL ?>/pages/detail_sarana.php?slug=<?= urlencode($s['slug']) ?>" target="_blank"
                   class="btn btn-sm btn-accent">👁️</a>
                <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-primary">✏️</a>
                <form method="POST" style="display:inline;"
                      onsubmit="return confirm('Yakin hapus sarana <?= e($s['nama']) ?>? Semua foto akan dihapus juga.');">
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button class="btn btn-sm btn-danger">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$title = 'Kelola Fasilitas';
$pageTitle = 'Kelola Fasilitas';
$activeMenu = 'fasilitas';
require_once __DIR__ . '/../../includes/dashboard_layout.php';