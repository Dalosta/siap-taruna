<?php
/**
 * ============================================================
 * FILE    : header.php
 * LOKASI  : /includes/header.php
 * FUNGSI  : Menyusun bagian atas halaman dashboard (HTML head,
 *           topbar, sidebar). Di-include di setiap halaman
 *           dashboard setelah fungsi helper.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. File ini adalah "kerangka" atas dari setiap halaman
 *    dashboard. Memuat HTML head, topbar, dan sidebar.
 * 2. Sidebar otomatis dipilih berdasarkan role (pengurus/warga).
 * 3. Profil hanya ada di dropdown topbar (bukan di sidebar)
 *    sesuai standar aplikasi modern (profil = manajemen akun,
 *    bukan fitur utama).
 * 4. Jam real-time WIB ditampilkan di topbar.
 * 5. Flash message ditampilkan di awal main content.
 */

// ── AMBIL DATA USER (dari session) ──────────────────────────────
$user       = getUser();
$namaUser   = $user['nama']    ?? '';
$roleUser   = $user['role']    ?? '';
$idUser     = $user['id_user'] ?? 0;

// ── HITUNG NOTIFIKASI BELUM DIBACA (opsional) ────────────────────
$jml_notif  = $idUser ? notifBelumBaca((int)$idUser) : 0;

// ── JUDUL HALAMAN (harus dideklarasikan sebelum include) ─────────
// Setiap halaman wajib mendeklarasikan $judul_halaman sebelum include.
$hal_judul  = $judul_halaman ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= session_id() ?>">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($hal_judul) ?> — SIAP TARUNA</title>

    <!-- ── FONT ────────────────────────────────────────────────────── -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- ── BOOTSTRAP 5 ────────────────────────────────────────────── -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- ── BOOTSTRAP ICONS ────────────────────────────────────────── -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
          rel="stylesheet">

    <!-- ── CUSTOM CSS ─────────────────────────────────────────────── -->
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">

    <!-- ── OVERRIDE KECIL UNTUK TOPBAR ───────────────────────────── -->
    <style>
        .topbar-clock {
            background: var(--blue-50);
            border-color: var(--blue-200);
            color: var(--blue-700);
        }
        .topbar-user-btn:hover {
            background: var(--blue-50);
            border-color: var(--blue-200);
        }
    </style>
</head>
<body>

<?php
// ── TENTUKAN SIDEBAR BERDASARKAN ROLE ──────────────────────────
// Pilih file sidebar yang sesuai dengan role user.
$sidebar_file = ($roleUser === 'pengurus')
    ? __DIR__ . '/sidebar_pengurus.php'
    : __DIR__ . '/sidebar_warga.php';

// Include sidebar (akan menampilkan navigasi di kiri)
require_once $sidebar_file;
?>

<!-- ═══════════════════════════════════════════════════════════════════
     TOPBAR — Header horizontal di atas
══════════════════════════════════════════════════════════════════════ -->
<header class="topbar">
    <div class="topbar-left">
        <!-- Tombol toggle sidebar (hanya tampil di mobile) -->
        <button class="d-lg-none border-0 bg-transparent me-2"
                id="sidebarToggle"
                style="font-size:1.3rem;color:var(--slate-700);cursor:pointer;padding:4px 8px">
            <i class="bi bi-list"></i>
        </button>

        <!-- Judul halaman & breadcrumb -->
        <div>
            <div class="topbar-title"><?= htmlspecialchars($hal_judul) ?></div>
            <div class="topbar-sub">
                SIAP TARUNA &rsaquo;
                <span><?= htmlspecialchars($hal_judul) ?></span>
            </div>
        </div>
    </div>

    <div class="topbar-right">
        <!-- ── Jam WIB real-time ──────────────────────────────────── -->
        <div class="topbar-clock d-none d-md-flex">
            <i class="bi bi-clock"></i>
            <span id="clockWIB">00.00.00</span>
            <span style="color:var(--slate-400);font-weight:400">WIB</span>
        </div>

        <!-- ── Dropdown Profil (hanya di topbar) ──────────────────── -->
        <div class="dropdown">
            <a class="topbar-user-btn" data-bs-toggle="dropdown" style="cursor:pointer">
                <!-- Avatar (inisial nama) -->
                <div class="topbar-user-avatar">
                    <?= inisial($namaUser) ?>
                </div>

                <!-- Nama user (sembunyi di mobile) -->
                <span class="d-none d-md-block"
                      style="font-size:.82rem;font-weight:600">
                    <?= htmlspecialchars($namaUser) ?>
                </span>

                <!-- Ikon panah bawah -->
                <i class="bi bi-chevron-down"
                   style="font-size:.68rem;color:var(--slate-400)"></i>
            </a>

            <!-- Dropdown menu -->
            <div class="dropdown-menu dropdown-menu-end" style="min-width:200px">
                <!-- Header dropdown: nama & role -->
                <div style="padding:9px 13px;border-bottom:1px solid var(--slate-100);margin-bottom:4px">
                    <div style="font-weight:700;font-size:.875rem">
                        <?= htmlspecialchars($namaUser) ?>
                    </div>
                    <div style="font-size:.72rem;color:var(--slate-500)">
                        <?= ucfirst($roleUser) ?>
                    </div>
                </div>

                <!-- Link Profil Saya (sesuai role) -->
                <a href="<?= APP_URL ?>/<?= $roleUser ?>/profil.php" class="dropdown-item">
                    <i class="bi bi-person-circle"></i> Profil Saya
                </a>

                <div style="height:1px;background:var(--slate-100);margin:4px 0"></div>

                <!-- Logout -->
                <a href="<?= APP_URL ?>/logout.php" class="dropdown-item red">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ═══════════════════════════════════════════════════════════════════
     MAIN CONTENT — Area konten utama (dimulai dari sini)
══════════════════════════════════════════════════════════════════════ -->
<div class="main-content">

<?php
// ── TAMPILKAN FLASH MESSAGE ──────────────────────────────────────
// Menampilkan pesan sukses/error dari session (jika ada)
echo tampilFlash();
?>