<?php
/**
 * ============================================================
 * FILE    : proses_approval.php
 * SISTEM  : SIAP TARUNA v1.0.0
 * LOKASI  : /handlers/proses_approval.php
 * FUNGSI  : Verifikasi pengurus dengan prepared statement
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

wajibPengurus();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pengurus/inbox.php');
}

// ── AMBIL INPUT ──────────────────────────────────────────────────
$idPengajuan = bersihInt($_POST['id_pengajuan'] ?? 0);
$aksi        = trim($_POST['aksi'] ?? '');
$catatan     = trim($_POST['catatan'] ?? '');

// ── VALIDASI ─────────────────────────────────────────────────────
$aksiValid = ['ACC', 'Diproses', 'Revisi', 'Tolak'];
if (!$idPengajuan || !in_array($aksi, $aksiValid)) {
    redirectDengan(APP_URL . '/pengurus/inbox.php', 'error', 'Data tidak valid.');
}

if (in_array($aksi, ['Tolak', 'Revisi']) && empty($catatan)) {
    redirectDengan(APP_URL . '/pengurus/inbox.php', 'error', 'Catatan wajib diisi untuk Tolak atau Revisi.');
}

// ── CEK PENGAJUAN ADA ──────────────────────────────────────────
$q = $koneksi->query("SELECT * FROM tb_pengajuan WHERE id_pengajuan = $idPengajuan LIMIT 1");
if (!$q || $q->num_rows === 0) {
    redirectDengan(APP_URL . '/pengurus/inbox.php', 'error', 'Pengajuan tidak ditemukan.');
}
$pj = $q->fetch_assoc();

// ── SIMPAN STATUS (PREPARED STATEMENT) ──────────────────────────
$idPetugas = (int)$_SESSION['id_user'];
$stmt = $koneksi->prepare("INSERT INTO tb_status (id_pengajuan, status, catatan, updated_by, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("issi", $idPengajuan, $aksi, $catatan, $idPetugas);
$stmt->execute();
$stmt->close();

// ── JIKA ACC: GENERATE NOMOR SURAT & ARSIP ─────────────────────
$nomor_surat = '';
if ($aksi === 'ACC') {
    $nomor_surat = genNomorSurat();
    $stmt = $koneksi->prepare("UPDATE tb_pengajuan SET nomor_surat = ? WHERE id_pengajuan = ?");
    $stmt->bind_param("si", $nomor_surat, $idPengajuan);
    $stmt->execute();
    $stmt->close();

    $stmt = $koneksi->prepare("INSERT IGNORE INTO tb_arsip (id_pengajuan, created_at) VALUES (?, NOW())");
    $stmt->bind_param("i", $idPengajuan);
    $stmt->execute();
    $stmt->close();
}

// ── NOTIFIKASI KE WARGA ──────────────────────────────────────────
$idWarga    = (int)$pj['id_user'];
$jenisSurat = $pj['jenis_surat'];

[$judul, $pesan] = match($aksi) {
    'ACC' => ['Surat Disetujui ✅', "Pengajuan $jenisSurat disetujui. No: $nomor_surat"],
    'Tolak' => ['Pengajuan Ditolak ❌', "Pengajuan $jenisSurat ditolak. Alasan: $catatan"],
    'Revisi' => ['Perlu Revisi 🔁', "Pengajuan $jenisSurat perlu revisi. Catatan: $catatan"],
    'Diproses' => ['Sedang Diproses 🔄', "Pengajuan $jenisSurat sedang diproses."],
    default => ['Update Status', 'Status pengajuan Anda diperbarui.'],
};
kirimNotifikasi($idWarga, $idPengajuan, $judul, $pesan);

// ── REDIRECT ─────────────────────────────────────────────────────
$pesanSukses = match($aksi) {
    'ACC' => "✅ Surat disetujui! Nomor: <strong>$nomor_surat</strong>",
    'Tolak' => '❌ Pengajuan berhasil ditolak.',
    'Revisi' => '🔁 Pengajuan dikembalikan untuk revisi.',
    'Diproses' => '🔄 Pengajuan ditandai sedang diproses.',
    default => 'Status berhasil diperbarui.',
};

redirectDengan(APP_URL . '/pengurus/inbox.php', 'sukses', $pesanSukses);
