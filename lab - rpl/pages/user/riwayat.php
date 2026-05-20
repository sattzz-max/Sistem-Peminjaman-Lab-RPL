<?php
// pages/user/riwayat.php
session_start();require_once '../../config/koneksi.php';requireLogin();
if(isAdmin()) redirect('../../pages/admin/dashboard.php');
$page_title='Riwayat Peminjaman';$active_menu='riwayat';$base_url='../../';
$breadcrumb=['Peminjaman'=>null,'Riwayat'=>null];
$id_user=(int)$_SESSION['user_id'];
$filter=sanitize($conn,$_GET['f']??'');

$where="WHERE p.id_user=$id_user";
if($filter==='dipinjam') $where.=" AND dp.status='dipinjam'";
elseif($filter==='menunggu') $where.=" AND p.status='menunggu'";
elseif($filter==='selesai') $where.=" AND dp.status='selesai'";

$data=mysqli_query($conn,"
  SELECT p.status as status_pinjam,p.tgl_kembali_rencana,p.alasan_tolak,p.keperluan,
         dp.status as status_detail,dp.tgl_pinjam,dp.tgl_kembali_aktual,
         dp.kondisi_kembali,dp.total_denda,dp.denda_lunas,
         ab.nama_alat,l.nama_lab,
         COALESCE(dp.id_detail,0) as id_detail
  FROM peminjaman p
  LEFT JOIN detail_pinjam dp ON dp.id_pinjam=p.id_pinjam
  JOIN alat_barang ab ON p.id_alat=ab.id_alat
  JOIN laboratorium l ON ab.id_lab=l.id_lab
  $where ORDER BY p.created_at DESC
");

include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>🕒 Riwayat Peminjaman</h1><p>Semua aktivitas peminjaman alatmu</p></div>
  <a href="pinjam.php" class="btn btn-primary">+ Pinjam Alat</a>
</div>

<div class="toolbar">
  <a href="riwayat.php" class="btn btn-sm <?=!$filter?'btn-primary':'btn-secondary'?>">Semua</a>
  <a href="riwayat.php?f=menunggu" class="btn btn-sm <?=$filter==='menunggu'?'btn-primary':'btn-secondary'?>">⏳ Menunggu</a>
  <a href="riwayat.php?f=dipinjam" class="btn btn-sm <?=$filter==='dipinjam'?'btn-primary':'btn-secondary'?>">📤 Dipinjam</a>
  <a href="riwayat.php?f=selesai" class="btn btn-sm <?=$filter==='selesai'?'btn-primary':'btn-secondary'?>">✅ Selesai</a>
  <div class="ms-auto search-bar" style="max-width:260px;"><span class="search-icon">🔍</span><input type="text" id="table-search" placeholder="Cari alat..."></div>
</div>

<div class="card"><div class="table-wrapper"><table>
  <thead><tr><th>#</th><th>Alat</th><th>Lab</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Tgl Kembali</th><th>Status</th><th>Kondisi</th><th>Denda</th></tr></thead>
  <tbody>
  <?php $i=1; while($row=mysqli_fetch_assoc($data)):
    // Tentukan status tampil
    $st=$row['status_detail']??$row['status_pinjam'];
    $badges=[
      'menunggu'    =>['badge-warning','⏳ Menunggu'],
      'ditolak'     =>['badge-danger','❌ Ditolak'],
      'disetujui'   =>['badge-info','✅ Disetujui'],
      'dipinjam'    =>['badge-orange','📤 Dipinjam'],
      'menunggu_cek'=>['badge-info','🔍 Dicek Admin'],
      'selesai'     =>['badge-success','✅ Selesai'],
    ];
    $b=$badges[$st]??['badge-secondary',$st];
    $kmap=['baik'=>['badge-success','✅ Baik'],'rusak_ringan'=>['badge-warning','⚠️ Ringan'],'rusak_berat'=>['badge-danger','❌ Berat']];
    $k=$kmap[$row['kondisi_kembali']]??null;
  ?>
  <tr data-searchable>
    <td class="row-num"><?=$i++?></td>
    <td><strong><?=htmlspecialchars($row['nama_alat'])?></strong>
      <?php if($row['keperluan']):?><div style="font-size:11px;color:var(--text-muted);"><?=htmlspecialchars($row['keperluan'])?></div><?php endif;?>
    </td>
    <td><span style="font-size:12px;color:var(--text-muted);"><?=htmlspecialchars($row['nama_lab'])?></span></td>
    <td style="font-size:12.5px;"><?=$row['tgl_pinjam']?date('d M Y',strtotime($row['tgl_pinjam'])):'—'?></td>
    <td style="font-size:12.5px;"><?=date('d M Y',strtotime($row['tgl_kembali_rencana']))?></td>
    <td style="font-size:12.5px;"><?=$row['tgl_kembali_aktual']?date('d M Y',strtotime($row['tgl_kembali_aktual'])):'<span style="color:var(--text-muted);">—</span>'?></td>
    <td>
      <span class="badge <?=$b[0]?>"><?=$b[1]?></span>
      <?php if($st==='ditolak'&&$row['alasan_tolak']):?>
        <div style="font-size:11px;color:var(--danger);margin-top:2px;">↳ <?=htmlspecialchars($row['alasan_tolak'])?></div>
      <?php endif;?>
    </td>
    <td><?=$k?'<span class="badge '.$k[0].'">'.$k[1].'</span>':'<span style="color:var(--text-muted);font-size:12px;">—</span>'?></td>
    <td>
      <?php if($row['total_denda']>0):?>
        <div style="font-weight:700;font-size:13px;font-family:var(--font-mono);color:var(--danger);"><?=formatRupiah($row['total_denda'])?></div>
        <?php if($row['denda_lunas']):?><span class="badge badge-success" style="font-size:10px;">Lunas</span>
        <?php else:?><span class="badge badge-danger" style="font-size:10px;">Belum Lunas</span><?php endif;?>
      <?php else:?><span style="color:var(--text-muted);">—</span><?php endif;?>
    </td>
  </tr>
  <?php endwhile; if($i===1):?><tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📋</div><p>Belum ada riwayat peminjaman</p></div></td></tr><?php endif;?>
  </tbody>
</table></div></div>

<?php include '../../includes/footer.php';?>
