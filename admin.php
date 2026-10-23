
<?php
require 'config.php';

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
    // Dashboard tetap bisa dibuka jika ada data yang belum tersedia.
}

function rupiahAdmin($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function amanAdmin($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | JelajahBanten</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body { background: #f4f7fb; }
        .admin-layout {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            gap: 25px;
            align-items: start;
        }
        .admin-sidebar {
            background: #173b65;
            color: white;
            padding: 22px;
            border-radius: 14px;
            position: sticky;
            top: 20px;
        }
        .admin-sidebar h3 { margin-top: 0; }
        .admin-sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            margin: 5px 0;
        }
        .admin-sidebar a:hover { background: #2b527d; }
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin: 22px 0;
        }
        .admin-stat {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 14px #00000008;
        }
        .admin-stat p { margin-bottom: 8px; }
        .admin-stat h2 { margin: 0; overflow-wrap: anywhere; }
        .admin-panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-top: 20px;
            overflow-x: auto;
        }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e5eaf0;
        }
        .admin-table th { background: #f0f4f8; }
        .admin-menu-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }
        .admin-menu-card {
            border: 1px solid #e2e8f0;
            padding: 18px;
            border-radius: 10px;
        }
        .admin-menu-card h3 { margin-top: 0; }
        .admin-menu-card p { color: #64748b; }
        .status { font-weight: bold; }
        @media (max-width: 760px) {
            .admin-layout { grid-template-columns: 1fr; }
            .admin-sidebar { position: static; }
            .admin-stats, .admin-menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<nav class="navbar">
    <a class="brand" href="admin.php">
        💐Jelajah<span>Banten</span>
    </a>
    <div class="nav-links">
        <span>Administrator</span>
        <a class="nav-cta" href="logout.php">Keluar</a>
    </div>
</nav>

<main class="container">
    <p class="eyebrow">PUSAT PENGELOLAAN</p>
    <h1 class="section-title">Dashboard Admin</h1>
    <p>
        Halo, <?= amanAdmin($_SESSION['nama'] ?? 'Administrator') ?>!
        Kelola data website JelajahBanten dari sini.
    </p>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <h3>💐 Menu Admin</h3>
            <a href="admin.php">🏠 Dashboard</a>
            <a href="kelola_wisata.php">🏝️ Kelola Wisata</a>
            <a href="admin_pesanan.php">🛒 Kelola Pesanan</a>
            <a href="admin_pengguna.php">👥 Kelola Pengguna</a>
            <a href="admin_kategori.php">🗂️ Kelola Kategori</a>
            <a href="admin_wilayah.php">📍 Kelola Wilayah</a>
            <a href="beranda.php" target="_blank" rel="noopener">🌐 Lihat Website</a>
            <a href="logout.php">↪ Keluar</a>
        </aside>

        <section>
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

            <div class="admin-panel">
                <h2>Pengelolaan Website</h2>
                <div class="admin-menu-grid">
                    <div class="admin-menu-card">
                        <h3>🏝️ Data Wisata</h3>
                        <p>Tambah destinasi, ubah harga, foto, lokasi, dan deskripsi.</p>
                        <a class="btn" href="kelola_wisata.php">Kelola Wisata</a>
                    </div>

                    <div class="admin-menu-card">
                        <h3>🛒 Pesanan</h3>
                        <p>Periksa pesanan pelanggan dan kelola status pesanan.</p>
                        <a class="btn" href="admin_pesanan.php">Kelola Pesanan</a>
                    </div>

                    <div class="admin-menu-card">
                        <h3>👥 Pengguna</h3>
                        <p>Lihat akun pelanggan yang sudah terdaftar.</p>
                        <a class="btn" href="admin_pengguna.php">Kelola Pengguna</a>
                    </div>

                    <div class="admin-menu-card">
                        <h3>🗂️ Kategori dan Wilayah</h3>
                        <p>Kelola kategori wisata dan wilayah destinasi.</p>
                        <a class="btn" href="admin_kategori.php">Kelola Kategori</a>
                        <a class="btn btn-secondary" href="admin_wilayah.php">Kelola Wilayah</a>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <h2>Pesanan Terbaru</h2>
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
                            <td colspan="4">Belum ada data pesanan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pesananTerbaru as $p): ?>
                            <tr>
                                <td><?= amanAdmin($p['kode_pesanan']) ?></td>
                                <td><?= amanAdmin($p['tanggal_kunjungan']) ?></td>
                                <td><?= rupiahAdmin($p['total']) ?></td>
                                <td class="status"><?= amanAdmin($p['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p><a href="admin_pesanan.php">Lihat semua pesanan →</a></p>
            </div>
        </section>
    </div>
</main>

<footer>
    <p>JelajahBanten &copy; <?= date('Y') ?></p>
</footer>
</body>
</html>
