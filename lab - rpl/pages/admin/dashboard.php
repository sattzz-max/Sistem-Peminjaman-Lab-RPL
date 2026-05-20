<?php
session_start();
require_once '../../config/koneksi.php';
requireAdmin();

$page_title  = 'Dashboard Admin';
$active_menu = 'dashboard';
$base_url    = '../../';
$breadcrumb  = ['Dashboard' => null];

$total_user     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM user WHERE role='user'"))['c'];
$total_alat     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM alat_barang"))['c'];
$total_lab      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM laboratorium"))['c'];
$total_dipinjam = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM detail_pinjam WHERE status='dipinjam'"))['c'];
$total_menunggu = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM peminjaman WHERE status='menunggu'"))['c'];
$total_cek      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM detail_pinjam WHERE status='menunggu_cek'"))['c'];
$total_denda    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total_denda) s FROM detail_pinjam WHERE denda_lunas=0 AND total_denda>0"))['s'] ?? 0;

$recent = mysqli_query($conn,"
  SELECT p.status as status_pinjam, dp.status as status_detail,
         u.nama as nama_user, ab.nama_alat, dp.tgl_pinjam, dp.total_denda
  FROM detail_pinjam dp
  JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  JOIN user u ON p.id_user=u.id_user
  JOIN alat_barang ab ON dp.id_alat=ab.id_alat
  ORDER BY dp.id_detail DESC LIMIT 8
");

$low_stock = mysqli_query($conn,"SELECT nama_alat, jumlah_baik FROM alat_barang WHERE jumlah_baik <= 2 ORDER BY jumlah_baik LIMIT 5");

include '../../includes/header.php';
?>
<?php include '../../includes/flash.php'; ?>

<div class="page-header">
  <h1>👋 Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h1>
  <p>Ringkasan sistem peminjaman alat laboratorium hari ini.</p>
</div>

<div class="stats-grid">
  <div class="stat-card blue"><div class="stat-icon">👥</div><div class="stat-info"><div class="stat-value"><?= $total_user ?></div><div class="stat-label">Total Siswa</div></div></div>
  <div class="stat-card green"><div class="stat-icon">🔧</div><div class="stat-info"><div class="stat-value"><?= $total_alat ?></div><div class="stat-label">Jenis Alat</div></div></div>
  <div class="stat-card orange"><div class="stat-icon">📋</div><div class="stat-info"><div class="stat-value"><?= $total_dipinjam ?></div><div class="stat-label">Sedang Dipinjam</div></div></div>
  <div class="stat-card purple"><div class="stat-icon">⏳</div><div class="stat-info"><div class="stat-value"><?= $total_menunggu ?></div><div class="stat-label">Menunggu Persetujuan</div></div></div>
  <div class="stat-card orange"><div class="stat-icon">🔍</div><div class="stat-info"><div class="stat-value"><?= $total_cek ?></div><div class="stat-label">Menunggu Dicek</div></div></div>
  <div class="stat-card red"><div class="stat-icon">💸</div><div class="stat-info"><div class="stat-value" style="font-size:16px;"><?= formatRupiah($total_denda) ?></div><div class="stat-label">Denda Belum Lunas</div></div></div>
</div>

<?php if ($total_menunggu > 0): ?>
<div class="alert alert-warning">
  ⏳ Ada <strong><?= $total_menunggu ?></strong> pengajuan peminjaman menunggu persetujuanmu!
  <a href="persetujuan.php" class="btn btn-warning btn-sm" style="margin-left:12px;">Proses Sekarang →</a>
</div>
<?php endif; ?>
<?php if ($total_cek > 0): ?>
<div class="alert alert-info">
  🔍 Ada <strong><?= $total_cek ?></strong> alat dikembalikan siswa, menunggu pemeriksaan kondisi!
  <a href="pengembalian.php" class="btn btn-info btn-sm" style="margin-left:12px;">Periksa Sekarang →</a>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;">
  <div class="card">
    <div class="card-header">
      <div class="card-title">📋 Aktivitas Terbaru</div>
      <a href="peminjaman.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Siswa</th><th>Alat</th><th>Tgl Pinjam</th><th>Status</th><th>Denda</th></tr></thead>
        <tbody>
          <?php $k=0; while ($row=mysqli_fetch_assoc($recent)): $k++; ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['nama_user']) ?></strong></td>
            <td><?= htmlspecialchars($row['nama_alat']) ?></td>
            <td style="font-size:12.5px;"><?= date('d M Y',strtotime($row['tgl_pinjam'])) ?></td>
            <td>
              <?php
              $bs = ['dipinjam'=>'badge-warning','menunggu_cek'=>'badge-info','selesai'=>'badge-success'];
              $ls = ['dipinjam'=>'⏳ Dipinjam','menunggu_cek'=>'🔍 Dicek','selesai'=>'✅ Selesai'];
              $st = $row['status_detail'];
              ?>
              <span class="badge <?= $bs[$st]??'badge-secondary' ?>"><?= $ls[$st]??$st ?></span>
            </td>
            <td style="font-size:12.5px;font-family:var(--font-mono);">
              <?php if ($row['total_denda'] > 0): ?>
                <span style="color:var(--danger);"><?= formatRupiah($row['total_denda']) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if ($k===0): ?><tr><td colspan="5"><div class="empty-state" style="padding:24px;"><div class="empty-icon">📭</div><p>Belum ada data</p></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">⚠️ Stok Baik Menipis</div></div>
    <div class="card-body">
      <?php $lc=0; while ($row=mysqli_fetch_assoc($low_stock)): $lc++; ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
        <div style="font-size:13.5px;font-weight:600;"><?= htmlspecialchars($row['nama_alat']) ?></div>
        <?php if ($row['jumlah_baik']==0): ?>
          <span class="badge badge-danger">Habis</span>
        <?php else: ?>
          <span class="badge badge-warning"><?= $row['jumlah_baik'] ?> unit</span>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
      <?php if ($lc===0): ?><div class="empty-state" style="padding:20px;"><div class="empty-icon">✅</div><p>Semua stok aman!</p></div><?php endif; ?>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
