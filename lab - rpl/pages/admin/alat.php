<?php

session_start();
require_once '../../config/koneksi.php';
requireAdmin();

$page_title  = 'Data Alat & Barang';
$active_menu = 'alat';
$base_url    = '../../';
$breadcrumb  = ['Manajemen' => null, 'Alat & Barang' => null];

$action = $_GET['action'] ?? 'list';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama          = sanitize($conn, $_POST['nama_alat']);
    $baik          = max(0, intval($_POST['jumlah_baik']));
    $rusak_ringan  = max(0, intval($_POST['jumlah_rusak_ringan']));
    $rusak_berat   = max(0, intval($_POST['jumlah_rusak_berat']));
    $id_lab        = intval($_POST['id_lab']);
    mysqli_query($conn, "INSERT INTO alat_barang (nama_alat,jumlah_baik,jumlah_rusak_ringan,jumlah_rusak_berat,id_lab)
        VALUES ('$nama',$baik,$rusak_ringan,$rusak_berat,$id_lab)");
    setFlash('success', 'Alat berhasil ditambahkan!');
    redirect('alat.php');
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = intval($_POST['id_alat']);
    $nama          = sanitize($conn, $_POST['nama_alat']);
    $baik          = max(0, intval($_POST['jumlah_baik']));
    $rusak_ringan  = max(0, intval($_POST['jumlah_rusak_ringan']));
    $rusak_berat   = max(0, intval($_POST['jumlah_rusak_berat']));
    $id_lab        = intval($_POST['id_lab']);
    mysqli_query($conn, "UPDATE alat_barang SET nama_alat='$nama',jumlah_baik=$baik,
        jumlah_rusak_ringan=$rusak_ringan,jumlah_rusak_berat=$rusak_berat,id_lab=$id_lab
        WHERE id_alat=$id");
    setFlash('success', 'Data alat berhasil diperbarui!');
    redirect('alat.php');
}

if ($action === 'delete') {
    $id = intval($_GET['id']);
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM detail_pinjam WHERE id_alat=$id AND status='dipinjam'"));
    if ($cek['c'] > 0) {
        setFlash('danger', 'Alat tidak bisa dihapus — sedang dipinjam!');
    } else {
        mysqli_query($conn, "DELETE FROM alat_barang WHERE id_alat=$id");
        setFlash('success', 'Alat berhasil dihapus.');
    }
    redirect('alat.php');
}

$edit_alat = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $edit_alat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM alat_barang WHERE id_alat=$id"));
}

$labs   = mysqli_query($conn, "SELECT * FROM laboratorium ORDER BY nama_lab");
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = $search ? "WHERE ab.nama_alat LIKE '%$search%'" : '';
$alat   = mysqli_query($conn, "
  SELECT ab.*, l.nama_lab,
         (ab.jumlah_baik+ab.jumlah_rusak_ringan+ab.jumlah_rusak_berat) as total_stok
  FROM alat_barang ab JOIN laboratorium l ON ab.id_lab=l.id_lab
  $where ORDER BY ab.id_alat DESC
");

include '../../includes/header.php';
?>
<?php include '../../includes/flash.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div><h1>🔧 Alat & Barang</h1><p>Inventaris peralatan — stok dipisah per kondisi</p></div>
  <button class="btn btn-primary" data-modal="modal-add">+ Tambah Alat</button>
</div>

<form class="toolbar" method="GET">
  <div class="search-bar" style="flex:1;max-width:360px;">
    <span class="search-icon">🔍</span>
    <input type="text" name="q" id="table-search" placeholder="Cari nama alat..." value="<?= htmlspecialchars($search) ?>">
  </div>
  <?php if ($search): ?><a href="alat.php" class="btn btn-secondary btn-sm">✕ Reset</a><?php endif; ?>
</form>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama Alat</th>
          <th>Laboratorium</th>
          <th>Stok per Kondisi</th>
          <th>Total</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; while ($a = mysqli_fetch_assoc($alat)): ?>
        <tr data-searchable>
          <td class="row-num"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($a['nama_alat']) ?></strong></td>
          <td><span style="font-size:12.5px;color:var(--text-muted);">🏫 <?= htmlspecialchars($a['nama_lab']) ?></span></td>
          <td>
            <div class="stok-box">
              <span class="stok-chip baik">✅ Baik: <?= $a['jumlah_baik'] ?></span>
              <?php if ($a['jumlah_rusak_ringan'] > 0): ?>
              <span class="stok-chip ringan">⚠️ Ringan: <?= $a['jumlah_rusak_ringan'] ?></span>
              <?php endif; ?>
              <?php if ($a['jumlah_rusak_berat'] > 0): ?>
              <span class="stok-chip berat">❌ Berat: <?= $a['jumlah_rusak_berat'] ?></span>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <span class="badge <?= $a['total_stok']>0 ? 'badge-info' : 'badge-danger' ?>">
              <?= $a['total_stok'] ?> unit
            </span>
          </td>
          <td>
            <div class="td-actions">
              <a href="alat.php?action=edit&id=<?= $a['id_alat'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
              <a href="alat.php?action=delete&id=<?= $a['id_alat'] ?>"
                 class="btn btn-danger btn-sm"
                 data-confirm="Hapus alat '<?= htmlspecialchars($a['nama_alat']) ?>'?">🗑️</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($i===1): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🔧</div><p>Belum ada data alat</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Modal Tambah ── -->
<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Tambah Alat/Barang</h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST" action="alat.php?action=create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Alat <span>*</span></label>
          <input type="text" name="nama_alat" class="form-control" placeholder="Contoh: Laptop ASUS" required>
        </div>
        <div class="form-group">
          <label class="form-label">Laboratorium <span>*</span></label>
          <select name="id_lab" class="form-control" required>
            <option value="">-- Pilih Lab --</option>
            <?php mysqli_data_seek($labs,0); while ($lab=mysqli_fetch_assoc($labs)): ?>
            <option value="<?= $lab['id_lab'] ?>"><?= htmlspecialchars($lab['nama_lab']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- REVISI 1: Input stok per kondisi -->
        <div class="kondisi-group">
          <div class="kondisi-group-header">📦 Jumlah Stok per Kondisi</div>
          <div class="kondisi-row">
            <div class="kondisi-dot baik"></div>
            <label>Kondisi Baik</label>
            <input type="number" name="jumlah_baik" id="jumlah_baik" min="0" value="0" class="form-control" style="width:90px;">
          </div>
          <div class="kondisi-row">
            <div class="kondisi-dot ringan"></div>
            <label>Rusak Ringan</label>
            <input type="number" name="jumlah_rusak_ringan" id="jumlah_rusak_ringan" min="0" value="0" class="form-control" style="width:90px;">
          </div>
          <div class="kondisi-row">
            <div class="kondisi-dot berat"></div>
            <label>Rusak Berat</label>
            <input type="number" name="jumlah_rusak_berat" id="jumlah_rusak_berat" min="0" value="0" class="form-control" style="width:90px;">
          </div>
        </div>
        <div style="background:var(--primary-50);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--primary);">
          Total Stok: <strong id="stok-total-preview">0</strong> unit
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Alat</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Modal Edit ── -->
<?php if ($edit_alat): ?>
<div class="modal-backdrop show">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Edit Alat: <?= htmlspecialchars($edit_alat['nama_alat']) ?></h3>
      <a href="alat.php" style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--bg);border-radius:6px;color:var(--text-secondary);">✕</a>
    </div>
    <form method="POST" action="alat.php?action=edit">
      <input type="hidden" name="id_alat" value="<?= $edit_alat['id_alat'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Alat</label>
          <input type="text" name="nama_alat" class="form-control" value="<?= htmlspecialchars($edit_alat['nama_alat']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Laboratorium</label>
          <select name="id_lab" class="form-control">
            <?php mysqli_data_seek($labs,0); while ($lab=mysqli_fetch_assoc($labs)): ?>
            <option value="<?= $lab['id_lab'] ?>" <?= $lab['id_lab']==$edit_alat['id_lab']?'selected':'' ?>>
              <?= htmlspecialchars($lab['nama_lab']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- REVISI 1: Edit stok per kondisi -->
        <div class="kondisi-group">
          <div class="kondisi-group-header">📦 Edit Stok per Kondisi</div>
          <div class="kondisi-row">
            <div class="kondisi-dot baik"></div>
            <label>Kondisi Baik</label>
            <input type="number" name="jumlah_baik" id="jumlah_baik" min="0"
              value="<?= $edit_alat['jumlah_baik'] ?>" class="form-control" style="width:90px;">
          </div>
          <div class="kondisi-row">
            <div class="kondisi-dot ringan"></div>
            <label>Rusak Ringan</label>
            <input type="number" name="jumlah_rusak_ringan" id="jumlah_rusak_ringan" min="0"
              value="<?= $edit_alat['jumlah_rusak_ringan'] ?>" class="form-control" style="width:90px;">
          </div>
          <div class="kondisi-row">
            <div class="kondisi-dot berat"></div>
            <label>Rusak Berat</label>
            <input type="number" name="jumlah_rusak_berat" id="jumlah_rusak_berat" min="0"
              value="<?= $edit_alat['jumlah_rusak_berat'] ?>" class="form-control" style="width:90px;">
          </div>
        </div>
        <div style="background:var(--primary-50);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--primary);">
          Total Stok: <strong id="stok-total-preview">
            <?= $edit_alat['jumlah_baik']+$edit_alat['jumlah_rusak_ringan']+$edit_alat['jumlah_rusak_berat'] ?>
          </strong> unit
        </div>
      </div>
      <div class="modal-footer">
        <a href="alat.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
