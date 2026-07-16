<?php
/**
 * ============================================================
 * FILE    : sidebar_pengurus.php
 * LOKASI  : /includes/sidebar_pengurus.php
 * FUNGSI  : Menampilkan navigasi sidebar untuk role pengurus.
 *           Menu: Dashboard, Inbox (dengan badge pending),
 *           Laporan, Arsip, Data Warga, dan Logout.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Menu "Profil Saya" TIDAK ADA di sidebar (hanya di dropdown topbar).
 *    Ini sesuai standar aplikasi modern (profil = manajemen akun,
 *    bukan fitur utama).
 * 2. Badge pending muncul di menu Inbox jika ada surat yang
 *    menunggu verifikasi.
 * 3. Menu aktif otomatis terhighlight menggunakan class 'active'.
 */

// ── DETEKSI HALAMAN AKTIF ──────────────────────────────────────
$hal_aktif = basename($_SERVER['PHP_SELF']);
$dir_aktif = basename(dirname($_SERVER['PHP_SELF']));

// ── HITUNG JUMLAH PENGAJUAN PENDING ──────────────────────────
$jml_pending = hitungStatusGlobal('Pending');
?>

<!-- SIDEBAR — Navigasi utama untuk pengurus -->
<aside class="sidebar" id="appSidebar">

    <!-- ── BRAND ──────────────────────────────────────────────── -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="bi bi-building-fill-check"></i>
        </div>
        <div>
            <div class="sidebar-brand-name">SIAP TARUNA</div>
            <span class="sidebar-brand-sub">Karang Taruna RW 01</span>
        </div>
    </div>

    <!-- ── INFO USER ───────────────────────────────────────────── -->
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?= inisial($user['nama'] ?? 'P') ?>
        </div>
        <div style="min-width:0">
            <div class="sidebar-user-name">
                <?= htmlspecialchars($user['nama'] ?? 'Pengurus') ?>
            </div>
            <div class="sidebar-user-role">
                🛡 Pengurus
            </div>
        </div>
    </div>

    <!-- ── NAVIGASI ─────────────────────────────────────────────── -->
    <nav class="sidebar-nav">

        <!-- Label: Menu Utama -->
        <div class="sidebar-label">Menu Utama</div>

        <!-- Dashboard -->
        <a href="<?= APP_URL ?>/pengurus/dashboard.php"
           class="nav-link-st <?= ($hal_aktif === 'dashboard.php' && $dir_aktif === 'pengurus') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Inbox Pengajuan (dengan badge pending) -->
        <a href="<?= APP_URL ?>/pengurus/inbox.php"
           class="nav-link-st <?= $hal_aktif === 'inbox.php' ? 'active' : '' ?>">
            <i class="bi bi-inbox"></i> Inbox Pengajuan
            <?php if ($jml_pending > 0): ?>
            <span class="nav-badge"><?= $jml_pending ?></span>
            <?php endif; ?>
        </a>

        <!-- Laporan & Statistik -->
        <a href="<?= APP_URL ?>/pengurus/laporan.php"
           class="nav-link-st <?= $hal_aktif === 'laporan.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Laporan & Statistik
        </a>

        <!-- Arsip Surat -->
        <a href="<?= APP_URL ?>/pengurus/arsip.php"
           class="nav-link-st <?= $hal_aktif === 'arsip.php' ? 'active' : '' ?>">
            <i class="bi bi-archive"></i> Arsip Surat
        </a>

        <!-- Label: Kelola -->
        <div class="sidebar-label">Kelola</div>

        <!-- Data Warga -->
        <a href="<?= APP_URL ?>/pengurus/data-warga.php"
           class="nav-link-st <?= $hal_aktif === 'data-warga.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Data Warga
        </a>

        <!-- ===================================================== -->
        <!-- ⚠️ Menu "Profil Saya" TIDAK ADA DI SINI              -->
        <!-- Profil hanya diakses dari dropdown topbar (header.php) -->
        <!-- ===================================================== -->

        <!-- Logout (diberi jarak dan warna merah) -->
        <a href="<?= APP_URL ?>/logout.php"
           class="nav-link-st nav-danger"
           style="margin-top:12px;">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </nav>

    <!-- ── FOOTER SIDEBAR ──────────────────────────────────────── -->
    <div class="sidebar-footer">
        SIAP TARUNA v1.0.0<br>
        &copy; 2026 Karang Taruna RW 01
    </div>
</aside>