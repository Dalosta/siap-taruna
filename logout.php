<?php
/**
 * ============================================================
 * FILE    : logout.php
 * LOKASI  : /logout.php
 * FUNGSI  : Menghapus session dan redirect ke halaman login.
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 * 
 * CATATAN SIDANG:
 * 1. Session dihapus total dengan hapusSession().
 * 2. Redirect ke login dengan pesan "sesi_habis" agar user
 *    tahu kenapa harus login lagi.
 */

// ── KONEKSI & FUNGSI ──────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/fungsi.php';

// ── HAPUS SESSION ──────────────────────────────────────────────────
hapusSession();

// ── REDIRECT KE LOGIN ─────────────────────────────────────────────
redirect(APP_URL . '/login.php?pesan=sesi_habis');