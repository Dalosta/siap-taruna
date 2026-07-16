<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : fungsi.php                                            ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /fungsi.php                                           ║
// ║  FUNGSI  : Kumpulan fungsi helper yang digunakan di seluruh      ║
// ║            halaman sistem. Mencakup: autentikasi, sanitasi,      ║
// ║            format tanggal, upload file, generate kode, dll.      ║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/koneksi.php';

// ═══════════════════════════════════════════════════════════════════
// SESSION & AUTENTIKASI
// ═══════════════════════════════════════════════════════════════════

/**
 * Mulai session jika belum aktif
 */
function mulaiSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('SIAPTARUNA_SESS');
        session_start();
    }
}

/**
 * Cek apakah user sudah login
 */
function sudahLogin(): bool {
    mulaiSession();
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}

/**
 * Ambil data user yang sedang login
 */
function getUser(): ?array {
    if (!sudahLogin()) return null;
    return [
        'id_user'  => $_SESSION['id_user']  ?? null,
        'nama'     => $_SESSION['nama']     ?? '',
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role']     ?? '',
        'jabatan'  => $_SESSION['jabatan']  ?? '',
        'NIK'      => $_SESSION['NIK']      ?? '',
        'no_hp'    => $_SESSION['no_hp']    ?? '',
        'alamat'   => $_SESSION['alamat']   ?? '',
    ];
}

/**
 * Ambil role user aktif
 */
function getRole(): string {
    mulaiSession();
    return $_SESSION['role'] ?? '';
}

/**
 * Paksa halaman hanya bisa diakses user login
 * ★ SCREENSHOT untuk Bab 4 → Implementasi Proteksi Halaman
 */
function wajibLogin(): void {
    mulaiSession();
    if (!sudahLogin()) {
        redirect('../login.php?pesan=login_dulu');
    }
}

/**
 * Paksa halaman hanya untuk role warga
 */
function wajibWarga(): void {
    wajibLogin();
    if (getRole() !== 'warga') {
        redirect('../pengurus/dashboard.php');
    }
}

/**
 * Paksa halaman hanya untuk role pengurus
 */
function wajibPengurus(): void {
    wajibLogin();
    if (getRole() !== 'pengurus') {
        redirect('../warga/dashboard.php');
    }
}

/**
 * Set session setelah login berhasil
 */
function setSession(array $user): void {
    mulaiSession();
    $_SESSION['id_user']  = $user['id_user'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['jabatan']  = $user['jabatan']  ?? '';
    $_SESSION['NIK']      = $user['NIK']      ?? '';
    $_SESSION['no_hp']    = $user['no_hp']    ?? '';
    $_SESSION['alamat']   = $user['alamat']   ?? '';
}

/**
 * Hapus session (logout)
 */
function hapusSession(): void {
    mulaiSession();
    $_SESSION = [];
    session_destroy();
}

// ═══════════════════════════════════════════════════════════════════
// NAVIGASI & REDIRECT
// ═══════════════════════════════════════════════════════════════════

/**
 * Redirect ke URL tertentu
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Redirect dengan pesan flash
 */
function redirectDengan(string $url, string $tipe, string $pesan): void {
    mulaiSession();
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    redirect($url);
}

/**
 * Tampilkan dan hapus pesan flash
 * ★ SCREENSHOT untuk Bab 4 → Notifikasi Flash Message
 */
function tampilFlash(): string {
    mulaiSession();
    if (!isset($_SESSION['flash'])) return '';
    $f    = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $tipe = $f['tipe'];
    $msg  = htmlspecialchars($f['pesan']);
    $ico  = match($tipe) {
        'sukses'  => 'bi-check-circle-fill',
        'error'   => 'bi-exclamation-circle-fill',
        'peringatan' => 'bi-exclamation-triangle-fill',
        default   => 'bi-info-circle-fill',
    };
    $bg = match($tipe) {
        'sukses'  => '#D1FAE5',
        'error'   => '#FEE2E2',
        'peringatan' => '#FEF3C7',
        default   => '#DBEAFE',
    };
    $clr = match($tipe) {
        'sukses'  => '#065F46',
        'error'   => '#991B1B',
        'peringatan' => '#92400E',
        default   => '#1E40AF',
    };
    return "
    <div class='flash-msg' style='
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;border-radius:12px;
        background:$bg;color:$clr;
        font-size:.875rem;margin-bottom:18px;'>
        <i class='bi $ico' style='font-size:1.05rem;flex-shrink:0'></i>
        <span>$msg</span>
        <button onclick='this.parentElement.remove()'
            style='margin-left:auto;background:none;border:none;
                   cursor:pointer;font-size:1.1rem;opacity:.6;
                   color:inherit'>&times;</button>
    </div>";
}

// ═══════════════════════════════════════════════════════════════════
// SANITASI & VALIDASI
// ═══════════════════════════════════════════════════════════════════

/**
 * Bersihkan input dari karakter berbahaya
 */
function bersihkan(string $input): string {
    global $koneksi;
    return $koneksi->real_escape_string(
        htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Sanitasi integer
 */
function bersihInt($val): int {
    return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Cek apakah string kosong
 */
function kosong(string $val): bool {
    return trim($val) === '';
}

// ═══════════════════════════════════════════════════════════════════
// FORMAT TANGGAL & WAKTU
// ═══════════════════════════════════════════════════════════════════

/**
 * Format tanggal ke Bahasa Indonesia
 * Contoh: "29 Mei 2026"
 */
function formatTanggal(string $tgl, string $format = 'd F Y'): string {
    if (empty($tgl) || $tgl === '0000-00-00') return '—';
    $bulan = [
        1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
        5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
        9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
    ];
    $ts  = strtotime($tgl);
    $bln = $bulan[(int)date('n', $ts)];
    return date('d', $ts) . ' ' . $bln . ' ' . date('Y', $ts);
}

/**
 * Format tanggal + waktu
 * Contoh: "29 Mei 2026, 14.30 WIB"
 */
function formatTanggalWaktu(string $tgl): string {
    if (empty($tgl)) return '—';
    return formatTanggal($tgl) . ', ' . date('H.i', strtotime($tgl)) . ' WIB';
}

/**
 * Tanggal relatif — "2 jam lalu", "kemarin", dst
 */
function relatifWaktu(string $tgl): string {
    $detik = time() - strtotime($tgl);
    if ($detik < 60)     return 'baru saja';
    if ($detik < 3600)   return floor($detik/60) . ' menit lalu';
    if ($detik < 86400)  return floor($detik/3600) . ' jam lalu';
    if ($detik < 172800) return 'kemarin';
    return formatTanggal($tgl);
}

/**
 * Konversi bulan angka ke Romawi
 */
function bulanRomawi(int $bulan): string {
    return ['','I','II','III','IV','V','VI',
            'VII','VIII','IX','X','XI','XII'][$bulan] ?? '';
}

// ═══════════════════════════════════════════════════════════════════
// GENERATE KODE & NOMOR
// ═══════════════════════════════════════════════════════════════════

/**
 * Generate kode pengajuan unik
 * Format: PGJ-YYYYMMDD-XXXXX
 * ★ SCREENSHOT untuk Bab 4 → Generate Kode Pengajuan
 */
function genKodePengajuan(): string {
    return 'PGJ-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
}

/**
 * Generate nomor surat resmi berurutan
 * Format: 001/KT-RW01/V/2026
 * ★ SCREENSHOT untuk Bab 4 → Generate Nomor Surat Resmi
 */
function genNomorSurat(): string {
    global $koneksi;
    $bln   = (int)date('n');
    $thn   = date('Y');
    $rom   = bulanRomawi($bln);
    $q     = $koneksi->query(
        "SELECT COUNT(*) AS jml FROM tb_arsip
         WHERE MONTH(created_at)=$bln AND YEAR(created_at)=$thn"
    );
    $urut  = ($q->fetch_assoc()['jml'] ?? 0) + 1;
    return sprintf('%03d/KT-RW01/%s/%s', $urut, $rom, $thn);
}

// ═══════════════════════════════════════════════════════════════════
// UPLOAD FILE
// ═══════════════════════════════════════════════════════════════════

/**
 * Proses upload file lampiran
 * @return array ['sukses'=>bool, 'nama'=>string, 'pesan'=>string]
 * ★ SCREENSHOT untuk Bab 4 → Implementasi Upload File
 */
function uploadFile(array $file): array {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['sukses' => true, 'nama' => '', 'pesan' => ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Upload gagal (kode error: ' . $file['error'] . ')'];
    }
    if ($file['size'] > MAX_UPLOAD) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Ukuran file maksimal 2 MB'];
    }
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ekstensi, ['jpg','jpeg','png','pdf'])) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Format file tidak didukung (JPG/PNG/PDF)'];
    }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $namaFile = 'lamp_' . time() . '_' . mt_rand(1000,9999) . '.' . $ekstensi;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $namaFile)) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Gagal menyimpan file ke server'];
    }
    return ['sukses' => true, 'nama' => $namaFile, 'pesan' => ''];
}

// ═══════════════════════════════════════════════════════════════════
// QUERY DATABASE HELPER
// ═══════════════════════════════════════════════════════════════════

/**
 * Hitung pengajuan berdasarkan status untuk user tertentu
 */
function hitungStatusWarga(int $idUser, string $status): int {
    global $koneksi;
    $s  = $koneksi->real_escape_string($status);
    $q  = $koneksi->query(
        "SELECT COUNT(*) AS jml FROM tb_pengajuan p
         JOIN tb_status st ON st.id_pengajuan = p.id_pengajuan
         WHERE p.id_user = $idUser AND st.status = '$s'
         AND st.id_status = (
             SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
         )"
    );
    return (int)($q->fetch_assoc()['jml'] ?? 0);
}

/**
 * Ambil status terbaru dari suatu pengajuan
 */
function getStatusTerbaru(int $idPengajuan): array {
    global $koneksi;
    $q = $koneksi->query(
        "SELECT * FROM tb_status
         WHERE id_pengajuan = $idPengajuan
         ORDER BY id_status DESC LIMIT 1"
    );
    return $q ? ($q->fetch_assoc() ?? []) : [];
}

/**
 * Hitung pengajuan global berdasarkan status (untuk pengurus)
 */
function hitungStatusGlobal(string $status): int {
    global $koneksi;
    $s = $koneksi->real_escape_string($status);
    $q = $koneksi->query(
        "SELECT COUNT(DISTINCT p.id_pengajuan) AS jml
         FROM tb_pengajuan p
         JOIN tb_status st ON st.id_pengajuan = p.id_pengajuan
         WHERE st.status = '$s'
         AND st.id_status = (
             SELECT MAX(id_status) FROM tb_status WHERE id_pengajuan = p.id_pengajuan
         )"
    );
    return (int)($q->fetch_assoc()['jml'] ?? 0);
}

/**
 * Kirim notifikasi ke user
 */
function kirimNotifikasi(int $idUser, int $idPengajuan, string $judul, string $pesan): void {
    global $koneksi;
    $j = $koneksi->real_escape_string($judul);
    $p = $koneksi->real_escape_string($pesan);
    $koneksi->query(
        "INSERT INTO tb_notifikasi (id_user, id_pengajuan, judul, pesan, is_read, created_at)
         VALUES ($idUser, $idPengajuan, '$j', '$p', 0, NOW())"
    );
}

// ═══════════════════════════════════════════════════════════════════
// UI HELPER
// ═══════════════════════════════════════════════════════════════════

/**
 * Tampilkan badge status dengan warna sesuai
 * ★ SCREENSHOT untuk Bab 4 → Komponen Badge Status
 */
function badgeStatus(string $status): string {
    $map = [
        'Pending'  => ['#FEF9C3','#854D0E','⏳'],
        'Diproses' => ['#DBEAFE','#1E40AF','🔄'],
        'Revisi'   => ['#F3E8FF','#6B21A8','🔁'],
        'ACC'      => ['#DCFCE7','#166534','✅'],
        'Tolak'    => ['#FEE2E2','#991B1B','❌'],
    ];
    [$bg,$fg,$ico] = $map[$status] ?? ['#F3F4F6','#374151','•'];
    $s = htmlspecialchars($status);
    return "<span style='display:inline-flex;align-items:center;gap:4px;
        padding:3px 10px;border-radius:99px;font-size:.75rem;
        font-weight:700;background:$bg;color:$fg;white-space:nowrap'>
        $ico $s</span>";
}

/**
 * Potong teks jika melebihi panjang tertentu
 */
function potong(string $teks, int $panjang = 50): string {
    return mb_strlen($teks) > $panjang
        ? mb_substr($teks, 0, $panjang) . '…'
        : $teks;
}

/**
 * Inisial nama (huruf pertama)
 */
function inisial(string $nama): string {
    return strtoupper(mb_substr(trim($nama), 0, 1));
}

/**
 * Hitung jumlah notifikasi belum dibaca
 */
function notifBelumBaca(int $idUser): int {
    global $koneksi;
    $q = $koneksi->query(
        "SELECT COUNT(*) AS jml FROM tb_notifikasi
         WHERE id_user=$idUser AND is_read=0"
    );
    return (int)($q->fetch_assoc()['jml'] ?? 0);
}