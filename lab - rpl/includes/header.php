<?php
// includes/header.php
// Hitung notif untuk badge sidebar
$notif_menunggu = 0;
$notif_cek      = 0;
if (isAdmin()) {
    $r1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM peminjaman WHERE status='menunggu'");
    $notif_menunggu = mysqli_fetch_assoc($r1)['c'];
    $r2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM detail_pinjam WHERE status='menunggu_cek'");
    $notif_cek = mysqli_fetch_assoc($r2)['c'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> — LabRPL</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔬</text></svg>">
  <link href="<?= $base_url ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="app-wrapper">

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <img src="<?= $base_url ?>assets/img/logo-sekolah.png" alt="Logo">
    </div>
    <div>
      <div class="logo-text">Lab RPL</div>
      <div class="logo-sub">SMKN Sukorejo</div>
    </div>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama']??'U',0,1)) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($_SESSION['nama']??'') ?></div>
      <span class="user-role"><?= htmlspecialchars($_SESSION['role']??'') ?></span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php if (isAdmin()): ?>
    <div class="nav-section">Main</div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/dashboard.php" class="nav-link <?= $active_menu==='dashboard'?'active':'' ?>">
        <span class="nav-icon">📊</span><span>Dashboard</span>
      </a>
    </div>

    <div class="nav-section">Manajemen</div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/users.php" class="nav-link <?= $active_menu==='users'?'active':'' ?>">
        <span class="nav-icon">👥</span><span>Data Pengguna</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/laboratorium.php" class="nav-link <?= $active_menu==='laboratorium'?'active':'' ?>">
        <span class="nav-icon">🏫</span><span>Laboratorium</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/alat.php" class="nav-link <?= $active_menu==='alat'?'active':'' ?>">
        <span class="nav-icon">🔧</span><span>Alat & Barang</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/denda.php" class="nav-link <?= $active_menu==='denda'?'active':'' ?>">
        <span class="nav-icon">⚙️</span><span>Atur Denda</span>
      </a>
    </div>

    <div class="nav-section">Peminjaman</div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/persetujuan.php" class="nav-link <?= $active_menu==='persetujuan'?'active':'' ?>">
        <span class="nav-icon">✅</span><span>Persetujuan</span>
        <?php if ($notif_menunggu > 0): ?>
          <span class="nav-badge"><?= $notif_menunggu ?></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/peminjaman.php" class="nav-link <?= $active_menu==='peminjaman'?'active':'' ?>">
        <span class="nav-icon">📋</span><span>Semua Peminjaman</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/pengembalian.php" class="nav-link <?= $active_menu==='pengembalian'?'active':'' ?>">
        <span class="nav-icon">🔍</span><span>Cek Pengembalian</span>
        <?php if ($notif_cek > 0): ?>
          <span class="nav-badge"><?= $notif_cek ?></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/admin/denda_siswa.php" class="nav-link <?= $active_menu==='denda_siswa'?'active':'' ?>">
        <span class="nav-icon">💰</span><span>Tagihan Denda</span>
      </a>
    </div>

    <?php else: ?>
    <div class="nav-section">Menu</div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/user/dashboard.php" class="nav-link <?= $active_menu==='dashboard'?'active':'' ?>">
        <span class="nav-icon">🏠</span><span>Dashboard</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/user/alat.php" class="nav-link <?= $active_menu==='alat'?'active':'' ?>">
        <span class="nav-icon">🔧</span><span>Cari Alat</span>
      </a>
    </div>

    <div class="nav-section">Peminjaman Saya</div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/user/pinjam.php" class="nav-link <?= $active_menu==='pinjam'?'active':'' ?>">
        <span class="nav-icon">📤</span><span>Pinjam Alat</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/user/riwayat.php" class="nav-link <?= $active_menu==='riwayat'?'active':'' ?>">
        <span class="nav-icon">🕒</span><span>Riwayat</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/user/kembalikan.php" class="nav-link <?= $active_menu==='kembalikan'?'active':'' ?>">
        <span class="nav-icon">↩️</span><span>Kembalikan Alat</span>
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= $base_url ?>pages/user/denda.php" class="nav-link <?= $active_menu==='denda_saya'?'active':'' ?>">
        <span class="nav-icon">💳</span><span>Denda Saya</span>
      </a>
    </div>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <form action="<?= $base_url ?>auth/logout.php" method="POST">
      <button type="submit" class="btn-logout">🚪 <span>Keluar</span></button>
    </form>
  </div>
</aside>

<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" id="hamburger">☰</button>
      <div>
        <div class="topbar-title"><?= htmlspecialchars($page_title??'') ?></div>
        <?php if (!empty($breadcrumb)): ?>
        <div class="breadcrumb">
          <span>Lab RPL</span>
          <?php foreach ($breadcrumb as $lbl => $url): ?>
          <span class="sep">›</span>
          <?php if ($url): ?><a href="<?= $url ?>"><?= $lbl ?></a>
          <?php else: ?><span class="current"><?= $lbl ?></span><?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div style="font-size:12px;color:var(--text-muted);"><?= date('l, d M Y') ?></div>
  </header>

  <div class="page-content">
