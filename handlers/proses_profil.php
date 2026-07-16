<?php
/**
 * ============================================================
 * FILE    : proses_profil.php
 * LOKASI  : /handlers/proses_profil.php
 * FUNGSI  : Memproses update data profil pengguna (warga/pengurus).
 *           Menerima input dari form profil, validasi, update database,
 *           dan memperbarui session agar data terbaru langsung tampil.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Semua user (warga & pengurus) menggunakan file yang sama.
 * 2. Validasi: nama wajib diisi, password baru minimal 8 karakter.
 * 3. Update semua field: nama, no HP, alamat, tempat lahir,
 *    tanggal lahir, jenis kelamin, agama, pekerjaan,
 *    status pernikahan, RT.
 * 4. Jika password baru diisi, hash dengan bcrypt dan update.
 * 5. Session diperbarui setelah update agar data langsung tampil.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA LOGIN ────────────────────────────────────────────────
// Semua user (warga dan pengurus) bisa mengakses.
wajibLogin();

// ── 3. CEK METODE REQUEST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

// ── 4. AMBIL DATA USER ────────────────────────────────────────────
$idUser = (int)$_SESSION['id_user'];
$role   = getRole();

// ── 5. AMBIL SEMUA INPUT DARI FORM ──────────────────────────────
$nama              = trim($_POST['nama']              ?? '');
$no_hp             = trim($_POST['no_hp']             ?? '');
$alamat            = trim($_POST['alamat']            ?? '');
$tempat_lahir      = trim($_POST['tempat_lahir']      ?? '');
$tanggal_lahir     = trim($_POST['tanggal_lahir']     ?? '');
$jenis_kelamin     = trim($_POST['jenis_kelamin']     ?? '');
$agama             = trim($_POST['agama']             ?? '');
$pekerjaan         = trim($_POST['pekerjaan']         ?? '');
$status_pernikahan = trim($_POST['status_pernikahan'] ?? '');
$rt                = trim($_POST['rt']                ?? '');
$pwd_baru          = trim($_POST['password_baru']      ?? '');

// ── 6. VALIDASI NAMA WAJIB ────────────────────────────────────────
if (empty($nama)) {
    redirectDengan(
        $role === 'pengurus'
            ? APP_URL . '/pengurus/profil.php'
            : APP_URL . '/warga/profil.php',
        'error',
        'Nama lengkap wajib diisi.'
    );
}

// ── 7. UPDATE DATA PROFIL ─────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → UPDATE Data Profil Lengkap
$nm   = $koneksi->real_escape_string($nama);
$hp   = $koneksi->real_escape_string($no_hp);
$al   = $koneksi->real_escape_string($alamat);
$tl   = $koneksi->real_escape_string($tempat_lahir);
$tgl  = $koneksi->real_escape_string($tanggal_lahir);
$jk   = $koneksi->real_escape_string($jenis_kelamin);
$ag   = $koneksi->real_escape_string($agama);
$pk   = $koneksi->real_escape_string($pekerjaan);
$sp   = $koneksi->real_escape_string($status_pernikahan);
$rt_esc = $koneksi->real_escape_string($rt);

$sql = "UPDATE tb_users SET 
            nama = '$nm',
            no_hp = '$hp',
            alamat = '$al',
            tempat_lahir = '$tl',
            tanggal_lahir = '$tgl',
            jenis_kelamin = '$jk',
            agama = '$ag',
            pekerjaan = '$pk',
            status_pernikahan = '$sp',
            rt = '$rt_esc'
        WHERE id_user = $idUser";

if (!$koneksi->query($sql)) {
    // Tampilkan error jika query gagal (debug)
    die("❌ Gagal update profil: " . $koneksi->error . "<br>SQL: " . $sql);
}

// ── 8. UPDATE PASSWORD JIKA DIISI ─────────────────────────────────
if (!empty($pwd_baru)) {
    // Validasi panjang password
    if (strlen($pwd_baru) < 8) {
        redirectDengan(
            $role === 'pengurus'
                ? APP_URL . '/pengurus/profil.php'
                : APP_URL . '/warga/profil.php',
            'error',
            'Password baru minimal 8 karakter.'
        );
    }
    
    // Hash password dengan bcrypt dan update
    $pwd = password_hash($pwd_baru, PASSWORD_BCRYPT);
    $koneksi->query("UPDATE tb_users SET password = '$pwd' WHERE id_user = $idUser");
}

// ── 9. PERBARUI SESSION ───────────────────────────────────────────
// Agar data baru langsung tampil di halaman tanpa perlu login ulang.
$_SESSION['nama']              = $nama;
$_SESSION['no_hp']             = $no_hp;
$_SESSION['alamat']            = $alamat;
$_SESSION['tempat_lahir']      = $tempat_lahir;
$_SESSION['tanggal_lahir']     = $tanggal_lahir;
$_SESSION['jenis_kelamin']     = $jenis_kelamin;
$_SESSION['agama']             = $agama;
$_SESSION['pekerjaan']         = $pekerjaan;
$_SESSION['status_pernikahan'] = $status_pernikahan;
$_SESSION['rt']                = $rt;

// ── 10. REDIRECT DENGAN PESAN SUKSES ─────────────────────────────
$back = $role === 'pengurus'
    ? APP_URL . '/pengurus/profil.php'
    : APP_URL . '/warga/profil.php';

redirectDengan($back, 'sukses', '✅ Profil berhasil diperbarui.');