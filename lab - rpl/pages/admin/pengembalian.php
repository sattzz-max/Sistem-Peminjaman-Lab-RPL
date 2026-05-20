<?php

session_start();
require_once '../../config/koneksi.php';
requireAdmin();

$page_title  = 'Cek Pengembalian';
$active_menu = 'pengembalian';
$base_url    = '../../';
$breadcrumb  = ['Peminjaman' => null, 'Cek Pengembalian' => null];

$admin_id = (int)$_SESSION['user_id'];

// ── Proses verifikasi pengembalian (admin cek kondisi) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_detail'])) {
    $id_detail      = intval($_POST['id_detail']);
    $id_alat        = intval($_POST['id_alat']);
    $kondisi        = sanitize($conn, $_POST['kondisi_kembali']);
    $catatan        = sanitize($conn, $_POST['catatan_kondisi'] ?? '');
    $tgl_aktual     = sanitize($conn, $_POST['tgl_kembali_aktual']);
    $tgl_rencana    = sanitize($conn, $_POST['tgl_kembali_rencana']);

    // Hitung denda
    $denda = hitungDenda($conn, $tgl_rencana, $tgl_aktual, $kondisi);

    // Update detail_pinjam
    mysqli_query($conn, "UPDATE detail_pinjam SET
        tgl_kembali_aktual  = '$tgl_aktual',
        kondisi_kembali     = '$kondisi',
        catatan_kondisi     = '$catatan',
        denda_terlambat     = {$denda['denda_terlambat']},
        denda_kerusakan     = {$denda['denda_kerusakan']},
        total_denda         = {$denda['total']},
        status              = 'selesai',
        dicek_oleh          = $admin_id
        WHERE id_detail = $id_detail
    ");

    // Update stok alat sesuai kondisi kembali
    if ($kondisi === 'baik') {
        mysqli_query($conn, "UPDATE alat_barang SET jumlah_baik=jumlah_baik+1 WHERE id_alat=$id_alat");
    } elseif ($kondisi === 'rusak_ringan') {
        mysqli_query($conn, "UPDATE alat_barang SET jumlah_rusak_ringan=jumlah_rusak_ringan+1 WHERE id_alat=$id_alat");
    } elseif ($kondisi === 'rusak_berat') {
        mysqli_query($conn, "UPDATE alat_barang SET jumlah_rusak_berat=jumlah_rusak_berat+1 WHERE id_alat=$id_alat");
    }

    // Update status peminjaman induk jadi selesai
    mysqli_query($conn, "UPDATE peminjaman SET status='selesai' WHERE id_pinjam=(
        SELECT id_pinjam FROM detail_pinjam WHERE id_detail=$id_detail
    )");

    $total_fmt = formatRupiah($denda['total']);
    $msg = 'Pengembalian dicatat! Kondisi: <strong>' . ucfirst(str_replace('_',' ',$kondisi)) . '</strong>.';
    if ($denda['total'] > 0) {
        $msg .= ' Total denda: <strong>' . $total_fmt . '</strong>';
    } else {
        $msg .= ' Tidak ada denda. ✅';
    }
    setFlash($denda['total'] > 0 ? 'warning' : 'success', $msg);
    redirect('pengembalian.php');
}

// ── List yang menunggu dicek (status menunggu_cek) ──
$menunggu_cek = mysqli_query($conn, "
  SELECT dp.id_detail, dp.id_alat, dp.tgl_pinjam, dp.tgl_kembali_rencana,
         u.nama as nama_user, ab.nama_alat, l.nama_lab,
         DATEDIFF(NOW(), dp.tgl_pinjam) as hari_pinjam
  FROM detail_pinjam dp
  JOIN peminjaman p ON dp.id_pinjam = p.id_pinjam
  JOIN user u ON p.id_user = u.id_user
  JOIN alat_barang ab ON dp.id_alat = ab.id_alat
  JOIN laboratorium l ON ab.id_lab = l.id_lab
  WHERE dp.status = 'menunggu_cek'
  ORDER BY dp.tgl_kembali_rencana ASC
");

// ── List yang sedang dipinjam (sudah disetujui, belum dikembalikan) ──
$dipinjam = mysqli_query($conn, "
  SELECT dp.id_detail, dp.id_alat, dp.tgl_pinjam, dp.tgl_kembali_rencana,
         u.nama as nama_user, ab.nama_alat, l.nama_lab,
         DATEDIFF(NOW(), dp.tgl_kembali_rencana) as hari_telat
  FROM detail_pinjam dp
  JOIN peminjaman p ON dp.id_pinjam = p.id_pinjam
  JOIN user u ON p.id_user = u.id_user
  JOIN alat_barang ab ON dp.id_alat = ab.id_alat
  JOIN laboratorium l ON ab.id_lab = l.id_lab
  WHERE dp.status = 'dipinjam'
  ORDER BY dp.tgl_kembali_rencana ASC
");

// Ambil config denda untuk preview
$cfg = getDenda($conn);

include '../../includes/header.php';
?>
<?php include '../../includes/flash.php'; ?>

<div class="page-header">
  <h1>🔍 Cek Pengembalian Alat</h1>
  <p>Verifikasi kondisi alat yang dikembalikan siswa & hitung denda otomatis</p>
</div>

<!-- ── Menunggu Dicek ── -->
<?php
$rows_cek = mysqli_fetch_all($menunggu_cek, MYSQLI_ASSOC);
?>
<?php if (!empty($rows_cek)): ?>
<div class="alert alert-warning">
  ⚠️ Ada <strong><?= count($rows_cek) ?></strong> alat yang sudah dikembalikan siswa dan menunggu pemeriksaan admin!
</div>

<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <div class="card-title" style="color:var(--warning);">⏳ Menunggu Pemeriksaan Admin</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>#</th><th>Peminjam</th><th>Alat</th><th>Lab</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Aksi Periksa</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows_cek as $i => $row): ?>
        <tr>
          <td class="row-num"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($row['nama_user']) ?></strong></td>
          <td><strong><?= htmlspecialchars($row['nama_alat']) ?></strong></td>
          <td><span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($row['nama_lab']) ?></span></td>
          <td><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
          <td>
            <?php $telat = strtotime($row['tgl_kembali_rencana']) < time(); ?>
            <span class="<?= $telat?'text-danger':'' ?>"><?= date('d M Y', strtotime($row['tgl_kembali_rencana'])) ?></span>
            <?php if ($telat): ?><span class="badge badge-danger" style="margin-left:4px;">Telat</span><?php endif; ?>
          </td>
          <td>
            <button class="btn btn-primary btn-sm" data-modal="modal-cek-<?= $row['id_detail'] ?>">
              🔍 Periksa & Catat
            </button>
          </td>
        </tr>

        <!-- Modal Pemeriksaan -->
        <div class="modal-backdrop" id="modal-cek-<?= $row['id_detail'] ?>">
          <div class="modal modal-lg">
            <div class="modal-header">
              <h3>🔍 Periksa Pengembalian: <?= htmlspecialchars($row['nama_alat']) ?></h3>
              <button class="modal-close">✕</button>
            </div>
            <form method="POST" action="pengembalian.php" id="form-cek-<?= $row['id_detail'] ?>">
              <input type="hidden" name="id_detail" value="<?= $row['id_detail'] ?>">
              <input type="hidden" name="id_alat" value="<?= $row['id_alat'] ?>">
              <input type="hidden" name="tgl_kembali_rencana" value="<?= $row['tgl_kembali_rencana'] ?>">
              <div class="modal-body">
                <!-- Info Peminjaman -->
                <div style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                  <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Peminjam</div><div style="font-weight:700;"><?= htmlspecialchars($row['nama_user']) ?></div></div>
                  <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Alat</div><div style="font-weight:700;"><?= htmlspecialchars($row['nama_alat']) ?></div></div>
                  <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Tgl Pinjam</div><div><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></div></div>
                  <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Rencana Kembali</div><div style="<?= $telat?'color:var(--danger);font-weight:700;':'' ?>"><?= date('d M Y', strtotime($row['tgl_kembali_rencana'])) ?></div></div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Tanggal Kembali Aktual <span>*</span></label>
                    <input type="date" name="tgl_kembali_aktual"
                      class="form-control" value="<?= date('Y-m-d') ?>"
                      max="<?= date('Y-m-d') ?>"
                      onchange="previewDenda(this, <?= $row['id_detail'] ?>, '<?= $row['tgl_kembali_rencana'] ?>')"
                      required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Kondisi Alat Dikembalikan <span>*</span></label>
                    <select name="kondisi_kembali" class="form-control" required
                      onchange="previewDenda(this, <?= $row['id_detail'] ?>, '<?= $row['tgl_kembali_rencana'] ?>')">
                      <option value="baik">✅ Baik — Tidak ada denda kerusakan</option>
                      <option value="rusak_ringan">⚠️ Rusak Ringan — Denda <?= formatRupiah($cfg['denda_rusak_ringan']??25000) ?></option>
                      <option value="rusak_berat">❌ Rusak Berat — Denda <?= formatRupiah($cfg['denda_rusak_berat']??100000) ?></option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Catatan Kondisi</label>
                  <textarea name="catatan_kondisi" class="form-control" rows="2" placeholder="Contoh: Layar retak, kabel putus, dll." style="resize:none;"></textarea>
                </div>

                <!-- Preview Denda -->
                <div class="denda-box" id="denda-preview-<?= $row['id_detail'] ?>">
                  <div style="font-weight:700;font-size:13px;margin-bottom:10px;color:var(--danger);">💰 Estimasi Denda</div>
                  <div class="denda-row">
                    <span class="denda-label">Denda Keterlambatan</span>
                    <span class="denda-value" id="val-terlambat-<?= $row['id_detail'] ?>">Rp 0</span>
                  </div>
                  <div class="denda-row">
                    <span class="denda-label">Denda Kerusakan</span>
                    <span class="denda-value" id="val-kerusakan-<?= $row['id_detail'] ?>">Rp 0</span>
                  </div>
                  <div class="denda-row">
                    <span class="denda-label">⚡ Total Denda</span>
                    <span class="denda-value" id="val-total-<?= $row['id_detail'] ?>">Rp 0</span>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Hasil Pemeriksaan</button>
              </div>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── Sedang Dipinjam ── -->
<div class="card">
  <div class="card-header">
    <div class="card-title">📋 Alat Sedang Dipinjam</div>
    <span style="font-size:12.5px;color:var(--text-muted);">Siswa perlu kembalikan via menu mereka, lalu admin cek di atas</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>#</th><th>Peminjam</th><th>Alat</th><th>Lab</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Keterlambatan</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while ($row=mysqli_fetch_assoc($dipinjam)): ?>
        <?php $telat = $row['hari_telat'] > 0; ?>
        <tr>
          <td class="row-num"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($row['nama_user']) ?></strong></td>
          <td><?= htmlspecialchars($row['nama_alat']) ?></td>
          <td><span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($row['nama_lab']) ?></span></td>
          <td><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
          <td><?= date('d M Y', strtotime($row['tgl_kembali_rencana'])) ?></td>
          <td>
            <?php if ($telat): ?>
              <span class="badge badge-danger">⚠️ Telat <?= $row['hari_telat'] ?> hari</span>
              <div style="font-size:11.5px;color:var(--danger);margin-top:3px;">
                Estimasi denda: <?= formatRupiah($row['hari_telat'] * ($cfg['denda_terlambat']??2000)) ?>
              </div>
            <?php else: ?>
              <span class="badge badge-success">Tepat waktu</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($i===1): ?>
        <tr><td colspan="7">
          <div class="empty-state"><div class="empty-icon">✅</div><p>Tidak ada alat yang sedang dipinjam</p></div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const cfg = {
  denda_terlambat:   <?= $cfg['denda_terlambat']  ?? 2000  ?>,
  denda_rusak_ringan:<?= $cfg['denda_rusak_ringan']?? 25000 ?>,
  denda_rusak_berat: <?= $cfg['denda_rusak_berat'] ?? 100000?>
};

function previewDenda(el, detailId, tglRencana) {
  const form     = document.getElementById('form-cek-' + detailId);
  const tglAktual= form.querySelector('[name=tgl_kembali_aktual]').value;
  const kondisi  = form.querySelector('[name=kondisi_kembali]').value;

  let dendaTerlambat = 0, dendaKerusakan = 0;
  if (tglAktual && tglRencana) {
    const r = new Date(tglRencana), a = new Date(tglAktual);
    if (a > r) {
      const hari = Math.ceil((a - r) / 86400000);
      dendaTerlambat = hari * cfg.denda_terlambat;
    }
  }
  if (kondisi === 'rusak_ringan') dendaKerusakan = cfg.denda_rusak_ringan;
  else if (kondisi === 'rusak_berat') dendaKerusakan = cfg.denda_rusak_berat;

  const total = dendaTerlambat + dendaKerusakan;
  const fmt = n => 'Rp ' + n.toLocaleString('id-ID');

  document.getElementById('val-terlambat-' + detailId).textContent = fmt(dendaTerlambat);
  document.getElementById('val-kerusakan-' + detailId).textContent = fmt(dendaKerusakan);
  document.getElementById('val-total-'     + detailId).textContent = fmt(total);

  const box = document.getElementById('denda-preview-' + detailId);
  if (total === 0) { box.classList.add('lunas'); } else { box.classList.remove('lunas'); }
}
</script>

<?php include '../../includes/footer.php'; ?>
