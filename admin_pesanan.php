
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya admin yang boleh masuk
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

// Fungsi keamanan tampilan
function amanPesanan($teks) {
    return htmlspecialchars(
        (string)$teks,
        ENT_QUOTES,
        'UTF-8'
    );
}

function rupiahPesanan($angka) {
    return 'Rp ' . number_format(
        (float)$angka,
        0,
        ',',
        '.'
    );
}

// CSRF
if (empty($_SESSION['csrf_admin_pesanan'])) {
    $_SESSION['csrf_admin_pesanan'] = bin2hex(
        random_bytes(32)
    );
}

$error = '';
$pesan = '';

$statusPilihan = [
    'Menunggu pembayaran',
    'Menunggu konfirmasi',
    'Lunas',
    'Dibatalkan'
];

// FILTER DAN PENCARIAN
$cari = trim((string)($_GET['cari'] ?? ''));
$filterStatus = trim(
    (string)($_GET['status'] ?? '')
);

if (
    $filterStatus !== '' &&
    !in_array($filterStatus, $statusPilihan, true)
) {
    $filterStatus = '';
}

// PROSES UBAH STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['csrf_admin_pesanan'],
            $token
        )
    ) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {

        $id = filter_var(
            $_POST['id'] ?? '',
            FILTER_VALIDATE_INT
        );

        $statusBaru = $_POST['status'] ?? '';

        if (
            !$id ||
            $id < 1 ||
            !in_array(
                $statusBaru,
                $statusPilihan,
                true
            )
        ) {
            $error = 'ID atau status pesanan tidak valid.';
        } else {

            try {
                $cek = $pdo->prepare(
                    'SELECT id FROM pesanan WHERE id = ?'
                );
                $cek->execute([$id]);

                if (!$cek->fetch()) {
                    $error = 'Pesanan tidak ditemukan.';

                } elseif ($statusBaru === 'Lunas') {
                    $error = 'Status Lunas hanya boleh diberikan setelah pembayaran benar-benar diterima dan diverifikasi. Sistem ini belum memiliki verifikasi pembayaran otomatis.';

                } else {

                    $stmt = $pdo->prepare(
                        'UPDATE pesanan
                         SET status = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $statusBaru,
                        $id
                    ]);

                    header(
                        'Location: admin_pesanan.php?pesan=berhasil'
                    );
                    exit;
                }

            } catch (PDOException $e) {
                $error = 'Status gagal diperbarui.';
            }
        }
    }
}

// PESAN BERHASIL
if (
    isset($_GET['pesan']) &&
    $_GET['pesan'] === 'berhasil'
) {
    $pesan = 'Status pesanan berhasil diperbarui.';
}

// RINGKASAN SELURUH PESANAN
$ringkasan = [
    'total' => 0,
    'menunggu_pembayaran' => 0,
    'menunggu_konfirmasi' => 0,
    'menunggu' => 0,
    'lunas' => 0,
    'dibatalkan' => 0
];

try {

    $stmtRingkasan = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            COALESCE(SUM(
                CASE
                    WHEN status = 'Menunggu pembayaran'
                    THEN 1 ELSE 0
                END
            ), 0) AS menunggu_pembayaran,
            COALESCE(SUM(
                CASE
                    WHEN status = 'Menunggu konfirmasi'
                    THEN 1 ELSE 0
                END
            ), 0) AS menunggu_konfirmasi,
            COALESCE(SUM(
                CASE
                    WHEN status = 'Lunas'
                    THEN 1 ELSE 0
                END
            ), 0) AS lunas,
            COALESCE(SUM(
                CASE
                    WHEN status = 'Dibatalkan'
                    THEN 1 ELSE 0
                END
            ), 0) AS dibatalkan
         FROM pesanan"
    );

    $hasilRingkasan = $stmtRingkasan->fetch(
        PDO::FETCH_ASSOC
    );

    if ($hasilRingkasan) {
        foreach ($hasilRingkasan as $kunci => $nilai) {
            $ringkasan[$kunci] = (int)$nilai;
        }
    }

    $ringkasan['menunggu'] =
        $ringkasan['menunggu_pembayaran'] +
        $ringkasan['menunggu_konfirmasi'];

} catch (PDOException $e) {
    $error = 'Ringkasan pesanan gagal dimuat.';
}

// DAFTAR PESANAN DENGAN FILTER
$daftarPesanan = [];

try {

    $syarat = [];
    $parameter = [];

    if ($cari !== '') {
        $syarat[] = 'kode_pesanan LIKE ?';
        $parameter[] = '%' . $cari . '%';
    }

    if ($filterStatus !== '') {
        $syarat[] = 'status = ?';
        $parameter[] = $filterStatus;
    }

    $sql = 'SELECT * FROM pesanan';

    if ($syarat) {
        $sql .= ' WHERE ' . implode(
            ' AND ',
            $syarat
        );
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parameter);

    $daftarPesanan = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {
    $error = 'Daftar pesanan gagal dimuat.';
}

// DETAIL TIKET
$detailSemua = [];

if ($daftarPesanan) {

    $ids = array_map(
        static fn($p) => (int)$p['id'],
        $daftarPesanan
    );

    $tanda = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    try {

        $stmt = $pdo->prepare(
            "SELECT
                dp.pesanan_id,
                dp.jumlah,
                dp.harga,
                w.nama AS nama_wisata
             FROM detail_pesanan dp
             LEFT JOIN wisata w
                ON w.id = dp.wisata_id
             WHERE dp.pesanan_id IN ($tanda)
             ORDER BY dp.id ASC"
        );

        $stmt->execute($ids);

        foreach (
            $stmt->fetchAll(PDO::FETCH_ASSOC) as $d
        ) {
            $detailSemua[$d['pesanan_id']][] = $d;
        }

    } catch (PDOException $e) {
        $error = 'Detail tiket gagal dimuat.';
    }
}

$adaFilter = (
    $cari !== '' ||
    $filterStatus !== ''
);
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Kelola Pesanan | JelajahBanten</title>

<style>

/* DASAR */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    width: 100%;
    max-width: 100%;
}

body {
    font-family: Arial, sans-serif;
    background: #fff7fb;
    color: #59434f;
    font-size: 14px;
    line-height: 1.5;
}

a {
    text-decoration: none;
}

button,
input,
select {
    font: inherit;
}

/* NAVBAR */

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 17px 5%;
    background: #d88ac1;
}

.brand {
    color: white;
    font-size: 20px;
    font-weight: bold;
}

.brand span {
    color: #fff0f5;
}

.nav-links {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.nav-links a {
    color: white;
    font-size: 13px;
}

/* KONTEN */

.container {
    width: calc(100% - 28px);
    max-width: 1100px;
    margin: 24px auto;
    min-width: 0;
}

.eyebrow {
    margin-bottom: 5px;
    color: #c85f89;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 1px;
}

.section-title {
    margin: 0 0 8px;
    color: #c85f89;
    font-size: 27px;
}

.subjudul {
    margin-bottom: 20px;
    color: #927b87;
    font-size: 13px;
}

.panel {
    min-width: 0;
    padding: 20px;
    margin: 18px 0;
    border: 1px solid #f2d5e1;
    border-radius: 13px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.panel h2 {
    margin: 0 0 14px;
    color: #b7507b;
    font-size: 19px;
}

/* RINGKASAN */

.ringkasan-grid {
    display: grid;
    grid-template-columns: repeat(
        3,
        minmax(0, 1fr)
    );
    gap: 14px;
    margin: 18px 0;
}

.kartu-ringkasan {
    min-width: 0;
    padding: 20px;
    border: 1px solid #f2d5e1;
    border-radius: 13px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.kartu-ringkasan .ikon {
    display: inline-block;
    margin-bottom: 10px;
    font-size: 24px;
}

.kartu-ringkasan h3 {
    margin: 0 0 8px;
    color: #927b87;
    font-size: 13px;
    font-weight: normal;
}

.kartu-ringkasan .angka {
    display: block;
    color: #c85f89;
    font-size: 31px;
    font-weight: bold;
    line-height: 1.2;
}

.kartu-ringkasan small {
    display: block;
    margin-top: 8px;
    color: #927b87;
    font-size: 11px;
}

.kartu-total {
    background: #fff0f5;
}

.kartu-menunggu {
    background: #fff8ee;
}

.kartu-lunas {
    background: #f1fbf5;
}

/* FORM */

input,
select {
    min-width: 0;
    padding: 10px;
    border: 1px solid #e9c6d5;
    border-radius: 8px;
    background: white;
    color: #59434f;
}

input:focus,
select:focus {
    outline: none;
    border-color: #e78eaf;
}

.btn {
    display: inline-block;
    padding: 10px 14px;
    border: none;
    border-radius: 8px;
    background: #e78eaf;
    color: white;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    cursor: pointer;
}

.btn:hover {
    background: #c85f89;
}

.btn-reset {
    background: #927b87;
}

.btn-reset:hover {
    background: #765e6b;
}

/* FILTER */

.filter-form {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(0, 1fr)
        auto
        auto;
    align-items: end;
    gap: 10px;
}

.filter-item {
    min-width: 0;
}

.filter-item label {
    display: block;
    margin-bottom: 6px;
    color: #b7507b;
    font-size: 12px;
    font-weight: bold;
}

.filter-item input,
.filter-item select {
    width: 100%;
}

.filter-form .btn {
    white-space: nowrap;
}

/* PESAN */

.error,
.success,
.peringatan {
    padding: 13px;
    margin: 14px 0;
    border-radius: 9px;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.error {
    background: #ffe7e7;
    color: #a83e4c;
}

.success {
    background: #e7f8ed;
    color: #21653e;
}

.peringatan {
    background: #fff4dc;
    color: #765b24;
}

/* LIHAT PESANAN */

.daftar-toggle {
    width: 100%;
    min-width: 0;
}

.daftar-toggle > summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px;
    border-radius: 10px;
    background: #e78eaf;
    color: white;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    list-style: none;
}

.daftar-toggle > summary::-webkit-details-marker {
    display: none;
}

.daftar-toggle > summary::after {
    content: "▼";
    font-size: 12px;
}

.daftar-toggle[open] > summary::after {
    content: "▲";
}

.daftar-toggle[open] > summary {
    background: #c85f89;
}

.isi-daftar {
    padding-top: 15px;
}

.info-filter {
    margin: 0 0 15px;
    color: #927b87;
    font-size: 12px;
}

/* KARTU PESANAN */

.pesanan {
    min-width: 0;
    padding: 17px;
    margin-bottom: 14px;
    border: 1px solid #f2d5e1;
    border-radius: 11px;
    background: #fffafd;
    overflow-wrap: anywhere;
}

.baris {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
}

.baris > div {
    min-width: 0;
}

.pesanan h3 {
    margin: 0 0 10px;
    color: #b7507b;
    font-size: 16px;
    overflow-wrap: anywhere;
}

.pesanan p {
    margin: 6px 0;
    font-size: 12px;
}

.total {
    color: #c85f89;
    font-size: 16px;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 7px;
    background: #fff0f5;
    color: #b7507b;
    font-size: 11px;
    font-weight: bold;
    overflow-wrap: anywhere;
}

.detail-tiket {
    min-width: 0;
    padding: 12px;
    margin: 13px 0;
    border-radius: 9px;
    background: #fff0f5;
}

.detail-tiket strong {
    color: #b7507b;
}

.detail-tiket ul {
    padding-left: 19px;
    margin-bottom: 0;
}

.detail-tiket li {
    margin-bottom: 7px;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.status-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 9px;
}

.status-form label {
    font-size: 12px;
    font-weight: bold;
}

.status-form select {
    flex: 1 1 180px;
    max-width: 100%;
}

.kosong {
    padding: 15px;
    text-align: center;
    color: #927b87;
}

footer {
    padding: 20px;
    text-align: center;
    color: #927b87;
    font-size: 12px;
}

/* TABLET */

@media (max-width: 850px) {

    .filter-form {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .filter-form .btn {
        width: 100%;
    }
}

/* HP */

@media (max-width: 600px) {

    .navbar {
        padding: 14px;
    }

    .brand {
        font-size: 18px;
    }

    .nav-links a {
        font-size: 11px;
    }

    .container {
        width: calc(100% - 20px);
        margin: 15px auto;
    }

    .section-title {
        font-size: 23px;
    }

    .panel {
        padding: 13px;
        margin: 13px 0;
    }

    .panel h2 {
        font-size: 17px;
    }

    .ringkasan-grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 7px;
        margin: 13px 0;
    }

    .kartu-ringkasan {
        padding: 11px 7px;
        text-align: center;
    }

    .kartu-ringkasan .ikon {
        font-size: 19px;
        margin-bottom: 6px;
    }

    .kartu-ringkasan h3 {
        font-size: 10px;
        line-height: 1.3;
    }

    .kartu-ringkasan .angka {
        font-size: 24px;
    }

    .kartu-ringkasan small {
        font-size: 9px;
        overflow-wrap: anywhere;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

    .filter-form .btn {
        width: 100%;
    }

    .daftar-toggle > summary {
        padding: 12px;
        font-size: 12px;
    }

    .pesanan {
        padding: 12px;
    }

    .baris {
        flex-direction: column;
        gap: 10px;
    }

    .baris > div {
        width: 100%;
    }

    .status-form {
        flex-direction: column;
        align-items: stretch;
    }

    .status-form select,
    .status-form .btn {
        width: 100%;
        flex: initial;
    }

    .detail-tiket {
        padding: 10px;
    }
}

/* HP SANGAT KECIL */

@media (max-width: 360px) {

    .ringkasan-grid {
        grid-template-columns: 1fr;
    }

    .kartu-ringkasan {
        text-align: left;
        padding: 12px;
    }

    .kartu-ringkasan .ikon {
        margin-bottom: 3px;
    }
}
</style>
</head>

<body>

<nav class="navbar">

    <a class="brand" href="admin.php">
        💐 Jelajah<span>Banten</span>
    </a>

    <div class="nav-links">
        <a href="admin.php">Dashboard</a>
        <a href="kelola_wisata.php">Kelola Wisata</a>
        <a href="logout.php">Keluar</a>
    </div>

</nav>

<main class="container">

    <p class="eyebrow">ADMIN PANEL</p>

    <h1 class="section-title">
        Kelola Pesanan
    </h1>

    <p class="subjudul">
        Pantau pesanan tiket wisata dan perbarui statusnya.
    </p>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= amanPesanan($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="success">
            <?= amanPesanan($pesan) ?>
        </div>
    <?php endif; ?>

    <!-- RINGKASAN PESANAN -->

    <section class="ringkasan-grid">

        <div class="kartu-ringkasan kartu-total">

            <span class="ikon">📋</span>

            <h3>Total Pesanan</h3>

            <span class="angka">
                <?= $ringkasan['total'] ?>
            </span>

            <small>Semua pesanan masuk</small>

        </div>

        <div class="kartu-ringkasan kartu-menunggu">

            <span class="ikon">⏳</span>

            <h3>Menunggu</h3>

            <span class="angka">
                <?= $ringkasan['menunggu'] ?>
            </span>

            <small>Menunggu pembayaran / konfirmasi</small>

        </div>

        <div class="kartu-ringkasan kartu-lunas">

            <span class="ikon">✅</span>

            <h3>Lunas</h3>

            <span class="angka">
                <?= $ringkasan['lunas'] ?>
            </span>

            <small>Pembayaran selesai</small>

        </div>

    </section>

    <!-- PERINGATAN -->

    <div class="peringatan">

        <strong>Penting:</strong>
        Status Lunas hanya boleh diberikan setelah
        pembayaran benar-benar diterima dan diverifikasi.
        Sistem belum memverifikasi pembayaran otomatis.

    </div>

    <!-- FILTER PESANAN -->

    <section class="panel">

        <h2>🔍 Cari & Filter Pesanan</h2>

        <form
            method="get"
            action="admin_pesanan.php"
            class="filter-form"
        >

            <div class="filter-item">

                <label for="cari">
                    Kode Pesanan
                </label>

                <input
                    type="search"
                    name="cari"
                    id="cari"
                    placeholder="Masukkan kode pesanan..."
                    value="<?= amanPesanan($cari) ?>"
                >

            </div>

            <div class="filter-item">

                <label for="filterStatus">
                    Filter Status
                </label>

                <select
                    name="status"
                    id="filterStatus"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <?php foreach ($statusPilihan as $s): ?>

                        <option
                            value="<?= amanPesanan($s) ?>"
                            <?= $filterStatus === $s
                                ? 'selected'
                                : '' ?>
                        >
                            <?= amanPesanan($s) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <button
                type="submit"
                class="btn"
            >
                Terapkan
            </button>

            <a
                href="admin_pesanan.php"
                class="btn btn-reset"
            >
                Reset
            </a>

        </form>

    </section>

    <!-- DAFTAR PESANAN -->

    <section class="panel">

        <h2>
            Daftar Pesanan
            (<?= count($daftarPesanan) ?>)
        </h2>

        <details
            class="daftar-toggle"
            <?= $adaFilter ? 'open' : '' ?>
        >

            <summary>
                Lihat Pesanan
            </summary>

            <div class="isi-daftar">

                <?php if ($adaFilter): ?>

                    <p class="info-filter">
                        Menampilkan
                        <?= count($daftarPesanan) ?>
                        pesanan sesuai pencarian atau filter.
                    </p>

                <?php endif; ?>

                <?php if (!$daftarPesanan): ?>

                    <p class="kosong">
                        Tidak ada pesanan yang ditemukan.
                    </p>

                <?php endif; ?>

                <?php foreach ($daftarPesanan as $p): ?>

                    <article class="pesanan">

                        <div class="baris">

                            <div>

                                <h3>
                                    <?= amanPesanan(
                                        $p['kode_pesanan']
                                    ) ?>
                                </h3>

                                <p>
                                    <strong>ID Pesanan:</strong>
                                    <?= (int)$p['id'] ?>
                                </p>

                                <p>
                                    <strong>Tanggal Kunjungan:</strong>
                                    <?= amanPesanan(
                                        $p['tanggal_kunjungan']
                                    ) ?>
                                </p>

                                <p>
                                    <strong>Dibuat Pada:</strong>
                                    <?= amanPesanan(
                                        $p['dibuat_pada']
                                    ) ?>
                                </p>

                            </div>

                            <div>

                                <strong class="total">
                                    <?= rupiahPesanan(
                                        $p['total']
                                    ) ?>
                                </strong>

                                <p>
                                    <strong>Metode:</strong>
                                    <?= amanPesanan(
                                        $p['metode']
                                    ) ?>
                                </p>

                                <p>
                                    <strong>Status:</strong>

                                    <span class="status">
                                        <?= amanPesanan(
                                            $p['status']
                                        ) ?>
                                    </span>
                                </p>

                            </div>

                        </div>

                        <!-- DETAIL TIKET -->

                        <div class="detail-tiket">

                            <strong>Detail Tiket</strong>

                            <?php if (
                                !empty(
                                    $detailSemua[$p['id']]
                                )
                            ): ?>

                                <ul>

                                <?php foreach (
                                    $detailSemua[$p['id']] as $d
                                ): ?>

                                    <li>
                                        <?= amanPesanan(
                                            $d['nama_wisata'] ??
                                            'Wisata tidak ditemukan'
                                        ) ?>

                                        —
                                        <?= (int)$d['jumlah'] ?>
                                        tiket

                                        ×
                                        <?= rupiahPesanan(
                                            $d['harga']
                                        ) ?>
                                    </li>

                                <?php endforeach; ?>

                                </ul>

                            <?php else: ?>

                                <p>
                                    Detail tiket tidak tersedia.
                                </p>

                            <?php endif; ?>

                        </div>

                        <!-- UBAH STATUS -->

                        <form
                            method="post"
                            class="status-form"
                            onsubmit="return confirm('Yakin ingin mengubah status pesanan ini?')"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= amanPesanan(
                                    $_SESSION[
                                        'csrf_admin_pesanan'
                                    ]
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$p['id'] ?>"
                            >

                            <label
                                for="status-<?= (int)$p['id'] ?>"
                            >
                                Ubah Status:
                            </label>

                            <select
                                name="status"
                                id="status-<?= (int)$p['id'] ?>"
                                required
                            >

                                <?php
                                $statusSaatIni = $p['status'];

                                $opsiStatus = array_unique(
                                    array_merge(
                                        $statusPilihan,
                                        [$statusSaatIni]
                                    )
                                );

                                foreach ($opsiStatus as $s):
                                ?>

                                    <option
                                        value="<?= amanPesanan($s) ?>"
                                        <?= $s === $statusSaatIni
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= amanPesanan($s) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button
                                type="submit"
                                class="btn"
                            >
                                Simpan Status
                            </button>

                        </form>

                    </article>

                <?php endforeach; ?>

            </div>

        </details>

    </section>

</main>

<footer>
    <p>
        JelajahBanten &copy; <?= date('Y') ?>
    </p>
</footer>

</body>
</html>
