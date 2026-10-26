
<?php
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya admin yang boleh masuk
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

$jumlahWisata = 0;
$jumlahPesanan = 0;
$jumlahPengguna = 0;
$totalPendapatan = 0;
$pesananTerbaru = [];

try {

    $jumlahWisata = (int)$pdo->query(
        "SELECT COUNT(*) FROM wisata"
    )->fetchColumn();

    $jumlahPesanan = (int)$pdo->query(
        "SELECT COUNT(*) FROM pesanan"
    )->fetchColumn();

    $jumlahPengguna = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'user'"
    )->fetchColumn();

    $totalPendapatan = (float)$pdo->query(
        "SELECT COALESCE(SUM(total), 0)
         FROM pesanan
         WHERE status IN ('Lunas', 'Berhasil', 'Dibayar')"
    )->fetchColumn();

    $pesananTerbaru = $pdo->query(
        "SELECT id, kode_pesanan, tanggal_kunjungan,
                total, status, dibuat_pada
         FROM pesanan
         ORDER BY id DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Dashboard tetap dapat dibuka jika query gagal.
}

function rupiahAdmin($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function amanAdmin($teks) {
    return htmlspecialchars(
        (string)$teks,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin | JelajahBanten</title>

<link rel="stylesheet" href="assets/css/style.css">

<style>

/* ==============================
   DASAR HALAMAN ADMIN
============================== */

* {
    box-sizing: border-box;
}

html,
body {
    width: 100%;
    max-width: 100%;
    margin: 0;
    overflow-x: clip;
}

body {
    background: #fff7fb;
    color: #59434f;
    font-family: Arial, sans-serif;
    line-height: 1.5;
}

.admin-container {
    width: calc(100% - 32px);
    max-width: 1120px;
    margin: 0 auto;
    padding: 20px 0 30px;
    min-width: 0;
}

.admin-container > p {
    overflow-wrap: anywhere;
}

.admin-container .section-title {
    margin-bottom: 8px;
    color: #c85f89;
}

/* ==============================
   NAVBAR
============================== */

.admin-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    width: 100%;
    padding: 15px 5%;
    background: #ffffff;
    border-bottom: 1px solid #f2d5e1;
}

.admin-navbar .brand {
    color: #c85f89;
    font-weight: bold;
    font-size: 20px;
    text-decoration: none;
    overflow-wrap: anywhere;
}

.admin-navbar .brand span {
    color: #e78eaf;
}

.admin-nav-links {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 12px;
}

.admin-nav-links a {
    padding: 8px 12px;
    border-radius: 8px;
    background: #e78eaf;
    color: white;
    text-decoration: none;
}

/* ==============================
   LAYOUT ADMIN
============================== */

.admin-layout {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
    width: 100%;
    min-width: 0;
}

.admin-layout > * {
    min-width: 0;
}

/* ==============================
   SIDEBAR
============================== */

.admin-sidebar {
    position: sticky;
    top: 15px;
    min-width: 0;
    padding: 17px;
    border-radius: 13px;
    background: #e9a3da;
    color: white;
}

.admin-sidebar h3 {
    margin: 0 0 13px;
    font-size: 17px;
}

.admin-sidebar a {
    display: block;
    margin: 4px 0;
    padding: 10px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-size: 12px;
}

.admin-sidebar a:hover,
.admin-sidebar a.aktif {
    background: #d88ac1;
}

/* ==============================
   STATISTIK
============================== */

.admin-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
    margin: 0 0 18px;
    width: 100%;
}

.admin-stat {
    min-width: 0;
    padding: 18px;
    border: 1px solid #f2d5e1;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.admin-stat p {
    margin: 0 0 8px;
    color: #927b87;
    font-size: 12px;
}

.admin-stat h2 {
    margin: 0;
    color: #c85f89;
    font-size: 23px;
    overflow-wrap: anywhere;
}

/* ==============================
   PANEL
============================== */

.admin-panel {
    width: 100%;
    min-width: 0;
    margin-top: 17px;
    padding: 19px;
    border: 1px solid #f2d5e1;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.admin-panel h2 {
    margin: 0 0 15px;
    color: #c85f89;
    font-size: 19px;
}

/* ==============================
   PENGELOLAAN WEBSITE
============================== */

.admin-menu-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
    width: 100%;
}

.admin-menu-card {
    min-width: 0;
    padding: 16px;
    border: 1px solid #f2d5e1;
    border-radius: 11px;
    background: #fffafd;
}

.admin-menu-card h3 {
    margin: 0 0 9px;
    color: #b7507b;
    font-size: 16px;
}

.admin-menu-card p {
    margin: 0 0 13px;
    color: #927b87;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.admin-menu-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.admin-menu-actions .btn {
    display: inline-block;
    width: auto;
    padding: 8px 11px;
    border-radius: 8px;
    background: #e78eaf;
    color: white;
    font-size: 11px;
    text-decoration: none;
    text-align: center;
}

.admin-menu-actions .btn:hover {
    background: #c85f89;
}

.admin-menu-actions .btn-secondary {
    background: #d88ac1;
}

/* ==============================
   PESANAN TERBARU
============================== */

.admin-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 11px 9px;
    border-bottom: 1px solid #f4e1e9;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.admin-table th {
    background: #fff0f5;
    color: #b7507b;
    white-space: nowrap;
}

.admin-table td {
    overflow-wrap: anywhere;
}

.admin-table .status {
    color: #c85f89;
    font-weight: bold;
}

.admin-link {
    display: inline-block;
    margin-top: 12px;
    color: #c85f89;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
}

/* ==============================
   FOOTER
============================== */

.admin-footer {
    padding: 18px;
    border-top: 1px solid #f2d5e1;
    background: white;
    color: #927b87;
    text-align: center;
    font-size: 12px;
}

/* ==============================
   TABLET
============================== */

@media (max-width: 900px) {

    .admin-layout {
        grid-template-columns: 180px minmax(0, 1fr);
        gap: 13px;
    }

    .admin-sidebar {
        padding: 12px;
    }

    .admin-menu-grid {
        grid-template-columns: 1fr;
    }
}

/* ==============================
   HP - TANPA GESER SAMPING
============================== */

@media (max-width: 760px) {

    .admin-navbar {
        padding: 12px;
    }

    .admin-navbar .brand {
        font-size: 17px;
    }

    .admin-nav-links {
        font-size: 11px;
    }

    .admin-container {
        width: calc(100% - 20px);
        padding: 13px 0 20px;
    }

    .admin-container .section-title {
        font-size: 23px;
    }

    .admin-container > p {
        font-size: 12px;
    }

    /* Sidebar menjadi menu atas */

    .admin-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    .admin-sidebar {
        position: static;
        width: 100%;
        padding: 12px;
    }

    .admin-sidebar h3 {
        font-size: 15px;
    }

    .admin-sidebar-links {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }

    .admin-sidebar a {
        display: flex;
        align-items: center;
        margin: 0;
        padding: 9px;
        font-size: 11px;
        overflow-wrap: anywhere;
    }

    /* Statistik */

    .admin-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
        margin-bottom: 12px;
    }

    .admin-stat {
        padding: 12px;
    }

    .admin-stat p {
        font-size: 11px;
    }

    .admin-stat h2 {
        font-size: 18px;
    }

    /* Panel */

    .admin-panel {
        padding: 12px;
        margin-top: 12px;
    }

    .admin-panel h2 {
        font-size: 17px;
    }

    /* Pengelolaan Website */

    .admin-menu-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 10px;
    }

    .admin-menu-card {
        padding: 13px;
    }

    .admin-menu-card h3 {
        font-size: 15px;
    }

    .admin-menu-card p {
        font-size: 12px;
    }

    /* Pesanan terbaru menjadi kartu */

    .admin-table-wrap {
        overflow-x: visible;
    }

    .admin-table,
    .admin-table tbody {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .admin-table thead {
        display: none;
    }

    .admin-table tbody tr {
        display: block;
        width: 100%;
        margin-bottom: 10px;
        padding: 11px;
        border: 1px solid #f2d5e1;
        border-radius: 10px;
        background: #fffafd;
    }

    .admin-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        width: 100%;
        padding: 8px 0;
        border-bottom: 1px solid #f7e8ef;
        font-size: 11px;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .admin-table tbody td::before {
        content: attr(data-label);
        flex: 0 0 95px;
        color: #c85f89;
        font-weight: bold;
        text-align: left;
    }

    .admin-table tbody td:last-child {
        border-bottom: none;
    }

    .admin-table td[colspan] {
        display: block;
        text-align: center;
    }

    .admin-table td[colspan]::before {
        display: none;
    }
}

/* HP KECIL */

@media (max-width: 360px) {

    .admin-sidebar-links {
        grid-template-columns: 1fr;
    }

    .admin-stats {
        grid-template-columns: 1fr;
    }

    .admin-table tbody td::before {
        flex-basis: 80px;
    }
}

</style>
</head>

<body>

<!-- NAVBAR -->

<nav class="admin-navbar">

    <a class="brand" href="admin.php">
        💐 Jelajah<span>Banten</span>
    </a>

    <div class="admin-nav-links">
        <span>Administrator</span>
        <a href="logout.php">Keluar</a>
    </div>

</nav>

<!-- KONTEN -->

<main class="admin-container">

    <p class="eyebrow">PUSAT PENGELOLAAN</p>

    <h1 class="section-title">
        Dashboard Admin
    </h1>

    <p>
        Halo,
        <?= amanAdmin($_SESSION['nama'] ?? 'Administrator') ?>!
        Kelola data website JelajahBanten dari sini.
    </p>

    <div class="admin-layout">

        <!-- MENU ADMIN -->

        <aside class="admin-sidebar">

            <h3>💐 Menu Admin</h3>

            <div class="admin-sidebar-links">

                <a href="admin.php" class="aktif">
                    🏠 Dashboard
                </a>

                <a href="kelola_wisata.php">
                    🏝️ Kelola Wisata
                </a>

                <a href="admin_pesanan.php">
                    🛒 Kelola Pesanan
                </a>

                <a href="admin_pengguna.php">
                    👥 Kelola Pengguna
                </a>

                <a href="admin_kategori.php">
                    🗂️ Kelola Kategori
                </a>

                <a href="admin_wilayah.php">
                    📍 Kelola Wilayah
                </a>

                <a
                    href="beranda.php"
                    target="_blank"
                    rel="noopener"
                >
                    🌐 Lihat Website
                </a>

                <a href="logout.php">
                    ↪ Keluar
                </a>

            </div>

        </aside>

        <!-- BAGIAN KANAN -->

        <section>

            <!-- STATISTIK -->

            <div class="admin-stats">

                <div class="admin-stat">
                    <p>🏝️ Total Wisata</p>
                    <h2><?= $jumlahWisata ?></h2>
                </div>

                <div class="admin-stat">
                    <p>🛒 Total Pesanan</p>
                    <h2><?= $jumlahPesanan ?></h2>
                </div>

                <div class="admin-stat">
                    <p>👥 Akun Pengguna</p>
                    <h2><?= $jumlahPengguna ?></h2>
                </div>

                <div class="admin-stat">
                    <p>💰 Total Pesanan Berstatus Lunas</p>
                    <h2><?= rupiahAdmin($totalPendapatan) ?></h2>
                </div>

            </div>

            <!-- PENGELOLAAN WEBSITE -->

            <div class="admin-panel">

                <h2>Pengelolaan Website</h2>

                <div class="admin-menu-grid">

                    <div class="admin-menu-card">

                        <h3>🏝️ Data Wisata</h3>

                        <p>
                            Tambah destinasi, ubah harga,
                            foto, lokasi, dan deskripsi.
                        </p>

                        <div class="admin-menu-actions">
                            <a
                                class="btn"
                                href="kelola_wisata.php"
                            >
                                Kelola Wisata
                            </a>
                        </div>

                    </div>

                    <div class="admin-menu-card">

                        <h3>🛒 Pesanan</h3>

                        <p>
                            Periksa pesanan pelanggan
                            dan kelola status pesanan.
                        </p>

                        <div class="admin-menu-actions">
                            <a
                                class="btn"
                                href="admin_pesanan.php"
                            >
                                Kelola Pesanan
                            </a>
                        </div>

                    </div>

                    <div class="admin-menu-card">

                        <h3>👥 Pengguna</h3>

                        <p>
                            Lihat akun pelanggan yang
                            sudah terdaftar.
                        </p>

                        <div class="admin-menu-actions">
                            <a
                                class="btn"
                                href="admin_pengguna.php"
                            >
                                Kelola Pengguna
                            </a>
                        </div>

                    </div>

                    <div class="admin-menu-card">

                        <h3>🗂️ Kategori dan Wilayah</h3>

                        <p>
                            Kelola kategori wisata dan
                            wilayah destinasi.
                        </p>

                        <div class="admin-menu-actions">

                            <a
                                class="btn"
                                href="admin_kategori.php"
                            >
                                Kelola Kategori
                            </a>

                            <a
                                class="btn btn-secondary"
                                href="admin_wilayah.php"
                            >
                                Kelola Wilayah
                            </a>

                        </div>

                    </div>

                </div>

            </div>

            <!-- PESANAN TERBARU -->

            <div class="admin-panel">

                <h2>Pesanan Terbaru</h2>

                <div class="admin-table-wrap">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>Kode Pesanan</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!$pesananTerbaru): ?>

                            <tr>
                                <td colspan="4">
                                    Belum ada data pesanan.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($pesananTerbaru as $p): ?>

                                <tr>

                                    <td data-label="Kode Pesanan">
                                        <?= amanAdmin($p['kode_pesanan']) ?>
                                    </td>

                                    <td data-label="Tanggal Kunjungan">
                                        <?= amanAdmin($p['tanggal_kunjungan']) ?>
                                    </td>

                                    <td data-label="Total">
                                        <?= rupiahAdmin($p['total']) ?>
                                    </td>

                                    <td
                                        data-label="Status"
                                        class="status"
                                    >
                                        <?= amanAdmin($p['status']) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <a
                    class="admin-link"
                    href="admin_pesanan.php"
                >
                    Lihat semua pesanan →
                </a>

            </div>

        </section>

    </div>

</main>

<!-- FOOTER -->

<footer class="admin-footer">
    <p>
        JelajahBanten &copy; <?= date('Y') ?>
    </p>
</footer>

</body>
</html>
