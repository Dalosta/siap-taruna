<?php
/**
 * ============================================================
 * FILE    : laporan.php
 * LOKASI  : /pengurus/laporan.php
 * FUNGSI  : Menampilkan laporan rekap pengajuan surat per bulan
 *           dan tahun dengan grafik 6 bulan terakhir.
 *           Fitur: filter periode, statistik, grafik, tabel detail.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Laporan adalah "ruang analisis" untuk pengurus melihat tren
 *    pengajuan surat dari waktu ke waktu.
 * 2. Filter periode: bulan dan tahun (dropdown).
 * 3. Statistik: total, selesai (ACC), pending, ditolak.
 * 4. Grafik 6 bulan terakhir menggunakan Chart.js.
 * 5. Tabel detail semua pengajuan di periode yang dipilih.
 * 6. Tombol cetak laporan menggunakan window.print().
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA PENGURUS ──────────────────────────────────────
wajibPengurus();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Laporan & Statistik';

// ── 4. FILTER PERIODE ─────────────────────────────────────────────
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Validasi rentang bulan dan tahun
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('n');
if ($tahun < 2024 || $tahun > 2030) $tahun = (int)date('Y');

// ── 5. DAFTAR BULAN BAHASA INDONESIA ─────────────────────────────
$bln_id2 = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// ── 6. QUERY LAPORAN ──────────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query Laporan Bulanan
$q = $koneksi->query(
    "SELECT p.*, u.nama AS nama_warga, u.NIK, s.status
     FROM tb_pengajuan p
     JOIN tb_users u ON u.id_user = p.id_user
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     WHERE MONTH(p.created_at) = $bulan AND YEAR(p.created_at) = $tahun
     ORDER BY p.created_at DESC"
);

$laporan = [];
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $laporan[] = $r;
    }
}

// ── 7. STATISTIK ──────────────────────────────────────────────────
$stat_total   = count($laporan);
$stat_selesai = count(array_filter($laporan, function($r) {
    return ($r['status'] ?? '') === 'ACC';
}));
$stat_pending = count(array_filter($laporan, function($r) {
    return ($r['status'] ?? '') === 'Pending';
}));
$stat_tolak   = count(array_filter($laporan, function($r) {
    return ($r['status'] ?? '') === 'Tolak';
}));

// ── 8. GRAFIK 6 BULAN ─────────────────────────────────────────────
$chart_labels = [];
$chart_data   = [];
$bln_id_short = [
    '', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

for ($i = 5; $i >= 0; $i--) {
    $ts  = strtotime("-$i months");
    $b   = (int)date('n', $ts);
    $t   = (int)date('Y', $ts);
    $chart_labels[] = $bln_id_short[$b] . ' ' . $t;

    $n = $koneksi->query(
        "SELECT COUNT(*) AS n FROM tb_pengajuan
         WHERE MONTH(created_at) = $b AND YEAR(created_at) = $t"
    )->fetch_assoc()['n'] ?? 0;
    $chart_data[] = (int)$n;
}

// ── 9. INCLUDE HEADER ─────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Laporan & Statistik -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-bar-chart-line" style="color:var(--blue-600)"></i> Laporan & Statistik
            </h1>
            <div class="page-sub">
                <?= date('l', mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'))); ?>,
                <?= formatTanggal(date('Y-m-d')) ?>
            </div>
        </div>
        <button onclick="window.print()" class="btn-st btn-primary-st btn-pill no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- ── 10. 4 KARTU STATISTIK ─────────────────────────────────────── -->
<div class="row g-3 mb-4 no-print">
    <?php foreach ([
        ['Total Bulan Ini', $stat_total,   'bi-file-text',   '#2563EB', '#EFF6FF'],
        ['Selesai (ACC)',   $stat_selesai, 'bi-check-circle', '#059669', '#D1FAE5'],
        ['Pending',         $stat_pending, 'bi-hourglass',   '#D97706', '#FEF3C7'],
        ['Ditolak',         $stat_tolak,   'bi-x-circle',    '#DC2626', '#FEE2E2'],
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

<!-- ── 11. GRAFIK 6 BULAN ────────────────────────────────────────── -->
<!-- ★ SCREENSHOT untuk Bab 4 → Grafik Statistik Pengajuan -->
<div class="card-st mb-4 no-print">
    <div class="card-header-st">
        <h6 class="card-title-st">
            <i class="bi bi-bar-chart" style="color:var(--blue-600)"></i> Grafik 6 Bulan Terakhir
        </h6>
    </div>
    <div class="card-body-st">
        <canvas id="chartLap" height="70"></canvas>
    </div>
</div>

<!-- ── 12. FORM FILTER ───────────────────────────────────────────── -->
<div class="card-st mb-4 no-print">
    <div class="card-body-st">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label-st">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php for ($b = 1; $b <= 12; $b++): ?>
                    <option value="<?= $b ?>" <?= $b === $bulan ? 'selected' : '' ?>>
                        <?= $bln_id2[$b] ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label-st">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-st btn-primary-st btn-pill w-100">
                    <i class="bi bi-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── 13. HEADER CETAK (hanya tampil saat print) ───────────────── -->
<div id="headerCetak" style="display:none;text-align:center;margin-bottom:24px">
    <h4 style="font-weight:700;margin:0">KARANG TARUNA RW 01</h4>
    <p style="color:#555;margin:4px 0 0">Kelurahan Kelapa Dua, Kec. Kebon Jeruk, Jakarta Barat</p>
    <hr>
    <h5>LAPORAN PENGAJUAN SURAT</h5>
    <p>Periode: <?= $bln_id2[$bulan] . ' ' . $tahun ?></p>
</div>

<!-- ── 14. TABEL LAPORAN ─────────────────────────────────────────── -->
<!-- ★ SCREENSHOT untuk Bab 4 → Tabel Rekap Laporan -->
<div class="card-st">
    <div class="card-header-st">
        <h6 class="card-title-st">
            <i class="bi bi-table" style="color:var(--blue-600)"></i>
            Rekap <?= $bln_id2[$bulan] . ' ' . $tahun ?>
            <span style="color:var(--slate-400);font-weight:400">
                (<?= $stat_total ?> pengajuan)
            </span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-st mb-0">
            <thead>
                <tr><th>No</th><th>Nama Warga</th><th>NIK</th>
                    <th>Jenis Surat</th><th>No. Surat</th><th>Tanggal</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($laporan): foreach ($laporan as $i => $row): ?>
            <tr>
                <td style="color:var(--slate-400)"><?= $i + 1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($row['nama_warga']) ?></td>
                <td><small style="font-family:monospace;color:var(--slate-500)"><?= htmlspecialchars($row['NIK']) ?></small></td>
                <td style="font-size:.845rem"><?= htmlspecialchars($row['jenis_surat']) ?></td>
                <td>
                    <?php if (!empty($row['nomor_surat'])): ?>
                    <span style="color:var(--blue-600);font-weight:700;font-size:.82rem;font-family:monospace">
                        <?= htmlspecialchars($row['nomor_surat']) ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--slate-300)">—</span>
                    <?php endif; ?>
                </td>
                <td><small><?= formatTanggal($row['created_at']) ?></small></td>
                <td><?= badgeStatus($row['status'] ?? 'Pending') ?></td>
            </tr>
            <?php endforeach;
            else: ?>
            <tr><td colspan="7">
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h6>Tidak ada data periode ini</h6>
                </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($laporan): ?>
    <div style="padding:9px 15px;background:var(--slate-50);font-size:.75rem;
                color:var(--slate-400);border-top:1px solid var(--slate-100)" class="no-print">
        Dicetak oleh: <?= htmlspecialchars($_SESSION['nama'] ?? 'Pengurus') ?> —
        <?= formatTanggalWaktu(date('Y-m-d H:i:s')) ?>
    </div>
    <?php endif; ?>
</div>

<?php
// ── 15. SCRIPT CHART.JS ───────────────────────────────────────────
$extra_script = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("chartLap");
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

// Tampilkan header cetak saat print
window.addEventListener("beforeprint", function() {
    document.getElementById("headerCetak").style.display = "block";
});
window.addEventListener("afterprint", function() {
    document.getElementById("headerCetak").style.display = "none";
});
</script>
';

// ── 16. INCLUDE FOOTER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/footer.php';
?>