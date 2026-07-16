<?php
/**
 * ============================================================
 * FILE    : database.php
 * LOKASI  : /config/database.php
 * FUNGSI  : Konfigurasi database MySQL dan konstanta global.
 *           File ini di-include di setiap halaman yang butuh DB.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Semua konstanta disimpan di sini agar mudah diubah jika
 *    terjadi migrasi server (hosting, domain, dll).
 * 2. Timezone diatur ke Asia/Jakarta agar semua waktu sesuai WIB.
 * 3. Koneksi menggunakan MySQLi, dengan fallback error jika gagal.
 */

// ── KONFIGURASI DATABASE (sesuai InfinityFree) ──────────────────
define('DB_HOST', 'sql108.infinityfree.com');
define('DB_USER', 'if0_41853985');
define('DB_PASS', 'Dalosta1771');
define('DB_NAME', 'if0_41853985_db_siaptaruna');
define('DB_PORT', 3306);

// ── KONFIGURASI APLIKASI ──────────────────────────────────────────
define('APP_NAME',    'SIAP TARUNA');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'https://siaptaruna.22web.org');
define('UPLOAD_DIR',  __DIR__ . '/../uploads/');
define('MAX_UPLOAD',  2 * 1024 * 1024); // 2 MB

// ── KONEKSI MYSQL ──────────────────────────────────────────────────
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Cek koneksi
if ($koneksi->connect_error) {
    die("⚠️ Koneksi database gagal: " . $koneksi->connect_error);
}

// Set charset untuk mendukung karakter khusus (termasuk emoji)
$koneksi->set_charset('utf8mb4');

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');