<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username']); $p = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND aktif=1");
    $stmt->bind_param('s', $u); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($p, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        redirect(BASE_URL . ($user['role']==='admin' ? '/pages/admin/dashboard.php' : '/pages/petugas/dashboard.php'));
    } else $err = 'Username atau password salah.';
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="login-wrap">
    <h2>Login Petugas</h2>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group"><label>Username</label><input name="username" class="form-control" required></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary btn-block">Masuk</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>