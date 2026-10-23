
<?php
require 'config.php';
wajib_login();

$stmt = $pdo->prepare(
    "SELECT * FROM pesanan WHERE user_id=? ORDER BY id DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$pesanan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pesanan Saya | JelajahBanten</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="navbar">
    <a class="brand" href="beranda.php">💐Jelajah<span>Banten</span></a>
    <nav>
        <a href="beranda.php">Beranda</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="logout.php">Keluar</a>
    </nav>
</header>

<main class="container">
    <h1>Riwayat Pesanan</h1>
    <p>Semua pemesanan tiketmu ada di sini.</p>

    <?php if (!$pesanan): ?>
        <div class="empty">
            <h3>Belum ada pesanan.</h3>
            <a class="btn" href="beranda.php">Cari Wisata</a>
        </div>
    <?php else: ?>
        <?php foreach ($pesanan as $p): ?>
            <div class="booking order-row">
                <div>
                    <small>Kode pesanan</small>
                    <h3><?= e($p['kode_pesanan']) ?></h3>
                    <p>Tanggal: <?= e($p['tanggal_kunjungan']) ?></p>
                    <p>Status: <strong><?= e($p['status']) ?></strong></p>
                </div>
                <div>
                    <h3><?= rupiah($p['total']) ?></h3>
                    <a class="btn" href="pembayaran.php?id=<?= (int)$p['id'] ?>">
                        Lihat Detail
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<footer>© <?= date('Y') ?> JelajahBanten</footer>
</body>
</html>
