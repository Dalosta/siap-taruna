<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : proses_approval.php                                   ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /proses/proses_approval.php                           ║
// ║  FUNGSI  : Memproses keputusan pengurus atas pengajuan.          ║
// ║            Aksi: ACC | Diproses | Revisi | Tolak                 ║
// ║            Jika ACC: generate nomor surat & simpan arsip.        ║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/../fungsi.php';
wajibPengurus();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/pengurus/inbox.php');
}

// ── [1] VALIDASI INPUT ────────────────────────────────────────────
$idPengajuan = bersihInt($_POST['id_pengajuan'] ?? 0);
$aksi        = trim($_POST['aksi']    ?? '');
$catatan     = trim($_POST['catatan'] ?? '');
$aksiValid   = ['ACC','Diproses','Revisi','Tolak'];

if (!$idPengajuan || !in_array($aksi, $aksiValid)) {
    redirectDengan(APP_URL.'/pengurus/inbox.php','error','Data tidak valid.');
}
if (in_array($aksi,['Tolak','Revisi']) && kosong($catatan)) {
    redirectDengan(
        APP_URL.'/pengurus/inbox.php','error',
        'Catatan wajib diisi jika aksi adalah Tolak atau Revisi.'
    );
}

// ── [2] CEK PENGAJUAN ADA ─────────────────────────────────────────
$q   = $koneksi->query("SELECT * FROM tb_pengajuan WHERE id_pengajuan=$idPengajuan LIMIT 1");
if (!$q || $q->num_rows === 0) {
    redirectDengan(APP_URL.'/pengurus/inbox.php','error','Pengajuan tidak ditemukan.');
}
$pj = $q->fetch_assoc();

// ── [3] TAMBAH RIWAYAT STATUS BARU ───────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Proses UPDATE Status Pengajuan
$cat_esc  = $koneksi->real_escape_string($catatan);
$idPetugas = (int)$_SESSION['id_user'];
$koneksi->query(
    "INSERT INTO tb_status (id_pengajuan, status, catatan, updated_by, created_at)
     VALUES ($idPengajuan, '$aksi', '$cat_esc', $idPetugas, NOW())"
);

// ── [4] PROSES KHUSUS JIKA ACC ────────────────────────────────────
$nomor_surat = '';
if ($aksi === 'ACC') {
    $nomor_surat = genNomorSurat();
    $ns = $koneksi->real_escape_string($nomor_surat);
    $koneksi->query(
        "UPDATE tb_pengajuan SET nomor_surat='$ns' WHERE id_pengajuan=$idPengajuan"
    );
    // Simpan ke arsip (cegah duplikat dengan INSERT IGNORE)
    $koneksi->query(
        "INSERT IGNORE INTO tb_arsip (id_pengajuan, created_at)
         VALUES ($idPengajuan, NOW())"
    );
}

// ── [5] NOTIFIKASI KE WARGA PEMOHON ──────────────────────────────
$idWarga    = (int)$pj['id_user'];
$jenisSurat = $pj['jenis_surat'];

[$judul, $pesan] = match($aksi) {
    'ACC'      => ['Surat Disetujui ✅', "Pengajuan $jenisSurat Anda telah disetujui. No. Surat: $nomor_surat"],
    'Tolak'    => ['Pengajuan Ditolak ❌', "Pengajuan $jenisSurat Anda ditolak. Alasan: $catatan"],
    'Revisi'   => ['Perlu Revisi 🔁', "Pengajuan $jenisSurat Anda perlu revisi. Catatan: $catatan"],
    'Diproses' => ['Sedang Diproses 🔄', "Pengajuan $jenisSurat Anda sedang diproses oleh pengurus."],
    default    => ['Update Status', 'Status pengajuan Anda telah diperbarui.'],
};
kirimNotifikasi($idWarga, $idPengajuan, $judul, $pesan);

// ── [6] REDIRECT DENGAN PESAN ────────────────────────────────────
$pesanSukses = match($aksi) {
    'ACC'      => "✅ Surat disetujui! Nomor: <strong>$nomor_surat</strong>",
    'Tolak'    => '❌ Pengajuan berhasil ditolak.',
    'Revisi'   => '🔁 Pengajuan dikembalikan untuk revisi.',
    'Diproses' => '🔄 Pengajuan ditandai sedang diproses.',
    default    => 'Status berhasil diperbarui.',
};

redirectDengan(APP_URL.'/pengurus/inbox.php','sukses',$pesanSukses);