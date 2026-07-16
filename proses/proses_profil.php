<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  FILE    : proses_profil.php                                     ║
// ║  SISTEM  : SIAP TARUNA v1.0.0                                    ║
// ║  LOKASI  : /proses/proses_profil.php                             ║
// ║  FUNGSI  : Memproses update data profil pengguna (warga/pengurus)║
// ║  AUTHOR  : Muhammad Alfi — NIM 17220381 — UBSI 2026              ║
// ╚══════════════════════════════════════════════════════════════════╝

require_once __DIR__ . '/../fungsi.php';
wajibLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

$idUser      = (int)$_SESSION['id_user'];
$nama        = trim($_POST['nama']         ?? '');
$no_hp       = trim($_POST['no_hp']        ?? '');
$alamat      = trim($_POST['alamat']       ?? '');
$pwd_baru    = trim($_POST['password_baru'] ?? '');

if (kosong($nama)) {
    redirectDengan(
        getRole()==='pengurus'
            ? APP_URL.'/pengurus/profil.php'
            : APP_URL.'/warga/profil.php',
        'error','Nama lengkap wajib diisi.'
    );
}

$nm  = $koneksi->real_escape_string($nama);
$hp  = $koneksi->real_escape_string($no_hp);
$al  = $koneksi->real_escape_string($alamat);

// ★ SCREENSHOT untuk Bab 4 → UPDATE Data Profil
$koneksi->query(
    "UPDATE tb_users SET nama='$nm', no_hp='$hp', alamat='$al'
     WHERE id_user=$idUser"
);

// Update password jika diisi
if (!kosong($pwd_baru)) {
    if (strlen($pwd_baru) < 8) {
        redirectDengan(
            getRole()==='pengurus'
                ? APP_URL.'/pengurus/profil.php'
                : APP_URL.'/warga/profil.php',
            'error','Password baru minimal 8 karakter.'
        );
    }
    $pwd = password_hash($pwd_baru, PASSWORD_BCRYPT);
    $koneksi->query("UPDATE tb_users SET password='$pwd' WHERE id_user=$idUser");
}

// Perbarui session
$_SESSION['nama']   = $nama;
$_SESSION['no_hp']  = $no_hp;
$_SESSION['alamat'] = $alamat;

$back = getRole()==='pengurus'
    ? APP_URL.'/pengurus/profil.php'
    : APP_URL.'/warga/profil.php';
redirectDengan($back,'sukses','✅ Profil berhasil diperbarui.');