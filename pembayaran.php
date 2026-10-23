
<?php
require 'config.php';
wajib_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT * FROM pesanan WHERE id=? AND user_id=?"
);
$stmt->execute([$id, $_SESSION['user_id']]);
$p = $stmt->fetch();

if (!$p) {
    exit('Pesanan tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $p['status'] === 'Menunggu pembayaran') {
    $stmt = $pdo->prepare(
        "UPDATE pesanan SET status='Menunggu verifikasi'
         WHERE id=? AND user_id=?"
    );
    $stmt->execute([$id, $_SESSION['user_id']]);

    header("Location: pembayaran.php?id=$id&ok=1");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT d.*, w.nama FROM detail_pesanan d
     JOIN wisata w ON w.id=d.wisata_id
     WHERE d.pesanan_id=?"
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pembayaran | JelajahBanten</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="navbar">
    <a class="brand" href="beranda.php">💐 Jelajah<span>Banten</span></a>
    <nav><a href="beranda.php">Beranda</a><a href="pesanan.php">Pesanan</a></nav>
</header>

<main class="container narrow">
    <div class="booking">
        <h1>Pesanan Berhasil Dibuat!</h1>
        <p>Simpan kode pesanan berikut.</p>

        <?php if (isset($_GET['ok'])): ?>
            <div class="success">Konfirmasi berhasil. Menunggu verifikasi.</div>
        <?php endif; ?>

        <p>Kode pesanan:</p>
        <h2><?= e($p['kode_pesanan']) ?></h2>

        <p>Tanggal kunjungan: <?= e($p['tanggal_kunjungan']) ?></p>
        <p>Metode: <?= e($p['metode']) ?></p>
        <p>Status: <strong><?= e($p['status']) ?></strong></p>

        <hr>

        <?php foreach ($items as $item): ?>
            <p>
                <?= e($item['nama']) ?> × <?= (int)$item['jumlah'] ?>
                — <?= rupiah($item['harga'] * $item['jumlah']) ?>
            </p>
        <?php endforeach; ?>

        <h2>Total: <?= rupiah($p['total']) ?></h2>

        <div class="notice">
            Ini adalah proyek latihan. Belum ada pembayaran uang sungguhan
            yang diproses melalui bank atau e-wallet.
        </div>

        <?php if ($p['status'] === 'Menunggu pembayaran'): ?>
            <form method="post">
                <button class="btn full" type="submit">
                    Konfirmasi Pembayaran (Simulasi)
                </button>
            </form>
        <?php endif; ?>

        <a class="btn secondary full" href="pesanan.php">Riwayat Pesanan</a>
    </div>
</main>
<footer>© <?= date('Y') ?> JelajahBanten</footer>
</body>
</html>
