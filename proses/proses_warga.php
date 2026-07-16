<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : proses_warga.php                                      ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /proses/proses_warga.php                              ║
// ║  FUNGSI  : Menangani tambah dan hapus data warga oleh pengurus.  ║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/../fungsi.php';
wajibPengurus();

$aksi = trim($_POST['aksi'] ?? $_GET['aksi'] ?? '');

// ══ TAMBAH WARGA ══════════════════════════════════════════════════
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama     = trim($_POST['nama']     ?? '');
    $nik      = trim($_POST['NIK']      ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $jabatan  = trim($_POST['jabatan']  ?? 'Warga');
    $no_hp    = trim($_POST['no_hp']    ?? '');
    $alamat   = trim($_POST['alamat']   ?? '');

    // Validasi
    $err = [];
    if (kosong($nama))     $err[] = 'Nama wajib diisi.';
    if (strlen($nik)!==16 || !ctype_digit($nik)) $err[] = 'NIK harus tepat 16 digit angka.';
    if (kosong($username)) $err[] = 'Username wajib diisi.';
    if (strlen($password)<8) $err[] = 'Password minimal 8 karakter.';

    // Cek duplikat NIK & username
    $n = $koneksi->real_escape_string($nik);
    $u = $koneksi->real_escape_string($username);
    if ($koneksi->query("SELECT id_user FROM tb_users WHERE NIK='$n' LIMIT 1")->num_rows > 0)
        $err[] = 'NIK sudah terdaftar dalam sistem.';
    if ($koneksi->query("SELECT id_user FROM tb_users WHERE username='$u' LIMIT 1")->num_rows > 0)
        $err[] = 'Username sudah digunakan.';

    if ($err) {
        redirectDengan(APP_URL.'/pengurus/data-warga.php','error',implode(' ',$err));
    }

    // ★ SCREENSHOT untuk Bab 4 → INSERT Data Warga Baru
    $nm  = $koneksi->real_escape_string($nama);
    $jb  = $koneksi->real_escape_string($jabatan);
    $hp  = $koneksi->real_escape_string($no_hp);
    $al  = $koneksi->real_escape_string($alamat);
    $pwd = password_hash($password, PASSWORD_BCRYPT);

    $koneksi->query(
        "INSERT INTO tb_users (nama,NIK,username,password,jabatan,no_hp,alamat,role,is_aktif,created_at)
         VALUES ('$nm','$n','$u','$pwd','$jb','$hp','$al','warga',1,NOW())"
    );

    redirectDengan(
        APP_URL.'/pengurus/data-warga.php','sukses',
        "✅ Akun warga <strong>$nama</strong> berhasil ditambahkan."
    );
}

// ══ HAPUS WARGA ═══════════════════════════════════════════════════
if ($aksi === 'hapus') {
    $id = bersihInt($_GET['id'] ?? 0);
    if (!$id) redirectDengan(APP_URL.'/pengurus/data-warga.php','error','ID tidak valid.');

    $q = $koneksi->query("SELECT nama,role FROM tb_users WHERE id_user=$id LIMIT 1");
    if (!$q || $q->num_rows === 0)
        redirectDengan(APP_URL.'/pengurus/data-warga.php','error','Data tidak ditemukan.');

    $row = $q->fetch_assoc();
    if ($row['role'] !== 'warga')
        redirectDengan(APP_URL.'/pengurus/data-warga.php','error','Tidak dapat menghapus akun pengurus.');

    $nama = $row['nama'];
    $koneksi->query("DELETE FROM tb_users WHERE id_user=$id AND role='warga'");

    redirectDengan(
        APP_URL.'/pengurus/data-warga.php','sukses',
        "🗑 Data warga <strong>$nama</strong> berhasil dihapus."
    );
}

redirect(APP_URL . '/pengurus/data-warga.php');