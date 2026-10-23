
<?php
require 'config.php';
wajib_login();

$stmt = $pdo->prepare(
    "SELECT * FROM pesanan
     WHERE user_id = ?
     ORDER BY id DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat Pesanan | JelajahBanten</title>
<link rel="stylesheet" href="assets/css/style.css">

<style>
body {
    margin: 0;
}

.riwayat-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 18px;
}

.judul-riwayat {
    margin-bottom: 8px;
}

.deskripsi {
    color: #777;
    margin-bottom: 20px;
}

.tombol-riwayat {
    width: 100%;
    padding: 16px;
    border: 1px solid #ddd;
    border-radius: 12px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
    text-align: left;
}

.tombol-kiri {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ikon-riwayat {
    background: #e8f7ee;
    padding: 10px;
    border-radius: 10px;
    font-size: 22px;
}

.panah {
    font-size: 20px;
    color: #198754;
}

.daftar-riwayat {
    display: none;
    margin-top: 12px;
    gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.daftar-riwayat.buka {
    display: grid;
}

.kartu-pesanan {
    padding: 18px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    min-width: 0;
    overflow-wrap: anywhere;
}

.kartu-pesanan h3 {
    margin: 0 0 14px;
    font-size: 15px;
}

.kartu-pesanan p {
    margin: 8px 0;
    font-size: 13px;
    line-height: 1.5;
}

.status {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 6px;
    background: #fef3c7;
    color: #92400e;
    font-size: 11px;
}

.btn-detail {
    display: block;
    margin-top: 14px;
    padding: 10px;
    text-align: center;
    text-decoration: none;
    color: white;
    background: #198754;
    border-radius: 8px;
    font-size: 13px;
}

.pesan-kosong {
    background: white;
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 16px;
}

@media (max-width: 600px) {
    .riwayat-container {
        margin: 20px auto;
        padding: 0 12px;
    }

    .judul-riwayat {
        font-size: 23px;
    }

    .tombol-riwayat {
        padding: 13px;
    }

    .daftar-riwayat {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .kartu-pesanan {
        padding: 14px;
    }
}
</style>
</head>

<body>

<header class="navbar">
    <a class="brand" href="beranda.php">
        💐 Jelajah<span>Banten</span>
    </a>
    <nav>
        <a href="beranda.php">Wisata</a>
        <a href="keranjang.php">Keranjang</a>
    </nav>
</header>

<main class="riwayat-container">

    <h1 class="judul-riwayat">Riwayat Pesanan</h1>
    <p class="deskripsi">Kelola dan lihat pesanan wisata kamu.</p>

    <button
        type="button"
        class="tombol-riwayat"
        id="tombolRiwayat"
        aria-expanded="false"
        aria-controls="daftarRiwayat"
    >
        <span class="tombol-kiri">
            <span class="ikon-riwayat">🎫</span>
            <span>
                Lihat Riwayat Pesanan
                <br>
                <small style="color:#777;font-weight:normal">
                    <?= count($pesanan) ?> pesanan
                </small>
            </span>
        </span>

        <span class="panah" id="panahRiwayat">⌄</span>
    </button>

    <div class="daftar-riwayat" id="daftarRiwayat">

        <?php if (!$pesanan): ?>
            <div class="pesan-kosong">
                Belum ada riwayat pesanan.
            </div>
        <?php else: ?>

            <?php foreach ($pesanan as $p): ?>
                <div class="kartu-pesanan">

                    <h3><?= e($p['kode_pesanan']) ?></h3>

                    <p>
                        <strong>Tanggal kunjungan</strong><br>
                        <?= e($p['tanggal_kunjungan']) ?>
                    </p>

                    <p>
                        <strong>Total pembayaran</strong><br>
                        <?= rupiah($p['total']) ?>
                    </p>

                    <p>
                        <span class="status">
                            <?= e($p['status']) ?>
                        </span>
                    </p>

                    <a
                        class="btn-detail"
                        href="pembayaran.php?id=<?= (int)$p['id'] ?>"
                    >
                        Lihat Detail
                    </a>

                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

<footer>© <?= date('Y') ?> JelajahBanten</footer>

<script>
const tombol = document.getElementById('tombolRiwayat');
const daftar = document.getElementById('daftarRiwayat');
const panah = document.getElementById('panahRiwayat');

tombol.addEventListener('click', function () {
    const terbuka = daftar.classList.toggle('buka');

    tombol.setAttribute('aria-expanded', terbuka);
    panah.textContent = terbuka ? '⌃' : '⌄';
});
</script>

</body>
</html>
