<?php
/**
 * ============================================================
 * FILE    : proses_login.php
 * LOKASI  : /handlers/proses_login.php
 * FUNGSI  : Memproses form login, memverifikasi akun, dan
 *           mengarahkan user ke dashboard sesuai role.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Menggunakan prepared statement untuk keamanan database.
 * 2. Password disimpan dalam bentuk hash (bcrypt) dan diverifikasi
 *    dengan password_verify().
 * 3. Jika login berhasil, data user disimpan ke session dan 
 *    user di-redirect ke halaman masing-masing (warga/pengurus).
 */

// ── 1. KONEKSI DATABASE & FUNGSI HELPER ──────────────────────────
// Memanggil file konfigurasi dan fungsi global.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. MULAI SESSION ──────────────────────────────────────────────
// Session diperlukan untuk menyimpan data user yang berhasil login.
mulaiSession();

// ── 3. CEK METODE REQUEST ─────────────────────────────────────────
// Hanya menerima metode POST. Jika bukan POST, kembalikan ke login.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

// ── 4. AMBIL DAN BERSIHKAN INPUT ─────────────────────────────────
// Data dari form login diambil dan spasi dihapus di ujungnya.
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// ── 5. VALIDASI INPUT KOSONG ─────────────────────────────────────
// Username dan password wajib diisi. Jika kosong, simpan error ke session.
if (empty($username) || empty($password)) {
    $_SESSION['error_login']   = 'Username dan password wajib diisi.';
    $_SESSION['last_username'] = $username; // Mengembalikan input ke form
    redirect(APP_URL . '/login.php');
}

// ── 6. PREPARED STATEMENT ─────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Implementasi Prepared Statement
// Mencegah SQL Injection dengan memisahkan query dan data.
$stmt = $koneksi->prepare("
    SELECT * 
    FROM tb_users 
    WHERE username = ? 
    AND is_aktif = 1 
    LIMIT 1
");

// Ikat parameter (string) ke query.
$stmt->bind_param("s", $username);

// Eksekusi query.
$stmt->execute();

// Ambil hasil query.
$result = $stmt->get_result();

// ── 7. CEK APAKAH USER DITEMUKAN ─────────────────────────────────
// Jika tidak ada baris yang cocok, berarti username salah atau akun nonaktif.
if ($result->num_rows === 0) {
    $_SESSION['error_login']   = 'Username tidak ditemukan atau akun tidak aktif.';
    $_SESSION['last_username'] = $username;
    redirect(APP_URL . '/login.php');
}

// ── 8. AMBIL DATA USER ────────────────────────────────────────────
// Konversi hasil query menjadi array asosiatif.
$user = $result->fetch_assoc();

// ── 9. VERIFIKASI PASSWORD ────────────────────────────────────────
// password_verify() mencocokkan password plaintext dengan hash di database.
if (!password_verify($password, $user['password'])) {
    $_SESSION['error_login']   = 'Password yang Anda masukkan salah.';
    $_SESSION['last_username'] = $username;
    redirect(APP_URL . '/login.php');
}

// ── 10. SET SESSION ───────────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Proses Autentikasi & Set Session
// Simpan data user ke session agar bisa diakses di seluruh halaman.
setSession($user);

// ── 11. REDIRECT BERDASARKAN ROLE ────────────────────────────────
// Jika role = pengurus, arahkan ke dashboard pengurus.
// Jika role = warga, arahkan ke dashboard warga.
if ($user['role'] === 'pengurus') {
    redirect(APP_URL . '/pengurus/dashboard.php');
} else {
    redirect(APP_URL . '/warga/dashboard.php');
}