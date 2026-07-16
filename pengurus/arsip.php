<?php
/**
 * ============================================================
 * FILE    : arsip.php
 * LOKASI  : /pengurus/arsip.php
 * FUNGSI  : Menampilkan daftar arsip surat yang telah diterbitkan (ACC).
 *           Fitur: pencarian berdasarkan nama warga, NIK, nomor surat,
 *           atau jenis surat, pagination, dan tombol cetak ulang.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Arsip adalah "lemari arsip" digital untuk surat-surat yang sudah selesai.
 * 2. Hanya menampilkan surat yang sudah ACC dan masuk tb_arsip.
 * 3. Pencarian fleksibel: nama warga, NIK, nomor surat, atau jenis surat.
 * 4. Pagination 15 item per halaman.
 * 5. Tombol cetak ulang untuk setiap arsip (memanggil cetak.php).
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA PENGURUS ──────────────────────────────────────
wajibPengurus();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Arsip Surat';

// ── 4. PENCARIAN ──────────────────────────────────────────────────
$cari = trim($_GET['cari'] ?? '');

// ── 5. PAGINATION ─────────────────────────────────────────────────
$page  = max(1, (int)($_GET['hal'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// ── 6. BUAT WHERE CLAUSE ─────────────────────────────────────────
$where = "WHERE 1=1";
if ($cari) {
    $c = $koneksi->real_escape_string($cari);
    $where .= " AND (u.nama LIKE '%$c%' OR u.NIK LIKE '%$c%'
                     OR p.nomor_surat LIKE '%$c%' OR p.jenis_surat LIKE '%$c%')";
}

// ── 7. TOTAL DATA ─────────────────────────────────────────────────
$total = (int)($koneksi->query(
    "SELECT COUNT(*) AS n FROM tb_arsip a
     JOIN tb_pengajuan p ON p.id_pengajuan = a.id_pengajuan
     JOIN tb_users u ON u.id_user = p.id_user
     $where"
)->fetch_assoc()['n'] ?? 0);
$total_page = ceil($total / $limit);

// ── 8. QUERY DATA ARSIP ──────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query Arsip Surat
$q = $koneksi->query(
    "SELECT a.*, p.jenis_surat, p.nomor_surat, p.perihal, p.created_at AS tgl_pengajuan,
            u.nama AS nama_warga, u.NIK
     FROM tb_arsip a
     JOIN tb_pengajuan p ON p.id_pengajuan = a.id_pengajuan
     JOIN tb_users u ON u.id_user = p.id_user
     $where
     ORDER BY a.created_at DESC
     LIMIT $limit OFFSET $offset"
);

$rows = [];
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $rows[] = $r;
    }
}

// ── 9. INCLUDE HEADER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Halaman Arsip Surat -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-archive" style="color:var(--blue-600)"></i> Arsip Surat
            </h1>
            <div class="page-sub"><?= $total ?> surat telah diterbitkan</div>
        </div>
    </div>
</div>

<!-- ── 10. FORM PENCARIAN ───────────────────────────────────────── -->
<div class="card-st mb-4">
    <div class="card-body-st">
        <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <div style="position:relative;flex:1;min-width:220px">
                <i class="bi bi-search" style="position:absolute;left:12px;top:50%;
                   transform:translateY(-50%);color:var(--slate-400)"></i>
                <input type="text" name="cari" class="form-control"
                       style="padding-left:36px"
                       placeholder="Cari nama, NIK, nomor surat, atau jenis surat..."
                       value="<?= htmlspecialchars($cari) ?>">
            </div>
            <button type="submit" class="btn-st btn-primary-st btn-pill">
                <i class="bi bi-search"></i> Cari
            </button>
            <?php if ($cari): ?>
            <a href="<?= APP_URL ?>/pengurus/arsip.php" class="btn-st btn-outline-st btn-pill">
                <i class="bi bi-x"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ── 11. TABEL ARSIP ────────────────────────────────────────────── -->
<div class="card-st">
    <div class="card-header-st">
        <h6 class="card-title-st">
            <i class="bi bi-archive" style="color:var(--blue-600)"></i> Daftar Arsip Surat
            <?php if ($cari): ?>
            <span style="color:var(--slate-400);font-weight:400">— "<?= htmlspecialchars($cari) ?>"</span>
            <?php endif; ?>
        </h6>
        <span style="font-size:.8rem;color:var(--slate-400)"><?= $total ?> surat</span>
    </div>
    <div class="table-responsive">
        <table class="table table-st mb-0">
            <thead>
                <tr><th>No. Surat</th><th>Nama Warga</th><th>Jenis Surat</th>
                    <th>Tgl Pengajuan</th><th>Tgl Diarsipkan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $row): ?>
            <tr>
                <td>
                    <span style="background:var(--blue-50);color:var(--blue-700);padding:3px 10px;
                                 border-radius:6px;font-size:.8rem;font-weight:700;font-family:monospace">
                        <?= htmlspecialchars($row['nomor_surat']) ?>
                    </span>
                </td>
                <td>
                    <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($row['nama_warga']) ?></div>
                    <div style="font-size:.75rem;color:var(--slate-400)"><?= htmlspecialchars($row['NIK']) ?></div>
                </td>
                <td style="font-size:.845rem"><?= htmlspecialchars($row['jenis_surat']) ?></td>
                <td><small style="color:var(--slate-500)"><?= formatTanggal($row['tgl_pengajuan']) ?></small></td>
                <td><small style="color:var(--slate-500)"><?= formatTanggal($row['created_at']) ?></small></td>
                <td>
                    <a href="<?= APP_URL ?>/cetak.php?id=<?= $row['id_pengajuan'] ?>"
                       target="_blank"
                       class="btn-st btn-sm-st btn-outline-st btn-pill">
                        <i class="bi bi-printer"></i> Cetak
                    </a>
                </td>
            </tr>
            <?php endforeach;
            else: ?>
            <tr><td colspan="6">
                <div class="empty-state">
                    <i class="bi bi-archive"></i>
                    <h6><?= $cari ? "Tidak ditemukan: \"$cari\"" : "Belum ada arsip surat" ?></h6>
                </div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── 12. PAGINATION ────────────────────────────────────────────── -->
<?php if ($total_page > 1): ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:18px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $total_page; $p++): ?>
    <a href="?cari=<?= urlencode($cari) ?>&hal=<?= $p ?>"
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