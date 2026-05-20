<?php
session_start();require_once '../../config/koneksi.php';requireLogin();
if(isAdmin()) redirect('../../pages/admin/dashboard.php');
$page_title='Dashboard Siswa';$active_menu='dashboard';$base_url='../../';
$breadcrumb=['Dashboard'=>null];
$id=$_SESSION['user_id'];

$stats=mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT
    COUNT(*) as total,
    SUM(dp.status='dipinjam') as aktif,
    SUM(dp.status='selesai') as selesai,
    SUM(p.status='menunggu') as menunggu
  FROM peminjaman p
  LEFT JOIN detail_pinjam dp ON dp.id_pinjam=p.id_pinjam
  WHERE p.id_user=$id
"));

$total_denda=mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT SUM(dp.total_denda) s FROM detail_pinjam dp
  JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  WHERE p.id_user=$id AND dp.denda_lunas=0 AND dp.total_denda>0
"))['s']??0;

$aktif_list=mysqli_query($conn,"
  SELECT dp.tgl_pinjam,dp.tgl_kembali_rencana,ab.nama_alat,l.nama_lab,
         DATEDIFF(NOW(),dp.tgl_kembali_rencana) as hari_telat
  FROM detail_pinjam dp JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  JOIN alat_barang ab ON dp.id_alat=ab.id_alat JOIN laboratorium l ON ab.id_lab=l.id_lab
  WHERE p.id_user=$id AND dp.status='dipinjam' ORDER BY dp.tgl_kembali_rencana ASC LIMIT 5
");

$menunggu_list=mysqli_query($conn,"
  SELECT p.*,ab.nama_alat FROM peminjaman p JOIN alat_barang ab ON p.id_alat=ab.id_alat
  WHERE p.id_user=$id AND p.status='menunggu' ORDER BY p.created_at DESC LIMIT 5
");

include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header"><h1>👋 Halo, <?=htmlspecialchars($_SESSION['nama'])?>!</h1><p>Selamat datang di Sistem Peminjaman Alat Lab RPL — SMKN Sukorejo</p></div>

<?php if($total_denda>0):?>
<div class="alert alert-danger">
  💸 Kamu memiliki denda belum lunas sebesar <strong><?=formatRupiah($total_denda)?></strong>! Segera bayar ke admin.
  <a href="denda.php" class="btn btn-danger btn-sm" style="margin-left:12px;">Lihat Detail →</a>
</div>
<?php endif;?>

<div class="stats-grid">
  <div class="stat-card blue"><div class="stat-icon">📋</div><div class="stat-info"><div class="stat-value"><?=$stats['total']??0?></div><div class="stat-label">Total Peminjaman</div></div></div>
  <div class="stat-card orange"><div class="stat-icon">⏳</div><div class="stat-info"><div class="stat-value"><?=$stats['menunggu']??0?></div><div class="stat-label">Menunggu Persetujuan</div></div></div>
  <div class="stat-card purple"><div class="stat-icon">📤</div><div class="stat-info"><div class="stat-value"><?=$stats['aktif']??0?></div><div class="stat-label">Sedang Dipinjam</div></div></div>
  <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-info"><div class="stat-value"><?=$stats['selesai']??0?></div><div class="stat-label">Selesai</div></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

  <div class="card">
    <div class="card-header"><div class="card-title">⏳ Menunggu Persetujuan</div></div>
    <div class="card-body" style="padding:0;">
      <?php $k=0; while($row=mysqli_fetch_assoc($menunggu_list)):$k++;?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid var(--border);">
        <div><div style="font-weight:600;font-size:13.5px;"><?=htmlspecialchars($row['nama_alat'])?></div>
        <div style="font-size:12px;color:var(--text-muted);">Diajukan <?=date('d M Y',strtotime($row['created_at']))?></div></div>
        <span class="badge badge-warning">⏳ Menunggu</span>
      </div>
      <?php endwhile; if($k===0):?><div class="empty-state" style="padding:24px;"><div class="empty-icon">✅</div><p>Tidak ada pengajuan pending</p></div><?php endif;?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">📤 Sedang Dipinjam</div><a href="kembalikan.php" class="btn btn-success btn-sm">↩️ Kembalikan</a></div>
    <div class="card-body" style="padding:0;">
      <?php $k=0; while($row=mysqli_fetch_assoc($aktif_list)):$k++;$telat=$row['hari_telat']>0;?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid var(--border);">
        <div><div style="font-weight:600;font-size:13.5px;"><?=htmlspecialchars($row['nama_alat'])?></div>
        <div style="font-size:12px;color:var(--text-muted);">Kembali: <?=date('d M Y',strtotime($row['tgl_kembali_rencana']))?></div></div>
        <?php if($telat):?><span class="badge badge-danger">⚠️ Telat <?=$row['hari_telat']?>h</span>
        <?php else:?><span class="badge badge-success">Tepat Waktu</span><?php endif;?>
      </div>
      <?php endwhile; if($k===0):?><div class="empty-state" style="padding:24px;"><div class="empty-icon">🎉</div><p>Tidak ada peminjaman aktif</p></div><?php endif;?>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php';?>
