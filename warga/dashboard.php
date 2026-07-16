<?php
/**
 * ============================================================
 * FILE    : dashboard.php
 * LOKASI  : /warga/dashboard.php
 * FUNGSI  : Halaman dashboard utama untuk role warga.
 *           Menampilkan statistik pribadi, aksi cepat,
 *           info layanan, dan 5 pengajuan terbaru.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Dashboard adalah ruang pribadi warga untuk memantau
 *    pengajuan surat dan mengakses fitur utama.
 * 2. Statistik dihitung khusus untuk warga yang login
 *    menggunakan hitungStatusWarga().
 * 3. Aksi cepat: Ajukan Surat, Lihat Riwayat, Edit Profil.
 * 4. Info layanan: estimasi, notifikasi, cetak PDF.
 * 5. 5 pengajuan terbaru dengan status dan tombol cetak jika ACC.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA WARGA ──────────────────────────────────────────
wajibWarga();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Dashboard';

// ── 4. AMBIL ID USER ──────────────────────────────────────────────
$idUser = (int)$_SESSION['id_user'];

// ── 5. STATISTIK ──────────────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Tampilan Dashboard Warga
$st_total   = (int)($koneksi->query("SELECT COUNT(*) AS n FROM tb_pengajuan WHERE id_user = $idUser")->fetch_assoc()['n'] ?? 0);
$st_pending = hitungStatusWarga($idUser, 'Pending');
$st_proses  = hitungStatusWarga($idUser, 'Diproses') + hitungStatusWarga($idUser, 'Revisi');
$st_selesai = hitungStatusWarga($idUser, 'ACC');

// ── 6. 5 PENGAJUAN TERBARU ───────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query JOIN Pengajuan + Status Terbaru
$q_terbaru = $koneksi->query(
    "SELECT p.*, s.status, s.catatan
     FROM tb_pengajuan p
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     WHERE p.id_user = $idUser
     ORDER BY p.created_at DESC
     LIMIT 5"
);

// ── 7. INCLUDE HEADER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Dashboard Warga -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                👋 Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!
            </h1>
            <div class="page-sub">
                <?php
                $hari_id = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $bln_id  = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo $hari_id[(int)date('w')] . ', ' . date('d') . ' ' . $bln_id[(int)date('n')] . ' ' . date('Y');
                ?>
                — Selamat datang di SIAP TARUNA
            </div>
        </div>
        <a href="<?= APP_URL ?>/warga/pengajuan.php" class="btn-st btn-primary-st btn-pill">
            <i class="bi bi-plus-lg"></i> Ajukan Surat
        </a>
    </div>
</div>

<!-- ── 8. 4 KARTU STATISTIK ────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['Total Pengajuan',    $st_total,   'bi-file-text',        '#2563EB', '#EFF6FF'],
        ['Menunggu Verifikasi', $st_pending, 'bi-hourglass-split',  '#D97706', '#FEF3C7'],
        ['Sedang Diproses',    $st_proses,  'bi-arrow-repeat',     '#7C3AED', '#F5F3FF'],
        ['Surat Selesai',      $st_selesai, 'bi-check-circle-fill','#059669', '#D1FAE5'],
    ];
    foreach ($stats as [$lbl, $val, $ico, $clr, $bg]): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $clr ?>">
                <i class="bi <?= $ico ?>"></i>
            </div>
            <div class="stat-value" style="color:<?= $clr ?>"><?= number_format($val) ?></div>
            <div class="stat-label"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <!-- ── 9. KOLOM KIRI: AKSI CEPAT & INFO ────────────────────── -->
    <div class="col-md-4">
        <!-- Aksi Cepat -->
        <div class="card-st mb-3">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-lightning-fill" style="color:var(--blue-600)"></i> Aksi Cepat
                </h6>
            </div>
            <div class="card-body-st d-flex flex-column gap-2">
                <a href="<?= APP_URL ?>/warga/pengajuan.php" class="btn-st btn-primary-st btn-pill">
                    <i class="bi bi-plus-circle"></i> Ajukan Surat Baru
                </a>
                <a href="<?= APP_URL ?>/warga/riwayat.php" class="btn-st btn-outline-st btn-pill">
                    <i class="bi bi-list-check"></i> Lihat Riwayat
                </a>
                <a href="<?= APP_URL ?>/warga/profil.php" class="btn-st btn-outline-st btn-pill">
                    <i class="bi bi-person"></i> Edit Profil
                </a>
            </div>
        </div>

        <!-- Info Layanan -->
        <div class="card-st">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-info-circle" style="color:var(--blue-600)"></i> Info Layanan
                </h6>
            </div>
            <div class="card-body-st">
                <?php foreach ([
                    ['⏳', 'Estimasi Proses', '1–3 hari kerja'],
                    ['🔔', 'Notifikasi', 'Status terupdate otomatis'],
                    ['🖨', 'Cetak PDF', 'Setelah surat disetujui'],
                ] as [$ico, $k, $v]): ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:11px">
                    <div style="width:32px;height:32px;background:var(--blue-50);border-radius:8px;
                                display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0">
                        <?= $ico ?>
                    </div>
                    <div>
                        <div style="font-size:.8rem;font-weight:700"><?= $k ?></div>
                        <div style="font-size:.75rem;color:var(--slate-400)"><?= $v ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── 10. KOLOM KANAN: 5 PENGAJUAN TERBARU ────────────────── -->
    <div class="col-md-8">
        <div class="card-st">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-clock-history" style="color:var(--blue-600)"></i> Pengajuan Terbaru
                </h6>
                <a href="<?= APP_URL ?>/warga/riwayat.php"
                   style="font-size:.8rem;color:var(--blue-600);font-weight:600;text-decoration:none">
                    Lihat semua →
                </a>
            </div>

            <?php
            $bc_map = ['Pending' => '#F59E0B', 'Diproses' => '#3B82F6',
                       'Revisi' => '#8B5CF6', 'ACC' => '#10B981', 'Tolak' => '#EF4444'];
            $ada = false;
            if ($q_terbaru) {
                while ($row = $q_terbaru->fetch_assoc()):
                    $ada   = true;
                    $st_   = $row['status'] ?? 'Pending';
                    $bc    = $bc_map[$st_] ?? '#E5E7EB';
            ?>
            <div style="padding:12px 18px;border-bottom:1px solid var(--slate-100);
                        border-left:3px solid <?= $bc ?>"
                 onmouseover="this.style.background='var(--slate-50)'"
                 onmouseout="this.style.background='white'">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                    <div>
                        <div style="font-weight:700;font-size:.875rem">
                            <?= htmlspecialchars($row['jenis_surat']) ?>
                        </div>
                        <div style="font-size:.76rem;color:var(--slate-400);margin-top:2px">
                            <?= formatTanggal($row['created_at']) ?>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px">
                        <?= badgeStatus($st_) ?>
                        <?php if ($st_ === 'ACC'): ?>
                        <a href="<?= APP_URL ?>/cetak.php?id=<?= $row['id_pengajuan'] ?>" target="_blank"
                           style="font-size:.75rem;color:var(--blue-600);font-weight:600;text-decoration:none">
                            <i class="bi bi-printer me-1"></i> Cetak
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile;
            } // end if

            if (!$ada): ?>
            <div class="empty-state" style="padding:42px">
                <i class="bi bi-inbox"></i>
                <h6>Belum ada pengajuan</h6>
                <p>Buat pengajuan surat pertamamu sekarang</p>
                <a href="<?= APP_URL ?>/warga/pengajuan.php" class="btn-st btn-primary-st btn-pill">
                    <i class="bi bi-plus-lg"></i> Ajukan Sekarang
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>