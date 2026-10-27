
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================
   STATUS LOGIN
========================================= */
$sudahLogin = !empty($_SESSION['user_id']);
$userId = (int)($_SESSION['user_id'] ?? 0);
$namaPengguna = $_SESSION['nama']
    ?? $_SESSION['username']
    ?? 'Pengguna';
$rolePengguna = $_SESSION['role'] ?? 'user';

if (empty($_SESSION['csrf_fitur'])) {
    $_SESSION['csrf_fitur'] = bin2hex(random_bytes(32));
}

/* =========================================
   PENCARIAN, KATEGORI, DAN URUTAN
========================================= */
$q = trim((string)($_GET['q'] ?? ''));
$kategoriDipilih = trim((string)($_GET['kategori'] ?? ''));
$urut = trim((string)($_GET['urut'] ?? ''));

$daftarKategori = $pdo->query(
    "SELECT nama FROM kategori ORDER BY nama ASC"
)->fetchAll(PDO::FETCH_COLUMN);

$kategoriWisata = $pdo->query(
    "SELECT DISTINCT kategori FROM wisata
     WHERE kategori IS NOT NULL AND kategori <> ''
     ORDER BY kategori ASC"
)->fetchAll(PDO::FETCH_COLUMN);

$daftarKategori = array_values(
    array_unique(
        array_merge($daftarKategori, $kategoriWisata)
    )
);

sort($daftarKategori, SORT_NATURAL | SORT_FLAG_CASE);

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(nama LIKE ? OR lokasi LIKE ? OR kategori LIKE ?)";

    $like = "%{$q}%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($kategoriDipilih !== '') {
    $where[] = "kategori = ?";
    $params[] = $kategoriDipilih;
}

$pilihanUrutan = [
    'termurah' => 'harga ASC, id ASC',
    'termahal' => 'harga DESC, id ASC',
    'nama_az' => 'nama ASC, id ASC',
    'nama_za' => 'nama DESC, id ASC'
];

$orderBy = $pilihanUrutan[$urut] ?? 'id ASC';

$sql = "SELECT * FROM wisata";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY " . $orderBy;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$wisata = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   DAFTAR FOTO WISATA
========================================= */
$daftarFoto = [
    'pantai anyer' =>
        'assets/css/images/pantai_anyer.jpg',

    'pantai sawarna' =>
        'assets/css/images/pantai_sawarna.jpg',

    'taman nasional ujung kulon' =>
        'assets/css/images/ujung_kulon.jpg',

    'masjid agung banten' =>
        'assets/css/images/masjid_agung_banten.jpg',

    'keraton surosowan' =>
        'assets/css/images/keraton_surosowan.jpg',

    'pantai carita' =>
        'assets/css/images/pantai_carita.jpg',

    'tanjung lesung' =>
        'assets/css/images/tanjung_lesung.jpg',

    'desa adat baduy' =>
        'assets/css/images/baduy_banten.jpg'
];

function fotoWisataBeranda($nama, $gambarDatabase, $daftarFoto)
{
    $namaNormal = strtolower(trim((string)$nama));
    $namaNormal = preg_replace('/\s+/', ' ', $namaNormal);

    if (isset($daftarFoto[$namaNormal])) {
        $foto = $daftarFoto[$namaNormal];

        if (is_file(__DIR__ . '/' . $foto)) {
            return $foto;
        }
    }

    $gambarDatabase = trim((string)$gambarDatabase);

    if ($gambarDatabase !== '') {
        return $gambarDatabase;
    }

    return 'assets/css/images/pantai_anyer.jpg';
}

function urlFotoBeranda($foto)
{
    $foto = (string)$foto;

    if (preg_match('~^https?://~i', $foto)) {
        return $foto;
    }

    $lokasiFile = __DIR__ . '/' . ltrim($foto, '/');

    if (is_file($lokasiFile)) {
        return $foto . '?v=' . filemtime($lokasiFile);
    }

    return $foto;
}

/* =========================================
   DATA FAVORIT
========================================= */
$idFavorit = [];

if ($sudahLogin) {
    try {
        $stmtFavorit = $pdo->prepare(
            "SELECT wisata_id
             FROM favorit
             WHERE user_id = ?"
        );

        $stmtFavorit->execute([$userId]);

        $idFavorit = array_map(
            'intval',
            $stmtFavorit->fetchAll(PDO::FETCH_COLUMN)
        );

    } catch (PDOException $e) {
        $idFavorit = [];
    }
}

/* =========================================
   WISATA PALING POPULER
========================================= */
$wisataPopuler = [];

if ($q === '' && $kategoriDipilih === '') {
    try {
        $stmtPopuler = $pdo->query(
            "SELECT
                w.*,
                SUM(dp.jumlah) AS total_dipesan
             FROM wisata w
             INNER JOIN detail_pesanan dp
                ON dp.wisata_id = w.id
             INNER JOIN pesanan p
                ON p.id = dp.pesanan_id
             WHERE p.status = 'Lunas'
             GROUP BY w.id
             ORDER BY total_dipesan DESC, w.nama ASC
             LIMIT 5"
        );

        $wisataPopuler = $stmtPopuler->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $wisataPopuler = [];
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Pariwisata Banten</title>

    <!-- CSS ASLI WEBSITE TETAP DIGUNAKAN -->
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=8"
    >

    <style>
        /* =====================================
           NAVBAR BUBBLE ASLI
        ===================================== */

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            padding: 16px 5%;
        }

        .navbar .brand {
            flex-shrink: 0;
            text-decoration: none;
        }

        .navbar nav {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
        }

        .navbar nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background: #fff0f5;
            color: #b7507b;
            border: 1px solid #f2c7d9;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .navbar nav a:hover {
            background: #e78eaf;
            border-color: #e78eaf;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .navbar .user-greeting {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background: #fce0eb;
            color: #a84670;
            border: 1px solid #f2c7d9;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
            white-space: nowrap;
        }

        .navbar nav a.nav-cta {
            background: #c85f89;
            border-color: #c85f89;
            color: white;
        }

        .navbar nav a.nav-cta:hover {
            background: #a84670;
            border-color: #a84670;
        }

        /* =====================================
           FILTER MENYATU DENGAN HERO
           TIDAK MENGUBAH UKURAN FOTO
        ===================================== */

        .hero-filter {
            margin-top: 20px;
            max-width: 100%;
        }

        .hero-filter-search {
            margin-bottom: 10px;
        }

        .hero-filter-search input {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 12px 15px;
            background: white;
            border: 1px solid #f2c7d9;
            border-radius: 12px;
            color: #59434f;
            font-size: 13px;
            outline: none;
        }

        .hero-filter-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .hero-filter-field {
            min-width: 0;
        }

        .hero-filter-field label {
            display: block;
            margin-bottom: 6px;
            color: #a84670;
            font-size: 12px;
            font-weight: 600;
        }

        .hero-filter-field select {
            display: block;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 11px 12px;
            background: white;
            border: 1px solid #f2c7d9;
            border-radius: 12px;
            color: #59434f;
            font-size: 12px;
            outline: none;
        }

        .hero-filter-search input:focus,
        .hero-filter-field select:focus {
            border-color: #c85f89;
        }

        .hero-filter-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .hero-filter-actions .btn {
            cursor: pointer;
        }

        .hero-filter-reset {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background: #fff0f5;
            border: 1px solid #f2c7d9;
            border-radius: 12px;
            color: #b7507b;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .hero-filter-reset:hover {
            background: #fce0eb;
        }

        /* =====================================
           KARTU WISATA
        ===================================== */

        .card img {
            width: 100%;
            object-fit: cover;
        }

        .favorit-form {
            margin: 0 0 10px;
        }

        .favorit-btn {
            padding: 8px 12px;
            background: #fff0f5;
            color: #c85f89;
            border: 1px solid #f2c7d9;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .favorit-btn:hover {
            background: #fce0eb;
        }

        .favorit-btn.tersimpan {
            background: #c85f89;
            color: white;
        }

        /* =====================================
           WISATA PALING POPULER
        ===================================== */

        .populer-section {
            width: calc(100% - 32px);
            max-width: 1150px;
            margin: 35px auto 45px;
            min-width: 0;
        }

        .populer-heading {
            margin-bottom: 20px;
        }

        .populer-heading h2 {
            margin: 7px 0;
            color: #c85f89;
            font-size: 25px;
        }

        .populer-heading p {
            color: #927b87;
            font-size: 13px;
        }

        .populer-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
        }

        .populer-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #f2d5e1;
            border-radius: 14px;
            background: white;
            box-shadow: 0 5px 15px rgba(160, 91, 120, .09);
        }

        .populer-foto {
            position: relative;
        }

        .populer-foto img {
            display: block;
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .populer-ranking {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 11px;
            border-radius: 20px;
            background: #e78eaf;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }

        .populer-body {
            padding: 16px;
        }

        .populer-body h3 {
            margin: 10px 0 7px;
            color: #59434f;
            font-size: 17px;
            overflow-wrap: anywhere;
        }

        .populer-lokasi,
        .populer-jumlah {
            color: #927b87;
            font-size: 12px;
        }

        .populer-jumlah {
            color: #c85f89;
            font-weight: bold;
        }

        .populer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .populer-bottom strong {
            color: #c85f89;
        }

        /* =====================================
           RESPONSIF
        ===================================== */

        @media (max-width: 900px) {
            .navbar {
                justify-content: center;
            }

            .navbar nav {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 800px) {
            .populer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 12px;
                gap: 12px;
            }

            .navbar nav {
                gap: 8px;
            }

            .navbar nav a,
            .navbar .user-greeting {
                padding: 8px 12px;
                font-size: 11px;
            }

            .hero-filter-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {
            .populer-section {
                width: calc(100% - 20px);
                margin: 25px auto;
            }

            .populer-heading h2 {
                font-size: 20px;
            }

            .populer-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .populer-foto img {
                height: 170px;
            }

            .populer-body {
                padding: 13px;
            }
        }
    </style>
</head>

<body>

<!-- =====================================
     NAVBAR
===================================== -->

<header class="navbar">

    <a class="brand" href="beranda.php">
        👀 Pariwisata <span>Banten</span>
    </a>

    <nav>
        <span class="user-greeting">
            Halo, <?= e($namaPengguna) ?>!
        </span>

        <a href="beranda.php">Beranda</a>

        <a href="beranda.php#wisata">
            Destinasi
        </a>

        <a href="faq.php">FAQ</a>

        <?php if ($sudahLogin): ?>

            <a href="keranjang.php">
                Keranjang
            </a>

            <a href="favorit.php">
                ❤️ Favorit
            </a>

            <a href="pesanan.php">
                Pesanan
            </a>

            <?php if ($rolePengguna === 'admin'): ?>

                <a href="admin.php">
                    Dashboard Admin
                </a>

            <?php endif; ?>

            <a href="logout.php" class="nav-cta">
                Keluar
            </a>

        <?php else: ?>

            <a href="login.php">
                Masuk
            </a>

            <a href="register.php" class="nav-cta">
                Daftar
            </a>

        <?php endif; ?>
    </nav>

</header>

<!-- =====================================
     HERO ASLI + FILTER DIGABUNG
===================================== -->

<section class="hero">

    <div>

        <?php if ($sudahLogin): ?>

            <span class="eyebrow">
                SELAMAT DATANG,
                <?= e($namaPengguna) ?>!
            </span>

        <?php else: ?>

            <span class="eyebrow">
                JELAJAHI KEINDAHAN BANTEN
            </span>

        <?php endif; ?>

        <h1>
            Temukan tempat indah untuk cerita
            yang tak terlupakan.
        </h1>

        <p>
            Cari destinasi favoritmu, pilih tiket,
            dan rencanakan petualanganmu bersama
            Pariwisata Banten.
        </p>

        <!-- SATU-SATUNYA FORM PENCARIAN -->

        <form
            class="hero-filter"
            method="get"
            action="beranda.php#wisata"
        >

            <div class="hero-filter-search">

                <input
                    type="search"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="🔍 Cari pantai, kota, atau wisata..."
                    aria-label="Cari Wisata"
                >

            </div>

            <div class="hero-filter-row">

                <div class="hero-filter-field">

                    <label for="kategori-wisata">
                        🌿 Kategori
                    </label>

                    <select
                        id="kategori-wisata"
                        name="kategori"
                    >

                        <option value="">
                            Semua Kategori
                        </option>

                        <?php foreach ($daftarKategori as $kategori): ?>

                            <option
                                value="<?= e($kategori) ?>"
                                <?= $kategoriDipilih === $kategori
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($kategori) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="hero-filter-field">

                    <label for="urut-wisata">
                        💰 Urutkan
                    </label>

                    <select
                        id="urut-wisata"
                        name="urut"
                    >

                        <option value="">
                            Urutan Default
                        </option>

                        <option
                            value="termurah"
                            <?= $urut === 'termurah'
                                ? 'selected'
                                : '' ?>
                        >
                            Harga Termurah
                        </option>

                        <option
                            value="termahal"
                            <?= $urut === 'termahal'
                                ? 'selected'
                                : '' ?>
                        >
                            Harga Termahal
                        </option>

                        <option
                            value="nama_az"
                            <?= $urut === 'nama_az'
                                ? 'selected'
                                : '' ?>
                        >
                            Nama A–Z
                        </option>

                        <option
                            value="nama_za"
                            <?= $urut === 'nama_za'
                                ? 'selected'
                                : '' ?>
                        >
                            Nama Z–A
                        </option>

                    </select>

                </div>

            </div>

            <div class="hero-filter-actions">

                <button
                    type="submit"
                    class="btn"
                >
                    🔍 Cari Wisata
                </button>

                <a
                    href="beranda.php#wisata"
                    class="hero-filter-reset"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>

    <!-- FOTO ASLI: TIDAK DIUBAH UKURANNYA -->

    <div class="hero-picture">

        <img
            src="<?= e(urlFotoBeranda('assets/css/images/pantai_anyer.jpg')) ?>"
            alt="Pantai Anyer"
            class="hero-image"
        >

        <div class="hero-caption">
            🌸 Waktunya Berpetualang
        </div>

    </div>

</section>

<!-- =====================================
     DESTINASI
     TIDAK ADA FILTER KEDUA
===================================== -->

<main class="container" id="wisata">

    <div class="section-title">

        <div>

            <span class="eyebrow">
                PILIHAN UNTUKMU
            </span>

            <h2>
                <?= ($q !== '' || $kategoriDipilih !== '')
                    ? 'Hasil Pencarian Wisata'
                    : 'Destinasi Populer' ?>
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
                    >
                        &#10094;
                    </button>

                    <button
                        type="button"
                        onclick="geserWisata(1)"
                        aria-label="Geser ke kanan"
                    >
                        &#10095;
                    </button>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <?php if (empty($wisata)): ?>

        <div class="empty">

            <h3>
                Wisata tidak ditemukan
            </h3>

            <p>
                Coba kata kunci atau kategori yang berbeda.
            </p>

            <a href="beranda.php" class="btn">
                Lihat Semua Wisata
            </a>

        </div>

    <?php else: ?>

        <div class="grid" id="slideWisata">

            <?php foreach ($wisata as $w): ?>

                <?php
                $namaWisata = trim((string)$w['nama']);

                $foto = fotoWisataBeranda(
                    $namaWisata,
                    $w['gambar'] ?? '',
                    $daftarFoto
                );

                $sudahFavorit = in_array(
                    (int)$w['id'],
                    $idFavorit,
                    true
                );
                ?>

                <article class="card">

                    <a href="detail.php?id=<?= (int)$w['id'] ?>">

                        <img
                            src="<?= e(urlFotoBeranda($foto)) ?>"
                            alt="<?= e($namaWisata) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='assets/css/images/pantai_anyer.jpg';"
                        >

                    </a>

                    <div class="card-body">

                        <?php if ($sudahLogin): ?>

                            <form
                                action="favorit.php"
                                method="post"
                                class="favorit-form"
                            >

                                <input
                                    type="hidden"
                                    name="wisata_id"
                                    value="<?= (int)$w['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= e($_SESSION['csrf_fitur']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="aksi"
                                    value="<?= $sudahFavorit
                                        ? 'hapus'
                                        : 'simpan' ?>"
                                >

                                <button
                                    type="submit"
                                    class="favorit-btn <?= $sudahFavorit
                                        ? 'tersimpan'
                                        : '' ?>"
                                >
                                    <?= $sudahFavorit
                                        ? '❤️ Sudah Favorit'
                                        : '♡ Simpan Favorit' ?>
                                </button>

                            </form>

                        <?php endif; ?>

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
                            <?= e($w['deskripsi'] ?? '') ?>
                        </p>

                        <div class="card-bottom">

                            <div>

                                <small>
                                    Harga tiket
                                </small>

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

<!-- =====================================
     WISATA PALING POPULER
===================================== -->

<?php if (!empty($wisataPopuler)): ?>

<section class="populer-section">

    <div class="populer-heading">

        <span class="eyebrow">
            FAVORIT PENGUNJUNG
        </span>

        <h2>
            ⭐ Wisata Paling Populer
        </h2>

        <p>
            Destinasi yang paling banyak dipesan
            berdasarkan tiket dengan pembayaran lunas.
        </p>

    </div>

    <div class="populer-grid">

        <?php foreach ($wisataPopuler as $nomor => $w): ?>

            <?php
            $namaPopuler = trim((string)$w['nama']);

            $fotoPopuler = fotoWisataBeranda(
                $namaPopuler,
                $w['gambar'] ?? '',
                $daftarFoto
            );
            ?>

            <article class="populer-card">

                <div class="populer-foto">

                    <a href="detail.php?id=<?= (int)$w['id'] ?>">

                        <img
                            src="<?= e(urlFotoBeranda($fotoPopuler)) ?>"
                            alt="<?= e($namaPopuler) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='assets/css/images/pantai_anyer.jpg';"
                        >

                    </a>

                    <span class="populer-ranking">
                        #<?= $nomor + 1 ?> Populer
                    </span>

                </div>

                <div class="populer-body">

                    <span class="tag">
                        <?= e($w['kategori']) ?>
                    </span>

                    <h3>
                        <?= e($namaPopuler) ?>
                    </h3>

                    <p class="populer-lokasi">
                        📍 <?= e($w['lokasi']) ?>
                    </p>

                    <p class="populer-jumlah">
                        🎟️ <?= (int)$w['total_dipesan'] ?>
                        tiket dipesan
                    </p>

                    <div class="populer-bottom">

                        <strong>
                            <?= rupiah($w['harga']) ?>
                        </strong>

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

</section>

<?php endif; ?>

<!-- =====================================
     FOOTER
===================================== -->

<footer>

    <a class="brand" href="beranda.php">
        💐 Pariwisata <span>Banten</span>
    </a>

    <p>
        © <?= date('Y') ?> Pariwisata Banten.
        Jelajahi Keindahan Banten.
    </p>

</footer>

<!-- =====================================
     JAVASCRIPT SLIDER
===================================== -->

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

<!-- WHATSAPP -->
<?php require_once __DIR__ . '/whatsapp.php'; ?>

</body>
</html>
