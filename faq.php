
<?php
require_once 'config.php';

$namaPengguna = $_SESSION['nama'] ?? 'Pengunjung';
$sudahLogin = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'user';

$daftarFAQ = [
    [
        'pertanyaan' => 'Bagaimana cara memesan tiket wisata?',
        'jawaban' => 'Buka halaman Beranda, pilih destinasi wisata yang kamu inginkan, lalu klik Detail. Pilih jumlah tiket dan lanjutkan proses pemesanan sesuai petunjuk yang tersedia.'
    ],
    [
        'pertanyaan' => 'Bagaimana cara melakukan pembayaran?',
        'jawaban' => 'Setelah melakukan pemesanan, buka halaman checkout atau pembayaran. Ikuti instruksi pembayaran yang ditampilkan pada website.'
    ],
    [
        'pertanyaan' => 'Di mana saya bisa melihat tiket yang sudah dipesan?',
        'jawaban' => 'Kamu bisa membuka menu Pesanan untuk melihat informasi tiket dan status pemesanan.'
    ],
    [
        'pertanyaan' => 'Apa fungsi QR Code pada tiket?',
        'jawaban' => 'QR Code berfungsi sebagai kode identifikasi tiket elektronik. Simpan tiket dan tunjukkan QR Code saat diperlukan untuk pemeriksaan tiket.'
    ],
    [
        'pertanyaan' => 'Apakah harus login untuk memesan tiket?',
        'jawaban' => 'Ya, kamu perlu masuk ke akun agar pemesanan dan riwayat tiket dapat tercatat pada akunmu.'
    ],
    [
        'pertanyaan' => 'Apakah saya bisa memesan lebih dari satu tiket?',
        'jawaban' => 'Bisa. Kamu dapat memilih jumlah tiket sesuai kebutuhan selama pilihan tersebut tersedia di halaman pemesanan.'
    ],
    [
        'pertanyaan' => 'Bagaimana jika saya mengalami masalah saat memesan tiket?',
        'jawaban' => 'Kamu bisa menghubungi pengelola melalui kontak atau WhatsApp yang tersedia pada website.'
    ],
    [
        'pertanyaan' => 'Apakah tiket yang sudah dibeli bisa dibatalkan?',
        'jawaban' => 'Ketentuan pembatalan mengikuti kebijakan pengelola. Hubungi admin terlebih dahulu untuk mengetahui apakah pesananmu dapat dibatalkan.'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FAQ - Pariwisata Banten</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #fff8fb;
            color: #59434f;
        }

        /* NAVBAR */

        .navbar {
            background: white;
            padding: 18px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 3px 15px rgba(200, 95, 137, 0.08);
        }

        .brand {
            font-size: 23px;
            font-weight: 800;
            color: #c85f89;
            text-decoration: none;
        }

        .brand span {
            color: #e78eaf;
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-links a {
            display: inline-block;
            padding: 10px 17px;
            background: #fff0f5;
            color: #b7507b;
            border: 1px solid #f2c7d9;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: #c85f89;
            color: white;
        }

        /* HERO FAQ */

        .faq-hero {
            padding: 65px 20px 55px;
            text-align: center;
            background: linear-gradient(135deg, #ffe4ef, #fff4f8);
        }

        .faq-hero .label {
            display: inline-block;
            padding: 8px 18px;
            background: white;
            border-radius: 30px;
            color: #c85f89;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .faq-hero h1 {
            margin: 0 0 15px;
            font-size: clamp(28px, 5vw, 42px);
            color: #b7507b;
        }

        .faq-hero p {
            margin: 0 auto;
            max-width: 550px;
            color: #856b78;
            line-height: 1.8;
            font-size: 15px;
        }

        /* ISI FAQ */

        .faq-container {
            max-width: 850px;
            margin: 45px auto 65px;
            padding: 0 20px;
        }

        .faq-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .faq-heading h2 {
            color: #c85f89;
            font-size: 25px;
            margin-bottom: 10px;
        }

        .faq-heading p {
            color: #937985;
            font-size: 14px;
        }

        .faq-item {
            margin-bottom: 14px;
            border: 1px solid #f4d5e2;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(200, 95, 137, 0.05);
        }

        .faq-item summary {
            list-style: none;
            padding: 20px 23px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            font-weight: 650;
            color: #6b4657;
            font-size: 15px;
        }

        .faq-item summary::-webkit-details-marker {
            display: none;
        }

        .faq-item summary::after {
            content: '+';
            font-size: 25px;
            font-weight: 400;
            color: #c85f89;
            flex-shrink: 0;
        }

        .faq-item[open] summary {
            background: #fff0f5;
            color: #b7507b;
        }

        .faq-item[open] summary::after {
            content: '−';
        }

        .faq-answer {
            padding: 18px 23px 22px;
            color: #7f6b75;
            font-size: 14px;
            line-height: 1.9;
        }

        /* BANTUAN */

        .help-box {
            background: #ffeaf2;
            border: 1px solid #f3c7d9;
            border-radius: 20px;
            padding: 32px 25px;
            text-align: center;
            margin-top: 35px;
        }

        .help-box h3 {
            color: #b7507b;
            font-size: 22px;
            margin: 0 0 12px;
        }

        .help-box p {
            color: #856b78;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 22px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #c85f89;
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn:hover {
            background: #a84670;
            transform: translateY(-2px);
        }

        /* FOOTER */

        footer {
            text-align: center;
            background: white;
            padding: 28px 20px;
            border-top: 1px solid #f5dce6;
        }

        footer p {
            color: #987e8a;
            font-size: 13px;
            margin: 10px 0 0;
        }

        @media (max-width: 600px) {
            .navbar {
                justify-content: center;
                padding: 16px;
            }

            .nav-links {
                justify-content: center;
            }

            .nav-links a {
                padding: 9px 13px;
                font-size: 12px;
            }

            .faq-hero {
                padding: 45px 18px;
            }

            .faq-container {
                margin-top: 30px;
            }

            .faq-item summary {
                padding: 17px;
                font-size: 14px;
            }

            .faq-answer {
                padding: 15px 17px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">

        <a href="beranda.php" class="brand">
            🌸 Pariwisata <span>Banten</span>
        </a>

        <nav class="nav-links">
            <a href="beranda.php">Beranda</a>
            <a href="beranda.php#wisata">Destinasi</a>
            <a href="faq.php" class="active">FAQ</a>

            <?php if ($sudahLogin): ?>

                <a href="keranjang.php">Keranjang</a>
                <a href="pesanan.php">Pesanan</a>

                <?php if ($role === 'admin'): ?>
                    <a href="admin.php">Dashboard Admin</a>
                <?php endif; ?>

                <a href="logout.php">Keluar</a>

            <?php else: ?>

                <a href="login.php">Masuk</a>

            <?php endif; ?>
        </nav>

    </header>

    <!-- HERO -->
    <section class="faq-hero">

        <span class="label">
            ❓ PUSAT BANTUAN
        </span>

        <h1>
            Pertanyaan yang Sering Diajukan
        </h1>

        <p>
            Punya pertanyaan tentang wisata, pemesanan tiket,
            pembayaran, atau QR Code? Temukan jawabannya di sini!
        </p>

    </section>

    <!-- DAFTAR FAQ -->
    <main class="faq-container">

        <div class="faq-heading">
            <h2>🌷 Ada yang Ingin Kamu Tanyakan?</h2>

            <p>
                Klik pertanyaan di bawah untuk melihat jawabannya.
            </p>
        </div>

        <?php foreach ($daftarFAQ as $index => $faq): ?>

            <details class="faq-item">

                <summary>
                    <?= htmlspecialchars(
                        $faq['pertanyaan'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </summary>

                <div class="faq-answer">
                    <?= htmlspecialchars(
                        $faq['jawaban'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            </details>

        <?php endforeach; ?>

        <!-- BANTUAN -->
        <div class="help-box">

            <h3>💌 Masih Butuh Bantuan?</h3>

            <p>
                Belum menemukan jawaban yang kamu cari?
                Kamu bisa kembali ke beranda untuk melihat
                informasi wisata dan kontak pengelola.
            </p>

            <a href="beranda.php" class="btn">
                ← Kembali ke Beranda
            </a>

        </div>

    </main>

    <!-- FOOTER -->
    <footer>

        <a href="beranda.php" class="brand">
            🌸 Pariwisata <span>Banten</span>
        </a>

        <p>
            © <?= date('Y') ?> Pariwisata Banten.
            Jelajahi Keindahan Banten.
        </p>

    </footer>

</body>
</html>
