
<?php
require 'config.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM wisata WHERE id = ?");
$stmt->execute([$id]);
$w = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$w) {
    http_response_code(404);
    exit('Wisata tidak ditemukan. <a href="beranda.php">Kembali</a>');
}

// Siapkan pencarian foto dan lokasi Google Maps
$kataFoto = rawurlencode(
    $w['nama'] . ' ' . $w['lokasi'] . ' foto wisata'
);

$kataMaps = rawurlencode(
    $w['nama'] . ', ' . $w['lokasi']
);

$urlMaps = 'https://www.google.com/maps/search/?api=1&query=' . $kataMaps;
$urlFoto = 'https://www.google.com/search?tbm=isch&q=' . $kataFoto;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($w['nama']) ?> | JelajahBanten</title>

<link rel="stylesheet" href="assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f7f9f7;
    color: #24352e;
    font-family: Arial, sans-serif;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 18px 6%;
    background: #fff;
    box-shadow: 0 2px 15px #00000008;
}

.brand {
    font-size: 23px;
    font-weight: bold;
    color: #174d3e;
    text-decoration: none;
}

.brand span {
    color: #d69b42;
}

.navbar nav {
    display: flex;
    gap: 22px;
    flex-wrap: wrap;
}

.navbar nav a {
    color: #37483f;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.navbar nav a:hover {
    color: #19805b;
}

.container {
    width: 90%;
    max-width: 1180px;
    margin: 32px auto 65px;
}

.back-link {
    display: inline-block;
    margin-bottom: 24px;
    color: #277357;
    font-weight: bold;
    text-decoration: none;
}

.detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.8fr);
    gap: 30px;
    align-items: start;
}

.detail-content,
.booking {
    background: white;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 8px 30px #183e2d0b;
}

.detail-image {
    display: block;
    width: 100%;
    height: 390px;
    object-fit: cover;
    border-radius: 13px;
    background: #e8eee9;
}

.tag {
    display: inline-block;
    margin-top: 23px;
    padding: 8px 13px;
    border-radius: 30px;
    background: #e6f5eb;
    color: #21704d;
    font-size: 12px;
    font-weight: bold;
}

h1 {
    margin: 15px 0 12px;
    font-size: 32px;
    line-height: 1.25;
}

.location {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #64746b;
    line-height: 1.7;
}

.description {
    color: #526259;
    line-height: 1.9;
    white-space: pre-line;
    margin-top: 20px;
}

.section-title {
    margin: 26px 0 12px;
    font-size: 18px;
}

.map-box {
    margin-top: 25px;
    padding: 19px;
    border: 1px solid #e0ece3;
    background: #f6fbf7;
    border-radius: 13px;
}

.map-box h3 {
    margin: 0 0 8px;
    font-size: 18px;
}

.map-box p {
    color: #66756c;
    font-size: 14px;
    line-height: 1.7;
    margin: 0 0 15px;
}

.btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 13px 17px;
    border: 0;
    border-radius: 9px;
    background: #247451;
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.btn:hover {
    background: #18583c;
    transform: translateY(-1px);
}

.btn-map {
    width: 100%;
    background: #2467ce;
}

.btn-map:hover {
    background: #174ea6;
}

.btn-photo {
    margin-top: 15px;
    background: #fff;
    border: 1px solid #d7e4da;
    color: #246348;
}

.btn-photo:hover {
    background: #f0f8f2;
}

.booking {
    position: sticky;
    top: 22px;
}

.booking-label {
    color: #78847c;
    font-size: 13px;
}

.booking h2 {
    color: #176345;
    font-size: 30px;
    margin: 9px 0 20px;
}

.booking hr {
    border: 0;
    border-top: 1px solid #edf0ed;
    margin: 20px 0;
}

.booking label {
    display: block;
    font-weight: bold;
    margin-bottom: 9px;
    font-size: 14px;
}

.booking input[type="number"] {
    width: 100%;
    padding: 13px;
    border: 1px solid #d8e1da;
    border-radius: 9px;
    margin-bottom: 16px;
    font-size: 15px;
}

.full {
    width: 100%;
}

.booking-note {
    font-size: 12px;
    color: #7a867e;
    line-height: 1.7;
    margin-top: 14px;
}

footer {
    padding: 25px 15px;
    text-align: center;
    color: #718077;
    background: #fff;
    font-size: 13px;
}

@media (max-width: 800px) {
    .detail-layout {
        grid-template-columns: 1fr;
    }

    .booking {
        position: static;
    }

    .detail-image {
        height: 300px;
    }

    h1 {
        font-size: 27px;
    }
}

@media (max-width: 520px) {
    .navbar {
        align-items: flex-start;
        flex-direction: column;
        padding: 17px 5%;
    }

    .navbar nav {
        gap: 15px;
    }

    .container {
        width: 92%;
        margin-top: 20px;
    }

    .detail-content,
    .booking {
        padding: 17px;
        border-radius: 13px;
    }

    .detail-image {
        height: 230px;
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
        <a href="beranda.php">Beranda</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="pesanan.php">Pesanan</a>
    </nav>
</header>

<main class="container">

    <a class="back-link" href="beranda.php">
        ← Kembali ke katalog wisata
    </a>

    <div class="detail-layout">

        <section class="detail-content">

            <img
                class="detail-image"
                src="<?= e($w['gambar']) ?>"
                alt="<?= e($w['nama']) ?>"
                onerror="this.onerror=null;this.src='assets/images/no-image.png';"
            >

            <span class="tag">
                <?= e($w['kategori']) ?>
            </span>

            <h1><?= e($w['nama']) ?></h1>

            <p class="location">
                <span aria-hidden="true">📍</span>
                <span><?= e($w['lokasi']) ?></span>
            </p>

            <h2 class="section-title">Tentang Wisata</h2>

            <p class="description"><?= e($w['deskripsi']) ?></p>

            <div class="map-box">
                <h3>📍 Lokasi Wisata</h3>

                <p>
                    Mau berkunjung ke sini? Buka Google Maps untuk melihat
                    lokasi berdasarkan nama tempat dan alamat wisata ini.
                </p>

                <a
                    class="btn btn-map"
                    href="<?= e($urlMaps) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    🗺️ Lihat Lokasi di Google Maps ↗
                </a>
            </div>

            <a
                class="btn btn-photo"
                href="<?= e($urlFoto) ?>"
                target="_blank"
                rel="noopener noreferrer"
            >
                🔎 Cari Foto Lain di Google
            </a>

        </section>

        <aside class="booking">

            <span class="booking-label">Harga tiket per orang</span>

            <h2><?= rupiah($w['harga']) ?></h2>

            <hr>

            <form method="post" action="keranjang.php">

                <input
                    type="hidden"
                    name="wisata_id"
                    value="<?= (int) $w['id'] ?>"
                >

                <label for="jumlah">Jumlah tiket</label>

                <input
                    id="jumlah"
                    type="number"
                    name="jumlah"
                    value="1"
                    min="1"
                    max="20"
                    required
                >

                <button
                    class="btn full"
                    type="submit"
                    name="aksi"
                    value="tambah"
                >
                    🛒 Tambah ke Keranjang
                </button>

            </form>

            <p class="booking-note">
                ✓ Harga ditampilkan per orang.<br>
                ✓ Pilih jumlah tiket sebelum menambahkan ke keranjang.<br>
                ✓ Pastikan lokasi wisata sudah sesuai sebelum berkunjung.
            </p>

        </aside>

    </div>

</main>

<footer>
    © <?= date('Y') ?> JelajahBanten · Jelajahi keindahan Banten
</footer>

</body>
</html>
