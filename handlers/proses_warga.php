<?php
/**
 * ============================================================
 * FILE    : proses_warga.php
 * LOKASI  : /handlers/proses_warga.php
 * FUNGSI  : Menangani penambahan dan penghapusan data warga
 *           oleh pengurus.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Hanya pengurus yang bisa mengakses (wajibPengurus()).
 * 2. Tambah warga: validasi NIK (16 digit), username unik,
 *    password min 8 karakter, hash dengan bcrypt.
 * 3. Hapus warga: hanya role 'warga' yang bisa dihapus,
 *    pengurus tidak bisa dihapus dari sini.
 * 4. Semua error dan sukses ditampilkan dengan flash message.
 */

// ── 1. KONEKSI & FUNGSI ──────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fungsi.php';

// ── 2. PAKSA HANYA PENGURUS ──────────────────────────────────────
wajibPengurus();

// ── 3. AMBIL AKSI ─────────────────────────────────────────────────
// Aksi bisa dari POST (form tambah) atau GET (tombol hapus)
$aksi = trim($_POST['aksi'] ?? $_GET['aksi'] ?? '');


// ═══════════════════════════════════════════════════════════════════
// AKSI 1: TAMBAH WARGA
// ═══════════════════════════════════════════════════════════════════
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 4. AMBIL INPUT ──────────────────────────────────────────────
    $nama     = trim($_POST['nama']     ?? '');
    $nik      = trim($_POST['NIK']      ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $no_hp    = trim($_POST['no_hp']    ?? '');
    $alamat   = trim($_POST['alamat']   ?? '');

    // ── 5. VALIDASI INPUT ───────────────────────────────────────────
    $errors = [];

    // Nama wajib diisi
    if (empty($nama)) {
        $errors[] = 'Nama wajib diisi.';
    }

    // NIK harus 16 digit angka
    if (strlen($nik) !== 16 || !ctype_digit($nik)) {
        $errors[] = 'NIK harus tepat 16 digit angka.';
    }

    // Username wajib diisi
    if (empty($username)) {
        $errors[] = 'Username wajib diisi.';
    }

    // Password minimal 8 karakter
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }

    // ── 6. CEK DUPLIKAT NIK ─────────────────────────────────────────
    $nik_esc = $koneksi->real_escape_string($nik);
    $cek_nik = $koneksi->query("SELECT id_user FROM tb_users WHERE NIK = '$nik_esc' LIMIT 1");
    if ($cek_nik && $cek_nik->num_rows > 0) {
        $errors[] = 'NIK sudah terdaftar dalam sistem.';
    }

    // ── 7. CEK DUPLIKAT USERNAME ────────────────────────────────────
    $user_esc = $koneksi->real_escape_string($username);
    $cek_user = $koneksi->query("SELECT id_user FROM tb_users WHERE username = '$user_esc' LIMIT 1");
    if ($cek_user && $cek_user->num_rows > 0) {
        $errors[] = 'Username sudah digunakan.';
    }

    // ── 8. TAMPILKAN ERROR JIKA ADA ─────────────────────────────────
    if (!empty($errors)) {
        redirectDengan(
            APP_URL . '/pengurus/data-warga.php',
            'error',
            implode(' ', $errors)
        );
    }

    // ── 9. SIMPAN KE DATABASE ───────────────────────────────────────
    // ★ SCREENSHOT untuk Bab 4 → INSERT Data Warga Baru
    $nm  = $koneksi->real_escape_string($nama);
    $hp  = $koneksi->real_escape_string($no_hp);
    $al  = $koneksi->real_escape_string($alamat);
    $pwd = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO tb_users 
            (nama, NIK, username, password, no_hp, alamat, role, is_aktif, created_at)
            VALUES 
            ('$nm', '$nik_esc', '$user_esc', '$pwd', '$hp', '$al', 'warga', 1, NOW())";

    if (!$koneksi->query($sql)) {
        // Jika error, tampilkan pesan (debug)
        die("❌ Gagal menambah warga: " . $koneksi->error);
    }

    // ── 10. REDIRECT SUKSES ─────────────────────────────────────────
    redirectDengan(
        APP_URL . '/pengurus/data-warga.php',
        'sukses',
        "✅ Akun warga <strong>$nama</strong> berhasil ditambahkan."
    );
}


// ═══════════════════════════════════════════════════════════════════
// AKSI 2: HAPUS WARGA
// ═══════════════════════════════════════════════════════════════════
if ($aksi === 'hapus') {

    // ── 11. AMBIL ID ─────────────────────────────────────────────────
    $id = bersihInt($_GET['id'] ?? 0);

    if (!$id) {
        redirectDengan(
            APP_URL . '/pengurus/data-warga.php',
            'error',
            'ID tidak valid.'
        );
    }

    // ── 12. CEK USER ADA DAN ROLE WARGA ─────────────────────────────
    $q = $koneksi->query("SELECT nama, role FROM tb_users WHERE id_user = $id LIMIT 1");

    if (!$q || $q->num_rows === 0) {
        redirectDengan(
            APP_URL . '/pengurus/data-warga.php',
            'error',
            'Data tidak ditemukan.'
        );
    }

    $row = $q->fetch_assoc();

    // Hanya warga yang bisa dihapus (pengurus tidak boleh dihapus dari sini)
    if ($row['role'] !== 'warga') {
        redirectDengan(
            APP_URL . '/pengurus/data-warga.php',
            'error',
            'Tidak dapat menghapus akun pengurus.'
        );
    }

    $nama = $row['nama'];

    // ── 13. HAPUS USER ───────────────────────────────────────────────
    $koneksi->query("DELETE FROM tb_users WHERE id_user = $id AND role = 'warga'");

    // ── 14. REDIRECT SUKSES ─────────────────────────────────────────
    redirectDengan(
        APP_URL . '/pengurus/data-warga.php',
        'sukses',
        "🗑 Data warga <strong>$nama</strong> berhasil dihapus."
    );
}


// ── 15. JIKA TIDAK ADA AKSI YANG COCOK ────────────────────────────
// Jika aksi tidak dikenali, redirect ke halaman data warga.
redirect(APP_URL . '/pengurus/data-warga.php');