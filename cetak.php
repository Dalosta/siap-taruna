<?php
/**
 * ============================================================
 * FILE    : cetak.php (VERSI GABUNGAN / FINAL)
 * LOKASI  : /cetak.php
 * FUNGSI  : Satu file utuh untuk modul cetak surat:
 *           - Koneksi & helper function
 *           - 3 template surat (RT/RW, Domisili, SKTM)
 *           - Validasi status ACC (diambil dari tb_status TERBARU)
 *           - Output HTML + CSS cetak (A4, Times New Roman)
 * AKSES   : /cetak.php?id=1
 * AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026
 * ============================================================
 *
 * CATATAN SIDANG:
 * File ini adalah gabungan dari functions.php, template-rtrw.php,
 * template-domisili.php, template-sktm.php, dan surat.css yang
 * sebelumnya dipisah per modul. Isi logika PERSIS SAMA, hanya
 * digabung menjadi satu file agar mudah dipindah/di-deploy.
 *
 * Beberapa data kop surat (RW, Kelurahan, Kecamatan, Kota, nama
 * Ketua RW) TIDAK tersedia pada tabel `tb_users`/`tb_pengajuan`,
 * sehingga disimpan sebagai KONSTANTA default di bawah ini
 * (instruksi proyek melarang perubahan struktur database).
 */

// ── KONEKSI DATABASE ──────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
// $koneksi (mysqli) sudah tersedia dari file di atas.

// ============================================================
// BAGIAN 1 — KONSTANTA KOP SURAT (default, tidak ada di database)
// ============================================================
define('ORG_NAMA',       'KARANG TARUNA RW 01 KELAPA DUA');
define('ORG_RW',         '01');
define('ORG_KELURAHAN',  'Kelapa Dua');
define('ORG_KECAMATAN',  'Kecamatan Kebon Jeruk');
define('ORG_KOTA',       'Jakarta Barat');
define('ORG_PROVINSI',   'Provinsi DKI Jakarta');
define('ORG_ALAMAT',     'Sekretariat Karang Taruna RW 01, Kelapa Dua');
define('NAMA_KETUA_RW',  'Muhamad Satiri'); // Sesuaikan dengan nama Ketua RW
define('WARGA_NEGARA',   'Indonesia');

// ============================================================
// BAGIAN 2 — FUNGSI DATABASE
// ============================================================

/**
 * Mengambil satu baris data pengajuan surat beserta data pemohon (JOIN tb_users).
 * Menggunakan prepared statement untuk mencegah SQL Injection.
 */
function getPengajuanById(mysqli $koneksi, int $idPengajuan): ?array
{
    $sql = "SELECT p.id_pengajuan, p.id_user, p.kode_pengajuan, p.nomor_surat,
                   p.jenis_surat, p.perihal, p.catatan_warga, p.created_at,
                   u.nama, u.NIK, u.alamat, u.tempat_lahir, u.tanggal_lahir,
                   u.jenis_kelamin, u.agama, u.pekerjaan, u.status_pernikahan, u.rt
            FROM tb_pengajuan p
            INNER JOIN tb_users u ON u.id_user = p.id_user
            WHERE p.id_pengajuan = ?
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $idPengajuan);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $data  = $hasil->fetch_assoc();
    $stmt->close();

    return $data ?: null;
}

/**
 * Mengambil STATUS TERBARU dari sebuah pengajuan.
 * PENTING: status pengajuan bisa berubah beberapa kali (riwayat ada di
 * tb_status), sehingga status yang berlaku adalah baris dengan id_status
 * TERBESAR (paling akhir dibuat) untuk id_pengajuan tersebut — bukan
 * hasil JOIN biasa yang bisa salah ambil baris.
 */
function getStatusTerbaru(mysqli $koneksi, int $idPengajuan): ?array
{
    $sql = "SELECT status, catatan, created_at
            FROM tb_status
            WHERE id_pengajuan = ?
            ORDER BY id_status DESC
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $idPengajuan);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $data  = $hasil->fetch_assoc();
    $stmt->close();

    return $data ?: null;
}

// ============================================================
// BAGIAN 3 — FUNGSI FORMAT & VALIDASI
// ============================================================

/** Escape output agar aman dari XSS. */
function esc(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Mengembalikan nilai default ('-') jika data kosong/null. */
function defaultJika($value, string $default = '-')
{
    if ($value === null) {
        return $default;
    }
    $value = trim((string) $value);
    return $value === '' ? $default : $value;
}

/**
 * Mengubah tanggal Y-m-d menjadi format Indonesia.
 * Contoh: "2026-07-14" -> "14 Juli 2026" (atau "Selasa, 14 Juli 2026" jika $denganHari=true)
 */
function tanggalIndonesia(?string $tanggal, bool $denganHari = false): string
{
    if (empty($tanggal) || $tanggal === '0000-00-00') {
        return '-';
    }

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return '-';
    }

    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $namaHari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    $tanggalFormat = date('j', $timestamp) . ' '
        . $namaBulan[(int) date('n', $timestamp)] . ' '
        . date('Y', $timestamp);

    if ($denganHari) {
        $hariIni = $namaHari[date('l', $timestamp)];
        $tanggalFormat = $hariIni . ', ' . $tanggalFormat;
    }

    return $tanggalFormat;
}

/** Fallback nomor surat: pakai kode_pengajuan jika nomor_surat belum diisi pengurus. */
function nomorSuratTampil(?string $nomorSurat, string $kodePengajuan): string
{
    $nomor = defaultJika($nomorSurat, '');
    return $nomor !== '' ? $nomor : $kodePengajuan;
}

// ============================================================
// BAGIAN 4 — FUNGSI TAMPILAN (HTML BERSAMA)
// ============================================================

/** Menampilkan kop surat dengan garis ganda khas surat resmi Indonesia. */
function tampilkanKopSurat(): void
{
    ?>
    <table class="kop-surat">
        <tr>
            <td class="kop-logo">
                <div class="logo-lingkaran">KT</div>
            </td>
            <td class="kop-teks">
                <h1><?= esc(ORG_NAMA) ?></h1>
                <p><?= esc(ORG_KECAMATAN) ?> — <?= esc(ORG_KOTA) ?> — <?= esc(ORG_PROVINSI) ?></p>
                <p class="kop-alamat"><?= esc(ORG_ALAMAT) ?></p>
            </td>
        </tr>
    </table>
    <div class="garis-ganda">
        <div class="garis-tebal"></div>
        <div class="garis-tipis"></div>
    </div>
    <?php
}

/** Menampilkan tabel identitas pemohon (dipakai sama oleh ketiga jenis surat). */
function tampilkanTabelIdentitas(array $d): void
{
    $ttl = defaultJika($d['tempat_lahir']) . ', ' . tanggalIndonesia($d['tanggal_lahir']);
    ?>
    <table class="tabel-identitas">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['nama'])) ?></td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['NIK'])) ?></td>
        </tr>
        <tr>
            <td class="label">Tempat, Tgl Lahir</td>
            <td class="titik-dua">:</td>
            <td><?= esc($ttl) ?></td>
        </tr>
        <tr>
            <td class="label">Jenis Kelamin</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['jenis_kelamin'])) ?></td>
        </tr>
        <tr>
            <td class="label">Agama</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['agama'])) ?></td>
        </tr>
        <tr>
            <td class="label">Pekerjaan</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['pekerjaan'])) ?></td>
        </tr>
        <tr>
            <td class="label">Status Perkawinan</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['status_pernikahan'])) ?></td>
        </tr>
        <tr>
            <td class="label">Warga Negara</td>
            <td class="titik-dua">:</td>
            <td><?= esc(WARGA_NEGARA) ?></td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="titik-dua">:</td>
            <td><?= esc(defaultJika($d['alamat'])) ?></td>
        </tr>
        <tr>
            <td class="label">RT / RW</td>
            <td class="titik-dua">:</td>
            <td>RT <?= esc(defaultJika($d['rt'])) ?> / RW <?= esc(ORG_RW) ?></td>
        </tr>
    </table>
    <?php
}

/** Menampilkan blok tanda tangan pengurus di kanan bawah surat. */
function tampilkanTandaTangan(string $jabatan): void
{
    $tanggalCetak = tanggalIndonesia(date('Y-m-d'));
    ?>
    <table class="blok-ttd">
        <tr>
            <td class="ttd-kosong"></td>
            <td class="ttd-isi">
                <p><?= esc(ORG_KOTA) ?>, <?= esc($tanggalCetak) ?></p>
                <p><?= esc($jabatan) ?></p>
                <div class="ruang-ttd">&nbsp;</div>
                <p class="nama-ttd"><u><?= esc(NAMA_KETUA_RW) ?></u></p>
            </td>
        </tr>
    </table>
    <?php
}

/** Menampilkan footer kecil di bagian paling bawah surat. */
function tampilkanFooterSurat(string $kodePengajuan): void
{
    ?>
    <div class="footer-surat">
        <p>Kode Pengajuan: <?= esc($kodePengajuan) ?> — Dicetak melalui <?= esc(APP_NAME) ?> pada <?= esc(tanggalIndonesia(date('Y-m-d'), true)) ?></p>
    </div>
    <?php
}

// ============================================================
// BAGIAN 5 — TEMPLATE SURAT PENGANTAR RT/RW
// ============================================================
function renderSuratRTRW(array $d): void
{
    $nomorSurat = nomorSuratTampil($d['nomor_surat'], $d['kode_pengajuan']);
    ?>
    <div class="judul-surat">
        <h2>SURAT PENGANTAR RT/RW</h2>
        <p>Nomor: <?= esc($nomorSurat) ?></p>
    </div>

    <div class="isi-surat">
        <p class="paragraf-buka">
            Yang bertanda tangan di bawah ini, Ketua RW <?= esc(ORG_RW) ?>
            <?= esc(ORG_KELURAHAN) ?>, <?= esc(ORG_KECAMATAN) ?>,
            dengan ini menerangkan bahwa warga kami dengan data sebagai berikut:
        </p>

        <?php tampilkanTabelIdentitas($d); ?>

        <p class="paragraf-isi">
            Adalah benar warga yang berdomisili di wilayah RT <?= esc(defaultJika($d['rt'])) ?>
            / RW <?= esc(ORG_RW) ?>, <?= esc(ORG_KELURAHAN) ?>. Surat pengantar ini dibuat
            untuk keperluan:
        </p>

        <p class="perihal-box"><?= esc(defaultJika($d['perihal'])) ?></p>

        <p class="paragraf-tutup">
            Demikian surat pengantar ini kami buat dengan sebenarnya agar dapat
            dipergunakan sebagaimana mestinya untuk diteruskan ke instansi/pihak
            terkait sesuai keperluan di atas.
        </p>
    </div>

    <?php tampilkanTandaTangan('Ketua RW ' . ORG_RW); ?>
    <?php
}

// ============================================================
// BAGIAN 6 — TEMPLATE SURAT KETERANGAN DOMISILI
// ============================================================
function renderSuratDomisili(array $d): void
{
    $nomorSurat = nomorSuratTampil($d['nomor_surat'], $d['kode_pengajuan']);
    ?>
    <div class="judul-surat">
        <h2>SURAT KETERANGAN DOMISILI</h2>
        <p>Nomor: <?= esc($nomorSurat) ?></p>
    </div>

    <div class="isi-surat">
        <p class="paragraf-buka">
            Yang bertanda tangan di bawah ini, Ketua RW <?= esc(ORG_RW) ?>
            <?= esc(ORG_KELURAHAN) ?>, <?= esc(ORG_KECAMATAN) ?>, dengan ini
            menerangkan bahwa:
        </p>

        <?php tampilkanTabelIdentitas($d); ?>

        <p class="paragraf-isi">
            Orang tersebut di atas adalah benar-benar warga kami dan berdomisili
            di RT <?= esc(defaultJika($d['rt'])) ?> / RW <?= esc(ORG_RW) ?>,
            <?= esc(ORG_KELURAHAN) ?>, <?= esc(ORG_KECAMATAN) ?>. Surat keterangan
            domisili ini dibuat untuk keperluan:
        </p>

        <p class="perihal-box"><?= esc(defaultJika($d['perihal'])) ?></p>

        <p class="paragraf-tutup">
            Demikian surat keterangan domisili ini kami buat dengan sebenarnya,
            untuk dapat dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <?php tampilkanTandaTangan('Ketua RW ' . ORG_RW); ?>
    <?php
}

// ============================================================
// BAGIAN 7 — TEMPLATE SURAT KETERANGAN TIDAK MAMPU (SKTM)
// ============================================================
function renderSuratSKTM(array $d): void
{
    $nomorSurat = nomorSuratTampil($d['nomor_surat'], $d['kode_pengajuan']);
    ?>
    <div class="judul-surat">
        <h2>SURAT KETERANGAN TIDAK MAMPU</h2>
        <p>Nomor: <?= esc($nomorSurat) ?></p>
    </div>

    <div class="isi-surat">
        <p class="paragraf-buka">
            Yang bertanda tangan di bawah ini, Ketua RW <?= esc(ORG_RW) ?>
            <?= esc(ORG_KELURAHAN) ?>, <?= esc(ORG_KECAMATAN) ?>, dengan ini
            menerangkan bahwa:
        </p>

        <?php tampilkanTabelIdentitas($d); ?>

        <p class="paragraf-isi">
            Berdasarkan pengamatan dan data yang ada pada kami, orang tersebut
            di atas adalah benar warga RT <?= esc(defaultJika($d['rt'])) ?> /
            RW <?= esc(ORG_RW) ?>, <?= esc(ORG_KELURAHAN) ?>, yang secara ekonomi
            termasuk dalam keluarga/kategori KURANG MAMPU. Surat keterangan ini
            dibuat untuk keperluan:
        </p>

        <p class="perihal-box"><?= esc(defaultJika($d['perihal'])) ?></p>

        <p class="paragraf-tutup">
            Demikian surat keterangan tidak mampu ini kami buat dengan sebenarnya
            berdasarkan keadaan yang sesungguhnya, untuk dapat dipergunakan
            sebagaimana mestinya.
        </p>
    </div>

    <?php tampilkanTandaTangan('Ketua RW ' . ORG_RW); ?>
    <?php
}

// ============================================================
// BAGIAN 8 — CSS CETAK (dipakai inline lewat fungsi cetakStyle())
// ============================================================
function cetakStyle(): void
{
    ?>
    <style>
        @page { size: A4; margin: 2cm 2cm 2cm 2.5cm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; background: #e9e9e9; margin: 0; padding: 0; }
        .toolbar { max-width: 21cm; margin: 16px auto; padding: 12px 16px; background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.15); display: flex; justify-content: space-between; align-items: center; font-family: Arial, sans-serif; }
        .toolbar .info-kode { font-size: 11pt; color: #555; }
        .toolbar .aksi button { font-family: Arial, sans-serif; font-size: 10.5pt; padding: 8px 16px; margin-left: 8px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-cetak { background: #1d6f42; color: #fff; }
        .btn-cetak:hover { background: #17592f; }
        .btn-kembali { background: #6c757d; color: #fff; }
        .btn-kembali:hover { background: #565e64; }
        .lembar-surat { width: 21cm; min-height: 29.7cm; margin: 0 auto 24px auto; padding: 2cm 2cm 2cm 2.5cm; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.25); }
        .kop-surat { width: 100%; border-collapse: collapse; }
        .kop-logo { width: 90px; vertical-align: middle; text-align: center; }
        .logo-lingkaran { width: 70px; height: 70px; border-radius: 50%; background: #0a4f9c; color: #fff; font-weight: bold; font-size: 16pt; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
        .kop-teks { text-align: center; vertical-align: middle; }
        .kop-teks h1 { font-size: 15pt; margin: 0 0 2px 0; letter-spacing: .5px; }
        .kop-teks p { margin: 1px 0; font-size: 11pt; }
        .kop-alamat { font-size: 10pt !important; font-style: italic; }
        .garis-ganda { margin: 6px 0 14px 0; }
        .garis-tebal { border-top: 3px solid #000; margin-bottom: 2px; }
        .garis-tipis { border-top: 1px solid #000; }
        .judul-surat { text-align: center; margin-bottom: 20px; }
        .judul-surat h2 { font-size: 13pt; text-decoration: underline; margin: 0 0 4px 0; letter-spacing: .5px; }
        .judul-surat p { margin: 0; font-size: 12pt; }
        .isi-surat { text-align: justify; }
        .isi-surat p { margin: 0 0 10px 0; text-indent: 1.2cm; }
        .perihal-box { text-indent: 0 !important; margin: 10px 0 14px 1.2cm !important; font-style: italic; font-weight: bold; }
        .tabel-identitas { margin: 4px 0 14px 1.2cm; border-collapse: collapse; font-size: 12pt; }
        .tabel-identitas td { padding: 2px 6px 2px 0; vertical-align: top; }
        .tabel-identitas .label { width: 170px; white-space: nowrap; }
        .tabel-identitas .titik-dua { width: 15px; }
        .blok-ttd { width: 100%; margin-top: 24px; border-collapse: collapse; }
        .blok-ttd .ttd-kosong { width: 55%; }
        .blok-ttd .ttd-isi { width: 45%; text-align: center; }
        .blok-ttd .ttd-isi p { margin: 2px 0; text-indent: 0; }
        .ruang-ttd { height: 70px; }
        .nama-ttd { font-weight: bold; margin-top: 4px !important; }
        .footer-surat { margin-top: 30px; padding-top: 6px; border-top: 1px dashed #999; text-align: center; font-family: Arial, sans-serif; font-size: 8.5pt; color: #777; }
        .halaman-info { width: 21cm; margin: 60px auto; background: #fff; padding: 40px; text-align: center; border-radius: 6px; box-shadow: 0 1px 6px rgba(0,0,0,.25); font-family: Arial, sans-serif; }
        .halaman-info h2 { color: #b02a2a; font-family: Arial, sans-serif; }
        .halaman-info p { color: #444; font-family: Arial, sans-serif; }
        .halaman-info a { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #6c757d; color: #fff; text-decoration: none; border-radius: 4px; font-family: Arial, sans-serif; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .lembar-surat { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .halaman-info { display: none; }
        }
    </style>
    <?php
}

// ============================================================
// BAGIAN 9 — LOGIKA UTAMA (VALIDASI & ROUTING JENIS SURAT)
// ============================================================

// ── VALIDASI PARAMETER ID ──────────────────────────────────────
$idPengajuan = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idPengajuan || $idPengajuan <= 0) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head><meta charset="UTF-8"><title>Parameter Tidak Valid</title><?php cetakStyle(); ?></head>
    <body>
        <div class="halaman-info">
            <h2>Parameter Tidak Valid</h2>
            <p>ID pengajuan tidak ditemukan atau tidak valid pada URL.</p>
            <a href="javascript:history.back()">&larr; Kembali</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ── AMBIL DATA PENGAJUAN ───────────────────────────────────────
$dataPengajuan = getPengajuanById($koneksi, $idPengajuan);

if ($dataPengajuan === null) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head><meta charset="UTF-8"><title>Data Tidak Ditemukan</title><?php cetakStyle(); ?></head>
    <body>
        <div class="halaman-info">
            <h2>Data Tidak Ditemukan</h2>
            <p>Pengajuan surat dengan ID tersebut tidak ditemukan di database.</p>
            <a href="javascript:history.back()">&larr; Kembali</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ── VALIDASI STATUS TERBARU HARUS ACC ──────────────────────────
$statusTerbaru  = getStatusTerbaru($koneksi, $idPengajuan);
$statusSekarang = $statusTerbaru['status'] ?? 'Belum Diproses';

if ($statusTerbaru === null || $statusTerbaru['status'] !== 'ACC') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head><meta charset="UTF-8"><title>Surat Belum Dapat Dicetak</title><?php cetakStyle(); ?></head>
    <body>
        <div class="halaman-info">
            <h2>Surat Belum Dapat Dicetak</h2>
            <p>
                Status pengajuan saat ini: <strong><?= esc($statusSekarang) ?></strong>.<br>
                Surat hanya dapat dicetak apabila status pengajuan sudah
                <strong>ACC</strong> dari pengurus.
            </p>
            <a href="javascript:history.back()">&larr; Kembali</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ── DATA VALID & STATUS ACC -> TAMPILKAN SURAT ─────────────────
$jenisSurat = $dataPengajuan['jenis_surat'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak <?= esc($jenisSurat) ?> - <?= esc($dataPengajuan['nama']) ?></title>
    <?php cetakStyle(); ?>
</head>
<body>

    <!-- TOOLBAR: disembunyikan otomatis saat print lewat @media print -->
    <div class="toolbar">
        <span class="info-kode">Kode Pengajuan: <strong><?= esc($dataPengajuan['kode_pengajuan']) ?></strong></span>
        <span class="aksi">
            <button type="button" class="btn-kembali" onclick="history.back()">&larr; Kembali</button>
            <button type="button" class="btn-cetak" onclick="window.print()">🖨️ Cetak Surat</button>
        </span>
    </div>

    <!-- LEMBAR SURAT -->
    <div class="lembar-surat">
        <?php tampilkanKopSurat(); ?>

        <?php
        // Menentukan template mana yang dirender berdasarkan jenis_surat
        // pada tb_pengajuan.jenis_surat.
        switch ($jenisSurat) {
            case 'Surat Pengantar RT/RW':
                renderSuratRTRW($dataPengajuan);
                break;

            case 'Surat Keterangan Domisili':
                renderSuratDomisili($dataPengajuan);
                break;

            case 'Surat Keterangan Tidak Mampu':
                renderSuratSKTM($dataPengajuan);
                break;

            default:
                echo '<p><em>Jenis surat "' . esc($jenisSurat) . '" belum memiliki template cetak.</em></p>';
                break;
        }
        ?>

        <?php tampilkanFooterSurat($dataPengajuan['kode_pengajuan']); ?>
    </div>

    <script>
        // Auto print: dialog cetak terbuka otomatis saat halaman siap.
        // Tombol "Cetak Surat" tetap tersedia jika dialog ditutup pengguna.
        window.addEventListener('load', function () {
            window.print();
        });
    </script>

</body>
</html>