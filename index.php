
<?php
require_once __DIR__ . '/config/database.php';
$conn->set_charset("utf8mb4");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function gambarWisata($foto) {
    $namaFile = basename((string)($foto ?? ''));
    $path = __DIR__ . '/images/' . $namaFile;

    return ($namaFile !== '' && is_file($path))
        ? 'images/' . rawurlencode($namaFile)
        : '';
}

$cari = trim($_GET['cari'] ?? '');
$kategoriDipilih = trim($_GET['kategori'] ?? 'Semua');

/* Ambil kategori dari database */
$daftarKategori = [];
$resultKategori = $conn->query(
    "SELECT DISTINCT kategori
     FROM wisata
     WHERE kategori IS NOT NULL AND kategori <> ''
     ORDER BY kategori ASC"
);

if ($resultKategori) {
    while ($row = $resultKategori->fetch_assoc()) {
        $daftarKategori[] = $row['kategori'];
    }
}

/* Ambil wisata sesuai pencarian dan kategori */
$sql = "SELECT id, nama, lokasi, kategori, deskripsi, foto FROM wisata";
$kondisi = [];
$parameter = [];
$tipe = '';

if ($cari !== '') {
    $kondisi[] = '(nama LIKE ? OR lokasi LIKE ? OR deskripsi LIKE ?)';
    $kata = '%' . $cari . '%';
    $parameter[] = $kata;
    $parameter[] = $kata;
    $parameter[] = $kata;
    $tipe .= 'sss';
}

if ($kategoriDipilih !== '' && $kategoriDipilih !== 'Semua') {
    $kondisi[] = 'kategori = ?';
    $parameter[] = $kategoriDipilih;
    $tipe .= 's';
}

if ($kondisi) {
    $sql .= ' WHERE ' . implode(' AND ', $kondisi);
}

$sql .= ' ORDER BY id ASC';

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit('Pencarian wisata gagal diproses. Periksa struktur database.');
}

if ($parameter) {
    $stmt->bind_param($tipe, ...$parameter);
}

$stmt->execute();
$daftarWisata = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Jelajah Banten | Wisata Pilihan</title>

<style>
:root {
    --hijau: #176b43;
    --hijau-gelap: #104d31;
    --hijau-muda: #eaf5ed;
    --teks: #20372a;
    --abu: #68776c;
    --putih: #fff;
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    background: #f5f8f5;
    color: var(--teks);
    font-family: Arial, Helvetica, sans-serif;
    line-height: 1.6;
}

a {
    -webkit-tap-highlight-color: transparent;
}

/* NAVIGASI */
header {
    position: relative;
    z-index: 5;
    background: var(--putih);
    border-bottom: 1px solid #e4ece5;
}

.navbar {
    width: min(1180px, 92%);
    min-height: 70px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.logo {
    margin: 0;
    color: var(--hijau-gelap);
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -.5px;
}

.logo span {
    color: #72a77e;
}

/* TOMBOL LOGIN ADMIN */
.login-admin {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 15px;
    min-height: 38px;
    flex-shrink: 0;
    border-radius: 9px;
    background: var(--hijau);
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
    transition: background .2s ease;
}

.login-admin:hover {
    background: var(--hijau-gelap);
}

/* HERO */
.hero {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 410px;
    padding: 65px 20px;
    overflow: hidden;
    color: white;
    text-align: center;
    background:
        linear-gradient(120deg, rgba(8,43,25,.76), rgba(13,77,43,.45)),
        url('images/pantai_anyer.webp') center/cover no-repeat;
}

.hero-content {
    position: relative;
    max-width: 760px;
}

.hero-label {
    display: inline-block;
    padding: 7px 15px;
    margin-bottom: 17px;
    border: 1px solid rgba(255,255,255,.45);
    border-radius: 30px;
    background: rgba(255,255,255,.12);
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
}

.hero h1 {
    margin: 0 0 15px;
    font-size: clamp(32px, 5vw, 53px);
    line-height: 1.15;
    letter-spacing: -1.3px;
}

.hero p {
    max-width: 620px;
    margin: auto;
    color: rgba(255,255,255,.92);
    font-size: 16px;
}

/* KONTEN */
.container {
    width: min(1180px, 92%);
    margin: 35px auto 55px;
}

/* PENCARIAN */
.search-panel {
    position: relative;
    z-index: 2;
    padding: 20px;
    margin: -62px auto 38px;
    background: white;
    border: 1px solid #e6eee7;
    border-radius: 17px;
    box-shadow: 0 12px 32px rgba(22,65,37,.10);
}

.search-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 230px auto;
    align-items: center;
    gap: 12px;
}

.search-form input,
.search-form select {
    width: 100%;
    height: 48px;
    padding: 0 14px;
    border: 1px solid #dce6dd;
    border-radius: 10px;
    outline: none;
    background: white;
    color: var(--teks);
    font: inherit;
    font-size: 14px;
}

.search-form input:focus,
.search-form select:focus {
    border-color: var(--hijau);
    box-shadow: 0 0 0 3px rgba(23,107,67,.10);
}

.search-button {
    height: 48px;
    padding: 0 22px;
    border: 0;
    border-radius: 10px;
    background: var(--hijau);
    color: white;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}

.search-button:hover,
.detail-button:hover {
    background: var(--hijau-gelap);
}

/* JUDUL DAFTAR WISATA */
.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 20px;
}

.heading-text {
    min-width: 0;
}

.heading-text h2 {
    margin: 0;
    color: var(--hijau-gelap);
    font-size: 29px;
    line-height: 1.3;
}

.heading-text p {
    margin: 6px 0 0;
    color: var(--abu);
    font-size: 14px;
}

/* TOMBOL SLIDE */
.slide-controls {
    display: flex;
    flex-shrink: 0;
    gap: 9px;
}

.slide-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 43px;
    height: 43px;
    padding: 0;
    border: 1px solid #dbe7dd;
    border-radius: 50%;
    background: white;
    color: var(--hijau);
    font-size: 25px;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(0,0,0,.04);
}

.slide-arrow:hover:not(:disabled) {
    background: var(--hijau);
    color: white;
}

.slide-arrow:disabled {
    opacity: .35;
    cursor: not-allowed;
}

/* SLIDE WISATA */
.slide-viewport {
    width: 100%;
    overflow: hidden;
    padding: 2px 2px 16px;
    touch-action: pan-y;
}

.slide-track {
    display: flex;
    align-items: stretch;
    gap: 22px;
    transition: transform .35s ease;
    will-change: transform;
}

/* KARTU WISATA */
.wisata-card {
    flex: 0 0 calc((100% - 44px) / 3);
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: white;
    border: 1px solid #e4ece5;
    border-radius: 16px;
    box-shadow: 0 5px 18px rgba(22,65,37,.055);
    transition: box-shadow .2s, transform .2s;
}

.wisata-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(22,65,37,.11);
}

.wisata-photo,
.photo-placeholder {
    display: block;
    width: 100%;
    height: 220px;
    object-fit: cover;
    background: #e7eee8;
}

.photo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--abu);
    font-size: 14px;
}

.card-content {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: stretch;
    padding: 19px;
}

.category {
    display: inline-block;
    align-self: flex-start;
    max-width: 100%;
    padding: 4px 11px;
    overflow-wrap: anywhere;
    border-radius: 30px;
    background: var(--hijau-muda);
    color: var(--hijau);
    font-size: 11px;
    font-weight: bold;
}

.card-content h3 {
    margin: 11px 0 5px;
    color: var(--hijau-gelap);
    font-size: 20px;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.location {
    margin: 0 0 11px;
    color: var(--abu);
    font-size: 13px;
    overflow-wrap: anywhere;
}

.description {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
    margin: 0 0 18px;
    color: #536257;
    font-size: 14px;
    line-height: 1.65;
    overflow-wrap: anywhere;
}

.detail-button {
    display: block;
    width: 100%;
    margin-top: auto;
    padding: 11px 14px;
    border-radius: 9px;
    background: var(--hijau);
    color: white;
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    text-decoration: none;
    transition: background .2s;
}

/* INDIKATOR SLIDE */
.slide-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 8px;
}

.slide-hint {
    color: var(--abu);
    font-size: 12px;
}

.slide-dots {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
}

.slide-dot {
    width: 8px;
    height: 8px;
    padding: 0;
    border: 0;
    border-radius: 20px;
    background: #c9d8cc;
    cursor: pointer;
}

.slide-dot.active {
    width: 22px;
    background: var(--hijau);
}

/* WISATA TIDAK DITEMUKAN */
.empty-state {
    padding: 38px 20px;
    border: 1px solid #e4ece5;
    border-radius: 15px;
    background: white;
    text-align: center;
}

.empty-state h3 {
    margin-top: 0;
    color: var(--hijau-gelap);
}

.empty-state p {
    color: var(--abu);
}

.reset-button {
    display: inline-block;
    padding: 10px 17px;
    border-radius: 9px;
    background: var(--hijau);
    color: white;
    text-decoration: none;
    font-weight: bold;
}

/* FOOTER */
footer {
    padding: 24px 15px;
    background: #103e28;
    color: #e6f2e8;
    text-align: center;
    font-size: 13px;
}

footer p {
    margin: 0;
}

/* TABLET */
@media (max-width: 900px) {
    .search-form {
        grid-template-columns: minmax(0, 1fr) 200px;
    }

    .search-button {
        grid-column: 1 / -1;
        width: 100%;
    }

    .wisata-card {
        flex-basis: calc((100% - 22px) / 2);
    }

    .hero {
        min-height: 350px;
    }
}

/* HP */
@media (max-width: 600px) {
    .navbar {
        width: 92%;
        min-height: 58px;
        gap: 8px;
    }

    .logo {
        font-size: 18px;
    }

    .login-admin {
        gap: 5px;
        padding: 8px 10px;
        min-height: 34px;
        border-radius: 8px;
        font-size: 11px;
    }

    .hero {
        min-height: 275px;
        padding: 37px 18px 70px;
        background-position: center;
    }

    .hero-label {
        padding: 5px 11px;
        margin-bottom: 12px;
        font-size: 10px;
    }

    .hero h1 {
        margin-bottom: 10px;
        font-size: 28px;
        letter-spacing: -.5px;
    }

    .hero p {
        font-size: 13px;
        line-height: 1.6;
    }

    .container {
        width: 92%;
        margin: 0 auto 30px;
    }

    .search-panel {
        padding: 12px;
        margin: -43px auto 27px;
        border-radius: 12px;
    }

    .search-form {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 9px;
    }

    .search-form input,
    .search-form select {
        height: 42px;
        padding: 0 11px;
        border-radius: 8px;
        font-size: 13px;
    }

    .search-button {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        border-radius: 8px;
        font-size: 13px;
    }

    .section-heading {
        align-items: center;
        gap: 8px;
        margin-bottom: 13px;
    }

    .heading-text h2 {
        font-size: 21px;
    }

    .heading-text p {
        margin-top: 4px;
        font-size: 11px;
        line-height: 1.45;
    }

    .slide-controls {
        gap: 5px;
    }

    .slide-arrow {
        width: 32px;
        height: 32px;
        font-size: 20px;
    }

    .slide-viewport {
        padding: 0 0 10px;
    }

    .slide-track {
        gap: 12px;
    }

    .wisata-card {
        flex: 0 0 100%;
        border-radius: 12px;
    }

    .wisata-photo,
    .photo-placeholder {
        height: 190px;
    }

    .card-content {
        padding: 14px;
    }

    .category {
        padding: 3px 9px;
        font-size: 10px;
    }

    .card-content h3 {
        margin: 8px 0 4px;
        font-size: 18px;
    }

    .location {
        margin-bottom: 8px;
        font-size: 12px;
    }

    .description {
        margin-bottom: 13px;
        font-size: 13px;
        line-height: 1.55;
    }

    .detail-button {
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 13px;
    }

    .slide-bottom {
        flex-direction: column;
        gap: 9px;
        margin-top: 4px;
    }

    .slide-hint {
        font-size: 11px;
        text-align: center;
    }

    .slide-dots {
        gap: 5px;
    }

    .slide-dot {
        width: 7px;
        height: 7px;
    }

    .slide-dot.active {
        width: 18px;
    }

    footer {
        padding: 17px 12px;
        font-size: 11px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .slide-track {
        transition: none;
    }
}
</style>
</head>

<body>

<header>
    <div class="navbar">
        <p class="logo">🌿 Jelajah <span>Banten</span></p>

        <a href="admin/index.php" class="login-admin">
            🔐 Login Admin
        </a>
    </div>
</header>

<section class="hero">
    <div class="hero-content">
        <span class="hero-label">JELAJAHI KEINDAHAN BANTEN</span>
        <h1>Temukan Destinasi Wisata Favoritmu</h1>
        <p>
            Dari pantai yang menawan hingga alam yang menenangkan,
            temukan tempat menarik untuk perjalanan berikutnya.
        </p>
    </div>
</section>

<main class="container">

    <section class="search-panel">
        <form class="search-form" method="GET" action="index.php">
            <input
                type="search"
                name="cari"
                placeholder="🔎 Cari nama atau lokasi wisata..."
                value="<?= e($cari) ?>"
                aria-label="Cari wisata"
            >

            <select
                name="kategori"
                aria-label="Pilih kategori wisata"
                onchange="this.form.submit()"
            >
                <option value="Semua"
                    <?= $kategoriDipilih === 'Semua' ? 'selected' : '' ?>>
                    Semua Kategori
                </option>

                <?php foreach ($daftarKategori as $kategori): ?>
                    <option value="<?= e($kategori) ?>"
                        <?= $kategoriDipilih === $kategori ? 'selected' : '' ?>>
                        <?= e($kategori) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="search-button" type="submit">
                Cari Wisata
            </button>
        </form>
    </section>

    <section aria-label="Pilihan destinasi wisata">
        <div class="section-heading">
            <div class="heading-text">
                <h2>Jelajahi Wisata</h2>
                <p>Pilih destinasi dan lihat informasi lengkapnya.</p>
            </div>

            <?php if (count($daftarWisata) > 1): ?>
                <div class="slide-controls">
                    <button
                        type="button"
                        class="slide-arrow"
                        id="slideSebelumnya"
                        aria-label="Wisata sebelumnya"
                    >&#8249;</button>

                    <button
                        type="button"
                        class="slide-arrow"
                        id="slideBerikutnya"
                        aria-label="Wisata berikutnya"
                    >&#8250;</button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($daftarWisata): ?>

            <div class="slide-viewport" id="slideViewport">
                <div class="slide-track" id="slideTrack">

                    <?php foreach ($daftarWisata as $w): ?>
                        <?php $foto = gambarWisata($w['foto'] ?? ''); ?>

                        <article class="wisata-card">
                            <?php if ($foto !== ''): ?>
                                <img
                                    class="wisata-photo"
                                    src="<?= e($foto) ?>"
                                    alt="<?= e($w['nama']) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div class="photo-placeholder">
                                    Foto wisata belum tersedia
                                </div>
                            <?php endif; ?>

                            <div class="card-content">
                                <span class="category">
                                    <?= e($w['kategori'] ?: 'Wisata') ?>
                                </span>

                                <h3><?= e($w['nama']) ?></h3>

                                <p class="location">
                                    📍 <?= e($w['lokasi'] ?: 'Lokasi belum tersedia') ?>
                                </p>

                                <p class="description">
                                    <?= e($w['deskripsi'] ?: 'Deskripsi wisata belum tersedia.') ?>
                                </p>

                                <a
                                    class="detail-button"
                                    href="detail.php?id=<?= (int)$w['id'] ?>"
                                >
                                    Lihat Detail →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>

                </div>
            </div>

            <div class="slide-bottom">
                <span class="slide-hint">
                    Gunakan tombol panah atau geser kartu di layar HP.
                </span>

                <div
                    class="slide-dots"
                    id="slideDots"
                    aria-label="Pilih posisi slide"
                ></div>
            </div>

        <?php else: ?>

            <div class="empty-state">
                <h3>Wisata belum ditemukan</h3>
                <p>
                    Coba kata pencarian lain atau pilih kategori yang berbeda.
                </p>
                <a href="index.php" class="reset-button">
                    Tampilkan Semua Kategori
                </a>
            </div>

        <?php endif; ?>
    </section>
</main>

<footer>
    <p>© <?= date('Y') ?> Jelajah Banten · Jelajahi, Temukan, Nikmati 🌿</p>
</footer>

<script>
(function () {
    const viewport = document.getElementById('slideViewport');
    const track = document.getElementById('slideTrack');
    const prev = document.getElementById('slideSebelumnya');
    const next = document.getElementById('slideBerikutnya');
    const dotsBox = document.getElementById('slideDots');

    if (!viewport || !track || !dotsBox) return;

    const cards = Array.from(track.querySelectorAll('.wisata-card'));
    if (!cards.length) return;

    let index = 0;
    let visible = 3;
    let maxIndex = 0;
    let gap = 0;
    let touchStartX = 0;
    let resizeTimer;

    function calculate() {
        if (window.innerWidth <= 600) {
            visible = 1;
        } else if (window.innerWidth <= 900) {
            visible = 2;
        } else {
            visible = 3;
        }

        maxIndex = Math.max(0, cards.length - visible);
        index = Math.min(index, maxIndex);

        const style = window.getComputedStyle(track);
        gap = parseFloat(style.gap) || 0;

        buildDots();
        update();
    }

    function buildDots() {
        dotsBox.innerHTML = '';

        for (let i = 0; i <= maxIndex; i++) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'slide-dot' + (i === index ? ' active' : '');
            dot.setAttribute('aria-label', 'Tampilkan slide ' + (i + 1));

            dot.addEventListener('click', function () {
                index = i;
                update();
            });

            dotsBox.appendChild(dot);
        }
    }

    function update() {
        const cardWidth = cards[0].getBoundingClientRect().width;

        track.style.transform =
            'translateX(-' + (index * (cardWidth + gap)) + 'px)';

        if (prev) prev.disabled = index <= 0;
        if (next) next.disabled = index >= maxIndex;

        dotsBox.querySelectorAll('.slide-dot').forEach(function (dot, i) {
            dot.classList.toggle('active', i === index);
            dot.setAttribute('aria-current', i === index ? 'true' : 'false');
        });
    }

    if (prev) {
        prev.addEventListener('click', function () {
            if (index > 0) {
                index--;
                update();
            }
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            if (index < maxIndex) {
                index++;
                update();
            }
        });
    }

    /* Geser kartu dengan jari di HP */
    viewport.addEventListener('touchstart', function (event) {
        touchStartX = event.changedTouches[0].clientX;
    }, { passive: true });

    viewport.addEventListener('touchend', function (event) {
        const endX = event.changedTouches[0].clientX;
        const difference = endX - touchStartX;

        if (Math.abs(difference) < 40) return;

        if (difference < 0 && index < maxIndex) {
            index++;
            update();
        } else if (difference > 0 && index > 0) {
            index--;
            update();
        }
    }, { passive: true });

    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(calculate, 120);
    });

    calculate();
})();
</script>

</body>
</html>
