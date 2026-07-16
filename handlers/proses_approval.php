<?php
/**
 * =============================================================================
 * FILE    : proses_approval.php
 * SISTEM  : SIAP TARUNA v1.0.0 (Versi Simpel)
 * LOKASI  : /handlers/proses_approval.php
 * FUNGSI  : Memproses keputusan pengurus terhadap pengajuan surat.
 *           Aksi yang tersedia: ACC, Diproses, Revisi, Tolak.
 *           Jika ACC: generate nomor surat otomatis dan simpan ke arsip.
 *           Kirim notifikasi ke warga pemohon untuk setiap perubahan status.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * =============================================================================
 * 
 * PENJELASAN SINGKAT:
 * File ini adalah "inti" dari proses verifikasi surat. Pengurus membuka detail
 * pengajuan, memilih keputusan, menulis catatan (jika perlu), dan sistem
 * memprosemua itu di sini.
 * 
 * KONSEP:
 * - Hanya pengurus yang bisa mengakses (wajibPengurus()).
 * - Validasi: Revisi dan Tolak WAJIB disertai catatan.
 * - Jika ACC: generate nomor surat dengan genNomorSurat(), update tb_pengajuan,
 *   dan simpan ke tb_arsip.
 * - Status disimpan di tb_status sebagai riwayat.
 * - Notifikasi dikirim ke warga pemohon untuk setiap perubahan.
 * - Redirect ke inbox dengan pesan sukses atau error.
 * 
 * ALUR KERJA:
 * 1. Pengurus mengirim POST dari modal detail dengan id_pengajuan, aksi, dan catatan.
 * 2. Validasi: aksi valid, id_pengajuan ada, catatan wajib untuk Revisi/Tolak.
 * 3. Ambil data pengajuan (untuk notifikasi).
 * 4. Insert status baru ke tb_status.
 * 5. Jika ACC: generate nomor surat, update tb_pengajuan, insert ke tb_arsip.
 * 6. Kirim notifikasi ke warga pemohon.
 * 7. Redirect ke inbox dengan pesan sukses.
 * =============================================================================
 */

// ── KONEKSI & FUNGSI ─────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── PAKSA HANYA PENGURUS ────────────────────────────────────────
wajibPengurus();

// ── CEK METODE REQUEST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pengurus/inbox.php');
}

// ── AMBIL INPUT ──────────────────────────────────────────────────
$idPengajuan = bersihInt($_POST['id_pengajuan'] ?? 0);
$aksi        = trim($_POST['aksi'] ?? '');
$catatan     = trim($_POST['catatan'] ?? '');

// ── VALIDASI: AKSI VALID ────────────────────────────────────────
$aksiValid = ['ACC', 'Diproses', 'Revisi', 'Tolak'];
if (!$idPengajuan || !in_array($aksi, $aksiValid)) {
    redirectDengan(APP_URL . '/pengurus/inbox.php', 'error', 'Data tidak valid.');
}

// ── VALIDASI: CATATAN WAJIB UNTUK REVISI & TOLAK ──────────────
if (in_array($aksi, ['Tolak', 'Revisi']) && empty($catatan)) {
    redirectDengan(
        APP_URL . '/pengurus/inbox.php',
        'error',
        'Catatan wajib diisi jika aksi adalah Tolak atau Revisi.'
    );
}

// ── CEK PENGAJUAN ADA ────────────────────────────────────────────
$q = $koneksi->query("SELECT * FROM tb_pengajuan WHERE id_pengajuan = $idPengajuan LIMIT 1");
if (!$q || $q->num_rows === 0) {
    redirectDengan(APP_URL . '/pengurus/inbox.php', 'error', 'Pengajuan tidak ditemukan.');
}
$pj = $q->fetch_assoc();

// ── TAMBAH RIWAYAT STATUS BARU ──────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Proses UPDATE Status Pengajuan
$cat_esc   = $koneksi->real_escape_string($catatan);
$idPetugas = (int)$_SESSION['id_user'];

$koneksi->query(
    "INSERT INTO tb_status (id_pengajuan, status, catatan, updated_by, created_at)
     VALUES ($idPengajuan, '$aksi', '$cat_esc', $idPetugas, NOW())"
);

// ── PROSES KHUSUS JIKA ACC ───────────────────────────────────────
$nomor_surat = '';
if ($aksi === 'ACC') {
    // Generate nomor surat resmi
    $nomor_surat = genNomorSurat();
    $ns = $koneksi->real_escape_string($nomor_surat);
    
    // Update nomor_surat di tb_pengajuan
    $koneksi->query(
        "UPDATE tb_pengajuan SET nomor_surat = '$ns' WHERE id_pengajuan = $idPengajuan"
    );
    
    // Simpan ke arsip (cegah duplikat dengan INSERT IGNORE)
    $koneksi->query(
        "INSERT IGNORE INTO tb_arsip (id_pengajuan, created_at)
         VALUES ($idPengajuan, NOW())"
    );
}

// ── NOTIFIKASI KE WARGA PEMOHON ──────────────────────────────────
$idWarga    = (int)$pj['id_user'];
$jenisSurat = $pj['jenis_surat'];

// Tentukan judul dan pesan notifikasi berdasarkan aksi
[$judul, $pesan] = match($aksi) {
    'ACC'      => [
        'Surat Disetujui ✅',
        "Pengajuan $jenisSurat Anda telah disetujui. No. Surat: $nomor_surat"
    ],
    'Tolak'    => [
        'Pengajuan Ditolak ❌',
        "Pengajuan $jenisSurat Anda ditolak. Alasan: $catatan"
    ],
    'Revisi'   => [
        'Perlu Revisi 🔁',
        "Pengajuan $jenisSurat Anda perlu revisi. Catatan: $catatan"
    ],
    'Diproses' => [
        'Sedang Diproses 🔄',
        "Pengajuan $jenisSurat Anda sedang diproses oleh pengurus."
    ],
    default    => [
        'Update Status',
        'Status pengajuan Anda telah diperbarui.'
    ],
};

// Kirim notifikasi ke warga pemohon
kirimNotifikasi($idWarga, $idPengajuan, $judul, $pesan);

// ── REDIRECT DENGAN PESAN SUKSES ─────────────────────────────────
$pesanSukses = match($aksi) {
    'ACC'      => "✅ Surat disetujui! Nomor: <strong>$nomor_surat</strong>",
    'Tolak'    => '❌ Pengajuan berhasil ditolak.',
    'Revisi'   => '🔁 Pengajuan dikembalikan untuk revisi.',
    'Diproses' => '🔄 Pengajuan ditandai sedang diproses.',
    default    => 'Status berhasil diperbarui.',
};

redirectDengan(APP_URL . '/pengurus/inbox.php', 'sukses', $pesanSukses);