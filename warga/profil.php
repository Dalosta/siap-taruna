<?php
/**
 * ============================================================
 * FILE    : profil.php
 * LOKASI  : /warga/profil.php
 * FUNGSI  : Menampilkan dan mengedit data profil warga.
 *           Layout split: kiri = kartu info, kanan = form edit.
 *           Field: nama, NIK, username, no HP, alamat,
 *           tempat lahir, tanggal lahir, jenis kelamin,
 *           agama, pekerjaan, status pernikahan, RT.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Halaman ini adalah "kartu identitas" digital untuk warga.
 * 2. Kartu info menampilkan semua data yang tersimpan di database.
 * 3. Form edit memiliki semua field yang sama dengan kartu info.
 * 4. Password baru bersifat opsional (kosongkan jika tidak diubah).
 * 5. Validasi dilakukan di handlers/proses_profil.php.
 * 6. Setelah update, session diperbarui dan redirect ke halaman ini.
 * 7. Tema slate modern dengan aksen biru netral.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA WARGA ──────────────────────────────────────────
wajibWarga();

// ── 3. JUDUL HALAMAN ─────────────────────────────────────────────
$judul_halaman = 'Profil Saya';

// ── 4. AMBIL DATA USER DARI DATABASE ─────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query Data Profil Warga
$idUser = (int)$_SESSION['id_user'];
$q = $koneksi->query("SELECT * FROM tb_users WHERE id_user = $idUser LIMIT 1");

// ── 5. CEK DATA DITEMUKAN ────────────────────────────────────────
if (!$q || $q->num_rows === 0) {
    redirectDengan(APP_URL . '/warga/dashboard.php', 'error', 'Data profil tidak ditemukan.');
}
$data = $q->fetch_assoc();

// ── 6. INCLUDE HEADER ────────────────────────────────────────────
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ★ SCREENSHOT untuk Bab 4 → Tampilan Halaman Profil Warga -->
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">
                <i class="bi bi-person-circle" style="color:var(--blue-600)"></i> Profil Saya
            </h1>
            <div class="page-sub">Dashboard &rsaquo; <span>Profil</span></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ── 7. PANEL KIRI: KARTU INFORMASI ────────────────────────── -->
    <div class="col-md-4">
        <div class="card-st text-center h-100">
            <div class="card-body-st" style="padding:28px 18px">
                <!-- Avatar -->
                <div style="width:70px;height:70px;
                            background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
                            border-radius:18px;margin:0 auto 14px;display:flex;
                            align-items:center;justify-content:center;font-size:1.8rem;
                            font-weight:800;color:white;text-transform:uppercase">
                    <?= inisial($data['nama']) ?>
                </div>

                <!-- Nama & Role -->
                <h6 style="font-weight:800;margin-bottom:5px">
                    <?= htmlspecialchars($data['nama']) ?>
                </h6>
                <span style="background:var(--blue-50);color:var(--blue-700);padding:3px 12px;
                             border-radius:99px;font-size:.75rem;font-weight:700">
                    👤 Warga
                </span>

                <hr style="margin:14px 0;border-color:var(--slate-200)">

                <!-- Detail Informasi -->
                <?php
                $fields = [
                    ['bi-person',      $data['username'],            'Username'],
                    ['bi-credit-card', $data['NIK'],                 'NIK'],
                    ['bi-calendar',    $data['tempat_lahir'] ?? '—', 'Tempat Lahir'],
                    ['bi-calendar3',   $data['tanggal_lahir'] ?? '—','Tanggal Lahir'],
                    ['bi-gender-ambiguous', $data['jenis_kelamin'] ?? '—', 'Jenis Kelamin'],
                    ['bi-building',    $data['agama'] ?? '—',        'Agama'],
                    ['bi-briefcase',   $data['pekerjaan'] ?? '—',    'Pekerjaan'],
                    ['bi-heart',       $data['status_pernikahan'] ?? '—', 'Status Pernikahan'],
                    ['bi-geo-alt',     $data['alamat'] ?? '—',       'Alamat'],
                    ['bi-house-door',  'RT ' . ($data['rt'] ?? '—'), 'RT'],
                    ['bi-telephone',   $data['no_hp'] ?? '—',        'No. HP'],
                ];
                foreach ($fields as [$ico, $val, $lbl]): ?>
                <div style="display:flex;align-items:center;gap:8px;font-size:.8rem;
                            color:var(--slate-500);margin-bottom:7px;text-align:left">
                    <i class="bi <?= $ico ?>" style="color:var(--blue-500);width:15px;flex-shrink:0"
                       title="<?= $lbl ?>"></i>
                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($val) ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <!-- Tanggal Bergabung -->
                <div style="margin-top:12px;padding:8px 12px;background:var(--blue-50);
                            border-radius:8px;font-size:.76rem;color:var(--blue-700)">
                    <i class="bi bi-calendar-check me-1"></i>
                    Bergabung: <?= formatTanggal($data['created_at']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 8. PANEL KANAN: FORM EDIT PROFIL ──────────────────────── -->
    <div class="col-md-8">
        <div class="card-st">
            <div class="card-header-st">
                <h6 class="card-title-st">
                    <i class="bi bi-pencil" style="color:var(--blue-600)"></i> Edit Informasi
                </h6>
            </div>
            <div class="card-body-st">
                <form method="POST" action="<?= APP_URL ?>/handlers/proses_profil.php">
                    <div class="row g-3">
                        <!-- Nama -->
                        <div class="col-md-6">
                            <label class="form-label-st">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control"
                                   value="<?= htmlspecialchars($data['nama']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-st">No. HP</label>
                            <input type="text" name="no_hp" class="form-control"
                                   value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>"
                                   placeholder="08xx-xxxx-xxxx">
                        </div>

                        <!-- Tempat & Tanggal Lahir -->
                        <div class="col-md-6">
                            <label class="form-label-st">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control"
                                   value="<?= htmlspecialchars($data['tempat_lahir'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-st">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control"
                                   value="<?= htmlspecialchars($data['tanggal_lahir'] ?? '') ?>">
                        </div>

                        <!-- Jenis Kelamin, Agama, Pekerjaan, Status -->
                        <div class="col-md-3">
                            <label class="form-label-st">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="">Pilih</option>
                                <option value="Laki-laki" <?= ($data['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($data['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-st">Agama</label>
                            <select name="agama" class="form-select">
                                <option value="">Pilih</option>
                                <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                                <option value="<?= $ag ?>" <?= ($data['agama'] ?? '') === $ag ? 'selected' : '' ?>><?= $ag ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-st">Pekerjaan</label>
                            <input type="text" name="pekerjaan" class="form-control"
                                   value="<?= htmlspecialchars($data['pekerjaan'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-st">Status Pernikahan</label>
                            <select name="status_pernikahan" class="form-select">
                                <option value="">Pilih</option>
                                <option value="Belum Kawin" <?= ($data['status_pernikahan'] ?? '') === 'Belum Kawin' ? 'selected' : '' ?>>Belum Kawin</option>
                                <option value="Kawin" <?= ($data['status_pernikahan'] ?? '') === 'Kawin' ? 'selected' : '' ?>>Kawin</option>
                                <option value="Cerai Hidup" <?= ($data['status_pernikahan'] ?? '') === 'Cerai Hidup' ? 'selected' : '' ?>>Cerai Hidup</option>
                                <option value="Cerai Mati" <?= ($data['status_pernikahan'] ?? '') === 'Cerai Mati' ? 'selected' : '' ?>>Cerai Mati</option>
                            </select>
                        </div>

                        <!-- Alamat & RT -->
                        <div class="col-md-8">
                            <label class="form-label-st">Alamat</label>
                            <input type="text" name="alamat" class="form-control"
                                   value="<?= htmlspecialchars($data['alamat'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-st">RT</label>
                            <input type="text" name="rt" class="form-control"
                                   value="<?= htmlspecialchars($data['rt'] ?? '') ?>"
                                   placeholder="Contoh: 001">
                        </div>

                        <!-- Password Baru -->
                        <div class="col-12">
                            <hr style="border-color:var(--slate-200)">
                            <label class="form-label-st">
                                Password Baru
                                <span style="color:var(--slate-400);font-weight:400">
                                    (kosongkan jika tidak diubah)
                                </span>
                            </label>
                            <input type="password" name="password_baru" class="form-control"
                                   placeholder="Minimal 8 karakter" minlength="8">
                        </div>

                        <!-- Tombol Simpan -->
                        <div class="col-12 text-end">
                            <button type="submit" class="btn-st btn-primary-st btn-pill">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>