<?php
session_start();require_once '../../config/koneksi.php';requireAdmin();
$page_title='Data Pengguna';$active_menu='users';$base_url='../../';
$breadcrumb=['Manajemen'=>null,'Pengguna'=>null];
$action=$_GET['action']??'list';

if($action==='create'&&$_SERVER['REQUEST_METHOD']==='POST'){
  $nama=sanitize($conn,$_POST['nama']);$username=sanitize($conn,$_POST['username']);
  $pw=password_hash($_POST['password'],PASSWORD_DEFAULT);$role=sanitize($conn,$_POST['role']);
  $chk=mysqli_query($conn,"SELECT id_user FROM user WHERE username='$username'");
  if(mysqli_num_rows($chk)>0){setFlash('danger','Username sudah digunakan!');}
  else{mysqli_query($conn,"INSERT INTO user(nama,username,password,role)VALUES('$nama','$username','$pw','$role')");setFlash('success','Pengguna berhasil ditambahkan!');}
  redirect('users.php');
}
if($action==='edit'&&$_SERVER['REQUEST_METHOD']==='POST'){
  $id=intval($_POST['id_user']);$nama=sanitize($conn,$_POST['nama']);$role=sanitize($conn,$_POST['role']);
  $sql="UPDATE user SET nama='$nama',role='$role' WHERE id_user=$id";
  if(!empty($_POST['password'])){$pw=password_hash($_POST['password'],PASSWORD_DEFAULT);$sql="UPDATE user SET nama='$nama',role='$role',password='$pw' WHERE id_user=$id";}
  mysqli_query($conn,$sql);setFlash('success','Data pengguna diperbarui!');redirect('users.php');
}
if($action==='delete'){
  $id=intval($_GET['id']);
  if($id===$_SESSION['user_id']){setFlash('danger','Tidak bisa menghapus akun sendiri!');}
  else{mysqli_query($conn,"DELETE FROM user WHERE id_user=$id");setFlash('success','Pengguna dihapus.');}
  redirect('users.php');
}
$edit_user=null;
if($action==='edit'&&isset($_GET['id'])){$id=intval($_GET['id']);$edit_user=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM user WHERE id_user=$id"));}
$search=sanitize($conn,$_GET['q']??'');
$where=$search?"WHERE nama LIKE '%$search%' OR username LIKE '%$search%'":'';
$users=mysqli_query($conn,"SELECT * FROM user $where ORDER BY id_user DESC");
include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>👥 Data Pengguna</h1><p>Kelola akun admin dan siswa</p></div>
  <button class="btn btn-primary" data-modal="modal-add">+ Tambah Pengguna</button>
</div>

<form class="toolbar" method="GET">
  <div class="search-bar" style="flex:1;max-width:360px;"><span class="search-icon">🔍</span><input type="text" name="q" id="table-search" placeholder="Cari nama atau username..." value="<?=htmlspecialchars($search)?>"></div>
  <?php if($search):?><a href="users.php" class="btn btn-secondary btn-sm">✕ Reset</a><?php endif;?>
</form>

<div class="card"><div class="table-wrapper"><table>
  <thead><tr><th>#</th><th>Nama</th><th>Username</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr></thead>
  <tbody>
    <?php $i=1; while($u=mysqli_fetch_assoc($users)):?>
    <tr data-searchable>
      <td class="row-num"><?=$i++?></td>
      <td><div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;"><?=strtoupper(substr($u['nama'],0,1))?></div><span style="font-weight:600;"><?=htmlspecialchars($u['nama'])?></span></div></td>
      <td><code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:12.5px;"><?=htmlspecialchars($u['username'])?></code></td>
      <td><?=$u['role']==='admin'?'<span class="badge badge-info">👑 Admin</span>':'<span class="badge badge-secondary">🎓 Siswa</span>'?></td>
      <td style="color:var(--text-muted);font-size:12.5px;"><?=date('d M Y',strtotime($u['created_at']))?></td>
      <td><div class="td-actions">
        <a href="users.php?action=edit&id=<?=$u['id_user']?>" class="btn btn-warning btn-sm">✏️ Edit</a>
        <?php if($u['id_user']!=$_SESSION['user_id']):?>
        <a href="users.php?action=delete&id=<?=$u['id_user']?>" class="btn btn-danger btn-sm" data-confirm="Hapus '<?=htmlspecialchars($u['nama'])?>'?">🗑️</a>
        <?php endif;?>
      </div></td>
    </tr>
    <?php endwhile; if($i===1):?><tr><td colspan="6"><div class="empty-state"><div class="empty-icon">👥</div><p>Belum ada pengguna</p></div></td></tr><?php endif;?>
  </tbody>
</table></div></div>

<div class="modal-backdrop" id="modal-add"><div class="modal"><div class="modal-header"><h3>➕ Tambah Pengguna</h3><button class="modal-close">✕</button></div>
<form method="POST" action="users.php?action=create"><div class="modal-body">
  <div class="form-group"><label class="form-label">Nama Lengkap <span>*</span></label><input type="text" name="nama" class="form-control" placeholder="Nama lengkap" required></div>
  <div class="form-group"><label class="form-label">Username <span>*</span></label><input type="text" name="username" class="form-control" placeholder="Username unik" required></div>
  <div class="form-group"><label class="form-label">Password <span>*</span></label><input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required minlength="6"></div>
  <div class="form-group"><label class="form-label">Role <span>*</span></label><select name="role" class="form-control"><option value="user">🎓 Siswa</option><option value="admin">👑 Admin</option></select></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-modal-close>Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div>

<?php if($edit_user):?>
<div class="modal-backdrop show"><div class="modal"><div class="modal-header"><h3>✏️ Edit Pengguna</h3><a href="users.php" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--bg);border-radius:6px;color:var(--text-secondary);">✕</a></div>
<form method="POST" action="users.php?action=edit"><input type="hidden" name="id_user" value="<?=$edit_user['id_user']?>"><div class="modal-body">
  <div class="form-group"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="<?=htmlspecialchars($edit_user['nama'])?>" required></div>
  <div class="form-group"><label class="form-label">Password Baru <span style="color:var(--text-muted);font-weight:400;">(kosongkan jika tidak diubah)</span></label><input type="password" name="password" class="form-control" placeholder="Password baru..."></div>
  <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-control"><option value="user" <?=$edit_user['role']==='user'?'selected':''?>>🎓 Siswa</option><option value="admin" <?=$edit_user['role']==='admin'?'selected':''?>>👑 Admin</option></select></div>
</div><div class="modal-footer"><a href="users.php" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-primary">Update</button></div></form></div></div>
<?php endif;?>
<?php include '../../includes/footer.php';?>
