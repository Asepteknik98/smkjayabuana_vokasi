
<?php
require 'config.php';

// Mengambil kata kunci pencarian
$q = trim($_GET['q'] ?? '');

// Mengambil data wisata dari database
if ($q !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM wisata
         WHERE nama LIKE ?
            OR lokasi LIKE ?
            OR kategori LIKE ?
         ORDER BY id ASC"
    );

    $like = "%{$q}%";
    $stmt->execute([$like, $like, $like]);
    $wisata = $stmt->fetchAll();
} else {
    $wisata = $pdo->query(
        "SELECT * FROM wisata ORDER BY id ASC"
    )->fetchAll();
}

// Daftar foto wisata
$daftarFoto = [
    'Pantai Anyer' =>
        'assets/css/images/pantai_anyer.jpg',
    'Pantai Sawarna' =>
        'assets/css/images/pantai_sawarna.jpg',
    'Taman Nasional Ujung Kulon' =>
        'assets/css/images/ujung_kulon.jpg',
    'Masjid Agung Banten' =>
        'assets/css/images/masjid_banten.jpg',
    'Keraton Surosowan' =>
        'assets/css/images/keraton_surosowan.jpg',
    'Pantai Carita' =>
        'assets/css/images/pantai_carita.jpg',
    'Tanjung Lesung' =>
        'assets/css/images/tanjung_lesung.jpg',
    'Desa Adat Baduy' =>
        'assets/css/images/baduy_banten.jpg'
];

// Memeriksa status login
$sudahLogin = !empty($_SESSION['user_id']);
$namaPengguna = $_SESSION['nama']
    ?? $_SESSION['username']
    ?? 'Pengguna';

$rolePengguna = $_SESSION['role'] ?? 'user';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pariwisata Banten</title>

    <link rel="stylesheet" href="assets/css/style.css?v=7">
</head>

<body>

<header class="navbar">

    <a class="brand" href="beranda.php">
        👀 Pariwisata <span>Banten</span>
    </a>

    <nav>
        <span class="user-greeting">
            Halo, <?= e($namaPengguna) ?>!
        </span>

        <a href="beranda.php">Beranda</a>
        <a href="#wisata">Destinasi</a>

        <?php if ($sudahLogin): ?>

            <a href="keranjang.php">Keranjang</a>
            <a href="pesanan.php">Pesanan</a>

            <?php if ($rolePengguna === 'admin'): ?>
                <a href="admin.php">Dashboard Admin</a>
            <?php endif; ?>

            <a href="logout.php" class="nav-cta">Keluar</a>

        <?php else: ?>

            <a href="login.php">Masuk</a>
            <a href="register.php" class="nav-cta">Daftar</a>

        <?php endif; ?>
    </nav>

</header>

<section class="hero">

    <div>
        <?php if ($sudahLogin): ?>
            <span class="eyebrow">
                SELAMAT DATANG, <?= e($namaPengguna) ?>!
            </span>
        <?php else: ?>
            <span class="eyebrow">
                JELAJAHI KEINDAHAN BANTEN
            </span>
        <?php endif; ?>

        <h1>
            Temukan tempat indah untuk cerita yang tak terlupakan.
        </h1>

        <p>
            Cari destinasi favoritmu, pilih tiket, dan rencanakan
            petualanganmu bersama Pariwisata Banten.
        </p>

        <form class="search" method="get" action="beranda.php">

            <input
                type="search"
                name="q"
                placeholder="Cari pantai, kota, atau wisata..."
                value="<?= e($q) ?>"
            >

            <button class="btn" type="submit">
                Cari Wisata
            </button>

        </form>
    </div>

    <div class="hero-picture">
        <img
            src="assets/css/images/pantai_anyer.jpg"
            alt="Pantai Anyer"
            class="hero-image"
        >

        <div class="hero-caption">
            Waktunya Berpetualang
        </div>
    </div>

</section>

<main class="container" id="wisata">

    <div class="section-title">

        <div>
            <span class="eyebrow">PILIHAN UNTUKMU</span>

            <h2>
                <?= $q !== '' ? 'Hasil Pencarian' : 'Destinasi Populer' ?>
            </h2>
        </div>

        <div class="wisata-controls">

            <span class="muted">
                <?= count($wisata) ?> destinasi
            </span>

            <?php if (!empty($wisata)): ?>
                <div class="slide-buttons">

                    <button
                        type="button"
                        onclick="geserWisata(-1)"
                        aria-label="Geser ke kiri"
                        title="Wisata sebelumnya"
                    >
                        &#10094;
                    </button>

                    <button
                        type="button"
                        onclick="geserWisata(1)"
                        aria-label="Geser ke kanan"
                        title="Wisata berikutnya"
                    >
                        &#10095;
                    </button>

                </div>
            <?php endif; ?>

        </div>

    </div>

    <?php if (empty($wisata)): ?>

        <div class="empty">

            <h3>Wisata tidak ditemukan</h3>

            <p>Coba kata kunci yang berbeda.</p>

            <a href="beranda.php" class="btn">
                Lihat Semua Wisata
            </a>

        </div>

    <?php else: ?>

        <div class="grid" id="slideWisata">

            <?php foreach ($wisata as $w): ?>

                <?php
                $namaWisata = trim($w['nama']);

                $foto = $daftarFoto[$namaWisata]
                    ?? ($w['gambar'] ?? '');
                ?>

                <article class="card">

                    <a href="detail.php?id=<?= (int)$w['id'] ?>">

                        <img
                            src="<?= e($foto) ?>"
                            alt="<?= e($namaWisata) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='assets/css/images/pantai_anyer.jpg';"
                        >

                    </a>

                    <div class="card-body">

                        <span class="tag">
                            <?= e($w['kategori']) ?>
                        </span>

                        <p class="location">
                            ⌖ <?= e($w['lokasi']) ?>
                        </p>

                        <h3>
                            <?= e($namaWisata) ?>
                        </h3>

                        <p class="description">
                            <?= e($w['deskripsi']) ?>
                        </p>

                        <div class="card-bottom">

                            <div>
                                <small>Harga tiket</small>

                                <strong>
                                    <?= rupiah($w['harga']) ?>
                                </strong>
                            </div>

                            <a
                                class="btn"
                                href="detail.php?id=<?= (int)$w['id'] ?>"
                            >
                                Detail →
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer>

    <a class="brand" href="beranda.php">
        💐 Pariwisata <span>Banten</span>
    </a>

    <p>
        © <?= date('Y') ?> Pariwisata Banten.
        Jelajahi Keindahan Banten.
    </p>

</footer>

<script>
function geserWisata(arah) {
    const slide = document.getElementById('slideWisata');

    if (!slide) return;

    const kartu = slide.querySelector('.card');

    if (!kartu) return;

    const gaya = window.getComputedStyle(slide);
    const jarakAntarkartu = parseFloat(gaya.columnGap) || 0;
    const jarak = kartu.getBoundingClientRect().width
        + jarakAntarkartu;

    slide.scrollBy({
        left: arah * jarak,
        behavior: 'smooth'
    });
}
</script>

</body>
</html>
