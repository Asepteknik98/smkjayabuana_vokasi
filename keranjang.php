
<?php
require 'config.php';
wajib_login();

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $wid = (int)($_POST['wisata_id'] ?? 0);
        $jumlah = max(1, min(20, (int)($_POST['jumlah'] ?? 1)));

        $stmt = $pdo->prepare("SELECT id FROM wisata WHERE id = ?");
        $stmt->execute([$wid]);

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare(
                "INSERT INTO keranjang (user_id, wisata_id, jumlah)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE jumlah = LEAST(20, jumlah + VALUES(jumlah))"
            );
            $stmt->execute([$uid, $wid, $jumlah]);
        }
    }

    if ($aksi === 'ubah') {
        foreach (($_POST['jumlah'] ?? []) as $id => $jumlah) {
            $id = (int)$id;
            $jumlah = (int)$jumlah;

            if ($jumlah <= 0) {
                $stmt = $pdo->prepare(
                    "DELETE FROM keranjang WHERE id = ? AND user_id = ?"
                );
                $stmt->execute([$id, $uid]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE keranjang SET jumlah = ?
                     WHERE id = ? AND user_id = ?"
                );
                $stmt->execute([min(20, $jumlah), $id, $uid]);
            }
        }
    }

    if ($aksi === 'hapus') {
        $stmt = $pdo->prepare(
            "DELETE FROM keranjang WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([(int)($_POST['id'] ?? 0), $uid]);
    }

    header("Location: keranjang.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT k.id AS kid, k.jumlah, w.*
     FROM keranjang k
     JOIN wisata w ON w.id = k.wisata_id
     WHERE k.user_id = ?"
);
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $item) {
    $total += $item['harga'] * $item['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Keranjang | JelajahNusa</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="navbar">
    <a class="brand" href="beranda.php">💐Jelajah<span>Banten</span></a>
    <nav><a href="beranda.php">Wisata</a><a href="pesanan.php">Pesanan</a><a href="logout.php">Keluar</a></nav>
</header>

<main class="container">
    <h1>Keranjang Tiket</h1>

    <?php if (!$items): ?>
        <div class="empty">
            <h2>Keranjang masih kosong</h2>
            <a class="btn" href="beranda.php">Cari Wisata</a>
        </div>
    <?php else: ?>

    <form method="post">
        <input type="hidden" name="aksi" value="ubah">

        <?php foreach ($items as $item): ?>
        <div class="cart-item">
            <img src="<?= e($item['gambar']) ?>" alt="">
            <div>
                <h3><?= e($item['nama']) ?></h3>
                <p><?= rupiah($item['harga']) ?> / tiket</p>
                <label>Jumlah</label>
                <input class="quantity" type="number" min="0" max="20"
                    name="jumlah[<?= (int)$item['kid'] ?>]"
                    value="<?= (int)$item['jumlah'] ?>">
            </div>
            <div>
                <strong><?= rupiah($item['harga'] * $item['jumlah']) ?></strong>
                <form></form>
            </div>
        </div>
        <?php endforeach; ?>

        <button class="btn secondary" type="submit">Perbarui Jumlah</button>
    </form>

    <?php foreach ($items as $item): ?>
    <form method="post" class="remove-form">
        <input type="hidden" name="aksi" value="hapus">
        <input type="hidden" name="id" value="<?= (int)$item['kid'] ?>">
        <button class="remove-button" type="submit">Hapus <?= e($item['nama']) ?></button>
    </form>
    <?php endforeach; ?>

    <div class="booking">
        <h3>Total Pembayaran</h3>
        <h2><?= rupiah($total) ?></h2>
        <a class="btn full" href="checkout.php">Lanjut Checkout →</a>
    </div>

    <?php endif; ?>
</main>
<footer>© <?= date('Y') ?> JelajahBanten</footer>
</body>
</html>
