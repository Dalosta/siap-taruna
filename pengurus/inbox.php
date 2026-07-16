<?php
/**
 * ============================================================
 * FILE    : inbox.php
 * LOKASI  : /pengurus/inbox.php
 * FUNGSI  : Menampilkan daftar pengajuan surat masuk kepada pengurus.
 *           Fitur: filter status, pagination, modal detail,
 *           dan tombol aksi (ACC, Diproses, Revisi, Tolak).
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Inbox adalah "meja kerja" pengurus untuk memproses surat.
 * 2. Filter status membantu pengurus fokus pada surat yang perlu tindakan.
 * 3. Modal detail menampilkan semua informasi lengkap + form approval.
 * 4. Aksi yang tersedia: ACC (setujui), Diproses, Revisi, Tolak.
 * 5. Setiap aksi memanggil handlers/proses_approval.php.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA PENGURUS ──────────────────────────────────────
wajibPengurus();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Inbox Pengajuan';

// ── 4. FILTER ─────────────────────────────────────────────────────
$filter = isset($_GET['filter']) ? $koneksi->real_escape_string($_GET['filter']) : 'semua';

// ── 5. HITUNG JUMLAH PER STATUS ──────────────────────────────────
$hitung = ['semua' => 0];
foreach (['Pending', 'Diproses', 'Revisi', 'ACC', 'Tolak'] as $s) {
    $hitung[$s] = hitungStatusGlobal($s);
}
$hitung['semua'] = (int)($koneksi->query("SELECT COUNT(*) AS n FROM tb_pengajuan")->fetch_assoc()['n'] ?? 0);

// ── 6. PAGINATION ─────────────────────────────────────────────────
$page  = max(1, (int)($_GET['hal'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
if ($filter !== 'semua') {
    $where .= " AND s.status = '$filter'";
}

// ── 7. TOTAL DATA ─────────────────────────────────────────────────
$total = (int)($koneksi->query(
    "SELECT COUNT(DISTINCT p.id_pengajuan) AS n FROM tb_pengajuan p
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     $where"
)->fetch_assoc()['n'] ?? 0);
$total_page = ceil($total / $limit);

// ── 8. QUERY DATA ─────────────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query JOIN Inbox Pengajuan
$q = $koneksi->query(
    "SELECT p.*, u.nama AS nama_warga, u.NIK, u.alamat, u.no_hp,
            s.status, s.catatan AS catatan_st, s.updated_by
     FROM tb_pengajuan p
     JOIN tb_users u ON u.id_user = p.id_user
     LEFT JOIN tb_status s ON s.id_status = (
         SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
     )
     $where
     ORDER BY p.created_at DESC
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

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Inbox Pengajuan -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-inbox" style="color:var(--blue-600)"></i> Inbox Pengajuan
            </h1>
            <div class="page-sub"><?= $hitung['semua'] ?> total pengajuan masuk</div>
        </div>
    </div>
</div>

<!-- ── 10. FILTER TAB ─────────────────────────────────────────────── -->
<div class="filter-tabs">
    <?php foreach ([
        'semua'    => ['Semua', 'bi-list-ul'],
        'Pending'  => ['Pending', 'bi-hourglass'],
        'Diproses' => ['Diproses', 'bi-arrow-repeat'],
        'Revisi'   => ['Revisi', 'bi-pencil'],
        'ACC'      => ['Selesai', 'bi-check-circle'],
        'Tolak'    => ['Ditolak', 'bi-x-circle'],
    ] as $key => [$label, $icon]): ?>
    <a href="?filter=<?= $key ?>" class="filter-tab <?= $filter === $key ? 'active' : '' ?>">
        <i class="bi <?= $icon ?>"></i><?= $label ?>
        <span class="filter-count"><?= $hitung[$key] ?? 0 ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── 11. TABEL ─────────────────────────────────────────────────── -->
<div class="card-st">
    <div class="table-responsive">
        <table class="table table-st mb-0">
            <thead>
                <tr><th>No</th><th>Nama Warga</th><th>Jenis Surat</th>
                    <th>Perihal</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $i => $row):
                $st_ = $row['status'] ?? 'Pending';
            ?>
            <tr>
                <td style="color:var(--slate-400)"><?= $offset + $i + 1 ?></td>
                <td>
                    <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($row['nama_warga']) ?></div>
                    <div style="font-size:.75rem;color:var(--slate-400)"><?= htmlspecialchars(mb_substr($row['alamat'] ?? '', 0, 28)) ?></div>
                </td>
                <td style="font-size:.845rem"><?= htmlspecialchars($row['jenis_surat']) ?></td>
                <td style="font-size:.835rem;color:var(--slate-500)"><?= htmlspecialchars(mb_substr($row['perihal'] ?? '', 0, 34)) ?></td>
                <td><small style="color:var(--slate-500)"><?= formatTanggal($row['created_at']) ?></small></td>
                <td><?= badgeStatus($st_) ?></td>
                <td>
                    <button class="btn-st btn-sm-st btn-primary-st btn-pill"
                            data-bs-toggle="modal"
                            data-bs-target="#modal<?= $row['id_pengajuan'] ?>">
                        <i class="bi bi-eye"></i>Detail
                    </button>
                </td>
            </tr>
            <?php endforeach;
            else: ?>
            <tr><td colspan="7">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h6>Tidak ada pengajuan <?= $filter !== 'semua' ? '"' . $filter . '"' : '' ?></h6>
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

<!-- ── 13. ═══ MODALS ══════════════════════════════════════════════ -->
<!-- ★ SCREENSHOT untuk Bab 4 → Modal Detail + Form Approval -->
<?php foreach ($rows as $row):
    $st_ = $row['status'] ?? 'Pending';
    $id  = $row['id_pengajuan'];
?>
<div class="modal fade" id="modal<?= $id ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <!-- Header Modal -->
      <div class="modal-header"
           style="background:var(--slate-800);color:white;border:none;
                  border-radius:14px 14px 0 0;padding:16px 20px">
        <div>
            <h5 class="modal-title fw-bold mb-0">📄 Detail Pengajuan</h5>
            <small style="opacity:.65"><?= htmlspecialchars($row['kode_pengajuan'] ?? '#' . $id) ?></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body Modal -->
      <div class="modal-body p-4">
        <div class="row g-3 mb-3">
          <!-- Data Warga -->
          <div class="col-md-6">
            <div style="background:var(--slate-50);border-radius:12px;padding:15px">
              <div style="font-size:.67rem;font-weight:800;color:var(--slate-600);
                          text-transform:uppercase;letter-spacing:1px;margin-bottom:11px">
                  👤 Data Warga
              </div>
              <?php foreach ([
                ['Nama',   $row['nama_warga']],
                ['NIK',    $row['NIK']],
                ['Alamat', $row['alamat'] ?? '—'],
                ['No. HP', $row['no_hp'] ?? '—'],
              ] as [$k, $v]): ?>
              <div style="margin-bottom:7px">
                  <div style="font-size:.7rem;color:var(--slate-400)"><?= $k ?></div>
                  <div style="font-weight:600;font-size:.845rem"><?= htmlspecialchars($v) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Data Surat -->
          <div class="col-md-6">
            <div style="background:var(--slate-50);border-radius:12px;padding:15px">
              <div style="font-size:.67rem;font-weight:800;color:var(--slate-600);
                          text-transform:uppercase;letter-spacing:1px;margin-bottom:11px">
                  📄 Data Surat
              </div>
              <?php foreach ([
                ['Jenis',     $row['jenis_surat']],
                ['Perihal',   $row['perihal']],
                ['Tanggal',   formatTanggalWaktu($row['created_at'])],
                ['No. Surat', $row['nomor_surat'] ?? '—'],
              ] as [$k, $v]): ?>
              <div style="margin-bottom:7px">
                  <div style="font-size:.7rem;color:var(--slate-400)"><?= $k ?></div>
                  <div style="font-weight:600;font-size:.845rem"><?= htmlspecialchars($v) ?></div>
              </div>
              <?php endforeach; ?>
              <div>
                  <div style="font-size:.7rem;color:var(--slate-400)">Status</div>
                  <div style="margin-top:3px"><?= badgeStatus($st_) ?></div>
              </div>
            </div>
          </div>

          <!-- File Lampiran -->
          <?php if (!empty($row['file_lampiran'])): ?>
          <div class="col-12">
            <a href="<?= UPLOAD_URL . htmlspecialchars($row['file_lampiran']) ?>" target="_blank"
               style="display:flex;align-items:center;gap:11px;padding:11px 14px;
                      border-radius:10px;border:1.5px solid var(--slate-200);
                      text-decoration:none;color:var(--slate-700);font-size:.845rem;
                      font-weight:600;transition:all .2s;background:white">
                <i class="bi bi-file-earmark-arrow-down" style="font-size:1.3rem"></i>
                <div>
                    <div><?= htmlspecialchars($row['file_lampiran']) ?></div>
                    <small style="color:var(--slate-400);font-weight:400">Klik untuk buka</small>
                </div>
                <i class="bi bi-box-arrow-up-right ms-auto" style="color:var(--slate-400)"></i>
            </a>
          </div>
          <?php endif; ?>

          <!-- Catatan Sebelumnya -->
          <?php if (!empty($row['catatan_st'])): ?>
          <div class="col-12">
            <div style="padding:11px 14px;border-radius:10px;font-size:.845rem;
                        background:<?= $st_ === 'Tolak' ? '#FEF2F2' : '#FFFBEB' ?>;
                        border:1px solid <?= $st_ === 'Tolak' ? '#FECACA' : '#FDE68A' ?>;
                        color:<?= $st_ === 'Tolak' ? '#991B1B' : '#92400E' ?>">
                <i class="bi bi-chat-left-text me-2"></i>
                <strong>Catatan:</strong> <?= htmlspecialchars($row['catatan_st']) ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- ── 14. FORM APPROVAL ──────────────────────────────────── -->
        <?php if (in_array($st_, ['Pending', 'Diproses', 'Revisi'])): ?>
        <div style="border-top:1px solid var(--slate-100);padding-top:16px">
            <div style="font-size:.67rem;font-weight:800;color:var(--slate-600);
                        text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">
                ✅ Keputusan Pengurus
            </div>
            <form method="POST" action="<?= APP_URL ?>/handlers/proses_approval.php">
                <input type="hidden" name="id_pengajuan" value="<?= $id ?>">
                <div class="mb-3">
                    <label class="form-label-st">
                        Catatan
                        <span style="color:var(--slate-400);font-weight:400">
                            (wajib jika Tolak/Revisi)
                        </span>
                    </label>
                    <textarea name="catatan" class="form-control" rows="2"
                              style="resize:none"
                              placeholder="Tuliskan catatan untuk warga..."></textarea>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" name="aksi" value="ACC"
                            class="btn-st flex-fill"
                            style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;font-weight:700;border-radius:9px"
                            onclick="return confirm('Setujui pengajuan ini?')">
                        ✅ Setujui
                    </button>
                    <button type="submit" name="aksi" value="Diproses"
                            class="btn-st flex-fill"
                            style="background:#DBEAFE;color:#1E40AF;border:1px solid #BFDBFE;font-weight:700;border-radius:9px">
                        🔄 Proses
                    </button>
                    <button type="submit" name="aksi" value="Revisi"
                            class="btn-st flex-fill"
                            style="background:#F3E8FF;color:#6B21A8;border:1px solid #DDD6FE;font-weight:700;border-radius:9px">
                        🔁 Revisi
                    </button>
                    <button type="submit" name="aksi" value="Tolak"
                            class="btn-st flex-fill"
                            style="background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;font-weight:700;border-radius:9px"
                            onclick="return confirm('Tolak pengajuan ini?')">
                        ❌ Tolak
                    </button>
                </div>
            </form>
        </div>
        <?php elseif ($st_ === 'ACC'): ?>
        <div style="border-top:1px solid var(--slate-100);padding-top:14px;text-align:center">
            <a href="<?= APP_URL ?>/cetak.php?id=<?= $id ?>" target="_blank"
               class="btn-st btn-primary-st btn-pill">
                <i class="bi bi-printer"></i> Cetak Surat PDF
            </a>
        </div>
        <?php else: ?>
        <div style="border-top:1px solid var(--slate-100);padding-top:12px;text-align:center;
                    font-size:.845rem;color:var(--slate-400)">
            Pengajuan selesai diproses — <?= badgeStatus($st_) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>