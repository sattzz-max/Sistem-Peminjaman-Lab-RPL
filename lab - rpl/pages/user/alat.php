<?php
// pages/user/alat.php
session_start();require_once '../../config/koneksi.php';requireLogin();
if(isAdmin()) redirect('../../pages/admin/dashboard.php');
$page_title='Cari Alat';$active_menu='alat';$base_url='../../';
$breadcrumb=['Alat'=>null,'Cari Alat'=>null];
$search=sanitize($conn,$_GET['q']??'');$id_lab=intval($_GET['id_lab']??0);
$where="WHERE 1=1";
if($search) $where.=" AND ab.nama_alat LIKE '%$search%'";
if($id_lab) $where.=" AND ab.id_lab=$id_lab";
$alat=mysqli_query($conn,"SELECT ab.*,l.nama_lab,(ab.jumlah_baik+ab.jumlah_rusak_ringan+ab.jumlah_rusak_berat) as total FROM alat_barang ab JOIN laboratorium l ON ab.id_lab=l.id_lab $where ORDER BY ab.jumlah_baik DESC,ab.nama_alat");
$labs=mysqli_query($conn,"SELECT * FROM laboratorium ORDER BY nama_lab");
include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header"><h1>🔍 Cari Alat Laboratorium</h1><p>Temukan alat yang tersedia, hanya alat kondisi baik yang bisa dipinjam</p></div>

<form class="toolbar" method="GET">
  <div class="search-bar" style="flex:1;max-width:360px;"><span class="search-icon">🔍</span><input type="text" name="q" placeholder="Cari nama alat..." value="<?=htmlspecialchars($search)?>" id="table-search"></div>
  <select name="id_lab" class="form-control" style="width:auto;padding:9px 14px;" onchange="this.form.submit()">
    <option value="">Semua Lab</option>
    <?php while($lab=mysqli_fetch_assoc($labs)):?><option value="<?=$lab['id_lab']?>" <?=$id_lab==$lab['id_lab']?'selected':''?>><?=htmlspecialchars($lab['nama_lab'])?></option><?php endwhile;?>
  </select>
  <button type="submit" class="btn btn-primary">Cari</button>
  <?php if($search||$id_lab):?><a href="alat.php" class="btn btn-secondary">✕ Reset</a><?php endif;?>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
  <?php $tot=0; while($row=mysqli_fetch_assoc($alat)):$tot++;$ok=$row['jumlah_baik']>0;?>
  <div class="card fade-in" style="<?=!$ok?'opacity:.65;':''?>">
    <div style="padding:20px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
        <span style="font-size:32px;">🔧</span>
        <span class="badge <?=$ok?'badge-success':'badge-danger'?>"><?=$ok?'Tersedia':'Stok Baik Habis'?></span>
      </div>
      <h3 style="font-size:15px;font-weight:700;margin-bottom:4px;"><?=htmlspecialchars($row['nama_alat'])?></h3>
      <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px;">🏫 <?=htmlspecialchars($row['nama_lab'])?></p>
      <div class="stok-box" style="margin-bottom:14px;">
        <span class="stok-chip baik">✅ <?=$row['jumlah_baik']?></span>
        <?php if($row['jumlah_rusak_ringan']>0):?><span class="stok-chip ringan">⚠️ <?=$row['jumlah_rusak_ringan']?></span><?php endif;?>
        <?php if($row['jumlah_rusak_berat']>0):?><span class="stok-chip berat">❌ <?=$row['jumlah_rusak_berat']?></span><?php endif;?>
      </div>
      <?php if($ok):?>
      <a href="pinjam.php?id_alat=<?=$row['id_alat']?>" class="btn btn-primary" style="width:100%;justify-content:center;">📤 Ajukan Pinjam</a>
      <?php else:?>
      <button class="btn btn-secondary" style="width:100%;justify-content:center;cursor:not-allowed;" disabled>Stok Baik Habis</button>
      <?php endif;?>
    </div>
  </div>
  <?php endwhile; if($tot===0):?>
  <div style="grid-column:1/-1;"><div class="card"><div class="empty-state"><div class="empty-icon">🔧</div><p>Tidak ada alat ditemukan</p></div></div></div>
  <?php endif;?>
</div>
<?php include '../../includes/footer.php';?>
