<?php

session_start();
require_once '../../config/koneksi.php';
requireAdmin();

$page_title  = 'Atur Denda';
$active_menu = 'denda';
$base_url    = '../../';
$breadcrumb  = ['Manajemen' => null, 'Konfigurasi Denda' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['nilai'] as $kode => $nilai) {
        $kode  = sanitize($conn, $kode);
        $nilai = floatval($nilai);
        mysqli_query($conn, "UPDATE konfigurasi_denda SET nilai=$nilai WHERE kode='$kode'");
    }
    setFlash('success', 'Konfigurasi denda berhasil disimpan!');
    redirect('denda.php');
}

$configs = mysqli_query($conn, "SELECT * FROM konfigurasi_denda ORDER BY id_config");

include '../../includes/header.php';
?>
<?php include '../../includes/flash.php'; ?>

<div class="page-header">
  <h1>⚙️ Konfigurasi Denda</h1>
  <p>Atur nominal denda keterlambatan dan kerusakan alat</p>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">

  <div class="card">
    <div class="card-header">
      <div class="card-title">💰 Nominal Denda</div>
    </div>
    <div class="card-body">
      <form method="POST">
        <?php while ($cfg = mysqli_fetch_assoc($configs)): ?>
        <div class="form-group">
          <label class="form-label">
            <?php
            $icons = [
              'denda_terlambat'    => '⏰',
              'denda_rusak_ringan' => '⚠️',
              'denda_rusak_berat'  => '❌',
            ];
            echo ($icons[$cfg['kode']] ?? '💰') . ' ' . htmlspecialchars($cfg['nama']);
            ?>
          </label>
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="background:var(--bg);border:1.5px solid var(--border);padding:10px 14px;border-radius:var(--radius-sm) 0 0 var(--radius-sm);font-weight:600;color:var(--text-secondary);white-space:nowrap;border-right:none;">Rp</span>
            <input type="number" name="nilai[<?= $cfg['kode'] ?>]"
              class="form-control" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0;"
              value="<?= number_format($cfg['nilai'],0,'','') ?>"
              min="0" step="500" required>
          </div>
          <div class="form-text">Satuan: <?= htmlspecialchars($cfg['satuan']) ?> — <?= htmlspecialchars($cfg['keterangan']) ?></div>
        </div>
        <?php endwhile; ?>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
          💾 Simpan Konfigurasi
        </button>
      </form>
    </div>
  </div>

  <!-- Info box -->
  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card">
      <div class="card-header"><div class="card-title">📖 Cara Perhitungan</div></div>
      <div class="card-body" style="font-size:13.5px;line-height:1.8;">
        <div style="margin-bottom:12px;">
          <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px;">⏰ Denda Keterlambatan</div>
          <div style="color:var(--text-secondary);">Nominal × Jumlah hari telat</div>
          <div style="background:var(--bg);padding:8px 12px;border-radius:6px;margin-top:6px;font-family:var(--font-mono);font-size:12px;color:var(--primary);">
            Rp 2.000 × 3 hari = Rp 6.000
          </div>
        </div>
        <div style="margin-bottom:12px;">
          <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px;">⚠️ Denda Rusak Ringan</div>
          <div style="color:var(--text-secondary);">Nominal flat per item dikembalikan rusak ringan</div>
        </div>
        <div>
          <div style="font-weight:700;color:var(--text-primary);margin-bottom:4px;">❌ Denda Rusak Berat</div>
          <div style="color:var(--text-secondary);">Nominal flat per item dikembalikan rusak berat</div>
        </div>
        <div style="margin-top:16px;padding:10px;background:var(--warning-light);border-radius:6px;border-left:3px solid var(--warning);">
          <div style="font-size:12px;color:#92400e;font-weight:600;">⚠️ Catatan</div>
          <div style="font-size:12px;color:#92400e;margin-top:2px;">Denda bisa kombinasi — terlambat DAN rusak dihitung keduanya</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">📊 Ringkasan Denda Aktif</div></div>
      <div class="card-body">
        <?php
        $sum_denda = mysqli_fetch_assoc(mysqli_query($conn,
          "SELECT SUM(total_denda) as total, SUM(denda_lunas=0 AND total_denda>0) as belum_lunas
           FROM detail_pinjam WHERE status='selesai'"));
        ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;font-size:13.5px;">
            <span style="color:var(--text-secondary);">Total tagihan denda</span>
            <strong><?= formatRupiah($sum_denda['total'] ?? 0) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13.5px;">
            <span style="color:var(--text-secondary);">Siswa belum bayar</span>
            <span class="badge badge-danger"><?= $sum_denda['belum_lunas'] ?? 0 ?> tagihan</span>
          </div>
          <a href="denda_siswa.php" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:4px;">
            💳 Lihat Tagihan Siswa
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
