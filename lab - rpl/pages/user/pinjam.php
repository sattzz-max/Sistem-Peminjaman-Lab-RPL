<?php
// pages/user/pinjam.php — REVISI 2: perlu persetujuan admin
session_start();require_once '../../config/koneksi.php';requireLogin();
if(isAdmin()) redirect('../../pages/admin/dashboard.php');
$page_title='Pinjam Alat';$active_menu='pinjam';$base_url='../../';
$breadcrumb=['Peminjaman'=>null,'Pinjam Alat'=>null];
$id_user=(int)$_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $id_alat    = intval($_POST['id_alat']);
  $tgl_pinjam = sanitize($conn,$_POST['tgl_pinjam']);
  $tgl_kembali= sanitize($conn,$_POST['tgl_kembali_rencana']);
  $keperluan  = sanitize($conn,$_POST['keperluan']??'');

  if(empty($tgl_pinjam)||empty($tgl_kembali)){setFlash('danger','Tanggal harus diisi!');redirect('pinjam.php');}
  if($tgl_kembali<=$tgl_pinjam){setFlash('danger','Tanggal kembali harus setelah tanggal pinjam!');redirect('pinjam.php');}

  $alat=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM alat_barang WHERE id_alat=$id_alat"));
  if(!$alat){setFlash('danger','Alat tidak ditemukan!');redirect('pinjam.php');}
  if($alat['jumlah_baik']<=0){setFlash('danger','Maaf, stok alat kondisi baik sudah habis!');redirect('pinjam.php');}

  // Cek sudah punya pengajuan pending/aktif untuk alat yang sama
  $already=mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT p.id_pinjam FROM peminjaman p
    LEFT JOIN detail_pinjam dp ON dp.id_pinjam=p.id_pinjam
    WHERE p.id_user=$id_user AND p.id_alat=$id_alat
    AND (p.status IN('menunggu','disetujui') OR dp.status='dipinjam')
    LIMIT 1
  "));
  if($already){setFlash('warning','Kamu sudah punya pengajuan/peminjaman aktif untuk alat ini!');redirect('pinjam.php');}

  mysqli_query($conn,"INSERT INTO peminjaman(id_user,id_alat,tgl_pinjam,tgl_kembali_rencana,keperluan,status)
    VALUES($id_user,$id_alat,'$tgl_pinjam','$tgl_kembali','$keperluan','menunggu')");
  setFlash('success','Pengajuan peminjaman berhasil dikirim! Tunggu persetujuan admin. ⏳');
  redirect('riwayat.php');
}

$id_alat_pre=intval($_GET['id_alat']??0);
$selected_alat=null;
if($id_alat_pre) $selected_alat=mysqli_fetch_assoc(mysqli_query($conn,"SELECT ab.*,l.nama_lab FROM alat_barang ab JOIN laboratorium l ON ab.id_lab=l.id_lab WHERE ab.id_alat=$id_alat_pre"));

$all_alat=mysqli_query($conn,"SELECT ab.*,l.nama_lab FROM alat_barang ab JOIN laboratorium l ON ab.id_lab=l.id_lab WHERE ab.jumlah_baik>0 ORDER BY l.nama_lab,ab.nama_alat");

include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header"><h1>📤 Pinjam Alat</h1><p>Ajukan peminjaman alat — menunggu persetujuan admin sebelum bisa diambil</p></div>

<!-- Alur -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:24px;">
  <div style="font-size:12.5px;font-weight:700;color:var(--text-secondary);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;">Alur Peminjaman</div>
  <div class="status-timeline">
    <div class="st-step active"><div class="st-dot">1</div><div class="st-label">Isi Form</div></div>
    <div class="st-step"><div class="st-dot">2</div><div class="st-label">Menunggu<br>Persetujuan</div></div>
    <div class="st-step"><div class="st-dot">3</div><div class="st-label">Disetujui<br>& Ambil</div></div>
    <div class="st-step"><div class="st-dot">4</div><div class="st-label">Kembalikan</div></div>
    <div class="st-step"><div class="st-dot">5</div><div class="st-label">Dicek<br>Admin</div></div>
    <div class="st-step"><div class="st-dot">✅</div><div class="st-label">Selesai</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">
  <div class="card">
    <div class="card-header"><div class="card-title">📋 Form Pengajuan Peminjaman</div></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Pilih Alat <span>*</span></label>
          <select name="id_alat" id="select-alat" class="form-control" required onchange="updateInfo(this)">
            <option value="">-- Pilih Alat Tersedia --</option>
            <?php $cur_lab=''; while($row=mysqli_fetch_assoc($all_alat)):
              if($cur_lab!==$row['nama_lab']){if($cur_lab) echo '</optgroup>';echo '<optgroup label="🏫 '.htmlspecialchars($row['nama_lab']).'">';$cur_lab=$row['nama_lab'];}?>
            <option value="<?=$row['id_alat']?>" data-baik="<?=$row['jumlah_baik']?>" data-lab="<?=htmlspecialchars($row['nama_lab'])?>"
              <?=$id_alat_pre==$row['id_alat']?'selected':''?>>
              <?=htmlspecialchars($row['nama_alat'])?> (Stok Baik: <?=$row['jumlah_baik']?>)
            </option>
            <?php endwhile; if($cur_lab) echo '</optgroup>';?>
          </select>
        </div>

        <div id="alat-info" style="display:none;margin-bottom:18px;">
          <div style="background:var(--primary-50);border:1px solid var(--primary-100);border-radius:var(--radius-sm);padding:14px;display:flex;gap:12px;">
            <span style="font-size:24px;">🔧</span>
            <div><div id="info-nama" style="font-weight:700;font-size:15px;"></div>
            <div id="info-lab" style="font-size:12.5px;color:var(--text-secondary);margin-top:2px;"></div>
            <div style="margin-top:8px;"><span id="info-stok" class="badge badge-success"></span></div></div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tanggal Pinjam <span>*</span></label>
            <input type="date" name="tgl_pinjam" class="form-control" value="<?=date('Y-m-d')?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Rencana Tanggal Kembali <span>*</span></label>
            <input type="date" name="tgl_kembali_rencana" class="form-control"
              value="<?=date('Y-m-d',strtotime('+7 days'))?>"
              min="<?=date('Y-m-d',strtotime('+1 day'))?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Keperluan / Keterangan</label>
          <textarea name="keperluan" class="form-control" rows="3"
            placeholder="Contoh: Untuk praktikum pemrograman web, tugas UAS..." style="resize:none;"></textarea>
        </div>

        <div style="padding:14px;background:var(--info-light);border-left:4px solid var(--info);border-radius:var(--radius-sm);margin-bottom:20px;">
          <p style="font-size:12.5px;color:#3730a3;font-weight:600;margin:0;">
            ℹ️ Pengajuanmu akan <strong>menunggu persetujuan admin</strong> sebelum alat bisa diambil. Kamu akan bisa melihat status di halaman Riwayat.
          </p>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">
          📤 Kirim Pengajuan Peminjaman
        </button>
      </form>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <div class="card-header"><div class="card-title">📌 Syarat Peminjaman</div></div>
      <div class="card-body" style="font-size:13.5px;display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;gap:8px;">✅ <span>Siswa aktif terdaftar</span></div>
        <div style="display:flex;gap:8px;">✅ <span>Tidak ada pengajuan pending untuk alat yang sama</span></div>
        <div style="display:flex;gap:8px;">✅ <span>Stok kondisi baik tersedia</span></div>
        <div style="display:flex;gap:8px;">✅ <span>Kembalikan tepat waktu untuk menghindari denda</span></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">💰 Info Denda</div></div>
      <?php $cfg=getDenda($conn);?>
      <div class="card-body" style="font-size:13px;display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">⏰ Terlambat</span><strong><?=formatRupiah($cfg['denda_terlambat']??2000)?>/hari</strong></div>
        <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">⚠️ Rusak Ringan</span><strong><?=formatRupiah($cfg['denda_rusak_ringan']??25000)?></strong></div>
        <div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">❌ Rusak Berat</span><strong><?=formatRupiah($cfg['denda_rusak_berat']??100000)?></strong></div>
      </div>
    </div>
  </div>
</div>

<script>
function updateInfo(sel){
  const opt=sel.options[sel.selectedIndex],info=document.getElementById('alat-info');
  if(!opt.value){info.style.display='none';return;}
  document.getElementById('info-nama').textContent=opt.text.split(' (Stok')[0];
  document.getElementById('info-lab').textContent='🏫 '+opt.dataset.lab;
  document.getElementById('info-stok').textContent='Stok Baik: '+opt.dataset.baik+' unit';
  info.style.display='block';
}
window.addEventListener('DOMContentLoaded',()=>{const s=document.getElementById('select-alat');if(s.value)updateInfo(s);});
</script>
<?php include '../../includes/footer.php';?>
