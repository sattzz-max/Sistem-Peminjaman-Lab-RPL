<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn=mysqli_connect('localhost','root','','lab - rpl');
if(!$conn)die('DB Error: '.mysqli_connect_error());
mysqli_set_charset($conn,'utf8mb4');

function sanitize($c,$d){return mysqli_real_escape_string($c,trim($d));}
function redirect($u){header("Location: $u");exit();}
function setFlash($t,$m){$_SESSION['flash']=['type'=>$t,'message'=>$m];}
function requireLogin(){if(!isset($_SESSION['user_id']))redirect('../auth/login.php');}
function requireAdmin(){requireLogin();if($_SESSION['role']!=='admin')redirect('../pages/user/dashboard.php');}
function isAdmin(){return isset($_SESSION['role'])&&$_SESSION['role']==='admin';}
function getDenda($conn){$c=[];$r=mysqli_query($conn,'SELECT kode,nilai FROM konfigurasi_denda');while($row=mysqli_fetch_assoc($r))$c[$row['kode']]=$row['nilai'];return $c;}
function hitungDenda($conn,$tgl_rencana,$tgl_aktual,$kondisi){$cfg=getDenda($conn);$dt=0;$dk=0;$r=new DateTime($tgl_rencana);$a=new DateTime($tgl_aktual);if($a>$r){$hari=$r->diff($a)->days;$dt=$hari*($cfg['denda_terlambat']??2000);}if($kondisi==='rusak_ringan')$dk=$cfg['denda_rusak_ringan']??25000;elseif($kondisi==='rusak_berat')$dk=$cfg['denda_rusak_berat']??100000;return['denda_terlambat'=>$dt,'denda_kerusakan'=>$dk,'total'=>$dt+$dk];}
function formatRupiah($n){return 'Rp '.number_format($n,0,',','.');}
