
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya pengguna login yang boleh mengakses favorit
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$namaPengguna = $_SESSION['nama']
    ?? $_SESSION['username']
    ?? 'Pengguna';

$rolePengguna = $_SESSION['role'] ?? 'user';

// Token keamanan, sama dengan beranda.php
if (empty($_SESSION['csrf_fitur'])) {
    $_SESSION['csrf_fitur'] = bin2hex(random_bytes(32));
}

$pesanError = '';

// ========================================
// PROSES SIMPAN / HAPUS FAVORIT
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = (string)($_POST['csrf'] ?? '');

    if (!hash_equals($_SESSION['csrf_fitur'], $token)) {
        http_response_code(403);
        exit('Token keamanan tidak valid. Muat ulang halaman.');
    }

    $wisataId = (int)($_POST['wisata_id'] ?? 0);
    $aksi = (string)($_POST['aksi'] ?? 'simpan');

    if ($wisataId > 0) {

        try {
            if ($aksi === 'hapus') {

                // Hapus favorit milik pengguna yang login
                $stmt = $pdo->prepare(
                    "DELETE FROM favorit
                     WHERE user_id = ? AND wisata_id = ?"
                );

                $stmt->execute([$userId, $wisataId]);

            } else {

                // Simpan hanya jika destinasi memang ada
                $stmt = $pdo->prepare(
                    "INSERT IGNORE INTO favorit (user_id, wisata_id)
                     SELECT ?, id FROM wisata WHERE id = ?"
                );

                $stmt->execute([$userId, $wisataId]);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            exit('Gagal memproses favorit. Periksa tabel favorit.');
        }
    }

    header('Location: favorit.php');
    exit;
}

// ========================================
// AMBIL DAFTAR FAVORIT
// ========================================
$daftarFavorit = [];

try {
    $stmt = $pdo->prepare(
        "SELECT w.*
         FROM favorit f
         INNER JOIN wisata w ON w.id = f.wisata_id
         WHERE f.user_id = ?
         ORDER BY f.id DESC"
    );

    $stmt->execute([$userId]);

    $daftarFavorit = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pesanError = 'Tabel favorit belum tersedia. Jalankan SQL terlebih dahulu.';
}

// ========================================
// FOTO DESTINASI
// ========================================
function gambarFavorit($wisata)
{
    $nama = strtolower(trim((string)($wisata['nama'] ?? '')));

    $daftarFoto = [
        'pantai anyer' => 'assets/css/images/pantai_anyer.jpg',
        'pantai sawarna' => 'assets/css/images/pantai_sawarna.jpg',
        'taman nasional ujung kulon' => 'assets/css/images/ujung_kulon.jpg',
        'masjid agung banten' => 'assets/css/images/masjid_agung_banten.jpg',
        'keraton surosowan' => 'assets/css/images/keraton_surosowan.jpg',
        'pantai carita' => 'assets/css/images/pantai_carita.jpg',
        'tanjung lesung' => 'assets/css/images/tanjung_lesung.jpg',
        'desa adat baduy' => 'assets/css/images/baduy_banten.jpg'
    ];

    if (isset($daftarFoto[$nama])) {
        $foto = $daftarFoto[$nama];

        if (is_file(__DIR__ . '/' . $foto)) {
            return $foto;
        }
    }

    $gambarDatabase = trim((string)($wisata['gambar'] ?? ''));

    if ($gambarDatabase !== '') {
        return $gambarDatabase;
    }

    return 'assets/css/images/pantai_anyer.jpg';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Wisata Favorit | Pariwisata Banten</title>

    <link rel="stylesheet" href="assets/css/style.css?v=10">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff7fa;
            color: #59434f;
            font-family: Arial, sans-serif;
        }

        /* NAVBAR */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            padding: 16px 5%;
            background: white;
            border-bottom: 1px solid #f4d3e0;
        }

        .navbar .brand {
            text-decoration: none;
            font-weight: bold;
            font-size: 19px;
            color: #c85f89;
        }

        .navbar nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .navbar nav a,
        .user-greeting {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            background: #fff0f5;
            color: #a84670;
            border: 1px solid #f2c7d9;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .navbar nav a:hover {
            background: #e78eaf;
            color: white;
        }

        .navbar nav a.active {
            background: #c85f89;
            color: white;
        }

        .user-greeting {
            background: #fce0eb;
        }

        /* HALAMAN FAVORIT */
        .favorit-container {
            width: calc(100% - 32px);
            max-width: 1150px;
            margin: 35px auto 60px;
        }

        .favorit-heading {
            margin-bottom: 25px;
        }

        .favorit-heading h1 {
            margin: 0 0 10px;
            color: #c85f89;
            font-size: 29px;
        }

        .favorit-heading p {
            color: #927b87;
            font-size: 14px;
        }

        .jumlah-favorit {
            display: inline-block;
            margin-top: 8px;
            padding: 8px 14px;
            background: #fce0eb;
            border-radius: 20px;
            color: #a84670;
            font-size: 12px;
            font-weight: bold;
        }

        /* GRID */
        .favorit-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .favorit-card {
            min-width: 0;
            overflow: hidden;
            background: white;
            border: 1px solid #f2d5e1;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(160,91,120,.08);
        }

        .favorit-card img {
            display: block;
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .favorit-body {
            padding: 18px;
        }

        .kategori-tag {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #fff0f5;
            color: #c85f89;
            font-size: 11px;
            font-weight: bold;
        }

        .favorit-body h3 {
            margin: 13px 0 8px;
            font-size: 18px;
            color: #59434f;
        }

        .favorit-lokasi {
            color: #927b87;
            font-size: 13px;
        }

        .favorit-harga {
            display: block;
            margin: 14px 0;
            color: #c85f89;
            font-size: 18px;
        }

        .favorit-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .favorit-actions form {
            margin: 0;
        }

        .btn-detail,
        .btn-hapus {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-detail {
            background: #c85f89;
            color: white;
            border: 1px solid #c85f89;
        }

        .btn-hapus {
            background: white;
            color: #c85f89;
            border: 1px solid #efbdd2;
        }

        .btn-detail:hover {
            background: #a84670;
        }

        .btn-hapus:hover {
            background: #fff0f5;
        }

        /* KONDISI KOSONG */
        .favorit-kosong {
            padding: 55px 20px;
            text-align: center;
            background: white;
            border: 1px solid #f2d5e1;
            border-radius: 20px;
        }

        .favorit-kosong .ikon {
            font-size: 48px;
        }

        .favorit-kosong h2 {
            color: #c85f89;
        }

        .favorit-kosong p {
            color: #927b87;
            margin-bottom: 25px;
        }

        .pesan-error {
            padding: 15px;
            background: #fff0f5;
            border-radius: 12px;
            color: #a84670;
            margin-bottom: 20px;
        }

        /* RESPONSIF */
        @media (max-width: 900px) {
            .navbar {
                justify-content: center;
            }

            .navbar nav {
                justify-content: center;
            }

            .favorit-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 12px;
            }

            .navbar nav a,
            .user-greeting {
                padding: 8px 11px;
                font-size: 11px;
            }

            .favorit-container {
                width: calc(100% - 20px);
                margin-top: 25px;
            }

            .favorit-heading h1 {
                font-size: 23px;
            }

            .favorit-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .favorit-card img {
                height: 190px;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">

    <a class="brand" href="beranda.php">
        💐 Pariwisata Banten
    </a>

    <nav>
        <span class="user-greeting">
            Halo, <?= e($namaPengguna) ?>!
        </span>

        <a href="beranda.php">Beranda</a>
        <a href="beranda.php#wisata">Destinasi</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="favorit.php" class="active">❤️ Favorit</a>
        <a href="pesanan.php">Pesanan</a>

        <?php if ($rolePengguna === 'admin'): ?>
            <a href="admin.php">Dashboard Admin</a>
        <?php endif; ?>

        <a href="logout.php">Keluar</a>
    </nav>

</header>

<!-- KONTEN -->
<main class="favorit-container">

    <div class="favorit-heading">
        <h1>❤️ Wisata Favoritku</h1>

        <p>
            Semua destinasi yang kamu sukai tersimpan di sini.
            Kamu bisa melihat detailnya kapan saja.
        </p>

        <span class="jumlah-favorit">
            <?= count($daftarFavorit) ?> wisata favorit
        </span>
    </div>

    <?php if ($pesanError !== ''): ?>
        <div class="pesan-error">
            <?= e($pesanError) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($daftarFavorit)): ?>

        <div class="favorit-kosong">
            <div class="ikon">💗</div>

            <h2>Belum Ada Wisata Favorit</h2>

            <p>
                Yuk, jelajahi destinasi wisata Banten
                dan simpan tempat yang kamu sukai!
            </p>

            <a href="beranda.php#wisata" class="btn-detail">
                Jelajahi Wisata →
            </a>
        </div>

    <?php else: ?>

        <div class="favorit-grid">

            <?php foreach ($daftarFavorit as $w): ?>

                <?php $foto = gambarFavorit($w); ?>

                <article class="favorit-card">

                    <a href="detail.php?id=<?= (int)$w['id'] ?>">
                        <img
                            src="<?= e($foto) ?>"
                            alt="<?= e($w['nama']) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='assets/css/images/pantai_anyer.jpg';"
                        >
                    </a>

                    <div class="favorit-body">

                        <span class="kategori-tag">
                            <?= e($w['kategori']) ?>
                        </span>

                        <h3>
                            <?= e($w['nama']) ?>
                        </h3>

                        <p class="favorit-lokasi">
                            📍 <?= e($w['lokasi']) ?>
                        </p>

                        <strong class="favorit-harga">
                            <?= rupiah($w['harga']) ?>
                        </strong>

                        <div class="favorit-actions">

                            <a
                                href="detail.php?id=<?= (int)$w['id'] ?>"
                                class="btn-detail"
                            >
                                Lihat Detail →
                            </a>

                            <form method="post" action="favorit.php">

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= e($_SESSION['csrf_fitur']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="wisata_id"
                                    value="<?= (int)$w['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="aksi"
                                    value="hapus"
                                >

                                <button
                                    type="submit"
                                    class="btn-hapus"
                                    onclick="return confirm('Hapus wisata ini dari favorit?')"
                                >
                                    Hapus ❤️
                                </button>

                            </form>

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

</body>
</html>
