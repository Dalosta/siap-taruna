<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : proses_pengajuan.php                                  ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /proses/proses_pengajuan.php                          ║
// ║  FUNGSI  : Memproses form pengajuan surat oleh warga.            ║
// ║            Validasi, upload file, simpan DB, notifikasi.         ║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/../fungsi.php';
wajibWarga();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/warga/pengajuan.php');
}

// ── [1] AMBIL & VALIDASI INPUT ────────────────────────────────────
$jenis_surat   = trim($_POST['jenis_surat']   ?? '');
$perihal       = trim($_POST['perihal']       ?? '');
$catatan_warga = trim($_POST['catatan_warga'] ?? '');

$errors = [];
if (kosong($jenis_surat)) $errors[] = 'Jenis surat wajib dipilih.';
if (kosong($perihal))     $errors[] = 'Keperluan / perihal wajib diisi.';

if ($errors) {
    redirectDengan(APP_URL.'/warga/pengajuan.php','error',implode(' ',$errors));
}

// ── [2] UPLOAD FILE LAMPIRAN (OPSIONAL) ──────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Proses Upload File Lampiran
$namaFile = '';
if (!empty($_FILES['file_lampiran']['name'])) {
    $up = uploadFile($_FILES['file_lampiran']);
    if (!$up['sukses']) {
        redirectDengan(APP_URL.'/warga/pengajuan.php','error',$up['pesan']);
    }
    $namaFile = $up['nama'];
}

// ── [3] SIMPAN KE tb_pengajuan ────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query INSERT Pengajuan
$idUser       = (int)$_SESSION['id_user'];
$kode         = genKodePengajuan();
$js           = $koneksi->real_escape_string($jenis_surat);
$per          = $koneksi->real_escape_string($perihal);
$cat          = $koneksi->real_escape_string($catatan_warga);
$fl           = $koneksi->real_escape_string($namaFile);

$koneksi->query(
    "INSERT INTO tb_pengajuan
        (id_user, kode_pengajuan, jenis_surat, perihal, catatan_warga, file_lampiran, created_at)
     VALUES
        ($idUser, '$kode', '$js', '$per', '$cat', '$fl', NOW())"
);
$idPengajuan = (int)$koneksi->insert_id;

// ── [4] BUAT STATUS AWAL: PENDING ────────────────────────────────
$koneksi->query(
    "INSERT INTO tb_status (id_pengajuan, status, created_at)
     VALUES ($idPengajuan, 'Pending', NOW())"
);

// ── [5] NOTIFIKASI KE SEMUA PENGURUS AKTIF ───────────────────────
$nama_warga = $_SESSION['nama'];
$pg = $koneksi->query(
    "SELECT id_user FROM tb_users WHERE role='pengurus' AND is_aktif=1"
);
while ($p = $pg->fetch_assoc()) {
    kirimNotifikasi(
        (int)$p['id_user'], $idPengajuan,
        'Pengajuan Surat Baru 📄',
        "$nama_warga mengajukan $jenis_surat."
    );
}

redirectDengan(
    APP_URL.'/warga/riwayat.php', 'sukses',
    "✅ Pengajuan <strong>$jenis_surat</strong> berhasil dikirim! Pengurus akan memproses dalam 1–3 hari kerja."
);