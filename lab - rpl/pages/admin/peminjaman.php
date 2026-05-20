<?php
session_start();
require_once '../../config/koneksi.php';
requireAdmin();
$page_title='Semua Peminjaman';$active_menu='peminjaman';$base_url='../../';
$breadcrumb=['Peminjaman'=>null,'Semua Data'=>null];
$search=sanitize($conn,$_GET['q']??'');$filter=sanitize($conn,$_GET['status']??'');
$where="WHERE 1=1";
if($search) $where.=" AND (u.nama LIKE '%$search%' OR ab.nama_alat LIKE '%$search%')";
if($filter) $where.=" AND dp.status='$filter'";
$data=mysqli_query($conn,"SELECT dp.*,u.nama as nama_user,ab.nama_alat,l.nama_lab,p.status as status_pinjam,p.tgl_kembali_rencana as tgl_rencana
  FROM detail_pinjam dp JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  JOIN user u ON p.id_user=u.id_user JOIN alat_barang ab ON dp.id_alat=ab.id_alat
  JOIN laboratorium l ON ab.id_lab=l.id_lab $where ORDER BY dp.id_detail DESC");
include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>
<div class="page-header"><h1>📋 Semua Data Peminjaman</h1><p>Pantau seluruh aktivitas peminjaman alat</p></div>
<form class="toolbar" method="GET">
  <div class="search-bar" style="flex:1;max-width:360px;"><span class="search-icon">🔍</span><input type="text" name="q" placeholder="Cari siswa atau alat..." value="<?=htmlspecialchars($search)?>"></div>
  <select name="status" class="form-control" style="width:auto;padding:9px 14px;" onchange="this.form.submit()">
    <option value="">Semua Status</option>
    <option value="dipinjam" <?=$filter==='dipinjam'?'selected':''?>>⏳ Dipinjam</option>
    <option value="menunggu_cek" <?=$filter==='menunggu_cek'?'selected':''?>>🔍 Menunggu Cek</option>
    <option value="selesai" <?=$filter==='selesai'?'selected':''?>>✅ Selesai</option>
  </select>
  <?php if($search||$filter):?><a href="peminjaman.php" class="btn btn-secondary btn-sm">✕ Reset</a><?php endif;?>
</form>
<div class="card"><div class="table-wrapper"><table>
  <thead><tr><th>#</th><th>Peminjam</th><th>Alat</th><th>Lab</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Kembali Aktual</th><th>Status</th><th>Denda</th></tr></thead>
  <tbody>
    <?php $i=1; while($row=mysqli_fetch_assoc($data)):?>
    <tr data-searchable>
      <td class="row-num"><?=$i++?></td>
      <td><strong><?=htmlspecialchars($row['nama_user'])?></strong></td>
      <td><?=htmlspecialchars($row['nama_alat'])?></td>
      <td><span style="font-size:12px;color:var(--text-muted);"><?=htmlspecialchars($row['nama_lab'])?></span></td>
      <td><?=date('d M Y',strtotime($row['tgl_pinjam']))?></td>
      <td><?=date('d M Y',strtotime($row['tgl_rencana']))?></td>
      <td><?=$row['tgl_kembali_aktual']?date('d M Y',strtotime($row['tgl_kembali_aktual'])):'<span style="color:var(--text-muted);">—</span>'?></td>
      <td><?php
        $bs=['dipinjam'=>['badge-warning','⏳ Dipinjam'],'menunggu_cek'=>['badge-info','🔍 Dicek'],'selesai'=>['badge-success','✅ Selesai']];
        $st=$bs[$row['status']]??['badge-secondary',$row['status']];
      ?><span class="badge <?=$st[0]?>"><?=$st[1]?></span></td>
      <td style="font-family:var(--font-mono);font-size:12.5px;">
        <?php if($row['total_denda']>0):?>
          <span style="color:var(--danger);"><?=formatRupiah($row['total_denda'])?></span>
          <?php if($row['denda_lunas']):?><span class="badge badge-success" style="margin-left:4px;font-size:10px;">Lunas</span><?php endif;?>
        <?php else:?>—<?php endif;?>
      </td>
    </tr>
    <?php endwhile; if($i===1):?><tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📋</div><p>Tidak ada data</p></div></td></tr><?php endif;?>
  </tbody>
</table></div></div>
<?php include '../../includes/footer.php';?>
