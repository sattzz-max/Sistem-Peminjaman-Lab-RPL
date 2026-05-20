<?php
session_start();
require_once '../config/koneksi.php';
if (isset($_SESSION['user_id'])) redirect(isAdmin() ? '../pages/admin/dashboard.php' : '../pages/user/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $sql    = "SELECT * FROM user WHERE username='$username' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        if ($result === false) {
            $error = 'Database error: ' . mysqli_error($conn) . '. Import database.sql terlebih dahulu!';
        } else {
            $user  = mysqli_fetch_assoc($result);
            $valid = false;
            if ($user) {
                if (password_verify($password, $user['password'])) $valid = true;
                elseif ($user['password'] === $password) $valid = true;
                elseif ($password === 'password' && $user['password'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') $valid = true;
            }
            if ($valid) {
                $_SESSION['user_id']  = $user['id_user'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
                setFlash('success', 'Selamat datang, ' . $user['nama'] . '! 👋');
                redirect($user['role'] === 'admin' ? '../pages/admin/dashboard.php' : '../pages/user/dashboard.php');
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Lab RPL</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔬</text></svg>">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-bg-pattern"></div>
  <div class="login-container">
    <div class="login-card">
      <div class="login-logo">
        <div style="width:90px;height:90px;border-radius:50%;overflow:hidden;margin:0 auto 16px;border:3px solid rgba(255,255,255,.15);box-shadow:0 8px 28px rgba(0,0,0,.4),0 0 0 5px rgba(37,99,235,.35);background:#fff;">
          <img src="../assets/img/logo-sekolah.png" alt="Logo" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
        <h1>Lab RPL</h1>
        <p>Sistem Peminjaman Alat Laboratorium</p>
        <p style="color:#475569;font-size:11.5px;margin-top:2px;">SMK Negeri 1 Sukorejo &mdash; Kab. Pasuruan</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger" style="background:rgba(239,68,68,.12);color:#fca5a5;border-left-color:#ef4444;">
        ❌ <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form class="login-form" method="POST">
        <div class="form-group">
          <label class="form-label">Username</label>
          <div style="position:relative;">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:15px;">👤</span>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" style="padding-left:38px;" value="<?= htmlspecialchars($_POST['username']??'') ?>" required autofocus>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div style="position:relative;">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:15px;">🔑</span>
            <input type="password" name="password" id="pw" class="form-control" placeholder="Masukkan password" style="padding-left:38px;padding-right:44px;" required>
            <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;" data-pw-toggle="pw">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:8px;">Masuk ke Sistem →</button>
      </form>

      <div class="login-demo">
        <p>🧪 Demo Akun (password: <code style="color:#93c5fd;">password</code>)</p>
        <div class="demo-row"><span>Admin</span><span>admin / password</span></div>
        <div class="demo-row"><span>Siswa</span><span>budi / password</span></div>
      </div>
    </div>
    <p style="text-align:center;color:#334155;font-size:11.5px;margin-top:20px;">
      © <?= date('Y') ?> Lab RPL System &nbsp;·&nbsp;
      <a href="../setup.php" style="color:#475569;text-decoration:underline;"> Dibuat dengan ❤️</a>
    </p>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
