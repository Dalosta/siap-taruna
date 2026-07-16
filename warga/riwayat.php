<?php
/**
 * ============================================================
 * FILE    : riwayat.php
 * LOKASI  : /warga/riwayat.php
 * FUNGSI  : Menampilkan seluruh riwayat pengajuan surat milik
 *           warga yang sedang login.
 *           Fitur: filter status, pagination, timeline status,
 *           kartu border warna, tombol cetak & ajukan ulang.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Riwayat adalah "arsip pribadi" warga untuk melihat semua
 *    surat yang pernah diajukan dan status terkininya.
 * 2. Filter status: Semua, Pending, Diproses, Revisi, ACC, Tolak.
 * 3. Timeline visual 4 langkah (Terkirim, Diterima, Diproses, Selesai)
 *    membantu warga memahami progres suratnya.
 * 4. Border warna kartu sesuai status memudahkan identifikasi.
 * 5. Tombol "Cetak PDF" muncul jika status ACC.
 * 6. Tombol "Ajukan Ulang" muncul jika status Tolak atau Revisi.
 * 7. Catatan pengurus ditampilkan jika ada.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA WARGA ──────────────────────────────────────────
wajibWarga();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Riwayat Pengajuan';

// ── 4. AMBIL ID USER ──────────────────────────────────────────────
$idUser = (int)$_SESSION['id_user'];

// ── 5. FILTER ─────────────────────────────────────────────────────
$filter = isset($_GET['filter']) ? $koneksi->real_escape_string($_GET['filter']) : 'semua';

// ── 6. HITUNG JUMLAH PER STATUS ──────────────────────────────────
$hitung = ['semua' => 0];
foreach (['Pending', 'Diproses', 'Revisi', 'ACC', 'Tolak'] as $s) {
    $hitung[$s] = hitungStatusWarga($idUser, $s);
}
$hitung['semua'] = array_sum(array_slice($hitung, 1));

// ── 7. PAGINATION ─────────────────────────────────────────────────
$page  = max(1, (int)($_GET['hal'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

// ── 8. BUILD WHERE CLAUSE ────────────────────────────────────────
$where = "p.id_user = $idUser";
if ($filter !== 'semua') {
    $where .= " AND s.status = '$filter'";
}

// ── 9. QUERY TOTAL DATA ──────────────────────────────────────────
$total_q = $koneksi->query(
    "SELECT COUNT(DISTINCT p.id_pengajuan) AS n FROM tb_pengajuan p
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     WHERE $where"
);
$total = (int)($total_q->fetch_assoc()['n'] ?? 0);
$total_page = ceil($total / $limit);

// ── 10. QUERY DATA RINCI ──────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query Riwayat + Filter Status
$q = $koneksi->query(
    "SELECT p.*, s.status, s.catatan
     FROM tb_pengajuan p
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     WHERE $where
     ORDER BY p.created_at DESC
     LIMIT $limit OFFSET $offset"
);

// ── 11. INCLUDE HEADER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Riwayat Pengajuan -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-list-check" style="color:var(--blue-600)"></i> Riwayat Pengajuan
            </h1>
            <div class="page-sub">Dashboard &rsaquo; <span>Riwayat</span></div>
        </div>
        <a href="<?= APP_URL ?>/warga/pengajuan.php" class="btn-st btn-primary-st btn-pill">
            <i class="bi bi-plus-lg"></i> Ajukan Baru
        </a>
    </div>
</div>

<!-- ── 12. FILTER TABS ───────────────────────────────────────────── -->
<div class="filter-tabs">
    <?php
    $tab_list = [
        'semua'    => ['Semua',    'bi-list-ul'],
        'Pending'  => ['Pending',  'bi-hourglass'],
        'Diproses' => ['Diproses', 'bi-arrow-repeat'],
        'Revisi'   => ['Revisi',   'bi-pencil'],
        'ACC'      => ['Selesai',  'bi-check-circle'],
        'Tolak'    => ['Ditolak',  'bi-x-circle'],
    ];
    foreach ($tab_list as $key => [$lbl, $ico]):
        $aktif = ($filter === $key) ? 'active' : '';
    ?>
    <a href="?filter=<?= $key ?>" class="filter-tab <?= $aktif ?>">
        <i class="bi <?= $ico ?>"></i><?= $lbl ?>
        <span class="filter-count"><?= $hitung[$key] ?? 0 ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php
// ── 13. MAP WARNA UNTUK BORDER ───────────────────────────────────
$bc_map = [
    'Pending'  => '#F59E0B',
    'Diproses' => '#3B82F6',
    'Revisi'   => '#8B5CF6',
    'ACC'      => '#10B981',
    'Tolak'    => '#EF4444'
];

// ── 14. TAMPILKAN DATA ────────────────────────────────────────────
if ($q && $q->num_rows > 0):
    while ($row = $q->fetch_assoc()):
        $st_ = $row['status'] ?? 'Pending';
        $bc  = $bc_map[$st_] ?? '#E5E7EB';
        // idx untuk timeline (1=Terkirim, 2=Diterima/Diproses, 3=Selesai)
        $idx = ['Pending' => 1, 'Diproses' => 2, 'Revisi' => 2, 'ACC' => 3, 'Tolak' => 3][$st_] ?? 0;
?>
<!-- ── 15. KARTU RINCI PENGAJUAN ─────────────────────────────────── -->
<div class="card-st mb-3" style="border-left:4px solid <?= $bc ?>">
    <div class="card-body-st">
        <!-- Header: jenis surat, tanggal, kode, status -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
            <div>
                <div style="font-weight:700;font-size:.95rem">
                    <?= htmlspecialchars($row['jenis_surat']) ?>
                </div>
                <div style="font-size:.775rem;color:var(--slate-400)">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= formatTanggal($row['created_at']) ?>
                    &nbsp;&middot;&nbsp;
                    <code style="font-size:.72rem;background:var(--slate-100);
                                 padding:1px 6px;border-radius:4px">
                        <?= htmlspecialchars($row['kode_pengajuan'] ?? '-') ?>
                    </code>
                </div>
            </div>
            <?= badgeStatus($st_) ?>
        </div>

        <!-- ── 16. TIMELINE VISUAL ────────────────────────────────── -->
        <div class="timeline-wrap" style="margin-bottom:12px">
            <?php
            $steps = ['Terkirim', 'Diterima', 'Diproses', 'Selesai'];
            foreach ($steps as $ti => $ts_):
                $done = $ti <= $idx;
                $err  = ($st_ === 'Tolak' && $ti === 3) || ($st_ === 'Revisi' && $ti === 2);
                $bgD  = $err ? '#EF4444' : ($done ? '#10B981' : '#E5E7EB');
                $txD  = $done ? 'white' : '#9CA3AF';
                $dot  = $err ? '✕' : ($done ? '✓' : $ti + 1);
            ?>
            <div style="display:flex;flex-direction:column;align-items:center;flex:1;text-align:center">
                <div style="width:26px;height:26px;border-radius:50%;
                            background:<?= $bgD ?>;color:<?= $txD ?>;
                            display:flex;align-items:center;justify-content:center;
                            font-size:.7rem;font-weight:800;margin-bottom:4px">
                    <?= $dot ?>
                </div>
                <div style="font-size:.63rem;color:<?= $done ? '#374151' : '#9CA3AF' ?>;
                            font-weight:<?= $ti === $idx ? '700' : '400' ?>">
                    <?= $ts_ ?>
                </div>
            </div>
            <?php if ($ti < 3): ?>
            <div style="flex:1;height:2px;margin-bottom:20px;
                        background:<?= $ti < $idx ? '#10B981' : '#E5E7EB' ?>"></div>
            <?php endif; endforeach; ?>
        </div>

        <!-- ── 17. CATATAN PENGURUS ───────────────────────────────── -->
        <?php if (!empty($row['catatan'])): ?>
        <div style="padding:9px 12px;border-radius:9px;font-size:.835rem;margin-bottom:10px;
                    background:<?= $st_ === 'Tolak' ? '#FEF2F2' : '#FFFBEB' ?>;
                    border:1px solid <?= $st_ === 'Tolak' ? '#FECACA' : '#FDE68A' ?>;
                    color:<?= $st_ === 'Tolak' ? '#991B1B' : '#92400E' ?>">
            <i class="bi bi-chat-left-text me-2"></i>
            <strong>Catatan Pengurus:</strong> <?= htmlspecialchars($row['catatan']) ?>
        </div>
        <?php endif; ?>

        <!-- ── 18. TOMBOL AKSI ────────────────────────────────────── -->
        <?php if ($st_ === 'ACC'): ?>
        <a href="<?= APP_URL ?>/cetak.php?id=<?= $row['id_pengajuan'] ?>" target="_blank"
           class="btn-st btn-sm-st btn-primary-st btn-pill">
            <i class="bi bi-printer"></i> Cetak Surat PDF
        </a>
        <?php elseif (in_array($st_, ['Tolak', 'Revisi'])): ?>
        <a href="<?= APP_URL ?>/warga/pengajuan.php"
           class="btn-st btn-sm-st btn-outline-st btn-pill"
           style="border-color:#F59E0B;color:#D97706">
            <i class="bi bi-arrow-repeat"></i> Ajukan Ulang
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endwhile;
else: ?>
<!-- ── 19. KONDISI DATA KOSONG ──────────────────────────────────── -->
<div class="card-st">
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h6><?= $filter !== 'semua' ? "Tidak ada pengajuan \"$filter\"" : 'Belum ada pengajuan' ?></h6>
        <p>Buat pengajuan surat pertamamu sekarang</p>
        <a href="<?= APP_URL ?>/warga/pengajuan.php" class="btn-st btn-primary-st btn-pill">
            <i class="bi bi-plus-lg"></i> Ajukan Surat
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ── 20. PAGINATION ────────────────────────────────────────────── -->
<?php if ($total_page > 1): ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $total_page; $p++): ?>
    <a href="?filter=<?= $filter ?>&hal=<?= $p ?>"
       style="display:inline-flex;align-items:center;justify-content:center;
              width:34px;height:34px;border-radius:8px;font-size:.84rem;font-weight:600;
              border:1.5px solid <?= $p === $page ? 'var(--blue-600)' : 'var(--slate-300)' ?>;
              background:<?= $p === $page ? 'var(--blue-600)' : 'white' ?>;
              color:<?= $p === $page ? 'white' : 'var(--slate-600)' ?>;text-decoration:none">
        <?= $p ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>