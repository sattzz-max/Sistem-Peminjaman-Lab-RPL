<?php
// pages/user/kembalikan.php — REVISI 2: siswa request kembali, admin yang cek
session_start();require_once '../../config/koneksi.php';requireLogin();
if(isAdmin()) redirect('../../pages/admin/dashboard.php');
$page_title='Kembalikan Alat';$active_menu='kembalikan';$base_url='../../';
$breadcrumb=['Peminjaman'=>null,'Kembalikan Alat'=>null];
$id_user=(int)$_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['id_detail'])){
  $id_detail=intval($_POST['id_detail']);
  // Verifikasi kepemilikan
  $check=mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT dp.id_detail FROM detail_pinjam dp JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
    WHERE dp.id_detail=$id_detail AND p.id_user=$id_user AND dp.status='dipinjam'
  "));
  if(!$check){setFlash('danger','Data tidak valid!');redirect('kembalikan.php');}
  // Ubah status jadi menunggu_cek — admin yang akan verifikasi kondisi
  mysqli_query($conn,"UPDATE detail_pinjam SET status='menunggu_cek' WHERE id_detail=$id_detail");
  setFlash('success','Alat berhasil diserahkan! Tunggu admin memeriksa kondisi dan mencatat pengembalian. 🔍');
  redirect('kembalikan.php');
}

$aktif=mysqli_query($conn,"
  SELECT dp.id_detail,dp.tgl_pinjam,dp.tgl_kembali_rencana,ab.nama_alat,l.nama_lab,
         DATEDIFF(NOW(),dp.tgl_kembali_rencana) as hari_telat
  FROM detail_pinjam dp JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  JOIN alat_barang ab ON dp.id_alat=ab.id_alat JOIN laboratorium l ON ab.id_lab=l.id_lab
  WHERE p.id_user=$id_user AND dp.status='dipinjam' ORDER BY dp.tgl_kembali_rencana ASC
");
$rows=mysqli_fetch_all($aktif,MYSQLI_ASSOC);

$cfg=getDenda($conn);
include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header"><h1>↩️ Kembalikan Alat</h1><p>Serahkan alat ke admin, admin akan memeriksa kondisi dan mencatat pengembalian</p></div>

<!-- Alur pengembalian -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:24px;">
  <div style="font-size:12.5px;font-weight:700;color:var(--text-secondary);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Alur Pengembalian</div>
  <div class="status-timeline">
    <div class="st-step done"><div class="st-dot">✓</div><div class="st-label">Alat Dipinjam</div></div>
    <div class="st-step active"><div class="st-dot">2</div><div class="st-label">Klik Kembalikan<br>(kamu)</div></div>
    <div class="st-step"><div class="st-dot">3</div><div class="st-label">Admin Cek<br>Kondisi</div></div>
    <div class="st-step"><div class="st-dot">4</div><div class="st-label">Denda<br>Dihitung</div></div>
    <div class="st-step"><div class="st-dot">✅</div><div class="st-label">Selesai</div></div>
  </div>
</div>

<?php if(empty($rows)):?>
<div class="card"><div class="empty-state" style="padding:60px 24px;">
  <div class="empty-icon">🎉</div>
  <p style="font-size:15px;font-weight:600;color:var(--text-secondary);">Tidak ada alat yang perlu dikembalikan!</p>
  <a href="pinjam.php" class="btn btn-primary" style="margin-top:16px;">+ Pinjam Alat Baru</a>
</div></div>
<?php else:?>

<?php $telat_list=array_filter($rows,fn($r)=>$r['hari_telat']>0);?>
<?php if(!empty($telat_list)):?>
<div class="alert alert-danger">⚠️ Kamu memiliki <?=count($telat_list)?> alat yang sudah melewati batas kembali! Segera kembalikan untuk mengurangi denda.</div>
<?php endif;?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
  <?php foreach($rows as $row):$telat=$row['hari_telat']>0;$est_denda=$telat?($row['hari_telat']*($cfg['denda_terlambat']??2000)):0;?>
  <div class="card fade-in" style="border-top:3px solid <?=$telat?'var(--danger)':'var(--success)'?>;">
    <div style="padding:20px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
        <span style="font-size:28px;">🔧</span>
        <?php if($telat):?><span class="badge badge-danger">⚠️ Telat <?=$row['hari_telat']?> hari</span>
        <?php else:?><span class="badge badge-success">✅ Tepat Waktu</span><?php endif;?>
      </div>
      <h3 style="font-size:16px;font-weight:700;margin-bottom:4px;"><?=htmlspecialchars($row['nama_alat'])?></h3>
      <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px;">🏫 <?=htmlspecialchars($row['nama_lab'])?></p>
      <div style="background:var(--bg);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px;"><span style="color:var(--text-muted);">Tgl Pinjam</span><strong><?=date('d M Y',strtotime($row['tgl_pinjam']))?></strong></div>
        <div style="display:flex;justify-content:space-between;font-size:12.5px;"><span style="color:var(--text-muted);">Batas Kembali</span><strong style="<?=$telat?'color:var(--danger);':''?>"><?=date('d M Y',strtotime($row['tgl_kembali_rencana']))?></strong></div>
      </div>
      <?php if($est_denda>0):?>
      <div style="background:var(--danger-light);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:12px;border-left:3px solid var(--danger);">
        <div style="font-size:12px;color:#991b1b;font-weight:600;">Estimasi denda keterlambatan:</div>
        <div style="font-size:15px;font-weight:800;color:var(--danger);font-family:var(--font-mono);"><?=formatRupiah($est_denda)?></div>
        <div style="font-size:11px;color:#991b1b;">(belum termasuk kerusakan)</div>
      </div>
      <?php endif;?>
      <form method="POST">
        <input type="hidden" name="id_detail" value="<?=$row['id_detail']?>">
        <button type="submit" class="btn <?=$telat?'btn-danger':'btn-success'?>"
          style="width:100%;justify-content:center;"
          onclick="return confirm('Konfirmasi pengembalian <?=htmlspecialchars($row['nama_alat'])?>?\nAdmin akan memeriksa kondisi alat.')">
          ↩️ Serahkan ke Admin
        </button>
      </form>
    </div>
  </div>
  <?php endforeach;?>
</div>
<?php endif;?>

<?php include '../../includes/footer.php';?>
