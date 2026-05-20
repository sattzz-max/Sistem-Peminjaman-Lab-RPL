<?php
// setup.php — Installer otomatis
$step=$_GET['step']??'1';$msg='';$err='';
if($step==='2'&&$_SERVER['REQUEST_METHOD']==='POST'){
  $host=$_POST['host']??'localhost';$user=$_POST['user']??'root';
  $pass=$_POST['pass']??'';$db=$_POST['db']??'lab_rpl';
  $conn=@mysqli_connect($host,$user,$pass);
  if(!$conn){$err='Koneksi gagal: '.mysqli_connect_error();}
  else{
    mysqli_query($conn,"CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    mysqli_select_db($conn,$db);
    $sql=file_get_contents(__DIR__.'/database.sql');
    $sql=preg_replace('/^CREATE DATABASE.*?;\s*/im','',$sql);
    $sql=preg_replace('/^USE.*?;\s*/im','',$sql);
    $errors=[];
    foreach(array_filter(array_map('trim',explode(';',$sql))) as $q){
      if(!empty($q)&&!mysqli_query($conn,$q)) $errors[]=mysqli_error($conn);
    }
    // Tulis koneksi.php
    $cfg="<?php\nmysqli_report(MYSQLI_REPORT_OFF);\n\$conn=mysqli_connect('$host','$user','$pass','$db');\nif(!\$conn)die('DB Error: '.mysqli_connect_error());\nmysqli_set_charset(\$conn,'utf8mb4');\n\nfunction sanitize(\$c,\$d){return mysqli_real_escape_string(\$c,trim(\$d));}\nfunction redirect(\$u){header(\"Location: \$u\");exit();}\nfunction setFlash(\$t,\$m){\$_SESSION['flash']=['type'=>\$t,'message'=>\$m];}\nfunction requireLogin(){if(!isset(\$_SESSION['user_id']))redirect('../auth/login.php');}\nfunction requireAdmin(){requireLogin();if(\$_SESSION['role']!=='admin')redirect('../pages/user/dashboard.php');}\nfunction isAdmin(){return isset(\$_SESSION['role'])&&\$_SESSION['role']==='admin';}\nfunction getDenda(\$conn){\$c=[];\$r=mysqli_query(\$conn,'SELECT kode,nilai FROM konfigurasi_denda');while(\$row=mysqli_fetch_assoc(\$r))\$c[\$row['kode']]=\$row['nilai'];return \$c;}\nfunction hitungDenda(\$conn,\$tgl_rencana,\$tgl_aktual,\$kondisi){\$cfg=getDenda(\$conn);\$dt=0;\$dk=0;\$r=new DateTime(\$tgl_rencana);\$a=new DateTime(\$tgl_aktual);if(\$a>\$r){\$hari=\$r->diff(\$a)->days;\$dt=\$hari*(\$cfg['denda_terlambat']??2000);}if(\$kondisi==='rusak_ringan')\$dk=\$cfg['denda_rusak_ringan']??25000;elseif(\$kondisi==='rusak_berat')\$dk=\$cfg['denda_rusak_berat']??100000;return['denda_terlambat'=>\$dt,'denda_kerusakan'=>\$dk,'total'=>\$dt+\$dk];}\nfunction formatRupiah(\$n){return 'Rp '.number_format(\$n,0,',','.');}\n";
    file_put_contents(__DIR__.'/config/koneksi.php',$cfg);
    $msg=empty($errors)?'success':'error: '.implode('<br>',$errors);
  }
}
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Setup Lab RPL</title><link href="assets/css/style.css" rel="stylesheet"></head><body>
<div class="login-page"><div class="login-bg-pattern"></div><div class="login-container" style="max-width:520px;">
<div class="login-card">
  <div class="login-logo">
    <div style="width:70px;height:70px;border-radius:50%;overflow:hidden;margin:0 auto 16px;border:3px solid rgba(255,255,255,.15);box-shadow:0 8px 28px rgba(0,0,0,.4);background:#fff;">
      <img src="assets/img/logo-sekolah.png" style="width:100%;height:100%;object-fit:cover;">
    </div>
    <h1>Setup Lab RPL v2</h1><p>Konfigurasi database sistem</p>
  </div>

  <?php if($msg==='success'):?>
  <div class="alert alert-success" style="background:rgba(16,185,129,.12);color:#6ee7b7;border-left-color:#10b981;">✅ <strong>Instalasi berhasil!</strong></div>
  <div style="background:rgba(37,99,235,.1);border:1px solid rgba(37,99,235,.2);border-radius:8px;padding:16px;margin-bottom:20px;">
    <p style="color:#93c5fd;font-size:13px;font-weight:600;margin-bottom:8px;">🔑 Akun Default (password: <code style="color:#fbbf24;">password</code>):</p>
    <div style="font-family:monospace;font-size:12px;color:#64748b;">
      <div>admin / password → Admin</div><div>budi / password → Siswa</div><div>siti / password → Siswa</div>
    </div>
  </div>
  <a href="auth/login.php" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">🚀 Mulai Login</a>
  <p style="color:#475569;font-size:11.5px;text-align:center;margin-top:12px;">⚠️ Hapus file <code>setup.php</code> setelah selesai!</p>

  <?php elseif($err||str_starts_with($msg,'error')):?>
  <div class="alert alert-danger" style="background:rgba(239,68,68,.12);color:#fca5a5;border-left-color:#ef4444;">❌ <?=$err?$err:$msg?></div>
  <a href="setup.php" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:8px;">← Coba Lagi</a>

  <?php else:?>
  <form method="POST" action="setup.php?step=2" class="login-form">
    <div class="form-group"><label class="form-label">Host MySQL</label><input type="text" name="host" class="form-control" value="localhost" required></div>
    <div class="form-group"><label class="form-label">Username MySQL</label><input type="text" name="user" class="form-control" value="root" required></div>
    <div class="form-group"><label class="form-label">Password MySQL <span style="color:#475569;font-weight:400;">(kosong jika tidak ada)</span></label><input type="password" name="pass" class="form-control" placeholder="Kosongkan jika tidak ada"></div>
    <div class="form-group"><label class="form-label">Nama Database</label><input type="text" name="db" class="form-control" value="lab_rpl" required></div>
    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:8px;">⚙️ Jalankan Instalasi</button>
  </form>
  <?php endif;?>
</div></div></div>
<script src="assets/js/app.js"></script></body></html>
