<?php
/**
 * ============================================================
 * FILE    : fungsi.php
 * LOKASI  : /includes/fungsi.php
 * FUNGSI  : Kumpulan fungsi global yang digunakan di seluruh sistem.
 *           Mencakup: session, autentikasi, redirect, sanitasi,
 *           format tanggal, upload file, notifikasi, dan UI helper.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. File ini adalah "otak" dari sistem. Semua fungsi reusable
 *    dikumpulkan di sini agar tidak duplikasi di setiap file.
 * 2. Fungsi autentikasi: mulaiSession, sudahLogin, setSession,
 *    hapusSession, wajibLogin, wajibWarga, wajibPengurus.
 * 3. Fungsi helper: formatTanggal, uploadFile, badgeStatus, dll.
 * 4. Fungsi database helper: hitungStatusWarga, hitungStatusGlobal,
 *    kirimNotifikasi.
 */

// ── SESSION ──────────────────────────────────────────────────────
function mulaiSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('SIAPTARUNA_SESS');
        session_start();
    }
}

function sudahLogin(): bool {
    mulaiSession();
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}

function getUser(): ?array {
    if (!sudahLogin()) return null;

    return [
        'id_user'  => $_SESSION['id_user']  ?? null,
        'nama'     => $_SESSION['nama']     ?? '',
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role']     ?? '',
        'NIK'      => $_SESSION['NIK']      ?? '',
        'no_hp'    => $_SESSION['no_hp']    ?? '',
        'alamat'   => $_SESSION['alamat']   ?? '',
        'rt'       => $_SESSION['rt']       ?? '',
    ];
}

function getRole(): string {
    mulaiSession();
    return $_SESSION['role'] ?? '';
}

function setSession(array $user): void {
    mulaiSession();

    $_SESSION['id_user']  = $user['id_user'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['NIK']      = $user['NIK']      ?? '';
    $_SESSION['no_hp']    = $user['no_hp']    ?? '';
    $_SESSION['alamat']   = $user['alamat']   ?? '';
    $_SESSION['rt']       = $user['rt']       ?? '';
}

function hapusSession(): void {
    mulaiSession();

    $_SESSION = [];
    session_destroy();
}

// ── AUTENTIKASI & PROTEKSI ──────────────────────────────────────
function wajibLogin(): void {
    mulaiSession();

    if (!sudahLogin()) {
        redirect(APP_URL . '/login.php?pesan=login_dulu');
    }
}

function wajibWarga(): void {
    wajibLogin();

    if (getRole() !== 'warga') {
        redirect(APP_URL . '/pengurus/dashboard.php');
    }
}

function wajibPengurus(): void {
    wajibLogin();

    if (getRole() !== 'pengurus') {
        redirect(APP_URL . '/warga/dashboard.php');
    }
}

// ── REDIRECT ──────────────────────────────────────────────────────
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function redirectDengan(string $url, string $tipe, string $pesan): void {
    mulaiSession();

    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    redirect($url);
}

function tampilFlash(): string {
    mulaiSession();

    if (!isset($_SESSION['flash'])) return '';

    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $msg = htmlspecialchars($f['pesan']);

    $bg = match ($f['tipe']) {
        'sukses'    => '#D1FAE5',
        'error'     => '#FEE2E2',
        'peringatan'=> '#FEF3C7',
        default     => '#DBEAFE',
    };

    $clr = match ($f['tipe']) {
        'sukses'    => '#065F46',
        'error'     => '#991B1B',
        'peringatan'=> '#92400E',
        default     => '#1E40AF',
    };

    $ico = match ($f['tipe']) {
        'sukses'    => 'bi-check-circle-fill',
        'error'     => 'bi-exclamation-circle-fill',
        'peringatan'=> 'bi-exclamation-triangle-fill',
        default     => 'bi-info-circle-fill',
    };

    return "
    <div class='flash-msg' style='
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 12px;
        background: $bg;
        color: $clr;
        font-size: 0.875rem;
        margin-bottom: 18px;
    '>
        <i class='bi $ico' style='font-size: 1.05rem; flex-shrink: 0;'></i>
        <span>$msg</span>
        <button onclick='this.parentElement.remove()' style='
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: inherit;
            opacity: 0.6;
        '>&times;</button>
    </div>";
}

// ── SANITASI ──────────────────────────────────────────────────────
function bersihkan(string $input): string {
    global $koneksi;

    return $koneksi->real_escape_string(
        htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8')
    );
}

function bersihInt($val): int {
    return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
}

function kosong(string $val): bool {
    return trim($val) === '';
}

// ── FORMAT TANGGAL ──────────────────────────────────────────────
function formatTanggal(string $tgl): string {
    if (empty($tgl) || $tgl === '0000-00-00') return '—';

    $bulan = [
        1 => 'Januari', 2 => 'Februari',  3 => 'Maret',   4 => 'April',
        5 => 'Mei',     6 => 'Juni',      7 => 'Juli',    8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $ts = strtotime($tgl);

    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function formatTanggalWaktu(string $tgl): string {
    if (empty($tgl)) return '—';

    return formatTanggal($tgl) . ', ' . date('H.i', strtotime($tgl)) . ' WIB';
}

function bulanRomawi(int $bulan): string {
    $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI',
               'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    return $romawi[$bulan] ?? '';
}

// ── GENERATE KODE ─────────────────────────────────────────────────
function genKodePengajuan(): string {
    return 'PGJ-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
}

function genNomorSurat(): string {
    global $koneksi;

    $bln = (int) date('n');
    $thn = date('Y');
    $rom = bulanRomawi($bln);

    $q = $koneksi->query(
        "SELECT COUNT(*) AS jml FROM tb_arsip
         WHERE MONTH(created_at) = $bln AND YEAR(created_at) = $thn"
    );

    $urut = ($q->fetch_assoc()['jml'] ?? 0) + 1;

    return sprintf('%03d/KT-RW01/%s/%s', $urut, $rom, $thn);
}

// ── UPLOAD FILE ──────────────────────────────────────────────────
function uploadFile(array $file): array {
    // Jika tidak ada file yang diupload → anggap sukses (opsional)
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['sukses' => true, 'nama' => '', 'pesan' => ''];
    }

    // Jika error upload selain "no file"
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Upload gagal (kode error: ' . $file['error'] . ')'];
    }

    // Validasi ukuran (MAX_UPLOAD = 2MB dari database.php)
    if ($file['size'] > MAX_UPLOAD) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Ukuran file maksimal 2 MB'];
    }

    // Validasi ekstensi
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'pdf'])) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Format file tidak didukung (JPG/PNG/PDF)'];
    }

    // Buat folder uploads jika belum ada
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate nama file unik
    $namaFile = 'lamp_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ekstensi;

    // Pindahkan file ke folder uploads
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $namaFile)) {
        return ['sukses' => false, 'nama' => '', 'pesan' => 'Gagal menyimpan file ke server'];
    }

    return ['sukses' => true, 'nama' => $namaFile, 'pesan' => ''];
}

// ── DATABASE HELPER ──────────────────────────────────────────────
function hitungStatusWarga(int $idUser, string $status): int {
    global $koneksi;

    $s = $koneksi->real_escape_string($status);

    $q = $koneksi->query(
        "SELECT COUNT(*) AS jml FROM tb_pengajuan p
         JOIN tb_status st ON st.id_pengajuan = p.id_pengajuan
         WHERE p.id_user = $idUser
         AND st.status = '$s'
         AND st.id_status = (
             SELECT MAX(id_status) FROM tb_status
             WHERE id_pengajuan = p.id_pengajuan
         )"
    );

    return (int) ($q->fetch_assoc()['jml'] ?? 0);
}

function hitungStatusGlobal(string $status): int {
    global $koneksi;

    $s = $koneksi->real_escape_string($status);

    $q = $koneksi->query(
        "SELECT COUNT(DISTINCT p.id_pengajuan) AS jml
         FROM tb_pengajuan p
         JOIN tb_status st ON st.id_pengajuan = p.id_pengajuan
         WHERE st.status = '$s'
         AND st.id_status = (
             SELECT MAX(id_status) FROM tb_status
             WHERE id_pengajuan = p.id_pengajuan
         )"
    );

    return (int) ($q->fetch_assoc()['jml'] ?? 0);
}

function kirimNotifikasi(int $idUser, int $idPengajuan, string $judul, string $pesan): void {
    global $koneksi;

    $j = $koneksi->real_escape_string($judul);
    $p = $koneksi->real_escape_string($pesan);

    $koneksi->query(
        "INSERT INTO tb_notifikasi (id_user, id_pengajuan, judul, pesan, is_read, created_at)
         VALUES ($idUser, $idPengajuan, '$j', '$p', 0, NOW())"
    );
}

function notifBelumBaca(int $idUser): int {
    global $koneksi;

    $q = $koneksi->query(
        "SELECT COUNT(*) AS jml FROM tb_notifikasi
         WHERE id_user = $idUser AND is_read = 0"
    );

    return (int) ($q->fetch_assoc()['jml'] ?? 0);
}

// ── UI HELPER ────────────────────────────────────────────────────
function badgeStatus(string $status): string {
    $map = [
        'Pending'  => ['#FEF9C3', '#854D0E', '⏳'],
        'Diproses' => ['#DBEAFE', '#1E40AF', '🔄'],
        'Revisi'   => ['#F3E8FF', '#6B21A8', '🔁'],
        'ACC'      => ['#DCFCE7', '#166534', '✅'],
        'Tolak'    => ['#FEE2E2', '#991B1B', '❌'],
    ];

    [$bg, $fg, $ico] = $map[$status] ?? ['#F3F4F6', '#374151', '•'];

    return "<span style='
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 700;
        background: $bg;
        color: $fg;
        white-space: nowrap;
    '>$ico $status</span>";
}

function inisial(string $nama): string {
    return strtoupper(mb_substr(trim($nama), 0, 1));
}