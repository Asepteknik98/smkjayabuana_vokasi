
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_wisata'])) {
    $_SESSION['csrf_wisata'] = bin2hex(random_bytes(32));
}

function bersihkan($nilai) {
    return htmlspecialchars(
        (string)$nilai,
        ENT_QUOTES,
        'UTF-8'
    );
}

// ========================================
// FUNGSI HARGA RUPIAH
// ========================================

function hargaKeAngka($harga) {
    $harga = trim((string)$harga);

    // Hapus tulisan Rp dan spasi
    $harga = preg_replace('/[Rr][Pp]/', '', $harga);
    $harga = preg_replace('/\s+/', '', $harga);

    // Hapus titik pemisah ribuan
    $harga = str_replace('.', '', $harga);

    // Hanya menerima angka bulat
    if (
        $harga === '' ||
        !ctype_digit($harga)
    ) {
        throw new Exception(
            'Harga harus berupa angka, contoh: 75.000 atau 75000.'
        );
    }

    // Batasi supaya sesuai kapasitas harga yang wajar
    if (strlen($harga) > 12) {
        throw new Exception('Harga terlalu besar.');
    }

    return (int)$harga;
}

function formatHargaInput($harga) {
    return number_format(
        (float)$harga,
        0,
        ',',
        '.'
    );
}

// ========================================
// FUNGSI GAMBAR WISATA
// ========================================

function gambarWisataAdmin($wisata) {
    $id = (int)($wisata['id'] ?? 0);

    $nama = strtolower(
        trim((string)($wisata['nama'] ?? ''))
    );

    $nama = preg_replace('/\s+/', ' ', $nama);

    // Khusus Masjid Agung Banten ID 9
    if (
        $id === 9 ||
        (
            strpos($nama, 'masjid') !== false &&
            strpos($nama, 'banten') !== false
        )
    ) {
        return 'assets/css/images/masjid_banten.jpg';
    }

    // Foto wisata lainnya
    $daftarFoto = [
        'pantai anyer' =>
            'assets/css/images/pantai_anyer.jpg',

        'pantai sawarna' =>
            'assets/css/images/pantai_sawarna.jpg',

        'taman nasional ujung kulon' =>
            'assets/css/images/ujung_kulon.jpg',

        'keraton surosowan' =>
            'assets/css/images/keraton_surosowan.jpg',

        'pantai carita' =>
            'assets/css/images/pantai_carita.jpeg',

        'tanjung lesung' =>
            'assets/css/images/tanjung_lesung.jpg',

        'desa adat baduy' =>
            'assets/css/images/baduy_banten.jpg'
    ];

    if (isset($daftarFoto[$nama])) {
        return $daftarFoto[$nama];
    }

    return trim((string)($wisata['gambar'] ?? ''));
}

function urlGambarAdmin($gambar) {
    $gambar = trim((string)$gambar);

    if ($gambar === '') {
        return '';
    }

    // URL dari luar website
    if (preg_match('~^https?://~i', $gambar)) {
        return $gambar;
    }

    $lokasi = __DIR__ . '/' . ltrim($gambar, '/');

    if (is_file($lokasi)) {
        $pemisah = strpos($gambar, '?') !== false
            ? '&'
            : '?';

        return $gambar . $pemisah . 'v=' . filemtime($lokasi);
    }

    return $gambar;
}

// ========================================
// PROSES TAMBAH, EDIT, HAPUS
// ========================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_wisata'], $token)
    ) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        try {
            if ($aksi === 'hapus') {
                $id = filter_var(
                    $_POST['id'] ?? '',
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new Exception('ID wisata tidak valid.');
                }

                $stmt = $pdo->prepare(
                    'DELETE FROM wisata WHERE id = ?'
                );
                $stmt->execute([$id]);

                header('Location: kelola_wisata.php?pesan=hapus');
                exit;
            }

            if ($aksi === 'simpan') {
                $idInput = $_POST['id'] ?? '';

                $id = $idInput === ''
                    ? null
                    : filter_var(
                        $idInput,
                        FILTER_VALIDATE_INT
                    );

                if (
                    $idInput !== '' &&
                    (!$id || $id < 1)
                ) {
                    throw new Exception('ID wisata tidak valid.');
                }

                $nama = trim(
                    (string)($_POST['nama'] ?? '')
                );

                $lokasi = trim(
                    (string)($_POST['lokasi'] ?? '')
                );

                $kategori = trim(
                    (string)($_POST['kategori'] ?? '')
                );

                $hargaInput = trim(
                    (string)($_POST['harga'] ?? '')
                );

                $gambar = trim(
                    (string)($_POST['gambar'] ?? '')
                );

                $deskripsi = trim(
                    (string)($_POST['deskripsi'] ?? '')
                );

                if (
                    $nama === '' ||
                    $lokasi === '' ||
                    $kategori === '' ||
                    $hargaInput === '' ||
                    $gambar === '' ||
                    $deskripsi === ''
                ) {
                    throw new Exception(
                        'Semua kolom wajib diisi.'
                    );
                }

                // Konversi 75.000 menjadi 75000
                $harga = hargaKeAngka($hargaInput);

                if (
                    mb_strlen($nama) > 150 ||
                    mb_strlen($lokasi) > 150 ||
                    mb_strlen($kategori) > 50 ||
                    mb_strlen($gambar) > 500
                ) {
                    throw new Exception(
                        'Ada teks yang melebihi batas panjang.'
                    );
                }

                if ($id !== null) {
                    $cek = $pdo->prepare(
                        'SELECT COUNT(*) FROM wisata WHERE id = ?'
                    );

                    $cek->execute([$id]);

                    if ((int)$cek->fetchColumn() === 0) {
                        throw new Exception(
                            'Wisata tidak ditemukan.'
                        );
                    }

                    $stmt = $pdo->prepare(
                        'UPDATE wisata
                         SET nama = ?,
                             lokasi = ?,
                             kategori = ?,
                             harga = ?,
                             gambar = ?,
                             deskripsi = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $nama,
                        $lokasi,
                        $kategori,
                        $harga,
                        $gambar,
                        $deskripsi,
                        $id
                    ]);

                    header(
                        'Location: kelola_wisata.php?pesan=edit'
                    );
                    exit;
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO wisata
                     (nama, lokasi, kategori, harga, gambar, deskripsi)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $nama,
                    $lokasi,
                    $kategori,
                    $harga,
                    $gambar,
                    $deskripsi
                ]);

                header(
                    'Location: kelola_wisata.php?pesan=tambah'
                );
                exit;
            }

            throw new Exception('Aksi tidak dikenali.');

        } catch (Throwable $e) {
            if ($e instanceof PDOException) {
                $error =
                    'Data gagal diproses. Periksa database atau hubungan data pesanan.';
            } else {
                $error = $e->getMessage();
            }
        }
    }
}

// ========================================
// AMBIL DATA EDIT
// ========================================

$edit = null;

if (isset($_GET['edit'])) {
    $idEdit = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($idEdit && $idEdit > 0) {
        $stmt = $pdo->prepare(
            'SELECT * FROM wisata WHERE id = ?'
        );

        $stmt->execute([$idEdit]);

        $edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

// ========================================
// DAFTAR WISATA
// ========================================

$stmt = $pdo->query(
    'SELECT * FROM wisata ORDER BY id DESC'
);

$wisataList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesan = [
    'tambah' => 'Wisata berhasil ditambahkan.',
    'edit' => 'Data wisata berhasil diperbarui.',
    'hapus' => 'Data wisata berhasil dihapus.'
];

// Untuk menampilkan data yang tadi diketik jika ada error
$formData = $edit ?? [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $error !== '' &&
    ($_POST['aksi'] ?? '') === 'simpan'
) {
    $formData = $_POST;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Kelola Wisata | JelajahBanten</title>

<style>
* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    width: 100%;
    max-width: 100%;
}

body {
    font-family: Arial, sans-serif;
    font-size: 14px;
    line-height: 1.5;
    background: #fff7fb;
    color: #59434f;
}

a {
    text-decoration: none;
}

header {
    padding: 18px 5%;
    background: #d88ac1;
    color: white;
}

header h2 {
    margin: 0 0 5px;
    font-size: 22px;
}

header p {
    margin: 5px 0 12px;
    font-size: 13px;
}

header nav {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 16px;
}

header a {
    color: white;
    font-size: 13px;
}

main {
    width: calc(100% - 28px);
    max-width: 1100px;
    margin: 20px auto;
    min-width: 0;
}

.panel {
    min-width: 0;
    padding: 20px;
    margin-bottom: 18px;
    border: 1px solid #f2d5e1;
    border-radius: 13px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.panel h2 {
    margin: 0 0 15px;
    color: #c85f89;
    font-size: 19px;
}

label {
    display: block;
    margin: 12px 0 5px;
    font-size: 13px;
    font-weight: bold;
}

input,
textarea {
    width: 100%;
    min-width: 0;
    padding: 11px;
    border: 1px solid #f2d5e1;
    border-radius: 8px;
    background: #fffafd;
    color: #59434f;
    font: inherit;
}

input:focus,
textarea:focus {
    outline: none;
    border-color: #e78eaf;
    box-shadow: 0 0 0 3px #e78eaf25;
}

textarea {
    min-height: 90px;
    resize: vertical;
}

small {
    display: block;
    margin-top: 5px;
    color: #927b87;
    font-size: 11px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 15px;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

button,
.btn {
    display: inline-block;
    padding: 9px 13px;
    border: none;
    border-radius: 8px;
    background: #e78eaf;
    color: white;
    font: inherit;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    text-align: center;
}

button:hover,
.btn:hover {
    background: #c85f89;
}

.hapus {
    background: #c75b68;
}

.hapus:hover {
    background: #a83e4c;
}

.batal {
    background: #927b87;
}

.batal:hover {
    background: #765e6b;
}

.success,
.error {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 9px;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.success {
    background: #fff0f5;
    color: #a63f6d;
}

.error {
    background: #ffe7e7;
    color: #a83e4c;
}

.cari-box {
    margin-bottom: 15px;
}

.cari-box label {
    margin-top: 0;
    color: #b7507b;
}

.cari-box input {
    width: 100%;
    background: white;
}

.info-hasil {
    margin: 10px 0 15px;
    font-size: 12px;
    color: #927b87;
}

#tidakDitemukan {
    display: none;
    padding: 18px;
    text-align: center;
    color: #927b87;
}

.table-wrap {
    width: 100%;
    min-width: 0;
    overflow-x: auto;
}

.tabel-admin {
    width: 100%;
    border-collapse: collapse;
}

.tabel-admin th,
.tabel-admin td {
    padding: 11px 9px;
    border-bottom: 1px solid #f4e1e9;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.tabel-admin th {
    background: #fff0f5;
    color: #b7507b;
    white-space: nowrap;
}

.tabel-admin td {
    overflow-wrap: anywhere;
}

.foto {
    display: block;
    width: 85px;
    height: 60px;
    max-width: 100%;
    object-fit: cover;
    border-radius: 7px;
}

.aksi-detail {
    min-width: 0;
}

.aksi-detail summary {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    background: #fff0f5;
    color: #b7507b;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    list-style: none;
}

.aksi-detail summary::-webkit-details-marker {
    display: none;
}

.aksi-detail summary::after {
    content: "⌄";
}

.aksi-detail[open] summary::after {
    content: "⌃";
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 10px;
}

.actions form {
    margin: 0;
}

.kosong {
    padding: 18px;
    text-align: center;
    color: #927b87;
}

@media (max-width: 850px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    header {
        padding: 14px;
    }

    header h2 {
        font-size: 18px;
    }

    header a {
        font-size: 11px;
    }

    main {
        width: calc(100% - 20px);
        margin: 12px auto;
    }

    .panel {
        padding: 13px;
        margin-bottom: 13px;
    }

    .panel h2 {
        font-size: 17px;
    }

    input,
    textarea {
        font-size: 12px;
    }

    .table-wrap {
        overflow-x: visible;
    }

    .tabel-admin,
    .tabel-admin tbody {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .tabel-admin thead {
        display: none;
    }

    .tabel-admin tbody tr.baris-wisata {
        display: block;
        width: 100%;
        margin-bottom: 11px;
        padding: 11px;
        border: 1px solid #f2d5e1;
        border-radius: 11px;
        background: #fffafd;
    }

    .tabel-admin tbody tr.baris-wisata td {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        width: 100%;
        padding: 8px 0;
        border-bottom: 1px solid #f7e8ef;
        font-size: 12px;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .tabel-admin tbody tr.baris-wisata td::before {
        content: attr(data-label);
        flex: 0 0 82px;
        color: #c85f89;
        font-weight: bold;
        text-align: left;
    }

    .tabel-admin tbody tr.baris-wisata td:last-child {
        border-bottom: none;
    }

    .tabel-admin .foto {
        width: 85px;
        height: 60px;
        flex-shrink: 0;
    }

    .aksi-detail {
        width: 100%;
    }

    .aksi-detail summary {
        width: 100%;
    }

    .actions {
        justify-content: flex-end;
    }

    .tabel-admin td[colspan] {
        display: block;
        text-align: center;
    }

    .tabel-admin td[colspan]::before {
        display: none;
    }
}

@media (max-width: 360px) {
    .tabel-admin tbody tr.baris-wisata td::before {
        flex-basis: 70px;
    }

    .tabel-admin tbody tr.baris-wisata td {
        font-size: 11px;
    }
}
</style>
</head>

<body>

<header>
    <h2>💐 Panel Admin — Kelola Wisata</h2>

    <p>
        Halo, <?= bersihkan($_SESSION['nama'] ?? 'Administrator') ?>!
    </p>

    <nav>
        <a href="admin.php">Dashboard Admin</a>
        <a href="admin_kategori.php">Kelola Kategori</a>
        <a href="admin_wilayah.php">Kelola Wilayah</a>
        <a href="beranda.php">Lihat Website</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>

    <?php if ($error !== ''): ?>
        <p class="error"><?= bersihkan($error) ?></p>
    <?php endif; ?>

    <?php if (
        isset($_GET['pesan']) &&
        isset($pesan[$_GET['pesan']])
    ): ?>
        <p class="success">
            <?= bersihkan($pesan[$_GET['pesan']]) ?>
        </p>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT -->

    <section class="panel" id="form-wisata">

        <h2>
            <?= $edit ? 'Edit Data Wisata' : 'Tambah Wisata Baru' ?>
        </h2>

        <form method="post" action="kelola_wisata.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= bersihkan($_SESSION['csrf_wisata']) ?>"
            >

            <input type="hidden" name="aksi" value="simpan">

            <input
                type="hidden"
                name="id"
                value="<?= bersihkan($formData['id'] ?? '') ?>"
            >

            <div class="form-grid">

                <div>
                    <label for="nama">Nama Wisata</label>

                    <input
                        id="nama"
                        name="nama"
                        maxlength="150"
                        required
                        value="<?= bersihkan($formData['nama'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="lokasi">Lokasi</label>

                    <input
                        id="lokasi"
                        name="lokasi"
                        maxlength="150"
                        required
                        value="<?= bersihkan($formData['lokasi'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="kategori">Kategori</label>

                    <input
                        id="kategori"
                        name="kategori"
                        maxlength="50"
                        required
                        placeholder="Pantai, Alam, Sejarah"
                        value="<?= bersihkan($formData['kategori'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="harga">Harga Tiket (Rp)</label>

                    <input
                        id="harga"
                        type="text"
                        name="harga"
                        inputmode="numeric"
                        required
                        placeholder="Contoh: 75.000"
                        value="<?= bersihkan(
                            isset($formData['harga'])
                                ? (
                                    $error !== '' &&
                                    $_SERVER['REQUEST_METHOD'] === 'POST'
                                        ? $formData['harga']
                                        : formatHargaInput($formData['harga'])
                                )
                                : ''
                        ) ?>"
                    >

                    <small>
                        Bisa ketik 75000 atau 75.000.
                        Keduanya menjadi Rp 75.000.
                    </small>
                </div>

            </div>

            <label for="gambar">Lokasi Gambar</label>

            <input
                id="gambar"
                name="gambar"
                maxlength="500"
                required
                placeholder="assets/css/images/pantai.jpg"
                value="<?= bersihkan($formData['gambar'] ?? '') ?>"
            >

            <small>
                Untuk Masjid Agung Banten, gunakan:
                <strong>assets/css/images/masjid_banten.jpg</strong>
            </small>

            <label for="deskripsi">Deskripsi</label>

            <textarea
                id="deskripsi"
                name="deskripsi"
                required
            ><?= bersihkan($formData['deskripsi'] ?? '') ?></textarea>

            <div class="form-actions">

                <button type="submit">
                    <?= $edit ? 'Simpan Perubahan' : 'Tambah Wisata' ?>
                </button>

                <?php if ($edit): ?>
                    <a
                        class="btn batal"
                        href="kelola_wisata.php"
                    >
                        Batal Edit
                    </a>
                <?php endif; ?>

            </div>

        </form>

    </section>

    <!-- DAFTAR WISATA -->

    <section class="panel">

        <h2>
            Daftar Wisata (<?= count($wisataList) ?>)
        </h2>

        <div class="cari-box">

            <label for="cariWisata">
                🔍 Cari Wisata untuk Diedit
            </label>

            <input
                type="search"
                id="cariWisata"
                placeholder="Ketik nama, lokasi, kategori, atau ID wisata..."
                autocomplete="off"
            >

        </div>

        <p class="info-hasil" id="infoHasil">
            Menampilkan <?= count($wisataList) ?> wisata
        </p>

        <div class="table-wrap">

            <table class="tabel-admin">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Gambar</th>
                        <th>Nama Wisata</th>
                        <th>Lokasi</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!$wisataList): ?>
                    <tr>
                        <td colspan="7" class="kosong">
                            Belum ada data wisata.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($wisataList as $w): ?>

                    <?php
                    $gambarTampil = gambarWisataAdmin($w);
                    $urlGambar = urlGambarAdmin($gambarTampil);
                    ?>

                    <tr
                        class="baris-wisata"
                        data-cari="<?= bersihkan(
                            $w['id'] . ' ' .
                            $w['nama'] . ' ' .
                            $w['lokasi'] . ' ' .
                            $w['kategori']
                        ) ?>"
                    >

                        <td data-label="ID">
                            <?= (int)$w['id'] ?>
                        </td>

                        <td data-label="Gambar">

                            <?php if ($urlGambar !== ''): ?>

                                <img
                                    class="foto"
                                    src="<?= bersihkan($urlGambar) ?>"
                                    alt="Foto <?= bersihkan($w['nama']) ?>"
                                    loading="lazy"
                                    onerror="this.style.display='none'"
                                >

                            <?php endif; ?>

                        </td>

                        <td data-label="Nama Wisata">
                            <?= bersihkan($w['nama']) ?>
                        </td>

                        <td data-label="Lokasi">
                            <?= bersihkan($w['lokasi']) ?>
                        </td>

                        <td data-label="Kategori">
                            <?= bersihkan($w['kategori']) ?>
                        </td>

                        <td data-label="Harga">
                            Rp <?= number_format(
                                (float)$w['harga'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td data-label="Aksi">

                            <details class="aksi-detail">

                                <summary>Lihat Aksi</summary>

                                <div class="actions">

                                    <a
                                        class="btn"
                                        href="kelola_wisata.php?edit=<?= (int)$w['id'] ?>#form-wisata"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        action="kelola_wisata.php"
                                        onsubmit="return confirm('Yakin ingin menghapus wisata ini?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= bersihkan(
                                                $_SESSION['csrf_wisata']
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="aksi"
                                            value="hapus"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$w['id'] ?>"
                                        >

                                        <button
                                            class="hapus"
                                            type="submit"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </details>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            <p id="tidakDitemukan">
                Wisata yang kamu cari tidak ditemukan.
            </p>

        </div>

    </section>

</main>

<script>
const inputCariWisata = document.getElementById('cariWisata');
const barisWisata = document.querySelectorAll('.baris-wisata');
const infoHasil = document.getElementById('infoHasil');
const tidakDitemukan = document.getElementById('tidakDitemukan');

function cariWisata() {
    const kata = inputCariWisata.value.toLowerCase().trim();
    let jumlah = 0;

    barisWisata.forEach(function(baris) {
        const data = (baris.dataset.cari || '').toLowerCase();
        const cocok = data.includes(kata);

        baris.style.display = cocok ? '' : 'none';

        if (cocok) {
            jumlah++;
        }
    });

    infoHasil.textContent =
        'Menampilkan ' + jumlah +
        ' dari ' + barisWisata.length + ' wisata';

    tidakDitemukan.style.display =
        barisWisata.length > 0 && jumlah === 0
            ? 'block'
            : 'none';
}

inputCariWisata.addEventListener('input', cariWisata);

// Otomatis format harga ketika mengetik
const inputHarga = document.getElementById('harga');

inputHarga.addEventListener('input', function() {
    let angka = this.value.replace(/\D/g, '');

    if (angka === '') {
        this.value = '';
        return;
    }

    this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
});
</script>

</body>
</html>
