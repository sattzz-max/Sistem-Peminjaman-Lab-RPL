<?php

session_start();
require_once '../../config/koneksi.php';
requireAdmin();

$page_title  = 'Tagihan Denda Siswa';
$active_menu = 'denda_siswa';
$base_url    = '../../';
$breadcrumb  = ['Peminjaman' => null, 'Tagihan Denda' => null];

// Tandai lunas
if (isset($_GET['lunas'])) {
    $id = intval($_GET['lunas']);
    mysqli_query($conn, "UPDATE detail_pinjam SET denda_lunas=1 WHERE id_detail=$id");
    setFlash('success', 'Denda ditandai lunas!');
    redirect('denda_siswa.php');
}

$filter = sanitize($conn, $_GET['f'] ?? 'belum');
$where  = $filter === 'lunas'
    ? "WHERE dp.total_denda > 0 AND dp.denda_lunas = 1"
    : "WHERE dp.total_denda > 0 AND dp.denda_lunas = 0";

$data = mysqli_query($conn, "
  SELECT dp.id_detail, dp.tgl_pinjam, dp.tgl_kembali_rencana, dp.tgl_kembali_aktual,
         dp.kondisi_kembali, dp.catatan_kondisi,
         dp.denda_terlambat, dp.denda_kerusakan, dp.total_denda, dp.denda_lunas,
         u.nama as nama_user, u.id_user,
         ab.nama_alat
  FROM detail_pinjam dp
  JOIN peminjaman p ON dp.id_pinjam = p.id_pinjam
  JOIN user u ON p.id_user = u.id_user
  JOIN alat_barang ab ON dp.id_alat = ab.id_alat
  $where
  ORDER BY dp.denda_lunas ASC, dp.total_denda DESC
");

// Total stats
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT
    SUM(CASE WHEN denda_lunas=0 AND total_denda>0 THEN total_denda ELSE 0 END) as belum_lunas,
    SUM(CASE WHEN denda_lunas=1 THEN total_denda ELSE 0 END) as sudah_lunas,
    COUNT(CASE WHEN denda_lunas=0 AND total_denda>0 THEN 1 END) as jml_belum,
    COUNT(CASE WHEN denda_lunas=1 THEN 1 END) as jml_lunas
  FROM detail_pinjam WHERE total_denda > 0
"));

include '../../includes/header.php';
?>
<?php include '../../includes/flash.php'; ?>

<div class="page-header">
  <h1>💰 Tagihan Denda Siswa</h1>
  <p>Kelola pembayaran denda keterlambatan dan kerusakan alat</p>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
  <div class="stat-card red">
    <div class="stat-icon">💸</div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:18px;"><?= formatRupiah($stats['belum_lunas']??0) ?></div>
      <div class="stat-label">Belum Dibayar</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">✅</div>
    <div class="stat-info">
      <div class="stat-value" style="font-size:18px;"><?= formatRupiah($stats['sudah_lunas']??0) ?></div>
      <div class="stat-label">Sudah Lunas</div>
    </div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon">📋</div>
    <div class="stat-info">
      <div class="stat-value"><?= $stats['jml_belum']??0 ?></div>
      <div class="stat-label">Tagihan Aktif</div>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="toolbar">
  <a href="denda_siswa.php?f=belum" class="btn btn-sm <?= $filter==='belum'?'btn-danger':'btn-secondary' ?>">💸 Belum Lunas</a>
  <a href="denda_siswa.php?f=lunas" class="btn btn-sm <?= $filter==='lunas'?'btn-success':'btn-secondary' ?>">✅ Sudah Lunas</a>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Siswa</th><th>Alat</th><th>Kondisi Kembali</th>
          <th>Denda Terlambat</th><th>Denda Rusak</th><th>Total Denda</th>
          <th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; while ($row=mysqli_fetch_assoc($data)): ?>
        <tr data-searchable>
          <td class="row-num"><?= $i++ ?></td>
          <td>
            <div style="font-weight:700;"><?= htmlspecialchars($row['nama_user']) ?></div>
            <div style="font-size:11.5px;color:var(--text-muted);">
              Kembali: <?= $row['tgl_kembali_aktual'] ? date('d M Y',strtotime($row['tgl_kembali_aktual'])) : '—' ?>
            </div>
          </td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($row['nama_alat']) ?></div>
            <?php if ($row['catatan_kondisi']): ?>
            <div style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($row['catatan_kondisi']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php
            $kmap = ['baik'=>['badge-success','✅ Baik'],'rusak_ringan'=>['badge-warning','⚠️ Ringan'],'rusak_berat'=>['badge-danger','❌ Berat']];
            $k = $kmap[$row['kondisi_kembali']] ?? ['badge-secondary','—'];
            ?>
            <span class="badge <?= $k[0] ?>"><?= $k[1] ?></span>
          </td>
          <td style="font-family:var(--font-mono);font-size:13px;">
            <?php if ($row['denda_terlambat'] > 0): ?>
              <span style="color:var(--danger);"><?= formatRupiah($row['denda_terlambat']) ?></span>
            <?php else: ?>
              <span style="color:var(--text-muted);">Rp 0</span>
            <?php endif; ?>
          </td>
          <td style="font-family:var(--font-mono);font-size:13px;">
            <?php if ($row['denda_kerusakan'] > 0): ?>
              <span style="color:var(--danger);"><?= formatRupiah($row['denda_kerusakan']) ?></span>
            <?php else: ?>
              <span style="color:var(--text-muted);">Rp 0</span>
            <?php endif; ?>
          </td>
          <td>
            <strong style="font-family:var(--font-mono);font-size:14px;color:var(--danger);">
              <?= formatRupiah($row['total_denda']) ?>
            </strong>
          </td>
          <td>
            <?php if ($row['denda_lunas']): ?>
              <span class="badge badge-success">✅ Lunas</span>
            <?php else: ?>
              <span class="badge badge-danger">💸 Belum</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$row['denda_lunas']): ?>
            <a href="denda_siswa.php?lunas=<?= $row['id_detail'] ?>"
               class="btn btn-success btn-sm"
               data-confirm="Tandai denda <?= htmlspecialchars($row['nama_user']) ?> sebagai LUNAS?">
              ✅ Lunas
            </a>
            <?php else: ?>
              <span style="font-size:12px;color:var(--text-muted);">Selesai</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($i===1): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <div class="empty-icon">💰</div>
            <p><?= $filter==='lunas' ? 'Belum ada denda yang lunas' : 'Tidak ada tagihan denda aktif 🎉' ?></p>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
