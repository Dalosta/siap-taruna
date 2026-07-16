-- ╔══════════════════════════════════════════════════════════════════╗
-- ║  FILE    : db_siaptaruna.sql                                     ║
-- ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
-- ║  LOKASI  : /db_siaptaruna.sql                                    ║
-- ║  FUNGSI  : Script SQL pembuatan database + tabel + data awal.   ║
-- ║  CARA    : Import via phpMyAdmin ATAU jalankan di terminal:      ║
-- ║            mysql -u root -p < db_siaptaruna.sql                  ║
-- ║  PASSWORD: Semua akun default → password123                      ║
-- ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
-- ╚══════════════════════════════════════════════════════════════════╝

-- ★ SCREENSHOT untuk Bab 4 → Struktur Database db_siaptaruna

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ═══════════════════════════════════════════════════════════════════
-- [1] BUAT & GUNAKAN DATABASE
-- ═══════════════════════════════════════════════════════════════════
CREATE DATABASE IF NOT EXISTS `db_siaptaruna`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `db_siaptaruna`;

-- ═══════════════════════════════════════════════════════════════════
-- [2] TABEL tb_users — Data pengguna (warga & pengurus)
-- ★ SCREENSHOT untuk Bab 4 → Struktur Tabel tb_users
-- ═══════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS `tb_users`;
CREATE TABLE `tb_users` (
    `id_user`    INT(11)      NOT NULL AUTO_INCREMENT,
    `nama`       VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pengguna',
    `NIK`        VARCHAR(20)  NOT NULL COMMENT 'Nomor Induk Kependudukan (16 digit)',
    `username`   VARCHAR(50)  NOT NULL COMMENT 'Username untuk login',
    `password`   VARCHAR(255) NOT NULL COMMENT 'Password ter-hash (bcrypt)',
    `no_hp`      VARCHAR(15)  DEFAULT NULL,
    `alamat`     TEXT         DEFAULT NULL,
    `role`       ENUM('warga','pengurus') NOT NULL DEFAULT 'warga',
    `jabatan`    VARCHAR(100) DEFAULT NULL,
    `is_aktif`   TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=aktif, 0=nonaktif',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_user`),
    UNIQUE KEY `uk_NIK`      (`NIK`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- [3] TABEL tb_pengajuan — Data pengajuan surat
-- ★ SCREENSHOT untuk Bab 4 → Struktur Tabel tb_pengajuan
-- ═══════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS `tb_pengajuan`;
CREATE TABLE `tb_pengajuan` (
    `id_pengajuan`   INT(11)      NOT NULL AUTO_INCREMENT,
    `id_user`        INT(11)      NOT NULL COMMENT 'FK → tb_users',
    `kode_pengajuan` VARCHAR(30)  DEFAULT NULL COMMENT 'Kode unik: PGJ-YYYYMMDD-XXXXX',
    `nomor_surat`    VARCHAR(60)  DEFAULT NULL COMMENT 'Terisi setelah ACC: 001/KT-RW01/V/2026',
    `jenis_surat`    VARCHAR(100) NOT NULL,
    `perihal`        TEXT         NOT NULL,
    `catatan_warga`  TEXT         DEFAULT NULL,
    `file_lampiran`  VARCHAR(255) DEFAULT NULL COMMENT 'Nama file di /uploads/',
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_pengajuan`),
    KEY `fk_pj_user` (`id_user`),
    CONSTRAINT `fk_pj_user` FOREIGN KEY (`id_user`)
        REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- [4] TABEL tb_status — Riwayat status setiap pengajuan
-- ★ SCREENSHOT untuk Bab 4 → Struktur Tabel tb_status
-- ═══════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS `tb_status`;
CREATE TABLE `tb_status` (
    `id_status`    INT(11)   NOT NULL AUTO_INCREMENT,
    `id_pengajuan` INT(11)   NOT NULL COMMENT 'FK → tb_pengajuan',
    `status`       ENUM('Pending','Diproses','Revisi','ACC','Tolak')
                   NOT NULL DEFAULT 'Pending',
    `catatan`      TEXT      DEFAULT NULL COMMENT 'Catatan dari pengurus',
    `updated_by`   INT(11)   DEFAULT NULL COMMENT 'id_user pengurus yang mengubah',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_status`),
    KEY `fk_st_pj` (`id_pengajuan`),
    CONSTRAINT `fk_st_pj` FOREIGN KEY (`id_pengajuan`)
        REFERENCES `tb_pengajuan` (`id_pengajuan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- [5] TABEL tb_arsip — Arsip surat yang telah disetujui
-- ★ SCREENSHOT untuk Bab 4 → Struktur Tabel tb_arsip
-- ═══════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS `tb_arsip`;
CREATE TABLE `tb_arsip` (
    `id_arsip`     INT(11)      NOT NULL AUTO_INCREMENT,
    `id_pengajuan` INT(11)      NOT NULL COMMENT 'FK UNIQUE → tb_pengajuan',
    `file_surat`   VARCHAR(255) DEFAULT NULL,
    `keterangan`   TEXT         DEFAULT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_arsip`),
    UNIQUE KEY `uk_arsip_pj` (`id_pengajuan`),
    CONSTRAINT `fk_ar_pj` FOREIGN KEY (`id_pengajuan`)
        REFERENCES `tb_pengajuan` (`id_pengajuan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- [6] TABEL tb_notifikasi — Notifikasi internal antar pengguna
-- ★ SCREENSHOT untuk Bab 4 → Struktur Tabel tb_notifikasi
-- ═══════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS `tb_notifikasi`;
CREATE TABLE `tb_notifikasi` (
    `id_notif`     INT(11)      NOT NULL AUTO_INCREMENT,
    `id_user`      INT(11)      NOT NULL COMMENT 'FK → tb_users (penerima)',
    `id_pengajuan` INT(11)      DEFAULT NULL,
    `judul`        VARCHAR(150) NOT NULL,
    `pesan`        TEXT         NOT NULL,
    `is_read`      TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_notif`),
    KEY `fk_nt_user` (`id_user`),
    CONSTRAINT `fk_nt_user` FOREIGN KEY (`id_user`)
        REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════
-- [7] DATA AWAL — Akun default sistem
--     Password semua akun: password123
-- ★ SCREENSHOT untuk Bab 4 → Data Awal / Seeder
-- ═══════════════════════════════════════════════════════════════════
INSERT INTO `tb_users`
    (`nama`,`NIK`,`username`,`password`,`no_hp`,`alamat`,`role`,`jabatan`,`is_aktif`)
VALUES

-- PENGURUS
('Muhamad Satiri',
 '3173010101900001','admin',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '081234567890','RW 01, Kelurahan Kelapa Dua','pengurus','Ketua',1),

('Abu Rizal Hasan',
 '3173010101900002','aburizal',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '081234567891','RW 01, Kelurahan Kelapa Dua','pengurus','Wakil Ketua',1),

-- WARGA
('Muhammad Alfi',
 '3173010101990001','alfi',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '081234567892','RT 003 RW 01, Kelurahan Kelapa Dua','warga','Sekretaris',1),

('Novita',
 '3173010101990002','novita',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '081234567893','RT 002 RW 01, Kelurahan Kelapa Dua','warga','Wakil Sekretaris',1),

('Ahmad Maulana',
 '3173010101990003','ahmadm',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '081234567894','RT 001 RW 01, Kelurahan Kelapa Dua','warga','Bendahara',1),

('Erika',
 '3173010101990004','erika',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '081234567895','RT 004 RW 01, Kelurahan Kelapa Dua','warga','Wakil Bendahara',1);

-- ═══════════════════════════════════════════════════════════════════
-- VERIFIKASI HASIL
-- ═══════════════════════════════════════════════════════════════════
SELECT '✅ Database db_siaptaruna berhasil dibuat!' AS info;
SELECT CONCAT('Tabel: ', COUNT(*), ' tabel') AS info
FROM information_schema.tables
WHERE table_schema = 'db_siaptaruna';
SELECT CONCAT('User: ', COUNT(*), ' akun') AS info FROM tb_users;