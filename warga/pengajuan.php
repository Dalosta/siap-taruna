<?php
/**
 * ============================================================
 * FILE    : pengajuan.php
 * LOKASI  : /warga/pengajuan.php
 * FUNGSI  : Form pengajuan surat oleh warga.
 *           Menampilkan 3 jenis surat prioritas: Domisili,
 *           Tidak Mampu, dan Pengantar RT/RW.
 *           Fitur: step indicator, upload file opsional,
 *           dan preview ringkasan.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Hanya 3 jenis surat yang disediakan berdasarkan analisis
 *    kebutuhan (yang paling sering diminta warga).
 * 2. Upload file bersifat OPSIONAL (tidak wajib).
 * 3. Step indicator visual membantu warga mengikuti alur.
 * 4. Preview ringkasan muncul setelah jenis surat dipilih.
 * 5. Validasi client-side sebelum submit.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA WARGA ──────────────────────────────────────────
wajibWarga();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Ajukan Surat';

// ── 4. 3 JENIS SURAT PILIHAN ─────────────────────────────────────
// ★ HANYA 3 JENIS SURAT (PRIORITAS UTAMA)
$jenis_list = [
    'Surat Keterangan Domisili',
    'Surat Keterangan Tidak Mampu',
    'Surat Pengantar RT/RW',
];

// ── 5. INCLUDE HEADER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Form Pengajuan Surat (3 Jenis Prioritas) -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-pencil-square" style="color:var(--blue-600)"></i>
                Ajukan Surat Pengantar
            </h1>
            <div class="page-sub">Dashboard &rsaquo; <span>Ajukan Surat</span></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form id="formPengajuan" method="POST"
              action="<?= APP_URL ?>/handlers/proses_pengajuan.php"
              enctype="multipart/form-data">

            <!-- ── 6. STEP INDICATOR ────────────────────────────────── -->
            <div style="display:flex;align-items:center;margin-bottom:22px">
                <?php foreach (['Jenis Surat', 'Keperluan', 'Dokumen', 'Kirim'] as $i => $sl):
                    $si = $i + 1;
                    $aktif = $i === 0; ?>
                <div style="display:flex;flex-direction:column;align-items:center;flex:1;text-align:center">
                    <div id="stepC<?= $si ?>"
                         style="width:30px;height:30px;border-radius:50%;display:flex;
                                align-items:center;justify-content:center;
                                font-size:.8rem;font-weight:700;margin-bottom:4px;
                                background:<?= $aktif ? 'var(--blue-600)' : 'var(--slate-200)' ?>;
                                color:<?= $aktif ? 'white' : 'var(--slate-500)' ?>;transition:all .3s">
                        <?= $si ?>
                    </div>
                    <div id="stepL<?= $si ?>"
                         style="font-size:.67rem;font-weight:<?= $aktif ? '700' : '500' ?>;
                                color:<?= $aktif ? 'var(--blue-600)' : 'var(--slate-400)' ?>;transition:all .3s">
                        <?= $sl ?>
                    </div>
                </div>
                <?php if ($i < 3): ?>
                <div id="line<?= $si ?>"
                     style="flex:1;height:2px;background:var(--slate-200);
                            margin-bottom:20px;transition:background .3s"></div>
                <?php endif; endforeach; ?>
            </div>

            <!-- ── 7. STEP 1: JENIS SURAT ──────────────────────────── -->
            <div class="card-st mb-3">
                <div class="card-header-st">
                    <h6 class="card-title-st">
                        <span style="width:22px;height:22px;background:var(--blue-600);color:white;
                                     border-radius:50%;display:inline-flex;align-items:center;
                                     justify-content:center;font-size:.72rem;font-weight:800">1</span>
                        Pilih Jenis Surat *
                    </h6>
                </div>
                <div class="card-body-st">
                    <div class="row g-2">
                        <?php foreach ($jenis_list as $j): ?>
                        <div class="col-md-4 col-12">
                            <div class="jenis-opt" onclick="pilihJenis(this,'<?= addslashes($j) ?>')">
                                <i class="bi bi-file-earmark-text me-2" style="font-size:1rem"></i>
                                <?= htmlspecialchars($j) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="jenis_surat" id="jenisSuratInput" required>
                </div>
            </div>

            <!-- ── 8. STEP 2: KEPERLUAN ─────────────────────────────── -->
            <div class="card-st mb-3">
                <div class="card-header-st">
                    <h6 class="card-title-st">
                        <span style="width:22px;height:22px;background:var(--blue-600);color:white;
                                     border-radius:50%;display:inline-flex;align-items:center;
                                     justify-content:center;font-size:.72rem;font-weight:800">2</span>
                        Keperluan / Perihal *
                    </h6>
                </div>
                <div class="card-body-st">
                    <div class="mb-3">
                        <label class="form-label-st">Jelaskan keperluan surat ini</label>
                        <input type="text" name="perihal" id="perihalInput" class="form-control"
                               placeholder="Contoh: Syarat pendaftaran beasiswa Baznas 2026..."
                               required>
                    </div>
                    <div>
                        <label class="form-label-st">
                            Catatan Tambahan
                            <span style="color:var(--slate-400);font-weight:400">(opsional)</span>
                        </label>
                        <textarea name="catatan_warga" class="form-control" rows="2"
                                  style="resize:none"
                                  placeholder="Informasi tambahan jika diperlukan..."></textarea>
                    </div>
                </div>
            </div>

            <!-- ── 9. STEP 3: UPLOAD DOKUMEN (OPSIONAL) ─────────────── -->
            <div class="card-st mb-4">
                <div class="card-header-st">
                    <h6 class="card-title-st">
                        <span style="width:22px;height:22px;background:var(--blue-600);color:white;
                                     border-radius:50%;display:inline-flex;align-items:center;
                                     justify-content:center;font-size:.72rem;font-weight:800">3</span>
                        Upload Dokumen Pendukung
                        <span style="color:var(--slate-400);font-size:.8rem;font-weight:400">(opsional)</span>
                    </h6>
                </div>
                <div class="card-body-st">
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="file_lampiran" id="fileInput"
                               accept=".jpg,.jpeg,.png,.pdf">
                        <i class="bi bi-cloud-upload"
                           style="font-size:2.2rem;color:var(--blue-400);display:block;margin-bottom:10px"></i>
                        <div id="uploadLabel"
                             style="font-size:.875rem;font-weight:600;color:var(--slate-700);margin-bottom:4px">
                            Klik atau seret file ke sini
                        </div>
                        <div style="font-size:.78rem;color:var(--slate-400)">
                            Format: JPG, PNG, PDF &nbsp;&middot;&nbsp; Maks: 2 MB
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── 10. TOMBOL ────────────────────────────────────────── -->
            <div style="display:flex;justify-content:space-between;gap:12px">
                <a href="<?= APP_URL ?>/warga/dashboard.php"
                   class="btn-st btn-outline-st btn-pill">
                    <i class="bi bi-arrow-left"></i> Batal
                </a>
                <button type="submit" class="btn-st btn-primary-st btn-pill"
                        style="padding:10px 32px">
                    <i class="bi bi-send"></i> Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>

    <!-- ── 11. PANEL KANAN: INFO & PREVIEW ────────────────────────── -->
    <div class="col-lg-4">
        <!-- Info Proses -->
        <div class="card-st mb-3">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-info-circle" style="color:var(--blue-600)"></i> Info Proses
                </h6>
            </div>
            <div class="card-body-st">
                <?php foreach ([
                    ['⏳', 'Estimasi', '1–3 hari kerja'],
                    ['🔔', 'Notifikasi', 'Status otomatis'],
                    ['🖨', 'Cetak PDF', 'Setelah disetujui'],
                    ['📎', 'Format', 'JPG, PNG, PDF ≤2MB'],
                ] as [$ico, $k, $v]): ?>
                <div style="display:flex;gap:11px;margin-bottom:13px">
                    <div style="width:34px;height:34px;background:var(--blue-50);border-radius:9px;
                                display:flex;align-items:center;justify-content:center;
                                font-size:.95rem;flex-shrink:0"><?= $ico ?></div>
                    <div>
                        <div style="font-size:.82rem;font-weight:700"><?= $k ?></div>
                        <div style="font-size:.77rem;color:var(--slate-400)"><?= $v ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ringkasan Pengajuan -->
        <div class="card-st" id="previewCard" style="display:none">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-eye" style="color:var(--blue-600)"></i> Ringkasan
                </h6>
            </div>
            <div class="card-body-st" style="font-size:.845rem">
                <div class="mb-2">
                    <div style="font-size:.7rem;color:var(--slate-400);text-transform:uppercase;
                                letter-spacing:.5px;margin-bottom:3px">Jenis Surat</div>
                    <div style="font-weight:700" id="previewJenis">—</div>
                </div>
                <div class="mb-2">
                    <div style="font-size:.7rem;color:var(--slate-400);text-transform:uppercase;
                                letter-spacing:.5px;margin-bottom:3px">Pemohon</div>
                    <div style="font-weight:700"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                </div>
                <div>
                    <div style="font-size:.7rem;color:var(--slate-400);text-transform:uppercase;
                                letter-spacing:.5px;margin-bottom:3px">Tanggal</div>
                    <div style="font-weight:700"><?= formatTanggal(date('Y-m-d')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ── 12. SCRIPT ──────────────────────────────────────────────────────
$extra_script = <<<JS
<script>
// ── Pilih Jenis Surat ──────────────────────────────────────────────
function pilihJenis(el, val) {
    document.querySelectorAll('.jenis-opt').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('jenisSuratInput').value = val;
    document.getElementById('previewJenis').textContent = val;
    document.getElementById('previewCard').style.display = 'block';
    updateStep(1);
}

// ── Update Step Indicator ──────────────────────────────────────────
function updateStep(active) {
    for (let i = 1; i <= 4; i++) {
        const dot = document.getElementById('stepC' + i);
        const lbl = document.getElementById('stepL' + i);
        const line = document.getElementById('line' + i);
        if (!dot) continue;
        if (i < active) {
            dot.style.background = 'var(--blue-400)';
            dot.style.color = 'white';
            lbl.style.color = 'var(--blue-400)';
            lbl.style.fontWeight = '700';
            if (line) line.style.background = 'var(--blue-400)';
        } else if (i === active) {
            dot.style.background = 'var(--blue-600)';
            dot.style.color = 'white';
            lbl.style.color = 'var(--blue-600)';
            lbl.style.fontWeight = '700';
        }
    }
}

// ── Validasi Sebelum Submit ────────────────────────────────────────
document.getElementById('formPengajuan').addEventListener('submit', function(e) {
    const j = document.getElementById('jenisSuratInput').value.trim();
    const p = document.getElementById('perihalInput').value.trim();
    if (!j) {
        e.preventDefault();
        alert('⚠ Silakan pilih jenis surat terlebih dahulu!');
        return false;
    }
    if (!p) {
        e.preventDefault();
        alert('⚠ Perihal wajib diisi!');
        return false;
    }
    return true;
});

// ── Perihal diisi → update step ────────────────────────────────────
document.getElementById('perihalInput')?.addEventListener('input', function() {
    if (this.value.trim()) updateStep(2);
});

// ── Upload Area ────────────────────────────────────────────────────
const area = document.getElementById('uploadArea');
const inp = document.getElementById('fileInput');

if (inp) {
    inp.addEventListener('change', function() {
        if (this.files[0]) {
            setLabel(this.files[0]);
        }
        updateStep(3);
    });
}

if (area) {
    area.addEventListener('dragover', function(e) {
        e.preventDefault();
        area.classList.add('dragover');
    });
    area.addEventListener('dragleave', function() {
        area.classList.remove('dragover');
    });
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        area.classList.remove('dragover');
        const f = e.dataTransfer.files[0];
        if (f && inp) {
            const dt = new DataTransfer();
            dt.items.add(f);
            inp.files = dt.files;
            setLabel(f);
            updateStep(3);
        }
    });
}

function setLabel(f) {
    const label = document.getElementById('uploadLabel');
    if (label) {
        label.innerHTML = '<i class="bi bi-check-circle-fill" style="color:var(--blue-500)"></i> <strong>' +
            f.name + '</strong> (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)';
    }
    if (area) area.classList.add('dragover');
}

// Jika user tidak upload file, step 3 tetap bisa dilewati (opsional)
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>