
<?php
require 'config.php';

wajib_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT * FROM pesanan WHERE id = ? AND user_id = ?"
);
$stmt->execute([$id, $_SESSION['user_id']]);
$pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pesanan) {
    exit('Pesanan tidak ditemukan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($pesanan['metode'] === 'QRIS') {
        $stmt = $pdo->prepare(
            "UPDATE pesanan SET status = 'lunas'
             WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$id, $_SESSION['user_id']]);
    }

    header('Location: pesanan.php');
    exit;
}

$metode = $pesanan['metode'] ?? 'QRIS';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran | JelajahBanten</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
        }

        .kotak {
            max-width: 400px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px #0001;
        }

        h2 {
            color: #166534;
        }

        .harga {
            font-size: 25px;
            font-weight: bold;
            color: #15803d;
        }

        .gambar-qris {
            width: 250px;
            max-width: 100%;
            height: auto;
            margin: 15px auto;
            display: block;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 5px;
            background: white;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 13px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .kembali {
            display: block;
            margin-top: 18px;
            color: #166534;
            text-decoration: none;
        }

        .catatan {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <div class="kotak">
        <h2>Pembayaran</h2>

        <p>Kode Pesanan:</p>
        <strong><?= e($pesanan['kode_pesanan']) ?></strong>

        <p>Total Pembayaran:</p>
        <div class="harga">
            <?= rupiah($pesanan['total']) ?>
        </div>

        <?php if ($metode === 'QRIS'): ?>

            <h3>Scan QRIS</h3>

            <img
                class="gambar-qris"
                src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&amp;data=QRIS-SIMULASI-JELAJAHBANTEN"
                alt="QRIS simulasi"
            >

            <p class="catatan">
                QR ini hanya simulasi untuk tugas sekolah.
                Tidak bisa digunakan untuk pembayaran sungguhan.
            </p>

            <form method="POST">
                <button type="submit">Simulasikan Sudah Bayar</button>
            </form>

        <?php else: ?>

            <h3>Bayar di Tempat</h3>
            <p>Silakan lakukan pembayaran langsung di lokasi wisata.</p>

            <form method="POST">
                <button type="submit">Konfirmasi Pesanan</button>
            </form>

        <?php endif; ?>

        <a class="kembali" href="pesanan.php">
            Kembali ke Pesanan
        </a>
    </div>
</body>
</html>
