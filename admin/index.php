
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

/* =========================
   FUNGSI DASAR
========================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function csrf_check() {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $token)
    ) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan muat ulang halaman.');
    }
}

function redirect_admin($url = 'index.php') {
    header('Location: ' . $url);
    exit;
}

$pesan = '';
$error = '';
$action = $_GET['action'] ?? 'dashboard';

/* =========================
   PROSES FORM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $aksi = $_POST['action'] ?? '';

    /* LOGIN */
    if ($aksi === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare(
            "SELECT id, username, password FROM admin WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            redirect_admin();
        } else {
            $error = 'Username atau password salah.';
            $action = 'login';
        }
    }

    /* LOGOUT */
    elseif ($aksi === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect_admin();
    }

    /* HARUS LOGIN UNTUK AKSI SELANJUTNYA */
    elseif (empty($_SESSION['admin_id'])) {
        redirect_admin('index.php?action=login');
    }

    /* TAMBAH WISATA */
    elseif ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $foto = trim($_POST['foto'] ?? '');
        $aktivitas = trim($_POST['aktivitas'] ?? '');
        $harga = trim($_POST['harga_tiket'] ?? '');
        $jam = trim($_POST['jam_buka'] ?? '');
        $fasilitas = trim($_POST['fasilitas'] ?? '');

        if ($nama === '' || $lokasi === '' || $kategori === '') {
            $error = 'Nama, lokasi, dan kategori wajib diisi.';
            $action = 'tambah';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO wisata
                (nama, lokasi, kategori, deskripsi, foto, aktivitas,
                 harga_tiket, jam_buka, fasilitas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                'sssssssss',
                $nama, $lokasi, $kategori, $deskripsi, $foto,
                $aktivitas, $harga, $jam, $fasilitas
            );

            if ($stmt->execute()) {
                $stmt->close();
                redirect_admin('index.php?pesan=tambah');
            }

            $error = 'Gagal menambahkan wisata: ' . $stmt->error;
            $stmt->close();
            $action = 'tambah';
        }
    }

    /* EDIT WISATA */
    elseif ($aksi === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $foto = trim($_POST['foto'] ?? '');
        $aktivitas = trim($_POST['aktivitas'] ?? '');
        $harga = trim($_POST['harga_tiket'] ?? '');
        $jam = trim($_POST['jam_buka'] ?? '');
        $fasilitas = trim($_POST['fasilitas'] ?? '');

        if ($id < 1 || $nama === '' || $lokasi === '' || $kategori === '') {
            $error = 'Data belum lengkap atau ID tidak valid.';
            $action = 'edit';
            $_GET['id'] = $id;
        } else {
            $stmt = $conn->prepare(
                "UPDATE wisata SET
                nama=?, lokasi=?, kategori=?, deskripsi=?, foto=?,
                aktivitas=?, harga_tiket=?, jam_buka=?, fasilitas=?
                WHERE id=?"
            );

            $stmt->bind_param(
                'sssssssssi',
                $nama, $lokasi, $kategori, $deskripsi, $foto,
                $aktivitas, $harga, $jam, $fasilitas, $id
            );

            if ($stmt->execute()) {
                $stmt->close();
                redirect_admin('index.php?pesan=edit');
            }

            $error = 'Gagal memperbarui wisata: ' . $stmt->error;
            $stmt->close();
            $action = 'edit';
            $_GET['id'] = $id;
        }
    }

    /* HAPUS WISATA */
    elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM wisata WHERE id=?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $stmt->close();
                redirect_admin('index.php?pesan=hapus');
            }

            $error = 'Gagal menghapus data wisata.';
            $stmt->close();
        } else {
            $error = 'ID wisata tidak valid.';
        }

        $action = 'dashboard';
    }

    /* GANTI PASSWORD */
    elseif ($aksi === 'password') {
        $passwordLama = $_POST['password_lama'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';
        $adminId = (int)$_SESSION['admin_id'];

        $stmt = $conn->prepare("SELECT password FROM admin WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$admin || !password_verify($passwordLama, $admin['password'])) {
            $error = 'Password lama tidak sesuai.';
            $action = 'password';
        } elseif (strlen($passwordBaru) < 10) {
            $error = 'Password baru minimal 10 karakter.';
            $action = 'password';
        } elseif ($passwordBaru !== $konfirmasi) {
            $error = 'Konfirmasi password tidak sama.';
            $action = 'password';
        } else {
            $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE admin SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $adminId);

            if ($stmt->execute()) {
                $stmt->close();
                redirect_admin('index.php?pesan=password');
            }

            $error = 'Gagal mengganti password.';
            $stmt->close();
            $action = 'password';
        }
    }
}

/* =========================
   CEK STATUS LOGIN
========================= */
$login = !empty($_SESSION['admin_id']);

if (!$login) {
    $action = 'login';
}

if (isset($_GET['pesan'])) {
    $daftarPesan = [
        'tambah' => 'Wisata berhasil ditambahkan.',
        'edit' => 'Data wisata berhasil diperbarui.',
        'hapus' => 'Data wisata berhasil dihapus.',
        'password' => 'Password berhasil diganti.'
    ];

    $pesan = $daftarPesan[$_GET['pesan']] ?? '';
}

/* =========================
   DATA DASHBOARD
========================= */
$totalWisata = 0;
$totalKategori = 0;
$daftarWisata = [];
$editData = null;

if ($login) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM wisata");
    if ($result) {
        $totalWisata = (int)$result->fetch_assoc()['total'];
    }

    $result = $conn->query("SELECT COUNT(DISTINCT kategori) AS total FROM wisata");
    if ($result) {
        $totalKategori = (int)$result->fetch_assoc()['total'];
    }

    /* PENCARIAN NAMA, LOKASI, DAN KATEGORI */
    $cari = trim($_GET['cari'] ?? '');

    if ($cari !== '') {
        $kata = '%' . $cari . '%';

        $stmt = $conn->prepare(
            "SELECT id, nama, lokasi, kategori, harga_tiket, jam_buka
             FROM wisata
             WHERE nama LIKE ? OR lokasi LIKE ? OR kategori LIKE ?
             ORDER BY id DESC"
        );
        $stmt->bind_param('sss', $kata, $kata, $kata);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $daftarWisata[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query(
            "SELECT id, nama, lokasi, kategori, harga_tiket, jam_buka
             FROM wisata ORDER BY id DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $daftarWisata[] = $row;
            }
        }
    }

    if ($action === 'edit') {
        $idEdit = (int)($_GET['id'] ?? 0);

        if ($idEdit > 0) {
            $stmt = $conn->prepare("SELECT * FROM wisata WHERE id=? LIMIT 1");
            $stmt->bind_param('i', $idEdit);
            $stmt->execute();
            $editData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if (!$editData && !$error) {
            $error = 'Data wisata yang ingin diedit tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Jelajah Banten</title>

<style>
:root {
    --hijau: #217346;
    --hijau-gelap: #165534;
    --hijau-muda: #e9f6ed;
    --latar: #f4f7f4;
    --teks: #24352a;
    --abu: #6b776e;
    --garis: #e1e9e2;
    --merah: #c73535;
}

* {
    box-sizing: border-box;
}

html {
    -webkit-text-size-adjust: 100%;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--latar);
    color: var(--teks);
    font-size: 14px;
}

a {
    color: inherit;
    text-decoration: none;
}

button, input, textarea, select {
    font: inherit;
}

.topbar {
    background: #fff;
    border-bottom: 1px solid var(--garis);
    position: sticky;
    top: 0;
    z-index: 10;
}

.topbar-inner {
    max-width: 1200px;
    width: calc(100% - 32px);
    margin: auto;
    min-height: 68px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.brand {
    font-weight: 800;
    color: var(--hijau-gelap);
    font-size: 19px;
    white-space: nowrap;
}

.brand small {
    display: block;
    font-size: 11px;
    color: var(--abu);
    font-weight: 500;
    margin-top: 4px;
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 9px;
}

.admin-name {
    color: var(--abu);
    font-size: 12px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: 0;
    border-radius: 9px;
    padding: 10px 13px;
    min-height: 38px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    text-align: center;
    transition: .2s;
}

.btn-primary {
    background: var(--hijau);
    color: #fff;
}

.btn-primary:hover {
    background: var(--hijau-gelap);
}

.btn-light {
    background: var(--hijau-muda);
    color: var(--hijau-gelap);
}

.btn-danger {
    background: #fff0f0;
    color: var(--merah);
}

.btn-outline {
    background: #fff;
    color: var(--teks);
    border: 1px solid var(--garis);
}

.logout-form {
    margin: 0;
}

.layout {
    max-width: 1200px;
    width: calc(100% - 32px);
    margin: 22px auto 40px;
}

.welcome {
    margin-bottom: 18px;
}

.welcome h1 {
    font-size: 25px;
    margin: 0 0 6px;
    letter-spacing: -.5px;
}

.welcome p {
    margin: 0;
    color: var(--abu);
    line-height: 1.5;
}

.nav-menu {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.nav-link {
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 10px;
    gap: 7px;
    border: 1px solid var(--garis);
    border-radius: 11px;
    background: #fff;
    color: #46564a;
    font-size: 13px;
    font-weight: 700;
}

.nav-link:hover,
.nav-link.active {
    background: var(--hijau-muda);
    border-color: #b9ddc4;
    color: var(--hijau-gelap);
}

.stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border: 1px solid var(--garis);
    border-radius: 13px;
    padding: 18px;
}

.stat-label {
    color: var(--abu);
    font-size: 13px;
}

.stat-number {
    font-size: 28px;
    font-weight: 800;
    margin-top: 8px;
    color: var(--hijau-gelap);
}

.panel {
    background: #fff;
    border: 1px solid var(--garis);
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 18px;
    min-width: 0;
}

.panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 17px;
}

.panel-heading h2 {
    margin: 0;
    font-size: 19px;
}

.panel-heading p {
    color: var(--abu);
    margin: 5px 0 0;
    font-size: 12px;
}

.search-form {
    display: flex;
    gap: 8px;
    width: 100%;
    margin-bottom: 16px;
}

.search-form input {
    min-width: 0;
    flex: 1;
}

input, textarea, select {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #dce5dd;
    border-radius: 9px;
    background: #fff;
    color: var(--teks);
    outline: none;
}

input:focus, textarea:focus, select:focus {
    border-color: var(--hijau);
    box-shadow: 0 0 0 3px rgba(33,115,70,.09);
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.field {
    min-width: 0;
}

.field.full {
    grid-column: 1 / -1;
}

.field label {
    display: block;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 7px;
}

.field small {
    display: block;
    color: var(--abu);
    margin-top: 5px;
    line-height: 1.4;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

th, td {
    padding: 13px 10px;
    text-align: left;
    border-bottom: 1px solid var(--garis);
    vertical-align: middle;
}

th {
    color: var(--abu);
    background: #f8faf8;
    font-size: 12px;
    white-space: nowrap;
}

td:first-child {
    color: var(--abu);
    width: 38px;
}

.category {
    display: inline-block;
    border-radius: 20px;
    padding: 5px 9px;
    background: var(--hijau-muda);
    color: var(--hijau-gelap);
    font-size: 11px;
    font-weight: 700;
}

.row-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.row-actions .btn {
    padding: 7px 9px;
    min-height: 32px;
    font-size: 12px;
}

.inline-form {
    display: inline;
    margin: 0;
}

.notice {
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 16px;
    line-height: 1.5;
}

.notice-success {
    color: #185b32;
    background: #e8f7ec;
    border: 1px solid #c5e8ce;
}

.notice-error {
    color: #922b2b;
    background: #fff0f0;
    border: 1px solid #f1cccc;
}

.empty {
    padding: 28px 15px;
    text-align: center;
    color: var(--abu);
    background: #fafcfa;
    border-radius: 10px;
}

.login-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 18px;
    background: linear-gradient(135deg, #e8f5eb, #f7faf7);
}

.login-card {
    background: #fff;
    width: 100%;
    max-width: 410px;
    padding: 27px;
    border-radius: 17px;
    border: 1px solid var(--garis);
    box-shadow: 0 15px 45px rgba(30, 70, 40, .08);
}

.login-card h1 {
    color: var(--hijau-gelap);
    margin: 0 0 7px;
    font-size: 24px;
}

.login-card > p {
    margin: 0 0 22px;
    color: var(--abu);
    line-height: 1.5;
}

.login-card .field {
    margin-bottom: 15px;
}

.login-card .btn {
    width: 100%;
    margin-top: 5px;
}

.footer {
    text-align: center;
    color: var(--abu);
    font-size: 12px;
    padding: 10px 0 0;
}

/* RESPONSIF TABLET */
@media (max-width: 800px) {
    .topbar-inner {
        width: calc(100% - 24px);
    }

    .layout {
        width: calc(100% - 24px);
        margin-top: 17px;
    }

    .nav-menu {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .panel {
        padding: 16px;
    }

    .welcome h1 {
        font-size: 22px;
    }
}

/* RESPONSIF HP */
@media (max-width: 600px) {
    body {
        font-size: 13px;
    }

    .topbar-inner {
        width: calc(100% - 20px);
        min-height: 58px;
        gap: 8px;
    }

    .brand {
        font-size: 15px;
    }

    .brand small {
        font-size: 10px;
        margin-top: 2px;
    }

    .top-actions {
        gap: 6px;
    }

    .admin-name {
        display: none;
    }

    .top-actions .btn {
        min-height: 33px;
        padding: 7px 9px;
        font-size: 11px;
    }

    .layout {
        width: calc(100% - 20px);
        margin: 15px auto 25px;
    }

    .welcome {
        margin-bottom: 13px;
    }

    .welcome h1 {
        font-size: 20px;
    }

    .welcome p {
        font-size: 12px;
    }

    .nav-menu {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
        margin-bottom: 14px;
    }

    .nav-link {
        min-height: 42px;
        padding: 8px 5px;
        border-radius: 9px;
        font-size: 11px;
        gap: 4px;
    }

    .stats {
        gap: 8px;
        margin-bottom: 14px;
    }

    .stat-card {
        padding: 12px;
        border-radius: 11px;
    }

    .stat-label {
        font-size: 11px;
        line-height: 1.4;
    }

    .stat-number {
        font-size: 23px;
        margin-top: 5px;
    }

    .panel {
        padding: 13px;
        border-radius: 11px;
        margin-bottom: 13px;
    }

    .panel-heading {
        margin-bottom: 13px;
        align-items: flex-start;
    }

    .panel-heading h2 {
        font-size: 16px;
    }

    .panel-heading p {
        font-size: 11px;
    }

    .btn {
        font-size: 12px;
        min-height: 36px;
        padding: 9px 10px;
    }

    .search-form {
        gap: 6px;
        margin-bottom: 12px;
    }

    .search-form input {
        padding: 10px;
        font-size: 12px;
    }

    .search-form .btn {
        padding: 8px 10px;
        white-space: nowrap;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .field.full {
        grid-column: auto;
    }

    input, textarea, select {
        padding: 10px;
        font-size: 13px;
    }

    /* Tabel menjadi kartu di HP */
    .table-wrap {
        overflow: visible;
    }

    table, tbody {
        display: block;
        width: 100%;
    }

    thead {
        display: none;
    }

    tbody tr {
        display: block;
        border: 1px solid var(--garis);
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 10px;
        background: #fff;
    }

    tbody td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        width: 100%;
        padding: 7px 0;
        border-bottom: 1px solid #edf1ed;
        font-size: 12px;
        text-align: right;
        overflow-wrap: anywhere;
    }

    tbody td:last-child {
        border-bottom: 0;
        padding-top: 10px;
    }

    tbody td::before {
        content: attr(data-label);
        flex: 0 0 88px;
        text-align: left;
        color: var(--abu);
        font-weight: 700;
        font-size: 11px;
    }

    tbody td:first-child {
        width: 100%;
    }

    .row-actions {
        justify-content: flex-end;
    }

    .row-actions .btn {
        min-height: 30px;
        font-size: 11px;
        padding: 6px 8px;
    }

    .form-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .form-actions .btn {
        width: 100%;
    }

    .login-card {
        padding: 21px;
        border-radius: 14px;
    }

    .login-card h1 {
        font-size: 21px;
    }
}

@media (max-width: 350px) {
    .brand {
        font-size: 13px;
    }

    .top-actions .btn {
        font-size: 10px;
        padding: 6px 7px;
    }

    .nav-link {
        font-size: 10px;
    }

    tbody td::before {
        flex-basis: 74px;
    }
}
</style>
</head>

<body>

<?php if (!$login): ?>

<div class="login-page">
    <div class="login-card">
        <h1>🌿 Jelajah Banten</h1>
        <p>Login administrator untuk mengelola data wisata.</p>

        <?php if ($error): ?>
            <div class="notice notice-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?action=login">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="login">

            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" name="username"
                       placeholder="Masukkan username" required autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                       placeholder="Masukkan password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary">Masuk ke Dashboard</button>
        </form>

        <div class="footer" style="margin-top:18px">
            Admin Panel · Jelajah Banten
        </div>
    </div>
</div>

<?php else: ?>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php">
            🌿 Jelajah Banten
            <small>Panel Administrator</small>
        </a>

        <div class="top-actions">
            <span class="admin-name">
                👤 <?= e($_SESSION['admin_username'] ?? 'Admin') ?>
            </span>

            <form method="post" class="logout-form"
                  onsubmit="return confirm('Yakin ingin keluar dari admin?')">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-outline">Keluar</button>
            </form>
        </div>
    </div>
</header>

<main class="layout">

    <div class="welcome">
        <h1>Dashboard Admin</h1>
        <p>Kelola informasi destinasi wisata Jelajah Banten dengan mudah.</p>
    </div>

    <nav class="nav-menu">
        <a class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>"
           href="index.php">🏠 Dashboard</a>

        <a class="nav-link <?= $action === 'tambah' ? 'active' : '' ?>"
           href="index.php?action=tambah">➕ Tambah Wisata</a>

        <a class="nav-link <?= $action === 'password' ? 'active' : '' ?>"
           href="index.php?action=password">🔐 Ganti Password</a>

        <a class="nav-link" href="../index.php" target="_blank" rel="noopener">
            🌐 Lihat Web
        </a>
    </nav>

    <?php if ($pesan): ?>
        <div class="notice notice-success"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice notice-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'dashboard'): ?>

        <section class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Destinasi Wisata</div>
                <div class="stat-number"><?= $totalWisata ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Kategori</div>
                <div class="stat-number"><?= $totalKategori ?></div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Data Wisata</h2>
                    <p>Cari berdasarkan nama, lokasi, atau kategori.</p>
                </div>
            </div>

            <form method="get" action="index.php" class="search-form">
                <input type="search" name="cari"
                       placeholder="🔎 Cari wisata atau lokasi..."
                       value="<?= e($_GET['cari'] ?? '') ?>"
                       aria-label="Cari wisata">
                <button type="submit" class="btn btn-primary">Cari</button>

                <?php if (!empty($_GET['cari'])): ?>
                    <a href="index.php" class="btn btn-light">Reset</a>
                <?php endif; ?>
            </form>

            <p style="font-size:12px;color:#6b776e;margin:0 0 12px">
                Ditemukan <?= count($daftarWisata) ?> data wisata.
            </p>

            <?php if (count($daftarWisata) > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Wisata</th>
                                <th>Lokasi</th>
                                <th>Kategori</th>
                                <th>Harga Tiket</th>
                                <th>Jam Buka</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($daftarWisata as $w): ?>
                            <tr>
                                <td data-label="ID"><?= (int)$w['id'] ?></td>
                                <td data-label="Nama Wisata">
                                    <strong><?= e($w['nama']) ?></strong>
                                </td>
                                <td data-label="Lokasi"><?= e($w['lokasi']) ?></td>
                                <td data-label="Kategori">
                                    <span class="category"><?= e($w['kategori']) ?></span>
                                </td>
                                <td data-label="Harga Tiket">
                                    <?= e($w['harga_tiket'] ?: 'Belum diisi') ?>
                                </td>
                                <td data-label="Jam Buka">
                                    <?= e($w['jam_buka'] ?: 'Belum diisi') ?>
                                </td>
                                <td data-label="Aksi">
                                    <div class="row-actions">
                                        <a class="btn btn-light"
                                           href="index.php?action=edit&id=<?= (int)$w['id'] ?>">
                                            Edit
                                        </a>

                                        <form method="post" class="inline-form"
                                              onsubmit="return confirm('Yakin ingin menghapus wisata ini?')">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id"
                                                   value="<?= (int)$w['id'] ?>">
                                            <button type="submit" class="btn btn-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty">
                    <p>Data wisata tidak ditemukan.</p>
                    <?php if (!empty($_GET['cari'])): ?>
                        <a class="btn btn-light" href="index.php">Tampilkan Semua Wisata</a>
                    <?php else: ?>
                        <a class="btn btn-primary" href="index.php?action=tambah">
                            Tambah Wisata Pertama
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php elseif ($action === 'tambah' || $action === 'edit'): ?>

        <?php
        $isEdit = ($action === 'edit');
        $d = $editData ?: [];
        ?>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2><?= $isEdit ? 'Edit Data Wisata' : 'Tambah Wisata Baru' ?></h2>
                    <p>Isi informasi destinasi wisata di bawah ini.</p>
                </div>
            </div>

            <?php if ($isEdit && !$editData): ?>
                <div class="empty">
                    Data wisata tidak ditemukan.
                    <p><a class="btn btn-light" href="index.php">Kembali ke Dashboard</a></p>
                </div>
            <?php else: ?>

            <form method="post" action="index.php?action=<?= $isEdit ? 'edit&id=' . (int)$d['id'] : 'tambah' ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="<?= $isEdit ? 'edit' : 'tambah' ?>">

                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="field">
                        <label for="nama">Nama Wisata *</label>
                        <input id="nama" name="nama" required
                               value="<?= e($d['nama'] ?? '') ?>"
                               placeholder="Contoh: Pantai Anyer">
                    </div>

                    <div class="field">
                        <label for="lokasi">Lokasi *</label>
                        <input id="lokasi" name="lokasi" required
                               value="<?= e($d['lokasi'] ?? '') ?>"
                               placeholder="Contoh: Serang, Banten">
                    </div>

                    
<div class="field">
    <label for="kategori">Kategori *</label>
    <input
        type="text"
        id="kategori"
        name="kategori"
        value="<?= e($d['kategori'] ?? '') ?>"
        placeholder="Contoh: Pantai, Pulau, Sejarah, Kuliner"
        maxlength="100"
        required
    >
    <small>Ketik sendiri kategori wisata sesuai keinginanmu.</small>
</div>


                    <div class="field">
                        <label for="foto">Nama File Foto</label>
                        <input id="foto" name="foto"
                               value="<?= e($d['foto'] ?? '') ?>"
                               placeholder="Contoh: pantai_anyer.webp">
                        <small>Masukkan nama file gambar yang tersedia di folder images.</small>
                    </div>

                    <div class="field">
                        <label for="harga_tiket">Harga Tiket</label>
                        <input id="harga_tiket" name="harga_tiket"
                               value="<?= e($d['harga_tiket'] ?? '') ?>"
                               placeholder="Contoh: Mulai Rp20.000">
                    </div>

                    
<div class="field">
    <label for="jenis_jam">Jam Operasional</label>

    <select id="jenis_jam" onchange="aturJam()">
        <option value="atur">Atur Jam Sendiri</option>
        <option value="24">Buka 24 Jam</option>
    </select>

    <div id="pilihan_waktu" style="display:flex; gap:10px; align-items:center; margin-top:10px;">
        <input type="time" id="jam_mulai" style="flex:1;">
        <span>sampai</span>
        <input type="time" id="jam_selesai" style="flex:1;">
    </div>

    <input
        type="hidden"
        id="jam_buka"
        name="jam_buka"
        value="<?= e($d['jam_buka'] ?? '') ?>"
    >

    <small>Pilih buka 24 jam atau tentukan sendiri jam operasionalnya.</small>
</div>

<script>
function aturJam() {
    const jenis = document.getElementById('jenis_jam').value;
    const pilihan = document.getElementById('pilihan_waktu');
    const mulai = document.getElementById('jam_mulai');
    const selesai = document.getElementById('jam_selesai');
    const hasil = document.getElementById('jam_buka');

    if (jenis === '24') {
        pilihan.style.display = 'none';
        hasil.value = '24 Jam';
    } else {
        pilihan.style.display = 'flex';
        perbaruiJam();
    }
}

function perbaruiJam() {
    const mulai = document.getElementById('jam_mulai').value;
    const selesai = document.getElementById('jam_selesai').value;
    const hasil = document.getElementById('jam_buka');

    if (mulai && selesai) {
        hasil.value = mulai + ' - ' + selesai + ' WIB';
    } else {
        hasil.value = '';
    }
}

document.getElementById('jam_mulai').addEventListener('input', perbaruiJam);
document.getElementById('jam_selesai').addEventListener('input', perbaruiJam);

(function () {
    const hasil = document.getElementById('jam_buka');
    const mulai = document.getElementById('jam_mulai');
    const selesai = document.getElementById('jam_selesai');
    const jenis = document.getElementById('jenis_jam');

    if (hasil.value.trim().toLowerCase() === '24 jam') {
        jenis.value = '24';
    } else {
        const nilaiLama = hasil.value.match(/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/);

        if (nilaiLama) {
            mulai.value = nilaiLama[1];
            selesai.value = nilaiLama[2];
        }
    }

    aturJam();
})();
</script>


                    <div class="field full">
                        <label for="deskripsi">Deskripsi Wisata</label>
                        <textarea id="deskripsi" name="deskripsi"
                                  placeholder="Jelaskan daya tarik wisata..."><?= e($d['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="aktivitas">Aktivitas</label>
                        <textarea id="aktivitas" name="aktivitas"
                                  placeholder="Contoh: berenang, berfoto, menikmati pemandangan"><?= e($d['aktivitas'] ?? '') ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="fasilitas">Fasilitas</label>
                        <textarea id="fasilitas" name="fasilitas"
                                  placeholder="Contoh: area parkir, toilet, tempat makan"><?= e($d['fasilitas'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Wisata' ?>
                    </button>
                    <a href="index.php" class="btn btn-outline">Batal</a>
                </div>
            </form>

            <?php endif; ?>
        </section>

    <?php elseif ($action === 'password'): ?>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Ganti Password Admin</h2>
                    <p>Gunakan password baru yang kuat dan mudah kamu ingat.</p>
                </div>
            </div>

            <form method="post" action="index.php?action=password">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="password">

                <div class="form-grid">
                    <div class="field full">
                        <label for="password_lama">Password Lama</label>
                        <input id="password_lama" type="password" name="password_lama"
                               required autocomplete="current-password">
                    </div>

                    <div class="field">
                        <label for="password_baru">Password Baru</label>
                        <input id="password_baru" type="password" name="password_baru"
                               minlength="10" required autocomplete="new-password">
                        <small>Minimal 10 karakter.</small>
                    </div>

                    <div class="field">
                        <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                        <input id="konfirmasi_password" type="password"
                               name="konfirmasi_password" minlength="10"
                               required autocomplete="new-password">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Password</button>
                    <a href="index.php" class="btn btn-outline">Kembali</a>
                </div>
            </form>
        </section>

    <?php endif; ?>

    <div class="footer">© <?= date('Y') ?> Jelajah Banten · Panel Administrator</div>

</main>

<?php endif; ?>

</body>
</html>
