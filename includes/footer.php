<?php
/**
 * ============================================================
 * FILE    : footer.php
 * LOKASI  : /includes/footer.php
 * FUNGSI  : Penutup halaman dashboard: menutup div main-content,
 *           menampilkan footer, dan memuat semua script global.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Menutup div main-content yang dibuka di header.php.
 * 2. Footer menampilkan copyright dan kredit pengembang.
 * 3. Script global: jam WIB, toggle sidebar mobile,
 *    auto dismiss flash, konfirmasi hapus dengan SweetAlert,
 *    dan disable double submit.
 * 4. Mendukung $extra_script untuk tambahan script spesifik.
 */

// ── TUTUP MAIN CONTENT ──────────────────────────────────────────
?>
</div><!-- /.main-content -->

<!-- ═══════════════════════════════════════════════════════════════════
     FOOTER — Informasi hak cipta dan kredit
══════════════════════════════════════════════════════════════════════ -->
<footer class="main-footer">
    <span>&copy; 2026 SIAP TARUNA — Karang Taruna RW 01 Kelurahan Kelapa Dua</span>
    <span>
        Dikembangkan oleh
        <span class="ft-brand">Muhammad Alfi</span> — UBSI 2026
    </span>
</footer>

<!-- ═══════════════════════════════════════════════════════════════════
     BOOTSTRAP 5 JS — Untuk komponen interaktif (dropdown, modal, dll)
══════════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>

<!-- ═══════════════════════════════════════════════════════════════════
     SWEETALERT2 — Konfirmasi hapus yang elegan
══════════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
</script>

<!-- ═══════════════════════════════════════════════════════════════════
     SCRIPT GLOBAL — Fungsi yang dipakai di seluruh halaman
══════════════════════════════════════════════════════════════════════ -->
<script>
    (function() {
        // ── 1. Jam WIB real-time ──────────────────────────────
        // Menampilkan jam di topbar (update setiap detik)
        function updateJam() {
            const wib = new Date(
                new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' })
            );

            const p = function(n) {
                return String(n).padStart(2, '0');
            };

            const el = document.getElementById('clockWIB');

            if (el) {
                el.textContent =
                    p(wib.getHours()) + '.' +
                    p(wib.getMinutes()) + '.' +
                    p(wib.getSeconds());
            }
        }

        updateJam();
        setInterval(updateJam, 1000);

        // ── 2. Toggle sidebar mobile ──────────────────────────
        // Saat tombol hamburger diklik, buka/tutup sidebar di layar kecil
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('appSidebar');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }

        // ── 3. Auto dismiss flash message ──────────────────────
        // Flash message akan hilang otomatis setelah 4,5 detik
        setTimeout(function() {
            document.querySelectorAll('.flash-msg').forEach(function(el) {
                el.style.transition = 'opacity .5s';
                el.style.opacity = '0';

                setTimeout(function() {
                    if (el.parentNode) el.remove();
                }, 500);
            });
        }, 4500);

        // ── 4. Konfirmasi hapus dengan SweetAlert ──────────────
        // Untuk elemen dengan atribut data-confirm
        document.querySelectorAll('[data-confirm]').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();

                const msg = this.dataset.confirm || 'Yakin ingin menghapus?';
                const href = this.href || null;
                const form = this.closest('form');

                Swal.fire({
                    title: 'Konfirmasi',
                    text: msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2563EB', // biru sesuai tema
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        if (href) window.location.href = href;
                        if (form) form.submit();
                    }
                });
            });
        });

        // ── 5. Disable double submit ──────────────────────────
        // Mencegah user mengklik tombol submit dua kali
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');

                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
                }
            });
        });
    })();
</script>

<?php
// ── SCRIPT TAMBAHAN SPESIFIK HALAMAN ──────────────────────────
// Variabel $extra_script bisa dideklarasikan di halaman sebelum
// include footer (misalnya untuk Chart.js, script custom, dll).
if (isset($extra_script)) {
    echo $extra_script;
}
?>

</body>
</html>