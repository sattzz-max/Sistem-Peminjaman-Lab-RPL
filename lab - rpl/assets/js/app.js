// assets/js/app.js
document.addEventListener('DOMContentLoaded', function () {

  // Sidebar mobile
  const hamburger = document.getElementById('hamburger');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebar-overlay');
  if (hamburger) {
    hamburger.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('show'); });
  }
  if (overlay) {
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('show'); });
  }

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => { a.style.transition='opacity .5s,transform .5s'; a.style.opacity='0'; a.style.transform='translateY(-8px)'; setTimeout(()=>a.remove(),500); }, 5000);
  });

  // Confirm delete
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      if (!confirm(this.dataset.confirm || 'Yakin ingin menghapus data ini?')) e.preventDefault();
    });
  });

  // Table search
  const si = document.getElementById('table-search');
  if (si) {
    si.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('tbody tr[data-searchable]').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // Password toggle
  document.querySelectorAll('[data-pw-toggle]').forEach(btn => {
    btn.addEventListener('click', function() {
      const inp = document.getElementById(this.dataset.pwToggle);
      if (!inp) return;
      inp.type = inp.type === 'password' ? 'text' : 'password';
      this.textContent = inp.type === 'password' ? '👁️' : '🙈';
    });
  });

  // Modal
  document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', function() {
      const m = document.getElementById(this.dataset.modal);
      if (m) m.classList.add('show');
    });
  });
  document.querySelectorAll('.modal-close,[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', function() {
      const m = this.closest('.modal-backdrop');
      if (m) m.classList.remove('show');
    });
  });
  document.querySelectorAll('.modal-backdrop').forEach(b => {
    b.addEventListener('click', function(e) { if (e.target===this) this.classList.remove('show'); });
  });

  // Animate cards
  document.querySelectorAll('.stat-card,.card').forEach((c,i) => {
    c.style.animationDelay = (i*.05)+'s'; c.classList.add('fade-in');
  });

  // Stok total preview (REVISI 1)
  function updateStokTotal() {
    const baik   = parseInt(document.getElementById('jumlah_baik')?.value||0);
    const ringan = parseInt(document.getElementById('jumlah_rusak_ringan')?.value||0);
    const berat  = parseInt(document.getElementById('jumlah_rusak_berat')?.value||0);
    const el = document.getElementById('stok-total-preview');
    if (el) el.textContent = (baik+ringan+berat);
  }
  ['jumlah_baik','jumlah_rusak_ringan','jumlah_rusak_berat'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updateStokTotal);
  });
  updateStokTotal();
});

function confirmDelete(url, name='data ini') {
  if (confirm('Hapus "'+name+'"?\n\nTindakan ini tidak dapat dibatalkan.')) window.location.href = url;
}
