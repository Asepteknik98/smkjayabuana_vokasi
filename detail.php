
<?php
<<<<<<< HEAD
require_once __DIR__ . '/config/database.php';

$conn->set_charset("utf8mb4");

function e($teks) {
    return htmlspecialchars((string)($teks ?? ''), ENT_QUOTES, 'UTF-8');
}

function gambarDetail($foto) {
    $namaFile = basename((string)($foto ?? ''));
    $lokal = __DIR__ . '/images/' . $namaFile;

    if ($namaFile !== '' && is_file($lokal)) {
        return 'images/' . rawurlencode($namaFile);
    }

    return '';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    $wisata = null;
} else {
    $stmt = $conn->prepare("SELECT * FROM wisata WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $hasil = $stmt->get_result();
    $wisata = $hasil->fetch_assoc();
    $stmt->close();

    if (!$wisata) {
        http_response_code(404);
    }
}

$nama = $wisata['nama'] ?? '';
$lokasi = $wisata['lokasi'] ?? '';
$kategori = $wisata['kategori'] ?? '';
$deskripsi = $wisata['deskripsi'] ?? '';
$foto = gambarDetail($wisata['foto'] ?? '');

$aktivitas = trim((string)($wisata['aktivitas'] ?? ''));
$harga = trim((string)($wisata['harga_tiket'] ?? ''));
$jam = trim((string)($wisata['jam_buka'] ?? ''));
$fasilitas = trim((string)($wisata['fasilitas'] ?? ''));

$queryGoogle = urlencode($nama . ' ' . $lokasi);
$linkMaps = 'https://www.google.com/maps/search/?api=1&query=' . $queryGoogle;
$linkCari = 'https://www.google.com/search?q=' . $queryGoogle;

$pesanWhatsApp = "Yuk, kunjungi {$nama} yang berlokasi di {$lokasi}!\n\n"
    . "Kategori: {$kategori}\n"
    . "Informasi wisata ini bisa kamu lihat di website Jelajah Banten.\n\n"
    . "Ayo temukan destinasi wisata menarik lainnya di Banten!";

$linkWhatsApp = 'https://wa.me/?text=' . urlencode($pesanWhatsApp);
=======
require 'config.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM wisata WHERE id = ?");
$stmt->execute([$id]);
$w = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$w) {
    http_response_code(404);
    exit('Wisata tidak ditemukan. <a href="beranda.php">Kembali</a>');
}

// =====================================
// PERBAIKAN FOTO WISATA
// =====================================

$daftarFoto = [
    'pantai anyer' => 'assets/css/images/pantai_anyer.jpg',
    'pantai sawarna' => 'assets/css/images/pantai_sawarna.jpg',
    'taman nasional ujung kulon' => 'assets/css/images/ujung_kulon.jpg',
    'masjid agung banten' => 'assets/css/images/masjid_banten.jpg',
    'keraton surosowan' => 'assets/css/images/keraton_surosowan.jpg',
    'pantai carita' => 'assets/css/images/pantai_carita.jpeg',
    'tanjung lesung' => 'assets/css/images/tanjung_lesung.jpg',
    'desa adat baduy' => 'assets/css/images/baduy_banten.jpg'
];

$namaNormal = strtolower(trim($w['nama']));
$namaNormal = preg_replace('/\s+/', ' ', $namaNormal);

// Ambil gambar awal dari database
$gambarWisata = trim((string)($w['gambar'] ?? ''));

// Khusus ID 9: Masjid Agung Banten
if ($id === 9) {
    $gambarWisata = 'assets/css/images/masjid_banten.jpg';
}
// Untuk wisata yang namanya sesuai daftar
elseif (isset($daftarFoto[$namaNormal])) {
    $gambarWisata = $daftarFoto[$namaNormal];
}

// Jika gambar kosong, gunakan gambar cadangan
if ($gambarWisata === '') {
    $gambarWisata = 'assets/css/images/pantai_anyer.jpg';
}

// Tambahkan versi gambar supaya cache diperbarui
$lokasiFile = __DIR__ . '/' . ltrim($gambarWisata, '/');

if (is_file($lokasiFile)) {
    $gambarWisata .= '?v=' . filemtime($lokasiFile);
}

// =====================================
// GOOGLE MAPS DAN PENCARIAN FOTO
// =====================================

$kataFoto = rawurlencode(
    $w['nama'] . ' ' . $w['lokasi'] . ' foto wisata'
);

$kataMaps = rawurlencode(
    $w['nama'] . ', ' . $w['lokasi']
);

$urlMaps = 'https://www.google.com/maps/search/?api=1&query=' . $kataMaps;
$urlFoto = 'https://www.google.com/search?tbm=isch&q=' . $kataFoto;
>>>>>>> 9b99be052337c1cf2021bc5cc09d0bb19528f0f6
?>

<!DOCTYPE html>
<html lang="id">
<head>
<<<<<<< HEAD
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $wisata ? e($nama) . ' - Jelajah Banten' : 'Wisata Tidak Ditemukan - Jelajah Banten' ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8f4;
            color: #26352b;
            line-height: 1.7;
        }

        header {
            background: #176b3a;
            color: white;
            padding: 18px 6%;
        }

        .logo {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .logo a {
            color: white;
            text-decoration: none;
        }

        .wadah {
            max-width: 1000px;
            width: 92%;
            margin: 30px auto 50px;
        }

        .kartu {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
        }

        .foto-wisata {
            display: block;
            width: 100%;
            height: 420px;
            object-fit: cover;
            background: #e1e9e2;
        }

        .tanpa-foto {
            height: 260px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #e1e9e2;
            color: #607465;
        }

        .isi {
            padding: 28px;
        }

        .kategori {
            display: inline-block;
            padding: 5px 13px;
            background: #e6f4e9;
            color: #176b3a;
            border-radius: 30px;
            font-size: 13px;
            font-weight: bold;
        }

        h1 {
            margin: 12px 0 5px;
            color: #174d2d;
            font-size: 32px;
            line-height: 1.3;
        }

        .lokasi {
            color: #68766c;
            margin-top: 5px;
        }

        .judul-bagian {
            margin: 28px 0 10px;
            color: #176b3a;
            font-size: 20px;
        }

        .deskripsi {
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .grid-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info {
            padding: 17px;
            background: #f5f9f5;
            border: 1px solid #e2ece3;
            border-radius: 12px;
            min-width: 0;
        }

        .info h3 {
            margin: 0 0 7px;
            font-size: 15px;
            color: #176b3a;
        }

        .info p {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .tombol-aksi {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 17px;
            min-height: 46px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .tombol:hover {
            transform: translateY(-2px);
            filter: brightness(0.96);
        }

        .tombol-kembali {
            background: #edf3ee;
            color: #245c37;
            border: 1px solid #d7e5d9;
        }

        .tombol-maps {
            background: #2563eb;
            color: white;
        }

        .tombol-cari {
            background: #e6f4e9;
            color: #176b3a;
            border: 1px solid #cde6d2;
        }

        .tombol-whatsapp {
            background: #25d366;
            color: white;
        }

        .tombol-whatsapp svg {
            width: 21px;
            height: 21px;
            flex-shrink: 0;
        }

        .tidak-ditemukan {
            text-align: center;
            background: white;
            padding: 40px 22px;
            border-radius: 16px;
        }

        .tidak-ditemukan h1 {
            font-size: 26px;
        }

        footer {
            padding: 22px;
            background: #174d2d;
            color: white;
            text-align: center;
        }

        @media (max-width: 650px) {
            header {
                padding: 16px 5%;
            }

            .logo {
                font-size: 21px;
            }

            .wadah {
                margin-top: 20px;
            }

            .foto-wisata {
                height: 250px;
            }

            .isi {
                padding: 20px;
            }

            h1 {
                font-size: 26px;
            }

            .grid-info {
                grid-template-columns: 1fr;
            }

            .tombol-aksi {
                flex-direction: column;
            }

            .tombol {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<header>
    <p class="logo">
        <a href="index.php">🌿 Jelajah Banten</a>
    </p>
</header>

<main class="wadah">

    <?php if (!$wisata): ?>

        <section class="tidak-ditemukan">
            <h1>Wisata Tidak Ditemukan</h1>
            <p>Data wisata tidak tersedia atau mungkin sudah dihapus.</p>
            <a href="index.php" class="tombol tombol-kembali">
                ← Kembali ke Daftar Wisata
            </a>
        </section>

    <?php else: ?>

        <article class="kartu">

            <?php if ($foto !== ''): ?>
                <img
                    src="<?= e($foto) ?>"
                    alt="<?= e($nama) ?>"
                    class="foto-wisata"
                >
            <?php else: ?>
                <div class="tanpa-foto">
                    Foto wisata belum tersedia
                </div>
            <?php endif; ?>

            <div class="isi">

                <span class="kategori"><?= e($kategori ?: 'Wisata') ?></span>

                <h1><?= e($nama) ?></h1>

                <div class="lokasi">
                    📍 <?= e($lokasi ?: 'Lokasi belum tersedia') ?>
                </div>

                <h2 class="judul-bagian">Tentang Wisata</h2>
                <div class="deskripsi"><?= e($deskripsi ?: 'Deskripsi wisata belum tersedia.') ?></div>

                <h2 class="judul-bagian">Informasi Wisata</h2>

                <div class="grid-info">

                    <section class="info">
                        <h3>🎟️ Harga Tiket</h3>
                        <p><?= e($harga !== '' ? $harga : 'Informasi harga tiket belum tersedia.') ?></p>
                    </section>

                    <section class="info">
                        <h3>🕒 Jam Buka</h3>
                        <p><?= e($jam !== '' ? $jam : 'Informasi jam buka belum tersedia.') ?></p>
                    </section>

                    <section class="info">
                        <h3>🏃 Aktivitas</h3>
                        <p><?= nl2br(e($aktivitas !== '' ? $aktivitas : 'Informasi aktivitas belum tersedia.')) ?></p>
                    </section>

                    <section class="info">
                        <h3>🏕️ Fasilitas</h3>
                        <p><?= nl2br(e($fasilitas !== '' ? $fasilitas : 'Informasi fasilitas belum tersedia.')) ?></p>
                    </section>

                </div>

                <div class="tombol-aksi">

                    <a href="index.php" class="tombol tombol-kembali">
                        ← Kembali ke Daftar Wisata
                    </a>

                    <a
                        href="<?= e($linkMaps) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tombol tombol-maps"
                    >
                        📍 Buka Google Maps
                    </a>

                    <a
                        href="<?= e($linkCari) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tombol tombol-cari"
                    >
                        🔎 Cari Penelusuran
                    </a>

                    <a
                        href="<?= e($linkWhatsApp) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="tombol tombol-whatsapp"
                    >
                        <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                            <path d="M16 .8A15.1 15.1 0 0 0 3.1 23.7L1 31l7.5-2A15.2 15.2 0 1 0 16 .8zm0 27.6a12.4 12.4 0 0 1-6.3-1.7l-.5-.3-4.5 1.2 1.2-4.4-.3-.5A12.4 12.4 0 1 1 16 28.4zm6.8-9.3c-.4-.2-2.1-1-2.4-1.1-.3-.1-.6-.2-.8.2s-.9 1.1-1.1 1.3c-.2.2-.4.3-.8.1a10 10 0 0 1-3-1.9 11.2 11.2 0 0 1-2.1-2.6c-.2-.4 0-.6.2-.8l.6-.7.4-.6c.1-.2 0-.5 0-.7l-1.1-2.7c-.3-.7-.6-.6-.8-.6h-.7c-.3 0-.7.1-1 .5s-1.3 1.3-1.3 3.1 1.3 3.5 1.5 3.7c.2.2 2.6 4 6.3 5.5.9.4 1.6.6 2.1.7.9.3 1.7.2 2.3.1.7-.1 2.1-.9 2.4-1.8.3-.9.3-1.7.2-1.8-.1-.2-.3-.3-.7-.5z"/>
                        </svg>
                        Bagikan WhatsApp
                    </a>

                </div>

            </div>
        </article>

    <?php endif; ?>
=======
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= e($w['nama']) ?> | JelajahBanten</title>

<link rel="stylesheet" href="assets/css/style.css?v=8">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #fff7fa;
    color: #493943;
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
    color: #e78eaf;
    text-decoration: none;
}

.brand span {
    color: #c85f89;
}

.navbar nav {
    display: flex;
    gap: 22px;
    flex-wrap: wrap;
}

.navbar nav a {
    color: #493943;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.navbar nav a:hover {
    color: #c85f89;
}

.container {
    width: 90%;
    max-width: 1180px;
    margin: 32px auto 65px;
}

.back-link {
    display: inline-block;
    margin-bottom: 24px;
    color: #c85f89;
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
    box-shadow: 0 8px 30px #a6537b12;
    min-width: 0;
}

.detail-image {
    display: block;
    width: 100%;
    height: 390px;
    object-fit: cover;
    border-radius: 13px;
    background: #fff0f5;
}

.tag {
    display: inline-block;
    margin-top: 23px;
    padding: 8px 13px;
    border-radius: 30px;
    background: #fff0f5;
    color: #c85f89;
    font-size: 12px;
    font-weight: bold;
}

h1 {
    margin: 15px 0 12px;
    font-size: 32px;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.location {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #806b76;
    line-height: 1.7;
}

.description {
    color: #665660;
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
    border: 1px solid #f3d6e2;
    background: #fff7fa;
    border-radius: 13px;
}

.map-box h3 {
    margin: 0 0 8px;
    font-size: 18px;
}

.map-box p {
    color: #806b76;
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
    background: #e78eaf;
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.btn:hover {
    background: #c85f89;
    transform: translateY(-1px);
}

.btn-map {
    width: 100%;
    background: #c85f89;
    text-align: center;
}

.btn-map:hover {
    background: #a84670;
}

.btn-photo {
    margin-top: 15px;
    background: white;
    border: 1px solid #f0cddd;
    color: #c85f89;
}

.btn-photo:hover {
    background: #fff0f5;
}

.booking {
    position: sticky;
    top: 22px;
}

.booking-label {
    color: #907b86;
    font-size: 13px;
}

.booking h2 {
    color: #c85f89;
    font-size: 30px;
    margin: 9px 0 20px;
}

.booking hr {
    border: 0;
    border-top: 1px solid #f2dce6;
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
    border: 1px solid #ead0dc;
    border-radius: 9px;
    margin-bottom: 16px;
    font-size: 15px;
}

.full {
    width: 100%;
}

.booking-note {
    font-size: 12px;
    color: #907b86;
    line-height: 1.7;
    margin-top: 14px;
}

footer {
    padding: 25px 15px;
    text-align: center;
    color: #907b86;
    background: white;
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

    .booking h2 {
        font-size: 26px;
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
                src="<?= e($gambarWisata) ?>"
                alt="<?= e($w['nama']) ?>"
                onerror="this.onerror=null;this.src='assets/css/images/pantai_anyer.jpg';"
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
                    value="<?= (int)$w['id'] ?>"
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
>>>>>>> 9b99be052337c1cf2021bc5cc09d0bb19528f0f6

</main>

<footer>
<<<<<<< HEAD
    <p>© <?= date('Y') ?> Jelajah Banten — Temukan Keindahan Alam Banten 🌿</p>
=======
    © <?= date('Y') ?> JelajahBanten · Jelajahi keindahan Banten
>>>>>>> 9b99be052337c1cf2021bc5cc09d0bb19528f0f6
</footer>

</body>
</html>
