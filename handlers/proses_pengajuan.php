<?php
/**
 * ============================================================
 * FILE    : proses_pengajuan.php
 * SISTEM  : SIAP TARUNA v1.0.0
 * LOKASI  : /handlers/proses_pengajuan.php
 * FUNGSI  : Memproses pengajuan surat dengan prepared statement
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

wajibWarga();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/warga/pengajuan.php');
}

// ── AMBIL & VALIDASI INPUT ──────────────────────────────────────
$jenis_surat   = trim($_POST['jenis_surat'] ?? '');
$perihal       = trim($_POST['perihal'] ?? '');
$catatan_warga = trim($_POST['catatan_warga'] ?? '');

if (empty($jenis_surat) || empty($perihal)) {
    redirectDengan(APP_URL . '/warga/pengajuan.php', 'error', 'Jenis surat dan perihal wajib diisi.');
}

// ── UPLOAD FILE (OPSIONAL) ──────────────────────────────────────
$namaFile = '';
if (!empty($_FILES['file_lampiran']['name'])) {
    $up = uploadFile($_FILES['file_lampiran']);
    if (!$up['sukses']) {
        redirectDengan(APP_URL . '/warga/pengajuan.php', 'error', $up['pesan']);
    }
    $namaFile = $up['nama'];
}

// ── SIMPAN KE DATABASE (PREPARED STATEMENT) ────────────────────
$idUser = (int)$_SESSION['id_user'];
$kode   = genKodePengajuan();

$stmt = $koneksi->prepare("
    INSERT INTO tb_pengajuan 
    (id_user, kode_pengajuan, jenis_surat, perihal, catatan_warga, file_lampiran, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param("isssss", $idUser, $kode, $jenis_surat, $perihal, $catatan_warga, $namaFile);
$stmt->execute();
$idPengajuan = $stmt->insert_id;
$stmt->close();

// ── STATUS AWAL PENDING ──────────────────────────────────────────
$stmt = $koneksi->prepare("INSERT INTO tb_status (id_pengajuan, status, created_at) VALUES (?, 'Pending', NOW())");
$stmt->bind_param("i", $idPengajuan);
$stmt->execute();
$stmt->close();

// ── NOTIFIKASI KE PENGURUS ───────────────────────────────────────
$nama_warga = $_SESSION['nama'] ?? 'Warga';
$pg = $koneksi->query("SELECT id_user FROM tb_users WHERE role='pengurus' AND is_aktif=1");
if ($pg) {
    while ($p = $pg->fetch_assoc()) {
        kirimNotifikasi((int)$p['id_user'], $idPengajuan, 'Pengajuan Surat Baru 📄', "$nama_warga mengajukan surat $jenis_surat.");
    }
}

redirectDengan(APP_URL . '/warga/riwayat.php', 'sukses', "✅ Pengajuan <strong>$jenis_surat</strong> berhasil dikirim!");
