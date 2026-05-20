<?php

session_start();
require_once '../../config/koneksi.php';
requireAdmin();

$page_title  = 'Persetujuan Peminjaman';
$active_menu = 'persetujuan';
$base_url    = '../../';
$breadcrumb  = ['Peminjaman' => null, 'Persetujuan' => null];

$admin_id = (int)$_SESSION['user_id'];

// ── Setujui ──
if (isset($_GET['setujui'])) {
    $id = intval($_GET['setujui']);
    $p  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id_pinjam=$id AND status='menunggu'"));
    if ($p) {
        // Kurangi stok baik
        $alat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM alat_barang WHERE id_alat={$p['id_alat']}"));
        if ($alat && $alat['jumlah_baik'] > 0) {
            mysqli_query($conn, "UPDATE alat_barang SET jumlah_baik=jumlah_baik-1 WHERE id_alat={$p['id_alat']}");
            mysqli_query($conn, "UPDATE peminjaman SET status='disetujui', disetujui_oleh=$admin_id WHERE id_pinjam=$id");
            // Buat detail_pinjam
            $tgl_p = sanitize($conn, $p['tgl_pinjam']);
            $tgl_r = sanitize($conn, $p['tgl_kembali_rencana']);
            mysqli_query($conn, "INSERT INTO detail_pinjam (id_pinjam,id_alat,tgl_pinjam,tgl_kembali_rencana,status)
                VALUES ($id,{$p['id_alat']},'$tgl_p','$tgl_r','dipinjam')");
            setFlash('success', 'Peminjaman disetujui! Stok alat berkurang 1.');
        } else {
            setFlash('danger', 'Stok alat kondisi baik sudah habis! Tidak bisa disetujui.');
        }
    }
    redirect('persetujuan.php');
}

// ── Tolak ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tolak_id'])) {
    $id     = intval($_POST['tolak_id']);
    $alasan = sanitize($conn, $_POST['alasan_tolak'] ?? 'Tidak memenuhi syarat.');
    mysqli_query($conn, "UPDATE peminjaman SET status='ditolak', alasan_tolak='$alasan' WHERE id_pinjam=$id AND status='menunggu'");
    setFlash('warning', 'Peminjaman ditolak.');
    redirect('persetujuan.php');
}

$filter = sanitize($conn, $_GET['f'] ?? 'menunggu');
$where  = in_array($filter, ['menunggu','disetujui','ditolak','selesai']) ? "WHERE p.status='$filter'" : '';
$data   = mysqli_query($conn, "
  SELECT p.*, u.nama as nama_user, ab.nama_alat, ab.jumlah_baik, l.nama_lab
  FROM peminjaman p
  JOIN user u ON p.id_user=u.id_user
  JOIN alat_barang ab ON p.id_alat=ab.id_alat
  JOIN laboratorium l ON ab.id_lab=l.id_lab
  $where ORDER BY p.created_at DESC
");

include '../../includes/header.php';
?>
<?php include '../../includes/flash.php'; ?>

<div class="page-header">
  <h1>✅ Persetujuan Peminjaman</h1>
  <p>Review dan setujui/tolak pengajuan peminjaman dari siswa</p>
</div>

<!-- Filter Tab -->
<div class="toolbar">
  <?php foreach (['menunggu'=>'⏳ Menunggu','disetujui'=>'✅ Disetujui','ditolak'=>'❌ Ditolak','selesai'=>'🏁 Selesai'] as $k=>$v): ?>
  <a href="persetujuan.php?f=<?= $k ?>" class="btn btn-sm <?= $filter===$k?'btn-primary':'btn-secondary' ?>"><?= $v ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Peminjam</th><th>Alat</th><th>Lab</th>
          <th>Tgl Pinjam</th><th>Tgl Rencana Kembali</th><th>Keperluan</th>
          <th>Stok Baik</th><th>Status</th><th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; while ($row=mysqli_fetch_assoc($data)): ?>
        <tr data-searchable>
          <td class="row-num"><?= $i++ ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:28px;height:28px;background:linear-gradient(135deg,#7c3aed,#a855f7);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700;">
                <?= strtoupper(substr($row['nama_user'],0,1)) ?>
              </div>
              <strong><?= htmlspecialchars($row['nama_user']) ?></strong>
            </div>
          </td>
          <td><?= htmlspecialchars($row['nama_alat']) ?></td>
          <td><span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($row['nama_lab']) ?></span></td>
          <td><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
          <td><?= date('d M Y', strtotime($row['tgl_kembali_rencana'])) ?></td>
          <td><span style="font-size:12.5px;color:var(--text-secondary);"><?= htmlspecialchars($row['keperluan'] ?: '—') ?></span></td>
          <td>
            <span class="badge <?= $row['jumlah_baik']>0?'badge-success':'badge-danger' ?>">
              <?= $row['jumlah_baik'] ?> unit
            </span>
          </td>
          <td>
            <?php $badges = [
              'menunggu'  => 'badge-warning',
              'disetujui' => 'badge-success',
              'ditolak'   => 'badge-danger',
              'selesai'   => 'badge-secondary',
            ]; ?>
            <span class="badge <?= $badges[$row['status']] ?? 'badge-secondary' ?>">
              <?= ucfirst($row['status']) ?>
            </span>
            <?php if ($row['status']==='ditolak' && $row['alasan_tolak']): ?>
            <div style="font-size:11px;color:var(--danger);margin-top:3px;">↳ <?= htmlspecialchars($row['alasan_tolak']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($row['status'] === 'menunggu'): ?>
            <div class="td-actions">
              <a href="persetujuan.php?setujui=<?= $row['id_pinjam'] ?>"
                 class="btn btn-success btn-sm"
                 data-confirm="Setujui peminjaman dari <?= htmlspecialchars($row['nama_user']) ?>?">
                ✅ Setujui
              </a>
              <button class="btn btn-danger btn-sm"
                data-modal="modal-tolak-<?= $row['id_pinjam'] ?>">
                ❌ Tolak
              </button>
            </div>
            <?php else: ?>
              <span style="font-size:12px;color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
        </tr>

        <!-- Modal Tolak -->
        <?php if ($row['status'] === 'menunggu'): ?>
        <div class="modal-backdrop" id="modal-tolak-<?= $row['id_pinjam'] ?>">
          <div class="modal">
            <div class="modal-header">
              <h3>❌ Tolak Peminjaman</h3>
              <button class="modal-close">✕</button>
            </div>
            <form method="POST" action="persetujuan.php">
              <input type="hidden" name="tolak_id" value="<?= $row['id_pinjam'] ?>">
              <div class="modal-body">
                <p style="font-size:13.5px;margin-bottom:16px;">
                  Tolak pengajuan <strong><?= htmlspecialchars($row['nama_user']) ?></strong>
                  untuk meminjam <strong><?= htmlspecialchars($row['nama_alat']) ?></strong>?
                </p>
                <div class="form-group">
                  <label class="form-label">Alasan Penolakan <span>*</span></label>
                  <textarea name="alasan_tolak" class="form-control" rows="3"
                    placeholder="Contoh: Stok kondisi baik habis / alat sedang diperbaiki..."
                    required style="resize:none;"></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-danger">Konfirmasi Tolak</button>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <?php endwhile; ?>
        <?php if ($i===1): ?>
        <tr><td colspan="10">
          <div class="empty-state"><div class="empty-icon">📋</div><p>Tidak ada data dengan status "<?= $filter ?>"</p></div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
