<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : koneksi.php                                           ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /koneksi.php                                          ║
// ║  FUNGSI  : Konfigurasi dan koneksi database MySQL.               ║
// ║            Di-include di setiap halaman yang butuh database.     ║
// ║  DATABASE: db_siaptaruna                                         ║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

// ── KONFIGURASI DATABASE ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_siaptaruna');
define('DB_PORT', 3306);

// ── KONFIGURASI APLIKASI ──────────────────────────────────────────
define('APP_NAME',    'SIAP TARUNA');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/siap-taruna');
define('UPLOAD_DIR',  __DIR__ . '/uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');
define('MAX_UPLOAD',  2 * 1024 * 1024); // 2 MB

// ── KONEKSI MYSQLI ────────────────────────────────────────────────
// ★ SCREENSHOT untuk Bab 4 → Konfigurasi Koneksi Database
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// ── CEK KONEKSI ───────────────────────────────────────────────────
if ($koneksi->connect_error) {
    die('
    <div style="font-family:sans-serif;padding:40px;text-align:center">
        <h3 style="color:#DC2626">⚠ Koneksi Database Gagal</h3>
        <p style="color:#666">Error: ' . $koneksi->connect_error . '</p>
        <p style="color:#999;font-size:.9rem">
            Pastikan MySQL sudah berjalan dan database
            <strong>db_siaptaruna</strong> sudah dibuat.
        </p>
    </div>');
}

// ── SET CHARSET UTF-8 ─────────────────────────────────────────────
$koneksi->set_charset('utf8mb4');

// ── TIMEZONE JAKARTA ──────────────────────────────────────────────
date_default_timezone_set('Asia/Jakarta');