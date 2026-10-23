
<?php
require 'config.php';
wajib_login();

$uid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare(
    "SELECT k.jumlah, w.id AS wisata_id, w.nama, w.harga
     FROM keranjang k JOIN wisata w ON w.id=k.wisata_id
     WHERE k.user_id=?"
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

if (!$items) {
    header("Location: keranjang.php");
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += $item['harga'] * $item['jumlah'];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? '';
    $metode = $_POST['metode'] ?? '';
    $metodeValid = ['Transfer Bank', 'E-Wallet', 'Bayar di Lokasi'];

    if (!$tanggal || $tanggal < date('Y-m-d')) {
        $error = 'Pilih tanggal kunjungan yang valid.';
    } elseif (!in_array($metode, $metodeValid, true)) {
        $error = 'Pilih metode pembayaran.';
    } else {
        try {
            $pdo->beginTransaction();

            $kode = 'JN' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));

            $stmt = $pdo->prepare(
                "INSERT INTO pesanan
                (user_id, kode_pesanan, tanggal_kunjungan, total, metode, status)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $uid, $kode, $tanggal, $total, $metode,
                'Menunggu pembayaran'
            ]);

            $pesananId = (int)$pdo->lastInsertId();

            $insert = $pdo->prepare(
                "INSERT INTO detail_pesanan
                (pesanan_id, wisata_id, jumlah, harga)
                VALUES (?, ?, ?, ?)"
            );

            foreach ($items as $item) {
                $insert->execute([
                    $pesananId,
                    $item['wisata_id'],
                    $item['jumlah'],
                    $item['harga']
                ]);
            }

            $stmt = $pdo->prepare("DELETE FROM keranjang WHERE user_id=?");
            $stmt->execute([$uid]);

            $pdo->commit();

            header("Location: pembayaran.php?id=" . $pesananId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Pesanan gagal dibuat. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout | JelajahBanten</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="navbar">
    <a class="brand" href="beranda.php">💐 Jelajah<span>Banten</span></a>
    <nav><a href="beranda.php">Wisata</a><a href="keranjang.php">Keranjang</a></nav>
</header>

<main class="container narrow">
    <h1>Checkout</h1>
    <p>Lengkapi informasi perjalananmu.</p>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="booking">
        <label>Tanggal kunjungan</label>
        <input type="date" name="tanggal"
            min="<?= date('Y-m-d') ?>"
            value="<?= e($_POST['tanggal'] ?? '') ?>" required>

        <label>Metode pembayaran</label>
        <select name="metode" required>
            <option value="">-- Pilih pembayaran --</option>
            <option value="Transfer Bank">Transfer Bank</option>
            <option value="E-Wallet">E-Wallet</option>
            <option value="Bayar di Lokasi">Bayar di Lokasi</option>
        </select>

        <h3>Rincian pesanan</h3>
        <?php foreach ($items as $item): ?>
            <p>
                <?= e($item['nama']) ?> × <?= (int)$item['jumlah'] ?>
                — <?= rupiah($item['harga'] * $item['jumlah']) ?>
            </p>
        <?php endforeach; ?>

        <hr>
        <h2>Total: <?= rupiah($total) ?></h2>
        <p class="muted">Pembayaran di website ini masih berupa simulasi.</p>

        <button class="btn full" type="submit">Buat Pesanan</button>
    </form>
</main>
<footer>© <?= date('Y') ?> JelajahBanten</footer>
</body>
</html>
