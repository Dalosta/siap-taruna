<?php
/**
 * ============================================================
 * FILE    : data-warga.php
 * LOKASI  : /pengurus/data-warga.php
 * FUNGSI  : Menampilkan dan mengelola data warga yang terdaftar.
 *           Fitur: pencarian, filter jabatan, pagination,
 *           grid kartu warga, modal tambah warga, dan hapus.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Data warga ditampilkan dalam kartu grid (bukan tabel) agar lebih
 *    modern dan informatif.
 * 2. Setiap kartu menampilkan: inisial nama, nama, jabatan,
 *    jumlah pengajuan, NIK, username, no HP, dan alamat.
 * 3. Pencarian berdasarkan nama, NIK, atau username.
 * 4. Filter berdasarkan jabatan (opsional).
 * 5. Pagination 12 item per halaman.
 * 6. Modal tambah warga dengan validasi: NIK 16 digit, username unik,
 *    password minimal 8 karakter.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA PENGURUS ──────────────────────────────────────
wajibPengurus();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Data Warga';

// ── 4. FILTER PENCARIAN ──────────────────────────────────────────
$cari    = trim($_GET['cari'] ?? '');
$jabatan = trim($_GET['jabatan'] ?? '');

// ── 5. PAGINATION ─────────────────────────────────────────────────
$page  = max(1, (int)($_GET['hal'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// ── 6. BUAT WHERE CLAUSE ─────────────────────────────────────────
$where = "WHERE u.role = 'warga'";

if ($cari) {
    $c = $koneksi->real_escape_string($cari);
    $where .= " AND (u.nama LIKE '%$c%' OR u.NIK LIKE '%$c%' OR u.username LIKE '%$c%')";
}

if ($jabatan) {
    $jb = $koneksi->real_escape_string($jabatan);
    $where .= " AND u.jabatan = '$jb'";
}

// ── 7. TOTAL DATA ─────────────────────────────────────────────────
$total = (int)($koneksi->query(
    "SELECT COUNT(*) AS n FROM tb_users u $where"
)->fetch_assoc()['n'] ?? 0);
$total_page = ceil($total / $limit);

// ── 8. QUERY DATA WARGA ──────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query Data Warga + Jumlah Pengajuan
$q = $koneksi->query(
    "SELECT u.*,
            (SELECT COUNT(*) FROM tb_pengajuan WHERE id_user = u.id_user) AS jml_pengajuan
     FROM tb_users u $where
     ORDER BY u.nama ASC
     LIMIT $limit OFFSET $offset"
);

$warga = [];
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $warga[] = $r;
    }
}

// ── 9. TOTAL WARGA (untuk ditampilkan di header) ─────────────────
$total_warga = (int)($koneksi->query(
    "SELECT COUNT(*) AS n FROM tb_users WHERE role = 'warga'"
)->fetch_assoc()['n'] ?? 0);

// ── 10. AMBIL DAFTAR JABATAN UNIK (untuk filter) ────────────────
$jabatan_list = [];
$qj = $koneksi->query(
    "SELECT DISTINCT jabatan FROM tb_users
     WHERE role = 'warga' AND jabatan IS NOT NULL AND jabatan != ''
     ORDER BY jabatan"
);
if ($qj) {
    while ($r = $qj->fetch_assoc()) {
        $jabatan_list[] = $r['jabatan'];
    }
}

// ── 11. WARNA AVATAR ROTASI ──────────────────────────────────────
$av_colors = [
    '#1E293B', '#2563EB', '#7C2D12', '#5B21B6',
    '#0F4C81', '#831843', '#047857', '#B45309'
];

// ── 12. INCLUDE HEADER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Data Warga -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-people" style="color:var(--blue-600)"></i> Data Warga
            </h1>
            <div class="page-sub"><?= $total_warga ?> warga terdaftar</div>
        </div>
        <button class="btn-st btn-primary-st btn-pill"
                data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-person-plus"></i> Tambah Warga
        </button>
    </div>
</div>

<!-- ── 13. FORM PENCARIAN ────────────────────────────────────────── -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <div style="position:relative;flex:1;min-width:200px">
        <i class="bi bi-search" style="position:absolute;left:12px;top:50%;
           transform:translateY(-50%);color:var(--slate-400)"></i>
        <input type="text" name="cari" class="form-control"
               style="padding-left:36px"
               placeholder="Cari nama, NIK, atau username..."
               value="<?= htmlspecialchars($cari) ?>">
    </div>
    <select name="jabatan" class="form-select" style="max-width:180px"
            onchange="this.form.submit()">
        <option value="">Semua Jabatan</option>
        <?php foreach ($jabatan_list as $jb): ?>
        <option value="<?= htmlspecialchars($jb) ?>" <?= $jabatan === $jb ? 'selected' : '' ?>>
            <?= htmlspecialchars($jb) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-st btn-primary-st btn-pill">
        <i class="bi bi-search"></i> Cari
    </button>
    <?php if ($cari || $jabatan): ?>
    <a href="<?= APP_URL ?>/pengurus/data-warga.php" class="btn-st btn-outline-st btn-pill">
        <i class="bi bi-x"></i> Reset
    </a>
    <?php endif; ?>
</form>

<!-- ── 14. GRID KARTU WARGA ─────────────────────────────────────── -->
<div class="row g-3">
<?php if ($warga): foreach ($warga as $idx => $w):
    $av_clr = $av_colors[$idx % count($av_colors)];
?>
<div class="col-md-6 col-lg-4">
    <div class="card-st h-100"
         style="transition:var(--ease)"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 28px rgba(0,0,0,.08)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div class="card-body-st">
            <!-- Header kartu: avatar & nama -->
            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:13px">
                <div style="width:44px;height:44px;background:<?= $av_clr ?>;
                            border-radius:11px;color:white;display:flex;align-items:center;
                            justify-content:center;font-weight:800;font-size:1.05rem;
                            flex-shrink:0;text-transform:uppercase">
                    <?= inisial($w['nama']) ?>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:.9rem;white-space:nowrap;
                                overflow:hidden;text-overflow:ellipsis">
                        <?= htmlspecialchars($w['nama']) ?>
                    </div>
                    <div style="font-size:.75rem;color:var(--slate-400)">
                        <?= htmlspecialchars($w['jabatan'] ?? 'Warga') ?>
                    </div>
                    <span style="background:var(--blue-50);color:var(--blue-700);padding:2px 9px;
                                 border-radius:99px;font-size:.7rem;font-weight:600">
                        <i class="bi bi-file-text"></i> <?= $w['jml_pengajuan'] ?> pengajuan
                    </span>
                </div>
            </div>

            <!-- Informasi detail -->
            <?php foreach ([
                ['bi-credit-card', $w['NIK']],
                ['bi-person',      '@' . $w['username']],
                ['bi-telephone',   $w['no_hp'] ?? '—'],
                ['bi-geo-alt',     mb_substr($w['alamat'] ?? '—', 0, 34)],
            ] as [$ico, $val]): ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:.79rem;
                        color:var(--slate-500);margin-bottom:5px;min-width:0">
                <i class="bi <?= $ico ?>" style="color:var(--blue-500);width:14px;flex-shrink:0"></i>
                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= htmlspecialchars($val) ?>
                </span>
            </div>
            <?php endforeach; ?>

            <!-- Tombol hapus -->
            <div style="margin-top:13px;padding-top:11px;border-top:1px solid var(--slate-100)">
                <a href="<?= APP_URL ?>/handlers/proses_warga.php?aksi=hapus&id=<?= $w['id_user'] ?>"
                   class="btn-st btn-sm-st btn-danger-st w-100"
                   style="border-radius:8px"
                   data-confirm="Yakin ingin menghapus warga <?= htmlspecialchars(addslashes($w['nama'])) ?>? Data tidak dapat dikembalikan.">
                    <i class="bi bi-trash"></i> Hapus Warga
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach;
else: ?>
<div class="col-12">
    <div class="card-st">
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <h6><?= ($cari || $jabatan) ? 'Tidak ditemukan warga yang cocok' : 'Belum ada warga terdaftar' ?></h6>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<!-- ── 15. PAGINATION ────────────────────────────────────────────── -->
<?php if ($total_page > 1): ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:18px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $total_page; $p++): ?>
    <a href="?cari=<?= urlencode($cari) ?>&jabatan=<?= urlencode($jabatan) ?>&hal=<?= $p ?>"
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

<!-- ── 16. ═══ MODAL TAMBAH WARGA ══════════════════════════════════ -->
<!-- ★ SCREENSHOT untuk Bab 4 → Modal Tambah Warga -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"
           style="background:var(--slate-800);color:white;border:none;
                  border-radius:14px 14px 0 0;padding:16px 20px">
        <h5 class="modal-title fw-bold">
            <i class="bi bi-person-plus me-2"></i>Tambah Warga Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="<?= APP_URL ?>/handlers/proses_warga.php">
        <input type="hidden" name="aksi" value="tambah">
        <div class="modal-body p-4">
            <div class="row g-3">
                <!-- Nama & NIK -->
                <div class="col-md-6">
                    <label class="form-label-st">Nama Lengkap *</label>
                    <input type="text" name="nama" class="form-control"
                           placeholder="Sesuai KTP" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-st">NIK *</label>
                    <input type="text" name="NIK" class="form-control"
                           placeholder="16 digit angka" maxlength="16" required>
                </div>

                <!-- Username & Password -->
                <div class="col-md-6">
                    <label class="form-label-st">Username *</label>
                    <input type="text" name="username" class="form-control"
                           placeholder="Untuk login" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-st">Password *</label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Min. 8 karakter" required>
                </div>

                <!-- No HP & Jabatan -->
                <div class="col-md-6">
                    <label class="form-label-st">No. HP</label>
                    <input type="text" name="no_hp" class="form-control"
                           placeholder="08xx-xxxx-xxxx">
                </div>
                <div class="col-md-6">
                    <label class="form-label-st">Jabatan</label>
                    <select name="jabatan" class="form-select">
                        <option value="">Warga</option>
                        <?php foreach (['Sekretaris','Wakil Sekretaris','Bendahara',
                            'Wakil Bendahara','Bidang Pendidikan','Bidang Kesejahteraan',
                            'Bidang Olahraga & Seni','Bidang Lingkungan','Bidang Humas'] as $jb): ?>
                        <option value="<?= $jb ?>"><?= $jb ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Alamat -->
                <div class="col-12">
                    <label class="form-label-st">Alamat</label>
                    <input type="text" name="alamat" class="form-control"
                           placeholder="RT/RW, Kelurahan Kelapa Dua">
                </div>
            </div>

            <!-- Info tambahan -->
            <div style="margin-top:14px;padding:11px 14px;background:var(--blue-50);
                        border-radius:10px;font-size:.8rem;color:#166534">
                <i class="bi bi-info-circle me-2"></i>
                Akun langsung aktif dan dapat digunakan untuk login setelah disimpan.
            </div>
        </div>

        <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn-st btn-outline-st btn-pill"
                    data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn-st btn-primary-st btn-pill">
                <i class="bi bi-person-check"></i> Simpan Warga
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>