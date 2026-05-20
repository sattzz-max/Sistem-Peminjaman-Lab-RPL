<?php
// pages/user/denda.php
session_start();require_once '../../config/koneksi.php';requireLogin();
if(isAdmin()) redirect('../../pages/admin/dashboard.php');
$page_title='Denda Saya';$active_menu='denda_saya';$base_url='../../';
$breadcrumb=['Peminjaman'=>null,'Denda Saya'=>null];
$id_user=(int)$_SESSION['user_id'];

$data=mysqli_query($conn,"
  SELECT dp.id_detail,dp.tgl_pinjam,dp.tgl_kembali_rencana,dp.tgl_kembali_aktual,
         dp.kondisi_kembali,dp.catatan_kondisi,
         dp.denda_terlambat,dp.denda_kerusakan,dp.total_denda,dp.denda_lunas,
         ab.nama_alat
  FROM detail_pinjam dp JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  JOIN alat_barang ab ON dp.id_alat=ab.id_alat
  WHERE p.id_user=$id_user AND dp.total_denda>0
  ORDER BY dp.denda_lunas ASC, dp.total_denda DESC
");

$sum=mysqli_fetch_assoc(mysqli_query($conn,"
  SELECT SUM(CASE WHEN dp.denda_lunas=0 THEN dp.total_denda ELSE 0 END) belum,
         SUM(CASE WHEN dp.denda_lunas=1 THEN dp.total_denda ELSE 0 END) lunas
  FROM detail_pinjam dp JOIN peminjaman p ON dp.id_pinjam=p.id_pinjam
  WHERE p.id_user=$id_user AND dp.total_denda>0
"));

include '../../includes/header.php';?>
<?php include '../../includes/flash.php';?>

<div class="page-header"><h1>💳 Denda Saya</h1><p>Rincian tagihan denda peminjaman alat laboratorium</p></div>

<?php if(($sum['belum']??0)>0):?>
<div class="alert alert-danger">💸 Kamu memiliki tagihan denda sebesar <strong><?=formatRupiah($sum['belum'])?></strong>. Segera bayar ke admin laboratorium!</div>
<?php endif;?>

<div class="stats-grid" style="grid-template-columns:1fr 1fr;">
  <div class="stat-card red"><div class="stat-icon">💸</div><div class="stat-info"><div class="stat-value" style="font-size:18px;"><?=formatRupiah($sum['belum']??0)?></div><div class="stat-label">Belum Dibayar</div></div></div>
  <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-info"><div class="stat-value" style="font-size:18px;"><?=formatRupiah($sum['lunas']??0)?></div><div class="stat-label">Sudah Lunas</div></div></div>
</div>

<div class="card">
  <div class="table-wrapper"><table>
    <thead><tr><th>#</th><th>Alat</th><th>Kondisi Kembali</th><th>Tgl Kembali</th><th>Denda Telat</th><th>Denda Rusak</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
      <?php $i=1; while($row=mysqli_fetch_assoc($data)):
        $kmap=['baik'=>['badge-success','✅ Baik'],'rusak_ringan'=>['badge-warning','⚠️ Ringan'],'rusak_berat'=>['badge-danger','❌ Berat']];
        $k=$kmap[$row['kondisi_kembali']]??['badge-secondary','—'];
      ?>
      <tr>
        <td class="row-num"><?=$i++?></td>
        <td><strong><?=htmlspecialchars($row['nama_alat'])?></strong>
          <?php if($row['catatan_kondisi']):?><div style="font-size:11px;color:var(--text-muted);"><?=htmlspecialchars($row['catatan_kondisi'])?></div><?php endif;?>
        </td>
        <td><span class="badge <?=$k[0]?>"><?=$k[1]?></span></td>
        <td style="font-size:12.5px;"><?=$row['tgl_kembali_aktual']?date('d M Y',strtotime($row['tgl_kembali_aktual'])):'—'?></td>
        <td style="font-family:var(--font-mono);font-size:12.5px;<?=$row['denda_terlambat']>0?'color:var(--danger);':''?>"><?=formatRupiah($row['denda_terlambat'])?></td>
        <td style="font-family:var(--font-mono);font-size:12.5px;<?=$row['denda_kerusakan']>0?'color:var(--danger);':''?>"><?=formatRupiah($row['denda_kerusakan'])?></td>
        <td><strong style="font-family:var(--font-mono);font-size:14px;color:var(--danger);"><?=formatRupiah($row['total_denda'])?></strong></td>
        <td><?=$row['denda_lunas']?'<span class="badge badge-success">✅ Lunas</span>':'<span class="badge badge-danger">💸 Belum Lunas</span>'?></td>
      </tr>
      <?php endwhile; if($i===1):?><tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🎉</div><p>Tidak ada tagihan denda!</p></div></td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>

<div class="card" style="margin-top:20px;">
  <div class="card-body" style="background:var(--warning-light);border-radius:var(--radius);">
    <div style="font-size:13.5px;color:#92400e;font-weight:600;margin-bottom:6px;">💡 Cara Menghindari Denda</div>
    <div style="font-size:13px;color:#92400e;display:flex;flex-direction:column;gap:6px;">
      <div>✅ Kembalikan alat sebelum atau tepat pada tanggal yang dijanjikan</div>
      <div>✅ Jaga alat dengan baik selama peminjaman</div>
      <div>✅ Laporkan kerusakan sesegera mungkin ke admin</div>
      <div>✅ Bayar denda segera setelah mendapat tagihan</div>
    </div>
  </div>
</div>
<?php include '../../includes/footer.php';?>
