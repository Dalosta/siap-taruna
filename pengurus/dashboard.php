<?php
/**
 * ============================================================
 * FILE    : dashboard.php
 * LOKASI  : /pengurus/dashboard.php
 * FUNGSI  : Halaman dashboard utama untuk role pengurus.
 *           Menampilkan statistik, grafik 6 bulan,
 *           dan 5 pengajuan terbaru.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Dashboard adalah "ruang kendali" pengurus untuk memantau
 *    seluruh aktivitas sistem secara real-time.
 * 2. Statistik diambil langsung dari database.
 * 3. Grafik 6 bulan menggunakan Chart.js (CDN).
 * 4. Tabel 5 pengajuan terbaru membantu pengurus merespon cepat.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA PENGURUS ──────────────────────────────────────
wajibPengurus();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Dashboard';

// ── 4. STATISTIK ──────────────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Dashboard Pengurus + Statistik
$st_total   = (int)($koneksi->query("SELECT COUNT(*) AS n FROM tb_pengajuan")->fetch_assoc()['n'] ?? 0);
$st_pending = hitungStatusGlobal('Pending');
$st_selesai = hitungStatusGlobal('ACC');
$st_warga   = (int)($koneksi->query("SELECT COUNT(*) AS n FROM tb_users WHERE role='warga'")->fetch_assoc()['n'] ?? 0);

// ── 5. GRAFIK 6 BULAN ─────────────────────────────────────────────
$chart_labels = [];
$chart_data   = [];
$bln_id = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

for ($i = 5; $i >= 0; $i--) {
    $ts  = strtotime("-$i months");
    $bln = (int)date('n', $ts);
    $thn = (int)date('Y', $ts);
    $chart_labels[] = $bln_id[$bln] . ' ' . $thn;

    $n = $koneksi->query(
        "SELECT COUNT(*) AS n FROM tb_pengajuan
         WHERE MONTH(created_at) = $bln AND YEAR(created_at) = $thn"
    )->fetch_assoc()['n'] ?? 0;
    $chart_data[] = (int)$n;
}

// ── 6. 5 PENGAJUAN TERBARU ──────────────────────────────────────
$q_terbaru = $koneksi->query(
    "SELECT p.*, u.nama AS nama_warga, u.alamat,
            s.status
     FROM tb_pengajuan p
     JOIN tb_users u ON u.id_user = p.id_user
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     ORDER BY p.created_at DESC LIMIT 5"
);

// ── 7. INCLUDE HEADER ─────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Dashboard Pengurus -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-speedometer2" style="color:var(--blue-600)"></i> Dashboard
            </h1>
            <div class="page-sub">
                <?php
                $hari_id = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $bln_id2 = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo $hari_id[(int)date('w')] . ', ' . date('d') . ' ' . $bln_id2[(int)date('n')] . ' ' . date('Y');
                ?>
                — Ringkasan aktivitas sistem
            </div>
        </div>
    </div>
</div>

<!-- ── 8. 4 KARTU STATISTIK ─────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Pengajuan',    $st_total,   'bi-file-text',        '#2563EB', '#EFF6FF'],
        ['Menunggu Verifikasi', $st_pending, 'bi-hourglass-split',  '#D97706', '#FEF3C7'],
        ['Surat Selesai',      $st_selesai, 'bi-check-circle-fill', '#059669', '#D1FAE5'],
        ['Total Warga',        $st_warga,   'bi-people-fill',      '#0891B2', '#ECFEFF'],
    ] as [$lbl, $val, $ico, $clr, $bg]): ?>
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

<!-- ── 9. GRAFIK & TABEL ────────────────────────────────────────── -->
<div class="row g-3">

    <!-- Grafik 6 Bulan -->
    <div class="col-lg-4">
        <div class="card-st h-100">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-bar-chart" style="color:var(--blue-600)"></i> Tren 6 Bulan
                </h6>
            </div>
            <div class="card-body-st">
                <canvas id="chartTren"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabel 5 Pengajuan Terbaru -->
    <div class="col-lg-8">
        <div class="card-st h-100">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-clock-history" style="color:var(--blue-600)"></i> Pengajuan Terbaru
                </h6>
                <a href="<?= APP_URL ?>/pengurus/inbox.php"
                   style="font-size:.8rem;color:var(--blue-600);font-weight:600;text-decoration:none">
                    Lihat semua →
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-st mb-0">
                    <thead>
                        <tr><th>Nama Warga</th><th>Jenis Surat</th><th>Tanggal</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if ($q_terbaru && $q_terbaru->num_rows > 0):
                        while ($row = $q_terbaru->fetch_assoc()):
                            $st_ = $row['status'] ?? 'Pending';
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:.875rem">
                                <?= htmlspecialchars($row['nama_warga']) ?>
                            </div>
                            <div style="font-size:.75rem;color:var(--slate-400)">
                                <?= htmlspecialchars(mb_substr($row['alamat'] ?? '', 0, 28)) ?>
                            </div>
                        </td>
                        <td style="font-size:.845rem"><?= htmlspecialchars($row['jenis_surat']) ?></td>
                        <td><small style="color:var(--slate-500)"><?= formatTanggal($row['created_at']) ?></small></td>
                        <td><?= badgeStatus($st_) ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/pengurus/inbox.php"
                               class="btn-st btn-sm-st btn-primary-st btn-pill">Proses</a>
                        </td>
                    </tr>
                    <?php endwhile;
                    else: ?>
                    <tr><td colspan="5">
                        <div class="empty-state" style="padding:30px">
                            <i class="bi bi-inbox"></i>
                            <h6>Belum ada pengajuan</h6>
                        </div>
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ── 10. SCRIPT TAMBAHAN: CHART.JS ──────────────────────────────
$extra_script = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("chartTren");
    if (!ctx) return;

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ' . json_encode($chart_labels) . ',
            datasets: [{
                label: "Pengajuan",
                data: ' . json_encode($chart_data) . ',
                backgroundColor: "rgba(37,99,235,0.12)",
                borderColor: "#2563EB",
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            aspectRatio: 1.5,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#1E293B",
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(c) { return " " + c.parsed.y + " pengajuan"; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    min: 0,
                    suggestedMax: Math.max(5, Math.ceil(Math.max(...' . json_encode($chart_data) . ') * 1.2) || 5),
                    ticks: { stepSize: 1, color: "#94A3B8" },
                    grid: { color: "#F1F5F9" },
                    border: { display: false }
                },
                x: {
                    ticks: { color: "#94A3B8" },
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
});
</script>
';

// ── 11. INCLUDE FOOTER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/footer.php';
?>