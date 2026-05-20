<?php
session_start();require_once '../../config/koneksi.php';requireAdmin();
$page_title='Data Laboratorium';$active_menu='laboratorium';$base_url='../../';
$breadcrumb=['Manajemen'=>null,'Laboratorium'=>null];
$action=$_GET['action']??'list';
if($action==='create'&&$_SERVER['REQUEST_METHOD']==='POST'){
  $n=sanitize($conn,$_POST['nama_lab']);$l=sanitize($conn,$_POST['lokasi']);$k=intval($_POST['kapasitas']);
  mysqli_query($conn,"INSERT INTO laboratorium(nama_lab,lokasi,kapasitas)VALUES('$n','$l',$k)");
  setFlash('success','Laboratorium ditambahkan!');redirect('laboratorium.php');
}
if($action==='edit'&&$_SERVER['REQUEST_METHOD']==='POST'){
  $id=intval($_POST['id_lab']);$n=sanitize($conn,$_POST['nama_lab']);$l=sanitize($conn,$_POST['lokasi']);$k=intval($_POST['kapasitas']);
  mysqli_query($conn,"UPDATE laboratorium SET nama_lab='$n',lokasi='$l',kapasitas=$k WHERE id_lab=$id");
  setFlash('success','Laboratorium diperbarui!');redirect('laboratorium.php');
}
if($action==='delete'){
  $id=intval($_GET['id']);
  $c=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM alat_barang WHERE id_lab=$id"))['c'];
  if($c>0){setFlash('warning','Masih ada alat di lab ini!');}
  else{mysqli_query($conn,"DELETE FROM laboratorium WHERE id_lab=$id");setFlash('success','Lab dihapus.');}
  redirect('laboratorium.php');
}
$edit_lab=null;
if($action==='edit'&&isset($_GET['id'])){$id=intval($_GET['id']);$edit_lab=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM laboratorium WHERE id_lab=$id"));}
$labs=mysqli_query($conn,"SELECT l.*,COUNT(ab.id_alat) jml_alat FROM laboratorium l LEFT JOIN alat_barang ab ON l.id_lab=ab.id_lab GROUP BY l.id_lab ORDER BY l.id_lab DESC");
include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>🏫 Data Laboratorium</h1><p>Kelola ruang laboratorium RPL</p></div>
  <button class="btn btn-primary" data-modal="modal-add">+ Tambah Lab</button>
</div>

<div class="card"><div class="table-wrapper"><table>
  <thead><tr><th>#</th><th>Nama Lab</th><th>Lokasi</th><th>Kapasitas</th><th>Jumlah Alat</th><th>Aksi</th></tr></thead>
  <tbody>
    <?php $i=1; while($lab=mysqli_fetch_assoc($labs)):?>
    <tr>
      <td class="row-num"><?=$i++?></td>
      <td><div style="display:flex;align-items:center;gap:10px;"><span style="font-size:22px;">🏫</span><strong><?=htmlspecialchars($lab['nama_lab'])?></strong></div></td>
      <td><span style="color:var(--text-secondary);font-size:13px;">📍 <?=htmlspecialchars($lab['lokasi'])?></span></td>
      <td><span class="badge badge-info"><?=$lab['kapasitas']?> orang</span></td>
      <td><span class="badge badge-success"><?=$lab['jml_alat']?> alat</span></td>
      <td><div class="td-actions">
        <a href="laboratorium.php?action=edit&id=<?=$lab['id_lab']?>" class="btn btn-warning btn-sm">✏️ Edit</a>
        <a href="laboratorium.php?action=delete&id=<?=$lab['id_lab']?>" class="btn btn-danger btn-sm" data-confirm="Hapus '<?=htmlspecialchars($lab['nama_lab'])?>'?">🗑️</a>
      </div></td>
    </tr>
    <?php endwhile; if($i===1):?><tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🏫</div><p>Belum ada laboratorium</p></div></td></tr><?php endif;?>
  </tbody>
</table></div></div>

<div class="modal-backdrop" id="modal-add"><div class="modal"><div class="modal-header"><h3>➕ Tambah Laboratorium</h3><button class="modal-close">✕</button></div>
<form method="POST" action="laboratorium.php?action=create"><div class="modal-body">
  <div class="form-group"><label class="form-label">Nama Lab <span>*</span></label><input type="text" name="nama_lab" class="form-control" placeholder="Lab RPL 1" required></div>
  <div class="form-group"><label class="form-label">Lokasi <span>*</span></label><input type="text" name="lokasi" class="form-control" placeholder="Gedung A Lantai 2" required></div>
  <div class="form-group"><label class="form-label">Kapasitas (orang) <span>*</span></label><input type="number" name="kapasitas" class="form-control" placeholder="30" min="1" required></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div>

<?php if($edit_lab):?>
<div class="modal-backdrop show"><div class="modal"><div class="modal-header"><h3>✏️ Edit Laboratorium</h3><a href="laboratorium.php" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--bg);border-radius:6px;color:var(--text-secondary);">✕</a></div>
<form method="POST" action="laboratorium.php?action=edit"><input type="hidden" name="id_lab" value="<?=$edit_lab['id_lab']?>"><div class="modal-body">
  <div class="form-group"><label class="form-label">Nama Lab</label><input type="text" name="nama_lab" class="form-control" value="<?=htmlspecialchars($edit_lab['nama_lab'])?>" required></div>
  <div class="form-group"><label class="form-label">Lokasi</label><input type="text" name="lokasi" class="form-control" value="<?=htmlspecialchars($edit_lab['lokasi'])?>" required></div>
  <div class="form-group"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" class="form-control" value="<?=$edit_lab['kapasitas']?>" min="1" required></div>
</div><div class="modal-footer"><a href="laboratorium.php" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-primary">Update</button></div></form></div></div>
<?php endif;?>
<?php include '../../includes/footer.php';?>
