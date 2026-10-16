<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$user_id = $_SESSION['user_id'];
$errors  = [];
$success = '';

// ============ PROSES FORM ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- UPDATE PROFIL (nama, username, no_hp) ----------
    if ($action === 'update_profil') {
        $nama     = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $no_hp    = trim($_POST['no_hp'] ?? '');

        if ($nama === '' || $username === '') {
            $errors[] = 'Nama dan username wajib diisi.';
        }

        // Cek username sudah dipakai orang lain?
        if (empty($errors)) {
            $cek = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $cek->bind_param('si', $username, $user_id);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) {
                $errors[] = 'Username sudah dipakai.';
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, username=?, no_hp=? WHERE id=?");
            $stmt->bind_param('sssi', $nama, $username, $no_hp, $user_id);
            $stmt->execute();

            $_SESSION['nama']     = $nama;
            $_SESSION['username'] = $username;
            $success = 'Profil berhasil diperbarui.';
        }
    }

    // ---------- GANTI PASSWORD ----------
    if ($action === 'ganti_password') {
        $lama  = $_POST['password_lama'] ?? '';
        $baru  = $_POST['password_baru'] ?? '';
        $ulang = $_POST['password_ulang'] ?? '';

        if ($lama === '' || $baru === '' || $ulang === '') {
            $errors[] = 'Semua field password wajib diisi.';
        }
        if (strlen($baru) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        }
        if ($baru !== $ulang) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }

        // Cek password lama
        if (empty($errors)) {
            $row = $conn->query("SELECT password FROM users WHERE id=$user_id")->fetch_assoc();
            if (!$row || !password_verify($lama, $row['password'])) {
                $errors[] = 'Password lama salah.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $user_id);
            $stmt->execute();
            $success = 'Password berhasil diganti.';
        }
    }
}

// ============ AMBIL DATA USER ============
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
if (!$user) { session_destroy(); redirect(BASE_URL . '/pages/login.php'); }

// Statistik aktivitas
$totalVerif = $conn->query("SELECT COUNT(*) c FROM booking WHERE verified_by=$user_id")->fetch_assoc()['c'];
$totalLogin = '-' ; // kalau ada tabel log, bisa diisi

ob_start();
?>

<div class="welcome">
  <h1>Profile Saya</h1>
  <p>Kelola informasi akun dan keamanan password Anda</p>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<?php if ($success): ?>
  <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;" class="profile-grid">

  <!-- ============ KARTU KIRI: INFO AKUN ============ -->
  <div>
    <div class="chart-card" style="text-align:center;">
      <div style="width:100px;height:100px;border-radius:50%;margin:0 auto 16px;
                  background:linear-gradient(135deg,#166534,#14532d);color:#facc15;
                  display:flex;align-items:center;justify-content:center;
                  font-size:2.4rem;font-weight:800;box-shadow:0 6px 20px rgba(22,101,52,.25);">
        <?= strtoupper(substr($user['nama_lengkap'],0,1)) ?>
      </div>
      <h3 style="margin:0 0 4px 0;"><?= e($user['nama_lengkap']) ?></h3>
      <p style="color:#64748b;margin:0 0 16px 0;">@<?= e($user['username']) ?></p>

      <span class="badge badge-success" style="font-size:.85rem;padding:6px 14px;">
        🛡️ Administrator
      </span>

      <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e2e8f0;text-align:left;">
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
          <span style="color:#64748b;font-size:.88rem;">No. HP</span>
          <strong style="font-size:.88rem;"><?= e($user['no_hp'] ?: '—') ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
          <span style="color:#64748b;font-size:.88rem;">Total Verifikasi</span>
          <strong style="font-size:.88rem;color:#166534;"><?= $totalVerif ?> booking</strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:#64748b;font-size:.88rem;">Bergabung</span>
          <strong style="font-size:.88rem;"><?= date('d M Y', strtotime($user['created_at'])) ?></strong>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ KARTU KANAN: FORM ============ -->
  <div>

    <!-- Form Update Profil -->
    <div class="chart-card" style="margin-bottom:20px;">
      <h3 style="margin-top:0;">✏️ Edit Profil</h3>
      <form method="POST">
        <input type="hidden" name="action" value="update_profil">
        <div class="form-group">
          <label>Nama Lengkap *</label>
          <input type="text" name="nama_lengkap" class="form-control" required
                 value="<?= e($user['nama_lengkap']) ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" class="form-control" required
                   value="<?= e($user['username']) ?>">
          </div>
          <div class="form-group">
            <label>No. HP</label>
            <input type="text" name="no_hp" class="form-control"
                   value="<?= e($user['no_hp'] ?? '') ?>" placeholder="cth: 08123456789">
          </div>
        </div>
        <button class="btn btn-primary">💾 Simpan Profil</button>
      </form>
    </div>

    <!-- Form Ganti Password -->
    <div class="chart-card">
      <h3 style="margin-top:0;">🔒 Ganti Password</h3>
      <p style="color:#64748b;font-size:.88rem;margin-bottom:16px;">
        Gunakan password yang kuat minimal 6 karakter. Kombinasikan huruf, angka, dan simbol.
      </p>
      <form method="POST">
        <input type="hidden" name="action" value="ganti_password">
        <div class="form-group">
          <label>Password Lama *</label>
          <input type="password" name="password_lama" class="form-control" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group">
            <label>Password Baru *</label>
            <input type="password" name="password_baru" class="form-control" required minlength="6">
          </div>
          <div class="form-group">
            <label>Ulangi Password Baru *</label>
            <input type="password" name="password_ulang" class="form-control" required minlength="6">
          </div>
        </div>
        <button class="btn btn-primary">🔑 Ganti Password</button>
      </form>
    </div>

  </div>
</div>

<style>
@media (max-width: 900px) {
  .profile-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Profile Saya';
$pageTitle = 'Profile Saya';
$activeMenu = 'profile';
require_once __DIR__ . '/../../includes/dashboard_layout.php';