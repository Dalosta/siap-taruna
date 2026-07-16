<?php
/**
 * ============================================================
 * FILE    : proses_pengajuan.php
 * LOKASI  : /handlers/proses_pengajuan.php
 * FUNGSI  : Memproses form pengajuan surat yang dikirim oleh warga.
 *           Validasi, upload file (opsional), simpan ke database,
 *           buat status Pending, dan kirim notifikasi ke pengurus.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Hanya warga yang bisa mengakses (wajibWarga()).
 * 2. Upload file bersifat OPSIONAL (tidak wajib).
 * 3. File yang diupload divalidasi: ukuran ≤ 2MB, format JPG/PNG/PDF.
 * 4. Status awal Pending dicatat di tb_status untuk riwayat.
 * 5. Notifikasi dikirim ke semua pengurus aktif agar segera memproses.
 */

// ── 1. KONEKSI DATABASE & FUNGSI HELPER ──────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA WARGA ──────────────────────────────────────────
// Jika user bukan warga, akan di-redirect ke dashboard pengurus.
wajibWarga();

// ── 3. CEK METODE REQUEST ─────────────────────────────────────────
// Hanya menerima POST dari form pengajuan.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/warga/pengajuan.php');
}

// ── 4. AMBIL INPUT DARI FORM ──────────────────────────────────────
$jenis_surat   = trim($_POST['jenis_surat']   ?? '');
$perihal       = trim($_POST['perihal']       ?? '');
$catatan_warga = trim($_POST['catatan_warga'] ?? '');

// ── 5. VALIDASI INPUT WAJIB ──────────────────────────────────────
// Jenis surat dan perihal wajib diisi. Jika kosong, tampilkan error.
$errors = [];
if (empty($jenis_surat)) {
    $errors[] = 'Jenis surat wajib dipilih.';
}
if (empty($perihal)) {
    $errors[] = 'Keperluan / perihal wajib diisi.';
}

if (!empty($errors)) {
    redirectDengan(
        APP_URL . '/warga/pengajuan.php',
        'error',
        implode(' ', $errors)
    );
}

// ── 6. PROSES UPLOAD FILE LAMPIRAN (OPSIONAL) ────────────────────
// ★ SCREENSHOT untuk Bab 4 → Proses Upload File
// File tidak wajib. Jika ada file yang diupload, validasi dan simpan.
$namaFile = '';
if (!empty($_FILES['file_lampiran']['name'])) {
    $up = uploadFile($_FILES['file_lampiran']);
    
    if (!$up['sukses']) {
        redirectDengan(
            APP_URL . '/warga/pengajuan.php',
            'error',
            $up['pesan']
        );
    }
    $namaFile = $up['nama'];
}

// ── 7. SIMPAN DATA PENGAJUAN KE DATABASE ─────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Query INSERT Pengajuan
$idUser = (int)$_SESSION['id_user'];
$kode   = genKodePengajuan(); // Generate kode unik: PGJ-YYYYMMDD-XXXXX

// Escape semua input untuk keamanan (mencegah SQL Injection).
$js  = $koneksi->real_escape_string($jenis_surat);
$per = $koneksi->real_escape_string($perihal);
$cat = $koneksi->real_escape_string($catatan_warga);
$fl  = $koneksi->real_escape_string($namaFile);

// Query INSERT ke tabel tb_pengajuan.
$sql = "INSERT INTO tb_pengajuan 
        (id_user, kode_pengajuan, jenis_surat, perihal, catatan_warga, file_lampiran, created_at)
        VALUES 
        ($idUser, '$kode', '$js', '$per', '$cat', '$fl', NOW())";

if (!$koneksi->query($sql)) {
    // Jika gagal, tampilkan error (sebaiknya di-log, bukan ditampilkan di produksi).
    die("❌ Gagal menyimpan pengajuan: " . $koneksi->error);
}

$idPengajuan = (int)$koneksi->insert_id;

// ── 8. BUAT STATUS AWAL: PENDING ─────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Insert Status Awal
// Setiap pengajuan dimulai dengan status 'Pending' di tabel tb_status.
$sql_status = "INSERT INTO tb_status (id_pengajuan, status, created_at)
               VALUES ($idPengajuan, 'Pending', NOW())";

if (!$koneksi->query($sql_status)) {
    die("❌ Gagal menyimpan status: " . $koneksi->error);
}

// ── 9. KIRIM NOTIFIKASI KE SEMUA PENGURUS AKTIF ──────────────────
// ★ SCREENSHOT untuk Bab 4 → Kirim Notifikasi ke Pengurus
// Pengurus mendapat notifikasi agar segera memproses pengajuan baru.
$nama_warga = $_SESSION['nama'] ?? 'Warga';
$pg = $koneksi->query("SELECT id_user FROM tb_users WHERE role = 'pengurus' AND is_aktif = 1");

if ($pg) {
    while ($p = $pg->fetch_assoc()) {
        kirimNotifikasi(
            (int)$p['id_user'],
            $idPengajuan,
            'Pengajuan Surat Baru 📄',
            "$nama_warga mengajukan surat $jenis_surat."
        );
    }
}

// ── 10. REDIRECT DENGAN PESAN SUKSES ─────────────────────────────
// Setelah semua proses selesai, arahkan warga ke halaman riwayat.
redirectDengan(
    APP_URL . '/warga/riwayat.php',
    'sukses',
    "✅ Pengajuan <strong>$jenis_surat</strong> berhasil dikirim! " .
    "Pengurus akan memproses dalam 1–3 hari kerja."
);