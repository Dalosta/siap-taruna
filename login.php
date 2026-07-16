<?php
/**
 * ============================================================
 * FILE    : login.php
 * LOKASI  : /login.php
 * FUNGSI  : Halaman login. Layout split: branding kiri + form kanan.
 *           Redirect otomatis jika sudah login sesuai role.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Menampilkan statistik real-time (warga, pengajuan, surat selesai)
 *    di panel kiri untuk meyakinkan pengunjung.
 * 2. Error handling: dari URL (?pesan) dan session flash.
 * 3. Toggle password visibility dengan JavaScript.
 * 4. Responsif: panel kiri hilang di mobile.
 */

// ── KONEKSI & FUNGSI ──────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/fungsi.php';

// ── CEK SESSION ────────────────────────────────────────────────────
mulaiSession();

if (sudahLogin()) {
    redirect(getRole() === 'pengurus'
        ? APP_URL . '/pengurus/dashboard.php'
        : APP_URL . '/warga/dashboard.php');
}

// ── AMBIL PESAN ERROR ─────────────────────────────────────────────
$error = match ($_GET['pesan'] ?? '') {
    'login_dulu' => 'Silakan login terlebih dahulu.',
    'sesi_habis' => 'Sesi Anda telah berakhir. Silakan login kembali.',
    default      => '',
};

// ── STATISTIK UNTUK PANEL KIRI ────────────────────────────────────
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
    <title>Masuk — SIAP TARUNA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
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
            --blue-600: #2563EB;
            --blue-500: #3B82F6;
            --blue-400: #60A5FA;
            --blue-50: #EFF6FF;
            --gradient-login: linear-gradient(160deg, #0F172A 0%, #1E293B 55%, #334155 100%);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; display: flex; background: var(--slate-50); -webkit-font-smoothing: antialiased; }

        /* ── PANEL KIRI ────────────────────────────────────────────── */
        .panel-left {
            width: 460px;
            flex-shrink: 0;
            background: var(--gradient-login);
            display: flex;
            flex-direction: column;
            padding: 36px 44px;
            position: relative;
            overflow: hidden;
        }
        .panel-left::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .left-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 48px;
            position: relative;
            z-index: 1;
        }
        .left-brand-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            color: white;
        }
        .left-brand-name {
            font-size: .95rem;
            font-weight: 800;
            color: white;
            line-height: 1.1;
        }
        .left-brand-sub {
            font-size: .62rem;
            color: rgba(255,255,255,.4);
        }
        .left-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        .left-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 99px;
            padding: 5px 14px;
            font-size: .75rem;
            color: var(--blue-400);
            margin-bottom: 20px;
            width: fit-content;
        }
        .left-title {
            font-size: clamp(1.9rem, 3vw, 2.6rem);
            font-weight: 900;
            color: white;
            line-height: 1.12;
            margin-bottom: 14px;
            letter-spacing: -.3px;
        }
        .left-title .acc {
            color: var(--blue-400);
        }
        .left-desc {
            font-size: .875rem;
            color: rgba(255,255,255,.6);
            line-height: 1.75;
            margin-bottom: 34px;
        }
        .stat-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 17px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            margin-bottom: 10px;
        }
        .stat-ico {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,.08);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--blue-400);
            flex-shrink: 0;
        }
        .stat-val {
            font-size: 1.45rem;
            font-weight: 800;
            color: white;
            line-height: 1;
        }
        .stat-lbl {
            font-size: .73rem;
            color: rgba(255,255,255,.45);
            margin-top: 2px;
        }

        /* ── PANEL KANAN ────────────────────────────────────────────── */
        .panel-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: white;
        }
        .form-wrap {
            width: 100%;
            max-width: 400px;
        }
        .form-heading h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--slate-800);
            margin-bottom: 5px;
        }
        .form-heading p {
            font-size: .875rem;
            color: var(--slate-500);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .form-card {
            background: white;
            border-radius: 18px;
            border: 1px solid var(--slate-200);
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,.04);
        }
        .err-box {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 14px;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            font-size: .845rem;
            color: #991B1B;
            margin-bottom: 16px;
        }
        .inp-group {
            margin-bottom: 15px;
        }
        .inp-group label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 6px;
        }
        .inp-wrap {
            position: relative;
        }
        .inp-ico {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            font-size: 1rem;
            pointer-events: none;
        }
        .inp-wrap input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            font-family: inherit;
            font-size: .875rem;
            color: var(--slate-800);
            border: 1.5px solid var(--slate-300);
            border-radius: 10px;
            outline: none;
            transition: all .2s;
            background: white;
        }
        .inp-wrap input:focus {
            border-color: var(--blue-500);
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }
        .inp-wrap input::placeholder {
            color: var(--slate-400);
        }
        .inp-has-eye input {
            padding-right: 42px;
        }
        .eye-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--slate-400);
            font-size: 1rem;
            padding: 0;
        }
        .eye-btn:hover {
            color: var(--slate-700);
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--blue-600);
            color: white;
            border: none;
            border-radius: 11px;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 14px;
        }
        .btn-submit:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37,99,235,.3);
        }
        .back-link {
            text-align: center;
            margin-top: 14px;
            font-size: .82rem;
        }
        .back-link a {
            color: var(--slate-400);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .back-link a:hover {
            color: var(--slate-700);
        }

        /* ── RESPONSIVE ────────────────────────────────────────────── */
        @media (max-width: 860px) {
            .panel-left { display: none; }
            .panel-right {
                background: var(--gradient-login);
                padding: 40px 20px;
                min-height: 100vh;
            }
            .form-wrap { max-width: 100%; }
            .form-heading h2 { color: white; }
            .form-heading p { color: rgba(255,255,255,.7); }
            .form-card {
                background: rgba(255,255,255,.95);
                backdrop-filter: blur(12px);
            }
            .back-link a { color: rgba(255,255,255,.6); }
            .back-link a:hover { color: white; }
        }
    </style>
</head>
<body>

<!-- ── PANEL KIRI ─────────────────────────────────────────────────── -->
<div class="panel-left">
    <div class="left-brand">
        <div class="left-brand-icon"><i class="bi bi-building-fill-check"></i></div>
        <div>
            <div class="left-brand-name">SIAP TARUNA</div>
            <div class="left-brand-sub">Karang Taruna RW 01</div>
        </div>
    </div>

    <div class="left-body">
        <div class="left-badge">
            <i class="bi bi-geo-alt-fill"></i>
            Kelapa Dua · Kebon Jeruk · Jakarta Barat
        </div>

        <h1 class="left-title">
            Layanan Surat<br>
            Warga <span class="acc">Digital</span><br>
            RW 01
        </h1>

        <p class="left-desc">
            Platform administrasi persuratan Karang Taruna RW 01 yang
            mudah, cepat, dan dapat dipantau kapan saja.
        </p>

        <?php foreach ([
            ['bi-people-fill', $st_warga, 'Warga Terdaftar'],
            ['bi-file-text',   $st_surat, 'Total Pengajuan'],
            ['bi-check-circle',$st_selesai, 'Surat Diterbitkan'],
        ] as [$ico, $val, $lbl]): ?>
        <div class="stat-row">
            <div class="stat-ico"><i class="bi <?= $ico ?>"></i></div>
            <div>
                <div class="stat-val"><?= number_format($val) ?></div>
                <div class="stat-lbl"><?= $lbl ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── PANEL KANAN ─────────────────────────────────────────────────── -->
<div class="panel-right">
    <div class="form-wrap">
        <div class="form-heading">
            <h2>Selamat Datang 👋</h2>
            <p>Masuk ke SIAP TARUNA untuk mengakses layanan administrasi persuratan digital Karang Taruna RW 01.</p>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
            <div class="err-box">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_login'])): ?>
            <div class="err-box">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['error_login']) ?>
            </div>
            <?php unset($_SESSION['error_login']); ?>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/handlers/proses_login.php">
                <!-- Username -->
                <div class="inp-group">
                    <label for="username">Username</label>
                    <div class="inp-wrap">
                        <i class="bi bi-person inp-ico"></i>
                        <input type="text" id="username" name="username"
                               placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_SESSION['last_username'] ?? '') ?>"
                               required autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div class="inp-group" style="margin-bottom:20px">
                    <label for="passInput">Password</label>
                    <div class="inp-wrap inp-has-eye">
                        <i class="bi bi-lock inp-ico"></i>
                        <input type="password" id="passInput" name="password"
                               placeholder="Masukkan password"
                               required autocomplete="current-password">
                        <button type="button" class="eye-btn" id="eyeBtn">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Masuk ke Sistem
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="<?= APP_URL ?>/index.php">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('eyeBtn').addEventListener('click', function() {
    const input = document.getElementById('passInput');
    const icon = document.getElementById('eyeIcon');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>

<?php unset($_SESSION['last_username']); ?>
</body>
</html>