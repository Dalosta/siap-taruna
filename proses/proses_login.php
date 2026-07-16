<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : proses_login.php                                      ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /proses/proses_login.php                              ║
// ║  FUNGSI  : Memproses form login. Validasi username/NIK,          ║
// ║            verifikasi password, set session, redirect role.      ║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/../fungsi.php';
mulaiSession();

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

// ── [1] AMBIL & BERSIHKAN INPUT ───────────────────────────────────
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (kosong($username) || kosong($password)) {
    $_SESSION['error_login']  = 'Username/NIK dan password wajib diisi.';
    $_SESSION['last_username'] = $username;
    redirect(APP_URL . '/login.php');
}

// ── [2] DETEKSI FIELD LOGIN ───────────────────────────────────────
// Jika semua karakter angka → gunakan kolom NIK, selainnya → username
$field = ctype_digit($username) ? 'NIK' : 'username';
$val   = $koneksi->real_escape_string($username);

// ── [3] CEK USER DI DATABASE ─────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Proses Autentikasi & Query Login
$query = $koneksi->query(
    "SELECT * FROM tb_users
     WHERE `$field` = '$val'
       AND is_aktif = 1
     LIMIT 1"
);

if (!$query || $query->num_rows === 0) {
    $_SESSION['error_login']   = 'Username/NIK tidak ditemukan atau akun tidak aktif.';
    $_SESSION['last_username'] = $username;
    redirect(APP_URL . '/login.php');
}

$user = $query->fetch_assoc();

// ── [4] VERIFIKASI PASSWORD ───────────────────────────────────────
if (!password_verify($password, $user['password'])) {
    $_SESSION['error_login']   = 'Password yang Anda masukkan salah.';
    $_SESSION['last_username'] = $username;
    redirect(APP_URL . '/login.php');
}

// ── [5] SET SESSION & REDIRECT ────────────────────────────────────
setSession($user);

if ($user['role'] === 'pengurus') {
    redirect(APP_URL . '/pengurus/dashboard.php');
} else {
    redirect(APP_URL . '/warga/dashboard.php');
}