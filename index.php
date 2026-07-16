<?php
/**
 * ============================================================
 * FILE    : index.php
 * LOKASI  : /index.php
 * FUNGSI  : Halaman beranda publik (landing page) SIAP TARUNA.
 *           Menampilkan 3 jenis surat prioritas, statistik,
 *           cara penggunaan, daftar pengurus, FAQ, dan CTA.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Hanya menampilkan 3 jenis surat prioritas (sesuai hasil
 *    observasi dan wawancara): Domisili, Tidak Mampu, RT/RW.
 * 2. Statistik diambil real-time dari database.
 * 3. Tema slate modern dengan gradasi gelap di hero.
 * 4. SEO dasar: meta description & Open Graph tags.
 * 5. Responsif: tampilan bagus di semua ukuran layar.
 * 6. Semua link internal menggunakan konstanta APP_URL.
 */

// ── KONEKSI DATABASE ──────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';

// ── STATISTIK ──────────────────────────────────────────────────────
$st_warga   = (int)($koneksi->query("SELECT COUNT(*) AS n FROM tb_users WHERE role='warga'")->fetch_assoc()['n'] ?? 0);
$st_surat   = (int)($koneksi->query("SELECT COUNT(*) AS n FROM tb_pengajuan")->fetch_assoc()['n'] ?? 0);
$st_selesai = (int)($koneksi->query(
    "SELECT COUNT(DISTINCT p.id_pengajuan) AS n FROM tb_pengajuan p
     JOIN tb_status s ON s.id_pengajuan = p.id_pengajuan
     WHERE s.status='ACC'
     AND s.id_status=(SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan=p.id_pengajuan)"
)->fetch_assoc()['n'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAP TARUNA — Layanan Surat Digital Karang Taruna RW 01</title>
    <meta name="description" content="Platform digital administrasi persuratan Karang Taruna RW 01 Kelurahan Kelapa Dua, Kebon Jeruk, Jakarta Barat.">
    <!-- Open Graph untuk media sosial -->
    <meta property="og:title" content="SIAP TARUNA">
    <meta property="og:description" content="Urus surat pengantar tanpa antri, dari mana saja.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ============================================================
           ROOT VARIABLES — Slate Modern
           ============================================================ */
        :root {
            --slate-900: #0F172A;
            --slate-800: #1E293B;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748B;
            --slate-400: #94A3B8;
            --slate-300: #CBD5E1;
            --slate-200: #E2E8F0;
            --slate-100: #F1F5F9;
            --slate-50: #F8FAFC;
            --blue-700: #1D4ED8;
            --blue-600: #2563EB;
            --blue-500: #3B82F6;
            --blue-400: #60A5FA;
            --blue-300: #93C5FD;
            --blue-200: #BFDBFE;
            --blue-100: #DBEAFE;
            --blue-50: #EFF6FF;
            --nav-h: 68px;
            --font: 'Plus Jakarta Sans', sans-serif;
            --gradient-hero: linear-gradient(145deg, #0F172A 0%, #1E293B 50%, #334155 100%);
            --gradient-card: linear-gradient(135deg, #1E293B, #334155);
        }

        /* ============================================================
           RESET & BASE
           ============================================================ */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font);
            background: var(--slate-50);
            color: var(--slate-800);
            -webkit-font-smoothing: antialiased;
            padding-top: 12px;
            margin: 0;
        }

        a {
            text-decoration: none;
        }

        .con {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ============================================================
           NAVBAR
           ============================================================ */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--nav-h);
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            z-index: 9999;
            transition: all 0.3s;
            padding: 0 20px;
        }

        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.98);
        }

        .navbar .con {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
            width: 100%;
        }

        .nav-in {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .nav-logo {
            width: 38px;
            height: 38px;
            background: var(--blue-500);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: white;
            flex-shrink: 0;
        }

        .nav-bname {
            font-size: 0.95rem;
            font-weight: 800;
            color: white;
            line-height: 1.1;
        }

        .nav-bsub {
            font-size: 0.6rem;
            color: rgba(255, 255, 255, 0.4);
            display: block;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: center;
        }

        .nav-links a {
            padding: 8px 16px;
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-links a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-cta-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-masuk {
            padding: 8px 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            color: white;
            border-radius: 9px;
            font-size: 0.855rem;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-masuk:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-akses {
            padding: 8px 22px;
            background: var(--blue-500);
            color: white;
            border-radius: 9px;
            font-size: 0.855rem;
            font-weight: 700;
            transition: all 0.2s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-akses:hover {
            background: var(--blue-400);
            transform: translateY(-1px);
        }

        .nav-ham {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
            padding: 4px;
        }

        .nav-mob {
            display: none;
            position: fixed;
            top: var(--nav-h);
            left: 0;
            right: 0;
            background: var(--slate-900);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 20px 16px;
            z-index: 9998;
        }

        .nav-mob.open {
            display: block;
        }

        .nav-mob a {
            display: block;
            padding: 10px 12px;
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
        }

        .nav-mob a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
        }

        /* ============================================================
           HERO
           ============================================================ */
        .hero {
            min-height: 100vh;
            padding-top: calc(var(--nav-h) + 8px);
            background: var(--gradient-hero);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(60px);
        }

        .orb1 {
            top: -150px;
            right: -150px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 65%);
        }

        .orb2 {
            bottom: -100px;
            left: -100px;
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, rgba(148, 163, 184, 0.15) 0%, transparent 65%);
        }

        .hero-body {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: 80px 0 60px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 99px;
            padding: 6px 16px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--blue-300);
            margin-bottom: 22px;
            backdrop-filter: blur(4px);
            width: fit-content;
        }

        .hero-title {
            font-size: clamp(2.2rem, 4.5vw, 3.6rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 18px;
            letter-spacing: -0.5px;
            color: white;
        }

        .hero-title .grad {
            background: linear-gradient(135deg, #93C5FD, #60A5FA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 0.975rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            margin-bottom: 32px;
            max-width: 480px;
        }

        .hero-cta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 36px;
        }

        .btn-hp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: white;
            color: var(--slate-800);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            transition: all 0.25s;
        }

        .btn-hp:hover {
            background: var(--blue-50);
            color: var(--blue-700);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.15);
        }

        .btn-hg {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.25s;
        }

        .btn-hg:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }

        .trust-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .trust-it {
            display: flex;
            align-items: center;
            gap: 7px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.82rem;
        }

        .trust-dot {
            width: 5px;
            height: 5px;
            background: var(--blue-300);
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* Hero kanan */
        .hero-right {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .stat-gl {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            backdrop-filter: blur(12px);
            transition: all 0.25s;
        }

        .stat-gl:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(59, 130, 246, 0.25);
            transform: translateX(4px);
        }

        .stat-gl-ico {
            width: 48px;
            height: 48px;
            background: rgba(59, 130, 246, 0.15);
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--blue-300);
            flex-shrink: 0;
        }

        .stat-gl-val {
            font-size: 1.9rem;
            font-weight: 800;
            color: white;
            line-height: 1;
        }

        .stat-gl-lbl {
            font-size: 0.76rem;
            color: rgba(255, 255, 255, 0.45);
            margin-top: 2px;
        }

        .stat-gl-tag {
            margin-left: auto;
            background: rgba(59, 130, 246, 0.12);
            color: var(--blue-200);
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ============================================================
           STATS BAR
           ============================================================ */
        .stats-bar {
            background: var(--slate-800);
            padding: 28px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .stats-bar-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        .stat-bar-item {
            text-align: center;
            padding: 14px 24px;
            position: relative;
        }

        .stat-bar-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255, 255, 255, 0.06);
        }

        .stat-bar-num {
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--blue-300);
            line-height: 1;
        }

        .stat-bar-lbl {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 5px;
        }

        /* ============================================================
           SECTION — LAYANAN SURAT (3 JENIS)
           ============================================================ */
        .sec {
            padding: 80px 0;
        }

        .sec-dark2 {
            background: var(--slate-100);
        }

        .sec-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--blue-50);
            border: 1px solid var(--blue-100);
            border-radius: 99px;
            padding: 5px 14px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--blue-700);
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .sec-title {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 12px;
            color: var(--slate-800);
        }

        .sec-desc {
            font-size: 0.95rem;
            color: var(--slate-500);
            line-height: 1.75;
            max-width: 500px;
        }

        /* Grid 3 jenis surat */
        .jenis-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 46px;
        }

        .jenis-card {
            background: white;
            border: 1px solid var(--slate-200);
            border-radius: 16px;
            padding: 22px;
            transition: all 0.25s;
        }

        .jenis-card:hover {
            transform: translateY(-4px);
            border-color: var(--blue-400);
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
        }

        .jenis-ico {
            width: 50px;
            height: 50px;
            background: var(--blue-50);
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 14px;
            color: var(--blue-600);
        }

        .jenis-name {
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--slate-800);
        }

        .jenis-desc {
            font-size: 0.83rem;
            color: var(--slate-500);
            line-height: 1.6;
        }

        .jenis-tag {
            display: inline-block;
            margin-top: 10px;
            padding: 3px 10px;
            background: var(--blue-50);
            color: var(--blue-700);
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        /* ============================================================
           STEPS / CARA PAKAI
           ============================================================ */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            margin-top: 52px;
            position: relative;
        }

        .steps-grid::before {
            content: '';
            position: absolute;
            top: 28px;
            left: calc(12.5%);
            right: calc(12.5%);
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--blue-300), var(--blue-300), transparent);
        }

        .step-nd {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 16px;
            position: relative;
            z-index: 1;
        }

        .step-num {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--gradient-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            margin-bottom: 18px;
            box-shadow: 0 0 0 6px var(--blue-50);
        }

        .step-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--slate-800);
            margin-bottom: 8px;
        }

        .step-desc {
            font-size: 0.835rem;
            color: var(--slate-500);
            line-height: 1.6;
        }

        /* ============================================================
           PENGURUS
           ============================================================ */
        .pengurus-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 46px;
        }

        .peng-card {
            background: white;
            border: 1px solid var(--slate-200);
            border-radius: 18px;
            padding: 26px;
            text-align: center;
            transition: all 0.25s;
        }

        .peng-card:hover {
            transform: translateY(-4px);
            border-color: var(--blue-400);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
        }

        .peng-av {
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: var(--gradient-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 800;
            color: white;
            margin: 0 auto 14px;
            text-transform: uppercase;
        }

        .peng-name {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--slate-800);
            margin-bottom: 4px;
        }

        .peng-role {
            font-size: 0.82rem;
            color: var(--blue-600);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .peng-desc {
            font-size: 0.82rem;
            color: var(--slate-500);
            line-height: 1.6;
        }

        /* ============================================================
           FAQ
           ============================================================ */
        .faq-wrap {
            max-width: 780px;
            margin: 46px auto 0;
        }

        .faq-item {
            border: 1px solid var(--slate-200);
            border-radius: 14px;
            margin-bottom: 10px;
            overflow: hidden;
            background: white;
        }

        .faq-item:hover {
            border-color: var(--blue-400);
        }

        .faq-btn {
            width: 100%;
            background: transparent;
            border: none;
            color: var(--slate-800);
            font-family: var(--font);
            font-size: 0.925rem;
            font-weight: 600;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            text-align: left;
        }

        .faq-btn:hover {
            background: var(--slate-50);
        }

        .faq-btn i {
            font-size: 1.1rem;
            color: var(--blue-500);
            transition: transform 0.25s;
            flex-shrink: 0;
        }

        .faq-btn.open i {
            transform: rotate(45deg);
        }

        .faq-ans {
            display: none;
            padding: 0 20px 18px;
            font-size: 0.875rem;
            color: var(--slate-500);
            line-height: 1.75;
        }

        .faq-ans.open {
            display: block;
        }

        /* ============================================================
           CTA
           ============================================================ */
        .sec-cta {
            padding: 100px 0;
            background: var(--gradient-hero);
            position: relative;
            overflow: hidden;
        }

        .sec-cta::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(59, 130, 246, 0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .cta-inner {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 16px;
            color: white;
        }

        .cta-title .grad {
            background: linear-gradient(135deg, #93C5FD, #60A5FA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-desc {
            font-size: 0.975rem;
            color: rgba(255, 255, 255, 0.55);
            line-height: 1.75;
            margin-bottom: 36px;
        }

        .cta-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 32px;
            background: white;
            color: var(--slate-800);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.25s;
        }

        .btn-cta-primary:hover {
            background: var(--blue-50);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }

        .btn-cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 32px;
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.25s;
        }

        .btn-cta-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }

        /* ============================================================
           FOOTER
           ============================================================ */
        .site-footer {
            background: var(--slate-900);
            padding: 60px 0 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.5);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1.2fr 1.2fr;
            gap: 40px;
            margin-bottom: 46px;
        }

        .ft-brand-block {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .ft-brand-icon {
            width: 36px;
            height: 36px;
            background: var(--blue-500);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .ft-bname {
            font-size: 1.05rem;
            font-weight: 800;
            color: white;
        }

        .ft-bdesc {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.4);
            line-height: 1.65;
            margin-bottom: 14px;
        }

        .ft-social {
            display: flex;
            gap: 10px;
        }

        .ft-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .ft-social a:hover {
            background: var(--blue-500);
            border-color: var(--blue-400);
            color: white;
        }

        .ft-head {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--blue-300);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 16px;
        }

        .ft-links {
            list-style: none;
            padding: 0;
        }

        .ft-links li {
            margin-bottom: 10px;
        }

        .ft-links a {
            font-size: 0.845rem;
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.2s;
        }

        .ft-links a:hover {
            color: var(--blue-300);
        }

        .ft-links li span {
            font-size: 0.845rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .ft-links li i {
            width: 18px;
            color: var(--blue-300);
            margin-right: 6px;
        }

        .ft-hr {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 22px;
        }

        .ft-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.25);
        }

        .ft-bottom a {
            color: var(--blue-300);
            font-weight: 600;
        }

        .ft-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.12);
            border-radius: 99px;
            padding: 4px 12px;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--blue-200);
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 991px) {
            .nav-links,
            .nav-cta-wrap {
                display: none;
            }
            .nav-ham {
                display: block;
            }
            .hero-grid {
                grid-template-columns: 1fr;
            }
            .jenis-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .pengurus-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .steps-grid::before {
                display: none;
            }
            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
            .stats-bar-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575px) {
            .jenis-grid {
                grid-template-columns: 1fr;
            }
            .pengurus-grid {
                grid-template-columns: 1fr;
            }
            .footer-grid {
                grid-template-columns: 1fr;
            }
            .steps-grid {
                grid-template-columns: 1fr;
            }
            .stat-bar-item:not(:last-child)::after {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<nav class="navbar" id="mainNav">
    <div class="con">
        <div class="nav-in">
            <!-- Brand -->
            <a href="<?= APP_URL ?>/index.php" class="nav-brand">
                <div class="nav-logo"><i class="bi bi-building-fill-check"></i></div>
                <div>
                    <div class="nav-bname">SIAP TARUNA</div>
                    <span class="nav-bsub">Karang Taruna RW 01</span>
                </div>
            </a>

            <!-- Menu Desktop -->
            <div class="nav-links">
                <a href="#layanan">Layanan</a>
                <a href="#cara">Cara Pakai</a>
                <a href="#pengurus">Pengurus</a>
                <a href="#faq">FAQ</a>
            </div>

            <!-- CTA Desktop -->
            <div class="nav-cta-wrap">
                <a href="<?= APP_URL ?>/login.php" class="btn-masuk">Masuk</a>
                <a href="<?= APP_URL ?>/login.php" class="btn-akses">
                    Akses Sistem <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Tombol Hamburger (mobile) -->
            <button class="nav-ham" id="navHam">
                <i class="bi bi-list" id="navHamIco"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Menu Mobile (di-toggle) -->
<div class="nav-mob" id="navMob">
    <a href="#layanan" onclick="closeMob()">Layanan Surat</a>
    <a href="#cara" onclick="closeMob()">Cara Pakai</a>
    <a href="#pengurus" onclick="closeMob()">Pengurus</a>
    <a href="#faq" onclick="closeMob()">FAQ</a>
    <hr style="border-color:rgba(255,255,255,.1);margin:10px 0">
    <a href="<?= APP_URL ?>/login.php"
       style="background:var(--blue-500);color:white;font-weight:700;
              text-align:center;border-radius:10px;margin-top:4px">
        Masuk ke Sistem →
    </a>
</div>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="hero" id="beranda">
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>

    <div class="con hero-body">
        <div class="hero-grid">
            <!-- Kolom kiri: teks -->
            <div>
                <div class="hero-badge">
                    <i class="bi bi-geo-alt-fill"></i>
                    Kelapa Dua · Kebon Jeruk · Jakarta Barat
                </div>

                <h1 class="hero-title">
                    Urus Surat Pengantar<br>
                    <span class="grad">Tanpa Antri,</span><br>
                    Dari Mana Saja
                </h1>

                <p class="hero-desc">
                    Platform digital administrasi persuratan Karang Taruna RW 01
                    yang cepat, transparan, dan bisa diakses kapan saja.
                </p>

                <div class="hero-cta">
                    <a href="<?= APP_URL ?>/login.php" class="btn-hp">
                        <i class="bi bi-pencil-square"></i> Ajukan Surat Sekarang
                    </a>
                    <a href="#cara" class="btn-hg">
                        <i class="bi bi-play-circle"></i> Cara Penggunaan
                    </a>
                </div>

                <div class="trust-row">
                    <?php foreach (['Gratis untuk semua warga', 'Proses 1–3 hari kerja', 'Notifikasi real-time'] as $t): ?>
                    <div class="trust-it">
                        <div class="trust-dot"></div>
                        <span><?= $t ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Kolom kanan: statistik -->
            <div class="hero-right">
                <?php foreach ([
                    ['bi-people-fill',      $st_warga,   'Warga Terdaftar',   '+'.$st_warga.' akun'],
                    ['bi-file-text-fill',   $st_surat,   'Total Pengajuan',   'Sejak 2026'],
                    ['bi-patch-check-fill', $st_selesai, 'Surat Diterbitkan', '100% digital'],
                ] as [$ico, $val, $lbl, $tag]): ?>
                <div class="stat-gl">
                    <div class="stat-gl-ico"><i class="bi <?= $ico ?>"></i></div>
                    <div>
                        <div class="stat-gl-val"><?= number_format($val) ?></div>
                        <div class="stat-gl-lbl"><?= $lbl ?></div>
                    </div>
                    <div class="stat-gl-tag"><?= $tag ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     STATS BAR
     ============================================================ -->
<div class="stats-bar">
    <div class="con">
        <div class="stats-bar-grid">
            <?php foreach ([
                [$st_warga, 'Warga Terdaftar'],
                [$st_surat, 'Total Pengajuan'],
                [$st_selesai, 'Surat Diterbitkan'],
                ['1–3 Hari', 'Estimasi Proses'],
            ] as [$n, $l]): ?>
            <div class="stat-bar-item">
                <div class="stat-bar-num"><?= $n ?></div>
                <div class="stat-bar-lbl"><?= $l ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION LAYANAN SURAT — HANYA 3 JENIS
     ============================================================ -->
<section class="sec sec-dark2" id="layanan">
    <div class="con">
        <div style="text-align:center">
            <div class="sec-badge"><i class="bi bi-file-earmark-text"></i>Layanan Surat</div>
            <h2 class="sec-title">Pilih Layanan Surat Prioritas</h2>
            <p class="sec-desc" style="margin:0 auto">
                3 jenis surat yang paling sering dibutuhkan warga RW 01.
            </p>
        </div>

        <div class="jenis-grid">
            <?php foreach ([
                ['bi-house-fill',       'Surat Keterangan Domisili',     'Keperluan KTP, KK, rekening bank, dan administrasi kependudukan.', '🏠 Populer'],
                ['bi-heart-pulse',      'Surat Keterangan Tidak Mampu',  'Pengajuan bantuan sosial, beasiswa, dan program bantuan pemerintah.', '🤝 Sosial'],
                ['bi-building',         'Surat Pengantar RT/RW',        'Pengantar ke instansi pemerintah, perbankan, dan keperluan resmi.', '📄 Umum'],
            ] as [$ico, $nama, $desc, $tag]): ?>
            <div class="jenis-card">
                <div class="jenis-ico"><i class="bi <?= $ico ?>"></i></div>
                <div class="jenis-name"><?= $nama ?></div>
                <div class="jenis-desc"><?= $desc ?></div>
                <div class="jenis-tag"><?= $tag ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:40px">
            <a href="<?= APP_URL ?>/login.php" class="btn-hp">
                <i class="bi bi-pencil-square"></i> Ajukan Surat Sekarang
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION CARA PAKAI
     ============================================================ -->
<section class="sec" id="cara">
    <div class="con">
        <div style="text-align:center">
            <div class="sec-badge"><i class="bi bi-lightning"></i>Cara Pakai</div>
            <h2 class="sec-title">Empat Langkah Mudah</h2>
            <p class="sec-desc" style="margin:0 auto">
                Proses pengajuan dirancang sesederhana mungkin untuk semua kalangan warga.
            </p>
        </div>

        <div class="steps-grid">
            <?php foreach ([
                ['bi-person-check',       'Masuk ke Sistem',   'Login menggunakan akun dari pengurus Karang Taruna RW 01.'],
                ['bi-ui-checks',          'Isi Formulir',      'Pilih jenis surat, isi keperluan, dan unggah dokumen jika ada.'],
                ['bi-hourglass-split',    'Tunggu Verifikasi', 'Pengurus memproses dalam 1–3 hari dan mengirim notifikasi.'],
                ['bi-file-earmark-check', 'Cetak Surat PDF',   'Surat disetujui? Langsung cetak PDF dari halaman riwayat.'],
            ] as [$ico, $title, $desc]): ?>
            <div class="step-nd">
                <div class="step-num"><i class="bi <?= $ico ?>"></i></div>
                <div class="step-title"><?= $title ?></div>
                <div class="step-desc"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION PENGURUS (sesuai skripsi)
     ============================================================ -->
<section class="sec sec-dark2" id="pengurus">
    <div class="con">
        <div style="text-align:center">
            <div class="sec-badge"><i class="bi bi-people"></i>Tim Pengurus</div>
            <h2 class="sec-title">Pengurus Karang Taruna RW 01</h2>
            <p class="sec-desc" style="margin:0 auto">
                Dikelola oleh pengurus yang berdedikasi melayani warga Kelurahan Kelapa Dua.
            </p>
        </div>

        <div class="pengurus-grid">
            <?php foreach ([
                ['MS', 'Muhamad Satiri', 'Ketua', 'Bertanggung jawab atas keseluruhan operasional administrasi.'],
                ['AR', 'Abu Rizal Hasan', 'Wakil Ketua', 'Membantu ketua dalam pengawasan program kerja.'],
                ['MA', 'Muhammad Alfi', 'Sekretaris', 'Mengelola dokumentasi dan sistem informasi digital.'],
                ['NV', 'Novita', 'Wakil Sekretaris', 'Mendukung pengelolaan dokumen administrasi.'],
                ['AM', 'Ahmad Maulana', 'Bendahara', 'Mengelola keuangan dan laporan keuangan organisasi.'],
                ['ER', 'Erika', 'Wakil Bendahara', 'Mendukung pengelolaan keuangan dan pembukuan.'],
                ['MZ', 'Muhammad Zaki', 'Bidang Pendidikan', 'Mengkoordinir kegiatan pendidikan.'],
                ['NS', 'Nasrullah', 'Bidang Kesejahteraan Sosial', 'Mengkoordinir kegiatan sosial.'],
                ['FR', 'Fachrul Rozzi', 'Bidang Olahraga & Seni', 'Mengkoordinir kegiatan olahraga dan seni.'],
                ['RN', 'Rian', 'Bidang Lingkungan Hidup', 'Mengkoordinir kebersihan dan lingkungan.'],
                ['AU', 'Aulia', 'Bidang Humas & Publikasi', 'Mengelola publikasi dan hubungan masyarakat.'],
            ] as [$in, $nm, $jb, $desc]): ?>
            <div class="peng-card">
                <div class="peng-av"><?= $in ?></div>
                <div class="peng-name"><?= $nm ?></div>
                <div class="peng-role"><?= $jb ?></div>
                <div class="peng-desc"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION FAQ
     ============================================================ -->
<section class="sec" id="faq">
    <div class="con">
        <div style="text-align:center">
            <div class="sec-badge"><i class="bi bi-question-circle"></i>FAQ</div>
            <h2 class="sec-title">Pertanyaan yang Sering Ditanyakan</h2>
        </div>

        <div class="faq-wrap">
            <?php foreach ([
                ['Apa itu SIAP TARUNA?', 'SIAP TARUNA adalah platform digital yang memungkinkan warga RW 01 Kelurahan Kelapa Dua mengajukan surat pengantar secara online tanpa perlu datang ke sekretariat.'],
                ['Bagaimana cara mendaftar?', 'Akun warga dibuat oleh pengurus Karang Taruna. Hubungi pengurus untuk pendaftaran, lalu gunakan username dan password yang diberikan untuk login.'],
                ['Berapa lama proses pengajuan?', 'Estimasi 1–3 hari kerja sejak surat diajukan. Notifikasi otomatis dikirim setiap ada pembaruan status.'],
                ['Apakah layanan ini gratis?', 'Ya, layanan SIAP TARUNA sepenuhnya gratis untuk seluruh warga RW 01 Kelurahan Kelapa Dua.'],
                ['Dokumen apa yang perlu dilampirkan?', 'Dokumen pendukung bersifat opsional. Format: JPG, PNG, PDF — maksimal 2 MB.'],
                ['Bagaimana jika pengajuan ditolak?', 'Pengurus akan memberikan catatan alasan. Lihat di halaman riwayat dan ajukan ulang setelah memenuhi persyaratan.'],
            ] as [$q, $a]): ?>
            <div class="faq-item">
                <button class="faq-btn" onclick="toggleFaq(this)">
                    <span><?= $q ?></span>
                    <i class="bi bi-plus"></i>
                </button>
                <div class="faq-ans"><?= $a ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION CTA
     ============================================================ -->
<section class="sec-cta">
    <div class="con">
        <div class="cta-inner">
            <div class="sec-badge" style="margin:0 auto 16px;background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1);color:var(--blue-200)">
                <i class="bi bi-rocket"></i>Mulai Sekarang
            </div>

            <h2 class="cta-title">
                Urus Surat Lebih Mudah<br>
                dengan <span class="grad">SIAP TARUNA</span>
            </h2>

            <p class="cta-desc">
                Bergabunglah dengan warga RW 01 yang sudah merasakan kemudahan
                layanan administrasi persuratan digital. Gratis, cepat, transparan.
            </p>

            <div class="cta-btns">
                <a href="<?= APP_URL ?>/login.php" class="btn-cta-primary">
                    <i class="bi bi-pencil-square"></i> Ajukan Surat Sekarang
                </a>
                <a href="<?= APP_URL ?>/login.php" class="btn-cta-secondary">
                    <i class="bi bi-box-arrow-in-right"></i> Sudah Punya Akun? Masuk
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="site-footer">
    <div class="con">
        <div class="footer-grid">
            <!-- Brand & Sosial -->
            <div>
                <div class="ft-brand-block">
                    <div class="ft-brand-icon"><i class="bi bi-building-fill-check"></i></div>
                    <div class="ft-bname">SIAP TARUNA</div>
                </div>
                <p class="ft-bdesc">
                    Sistem Informasi Administrasi Persuratan Karang Taruna RW 01
                    — mendukung transformasi digital pelayanan warga menuju
                    <strong style="color:var(--blue-300);font-weight:700;">Jakarta Smart City 4.0</strong>.
                </p>
                <div class="ft-social">
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-youtube"></i></a>
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                    <a href="#"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            <!-- Menu -->
            <div>
                <div class="ft-head">Menu</div>
                <ul class="ft-links">
                    <li><a href="#layanan">Layanan Surat</a></li>
                    <li><a href="#cara">Cara Pakai</a></li>
                    <li><a href="#pengurus">Tim Pengurus</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="<?= APP_URL ?>/login.php">Masuk</a></li>
                </ul>
            </div>

            <!-- Layanan (3 prioritas) -->
            <div>
                <div class="ft-head">Layanan</div>
                <ul class="ft-links">
                    <li><a href="<?= APP_URL ?>/login.php">Surat Keterangan Domisili</a></li>
                    <li><a href="<?= APP_URL ?>/login.php">Surat Keterangan Tidak Mampu</a></li>
                    <li><a href="<?= APP_URL ?>/login.php">Surat Pengantar RT/RW</a></li>
                </ul>
            </div>

            <!-- Informasi -->
            <div>
                <div class="ft-head">Informasi</div>
                <ul class="ft-links">
                    <li><span><i class="bi bi-geo-alt"></i>RW 01, Kel. Kelapa Dua</span></li>
                    <li><span><i class="bi bi-building"></i>Kec. Kebon Jeruk, Jkt Barat</span></li>
                    <li><span><i class="bi bi-clock"></i>Proses: 1–3 Hari Kerja</span></li>
                    <li><span><i class="bi bi-shield-check"></i>Data Aman & Terlindungi</span></li>
                    <li style="margin-top:8px;">
                        <span class="ft-badge">
                            <i class="bi bi-wifi"></i> Jakarta Smart City 4.0
                        </span>
                    </li>
                    <li>
                        <span class="ft-badge" style="background:rgba(255,215,0,.08);border-color:rgba(255,215,0,.1);color:#FCD34D;">
                            <i class="bi bi-award"></i> Digital RW 2026
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="ft-hr">

        <div class="ft-bottom">
            <span>&copy; 2026 SIAP TARUNA — Karang Taruna RW 01 Kelurahan Kelapa Dua</span>
            <span>
                Dikembangkan oleh <a href="#">Muhammad Alfi</a> — UBSI 2026
                &nbsp;·&nbsp; <span style="color:rgba(255,255,255,.15);">v1.0.0</span>
            </span>
        </div>
    </div>
</footer>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
    (function() {
        // Navbar efek scroll
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 30);
        });

        // Toggle menu mobile
        const ham = document.getElementById('navHam');
        const mob = document.getElementById('navMob');
        const ico = document.getElementById('navHamIco');
        ham.addEventListener('click', () => {
            const o = mob.classList.toggle('open');
            ico.className = o ? 'bi bi-x-lg' : 'bi bi-list';
        });

        // Smooth scroll untuk anchor link
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const t = document.querySelector(a.getAttribute('href'));
                if (!t) return;
                e.preventDefault();
                window.scrollTo({ top: t.offsetTop - 80, behavior: 'smooth' });
            });
        });
    })();

    // Tutup menu mobile saat link diklik
    function closeMob() {
        document.getElementById('navMob').classList.remove('open');
        document.getElementById('navHamIco').className = 'bi bi-list';
    }

    // Toggle FAQ
    function toggleFaq(btn) {
        const ans = btn.nextElementSibling;
        const isOpen = ans.classList.contains('open');
        document.querySelectorAll('.faq-ans').forEach(a => a.classList.remove('open'));
        document.querySelectorAll('.faq-btn').forEach(b => b.classList.remove('open'));
        if (!isOpen) {
            ans.classList.add('open');
            btn.classList.add('open');
        }
    }
</script>

</body>
</html>