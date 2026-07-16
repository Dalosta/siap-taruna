<?php
/**
 * ============================================================
 * FILE    : sidebar_warga.php
 * LOKASI  : /includes/sidebar_warga.php
 * FUNGSI  : Menampilkan navigasi sidebar untuk role warga.
 *           Menu: Dashboard, Ajukan Surat, Riwayat, dan Logout.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Menu "Profil Saya" TIDAK ADA di sidebar (hanya di dropdown topbar).
 *    Ini sesuai standar aplikasi modern.
 * 2. Menu aktif otomatis terhighlight menggunakan class 'active'.
 */

// ── DETEKSI HALAMAN AKTIF ──────────────────────────────────────
$hal_aktif = basename($_SERVER['PHP_SELF']);
$dir_aktif = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- SIDEBAR — Navigasi utama untuk warga -->
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
            <?= inisial($user['nama'] ?? 'W') ?>
        </div>
        <div style="min-width:0">
            <div class="sidebar-user-name">
                <?= htmlspecialchars($user['nama'] ?? 'Warga') ?>
            </div>
            <div class="sidebar-user-role">
                👤 Warga
            </div>
        </div>
    </div>

    <!-- ── NAVIGASI ─────────────────────────────────────────────── -->
    <nav class="sidebar-nav">

        <!-- Label: Menu -->
        <div class="sidebar-label">Menu</div>

        <!-- Dashboard -->
        <a href="<?= APP_URL ?>/warga/dashboard.php"
           class="nav-link-st <?= ($hal_aktif === 'dashboard.php' && $dir_aktif === 'warga') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Ajukan Surat -->
        <a href="<?= APP_URL ?>/warga/pengajuan.php"
           class="nav-link-st <?= $hal_aktif === 'pengajuan.php' ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i> Ajukan Surat
        </a>

        <!-- Riwayat Pengajuan -->
        <a href="<?= APP_URL ?>/warga/riwayat.php"
           class="nav-link-st <?= $hal_aktif === 'riwayat.php' ? 'active' : '' ?>">
            <i class="bi bi-list-check"></i> Riwayat Pengajuan
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