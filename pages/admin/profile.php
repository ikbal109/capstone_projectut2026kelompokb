<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$user_id = $_SESSION['user_id'];
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ... (Biarkan blok update_profil, ganti_password, dan update_rekening tetap sama seperti sebelumnya) ...
    // SALIN BLOK INI DARI FILE LAMA ANDA JIKA ANDA MEMBUATNYA MANUAL SEBELUMNYA
    // TAPI KARENA SAYA BERI FULL KODE, SAYA TULIS ULANG SEMUANYA DI BAWAH INI.
    
    // --- UPDATE PROFIL ---
    if ($action === 'update_profil') {
        $nama     = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $no_hp    = trim($_POST['no_hp'] ?? '');
        if ($nama === '' || $username === '') $errors[] = 'Nama dan username wajib diisi.';
        if (empty($errors)) {
            $cek = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $cek->bind_param('si', $username, $user_id); $cek->execute();
            if ($cek->get_result()->num_rows > 0) $errors[] = 'Username sudah dipakai.';
        }
        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, username=?, no_hp=? WHERE id=?");
            $stmt->bind_param('sssi', $nama, $username, $no_hp, $user_id); $stmt->execute();
            $_SESSION['nama'] = $nama; $_SESSION['username'] = $username;
            $success = 'Profil berhasil diperbarui.';
        }
    }

    // --- GANTI PASSWORD ---
    if ($action === 'ganti_password') {
        $lama  = $_POST['password_lama'] ?? '';
        $baru  = $_POST['password_baru'] ?? '';
        $ulang = $_POST['password_ulang'] ?? '';
        if ($lama === '' || $baru === '' || $ulang === '') $errors[] = 'Semua field password wajib diisi.';
        if (strlen($baru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        if ($baru !== $ulang)  $errors[] = 'Konfirmasi password tidak cocok.';
        if (empty($errors)) {
            $row = $conn->query("SELECT password FROM users WHERE id=$user_id")->fetch_assoc();
            if (!$row || !password_verify($lama, $row['password'])) $errors[] = 'Password lama salah.';
        }
        if (empty($errors)) {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $user_id); $stmt->execute();
            $success = 'Password berhasil diganti.';
        }
    }

    // --- UPDATE REKENING ---
    if ($action === 'update_rekening') {
        $bank    = trim($_POST['nama_bank'] ?? '');
        $rek     = trim($_POST['nomor_rekening'] ?? '');
        $pemilik = trim($_POST['nama_pemilik_rekening'] ?? '');
        if ($bank === '' || $rek === '' || $pemilik === '') $errors[] = 'Nama bank, nomor rekening, dan pemilik wajib diisi.';
        if (empty($errors)) {
            setPengaturan($conn, 'nama_bank', $bank);
            setPengaturan($conn, 'nomor_rekening', $rek);
            setPengaturan($conn, 'nama_pemilik_rekening', $pemilik);
            $success = 'Pengaturan rekening berhasil disimpan.';
        }
    }

    // --- UPLOAD QRIS ---
    if ($action === 'update_qris') {
        if (!empty($_FILES['qris_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) {
                $errors[] = 'Format gambar QRIS harus JPG/PNG.';
            } else {
                $filename = 'qris-' . time() . '.' . $ext;
                $dir = __DIR__ . '/../../assets/img/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $dir . $filename)) {
                    setPengaturan($conn, 'qris_image', $filename);
                    $success = 'Gambar QRIS berhasil diupdate.';
                } else {
                    $errors[] = 'Gagal mengunggah gambar.';
                }
            }
        } else {
            $errors[] = 'Pilih gambar QRIS terlebih dahulu.';
        }
    }
}

$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
if (!$user) { session_destroy(); redirect(BASE_URL . '/pages/login.php'); }

$totalVerif = $conn->query("SELECT COUNT(*) c FROM booking WHERE verified_by=$user_id")->fetch_assoc()['c'];
$namaBank     = getPengaturan($conn, 'nama_bank', 'Bank Kalsel');
$noRekening   = getPengaturan($conn, 'nomor_rekening', '');
$pemilikRek   = getPengaturan($conn, 'nama_pemilik_rekening', '');
$qrisImage    = getPengaturan($conn, 'qris_image', '');

ob_start();
?>

<div class="welcome">
  <h1>Profile Saya</h1>
  <p>Kelola informasi akun dan keamanan password Anda</p>
</div>

<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;" class="profile-grid">
  <div>
    <div class="chart-card" style="text-align:center;">
      <div style="width:100px;height:100px;border-radius:50%;margin:0 auto 16px;background:linear-gradient(135deg,#166534,#14532d);color:#facc15;display:flex;align-items:center;justify-content:center;font-size:2.4rem;font-weight:800;">
        <?= strtoupper(substr($user['nama_lengkap'],0,1)) ?>
      </div>
      <h3 style="margin:0 0 4px 0;"><?= e($user['nama_lengkap']) ?></h3>
      <p style="color:#64748b;margin:0 0 16px 0;">@<?= e($user['username']) ?></p>
      <span class="badge badge-success" style="font-size:.85rem;padding:6px 14px;">🛡️ Administrator</span>
      <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e2e8f0;text-align:left;">
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
          <span style="color:#64748b;font-size:.88rem;">No. HP</span><strong style="font-size:.88rem;"><?= e($user['no_hp'] ?: '—') ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:#64748b;font-size:.88rem;">Bergabung</span><strong style="font-size:.88rem;"><?= date('d M Y', strtotime($user['created_at'])) ?></strong>
        </div>
      </div>
    </div>
  </div>

  <div>
    <!-- Edit Profil -->
    <div class="chart-card" style="margin-bottom:20px;">
      <h3 style="margin-top:0;">✏️ Edit Profil</h3>
      <form method="POST">
        <input type="hidden" name="action" value="update_profil">
        <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="nama_lengkap" class="form-control" required value="<?= e($user['nama_lengkap']) ?>"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group"><label>Username *</label><input type="text" name="username" class="form-control" required value="<?= e($user['username']) ?>"></div>
          <div class="form-group"><label>No. HP</label><input type="text" name="no_hp" class="form-control" value="<?= e($user['no_hp'] ?? '') ?>"></div>
        </div>
        <button class="btn btn-primary">💾 Simpan Profil</button>
      </form>
    </div>

    <!-- Ganti Password -->
    <div class="chart-card" style="margin-bottom:20px;">
      <h3 style="margin-top:0;">🔒 Ganti Password</h3>
      <form method="POST">
        <input type="hidden" name="action" value="ganti_password">
        <div class="form-group"><label>Password Lama *</label><input type="password" name="password_lama" class="form-control" required></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group"><label>Password Baru *</label><input type="password" name="password_baru" class="form-control" required minlength="6"></div>
          <div class="form-group"><label>Ulangi Password *</label><input type="password" name="password_ulang" class="form-control" required minlength="6"></div>
        </div>
        <button class="btn btn-primary">🔑 Ganti Password</button>
      </form>
    </div>

    <!-- Rekening Bank -->
    <div class="chart-card" style="margin-bottom:20px;">
      <h3 style="margin-top:0;">🏦 Pengaturan Rekening (Transfer Bank)</h3>
      <form method="POST">
        <input type="hidden" name="action" value="update_rekening">
        <div class="form-group"><label>Nama Bank *</label><input type="text" name="nama_bank" class="form-control" required value="<?= e($namaBank) ?>"></div>
        <div class="form-group"><label>Nomor Rekening *</label><input type="text" name="nomor_rekening" class="form-control" required value="<?= e($noRekening) ?>"></div>
        <div class="form-group"><label>Nama Pemilik *</label><input type="text" name="nama_pemilik_rekening" class="form-control" required value="<?= e($pemilikRek) ?>"></div>
        <button class="btn btn-primary">💾 Simpan Rekening</button>
      </form>
    </div>

    <!-- Upload QRIS (BARU) -->
    <div class="chart-card">
      <h3 style="margin-top:0;">📱 Pengaturan Gambar QRIS</h3>
      <p style="color:#64748b;font-size:.88rem;margin-bottom:16px;">Unggah gambar barcode QRIS yang akan ditampilkan ke penyewa.</p>
      
      <?php if ($qrisImage): ?>
        <div style="margin-bottom:16px;">
          <img src="<?= BASE_URL ?>/assets/img/<?= e($qrisImage) ?>" style="max-width:200px; border-radius:8px; border:1px solid #e2e8f0;">
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_qris">
        <div class="form-group">
          <label>Upload Gambar QRIS (JPG/PNG) *</label>
          <input type="file" name="qris_image" class="form-control" accept="image/*" required>
        </div>
        <button class="btn btn-primary">📤 Upload QRIS</button>
      </form>
    </div>
  </div>
</div>

<style>@media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr !important; } }</style>

<?php
$content = ob_get_clean();
$title = 'Profile Saya';
$pageTitle = 'Profile Saya';
$activeMenu = 'profile';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
?>