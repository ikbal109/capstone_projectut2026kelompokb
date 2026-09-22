<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$sarana_id = (int)($_GET['sarana_id'] ?? 0);
$sarana = $conn->query("SELECT * FROM sarana WHERE id=$sarana_id")->fetch_assoc();
if (!$sarana) die('Sarana tidak ditemukan');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama_penyewa']);
    $no_hp     = trim($_POST['no_hp']);
    $email     = trim($_POST['email']);
    $instansi  = trim($_POST['instansi']);
    $tanggal   = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    if (!$nama || !$no_hp || !$tanggal || !$jam_mulai || !$jam_selesai) $errors[] = 'Semua field wajib diisi.';
    if (strtotime($tanggal) < strtotime(date('Y-m-d'))) $errors[] = 'Tanggal tidak boleh di masa lalu.';
    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) $errors[] = 'Jam selesai harus lebih dari jam mulai.';

    // cek bentrok
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM booking WHERE sarana_id=? AND tanggal=? AND status IN ('pending','terverifikasi') AND (jam_mulai < ? AND jam_selesai > ?)");
        $stmt->bind_param('isss', $sarana_id, $tanggal, $jam_selesai, $jam_mulai);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = 'Jadwal bentrok dengan booking lain.';
    }

    if (empty($errors)) {
        $h = hitungTotal($conn, $sarana_id, $tanggal, $jam_mulai, $jam_selesai);
        $kode = generateKodeBooking();
        $kode_gambar = generateKodeGambar($kode);

        $diskon_id = $h['diskon']['id'] ?? null;

        $stmt = $conn->prepare("INSERT INTO booking 
          (kode_booking,sarana_id,nama_penyewa,no_hp,email,instansi,tanggal,jam_mulai,jam_selesai,durasi_jam,harga_per_jam,subtotal,diskon_id,diskon_persen,total_bayar,kode_gambar) 
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sisssssssidddids',
            $kode, $sarana_id, $nama, $no_hp, $email, $instansi, $tanggal,
            $jam_mulai, $jam_selesai, $h['durasi'], $h['harga_per_jam'],
            $h['subtotal'], $diskon_id, $h['persen'], $h['total'], $kode_gambar
        );
        $stmt->execute();
        $booking_id = $conn->insert_id;
        redirect(BASE_URL . '/pages/booking_sukses.php?id=' . $booking_id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page" style="max-width:700px;">
  <h2>Form Booking - <?= e($sarana['nama']) ?></h2>
  <p style="color:#64748b;margin-bottom:20px;">Harga: <strong>Rp <?= number_format($sarana['harga_per_jam'],0,',','.') ?>/jam</strong></p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="form-group"><label>Nama Penyewa *</label><input type="text" name="nama_penyewa" class="form-control" required value="<?= e($_POST['nama_penyewa']??'') ?>"></div>
    <div class="form-group"><label>No. HP *</label><input type="text" name="no_hp" class="form-control" required value="<?= e($_POST['no_hp']??'') ?>"></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>"></div>
    <div class="form-group"><label>Instansi/Asal</label><input type="text" name="instansi" class="form-control" value="<?= e($_POST['instansi']??'') ?>"></div>
    <div class="form-group"><label>Tanggal *</label><input type="date" name="tanggal" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= e($_POST['tanggal']??'') ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="form-group"><label>Jam Mulai *</label><input type="time" name="jam_mulai" class="form-control" required value="<?= e($_POST['jam_mulai']??'08:00') ?>"></div>
      <div class="form-group"><label>Jam Selesai *</label><input type="time" name="jam_selesai" class="form-control" required value="<?= e($_POST['jam_selesai']??'10:00') ?>"></div>
    </div>
    <button class="btn btn-primary btn-block">Booking Sekarang</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>