
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

$pesan = '';
$error = '';

if (empty($_SESSION['csrf_wilayah'])) {
    $_SESSION['csrf_wilayah'] = bin2hex(random_bytes(32));
}

function amanWilayah($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Deteksi nama kolom wilayah sesuai database.
$kolom = $pdo->query(
    'SHOW COLUMNS FROM wilayah'
)->fetchAll(PDO::FETCH_COLUMN);

$idKolom = in_array('id', $kolom, true) ? 'id' : null;
$namaKolom = null;

foreach (['nama', 'nama_wilayah', 'wilayah'] as $calon) {
    if (in_array($calon, $kolom, true)) {
        $namaKolom = $calon;
        break;
    }
}

if (!$idKolom || !$namaKolom) {
    exit(
        'Struktur tabel wilayah belum cocok. ' .
        'Diperlukan kolom id dan nama wilayah.'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_wilayah'], $token)
    ) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {

        $aksi = $_POST['aksi'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim((string)($_POST['nama'] ?? ''));

        try {

            if ($aksi === 'tambah' || $aksi === 'edit') {

                if ($nama === '') {
                    throw new Exception('Nama wilayah wajib diisi.');
                }

                if (mb_strlen($nama) > 150) {
                    throw new Exception(
                        'Nama wilayah maksimal 150 karakter.'
                    );
                }

                if ($aksi === 'tambah') {

                    $cek = $pdo->prepare(
                        "SELECT COUNT(*) FROM wilayah
                         WHERE `$namaKolom` = ?"
                    );
                    $cek->execute([$nama]);

                    if ((int)$cek->fetchColumn() > 0) {
                        throw new Exception('Wilayah sudah terdaftar.');
                    }

                    $stmt = $pdo->prepare(
                        "INSERT INTO wilayah (`$namaKolom`) VALUES (?)"
                    );
                    $stmt->execute([$nama]);

                    $pesan = 'Wilayah berhasil ditambahkan.';

                } else {

                    if ($id < 1) {
                        throw new Exception('ID wilayah tidak valid.');
                    }

                    $cekId = $pdo->prepare(
                        'SELECT COUNT(*) FROM wilayah WHERE id = ?'
                    );
                    $cekId->execute([$id]);

                    if ((int)$cekId->fetchColumn() === 0) {
                        throw new Exception('Wilayah tidak ditemukan.');
                    }

                    $cek = $pdo->prepare(
                        "SELECT COUNT(*) FROM wilayah
                         WHERE `$namaKolom` = ? AND id != ?"
                    );
                    $cek->execute([$nama, $id]);

                    if ((int)$cek->fetchColumn() > 0) {
                        throw new Exception(
                            'Nama wilayah sudah digunakan.'
                        );
                    }

                    $stmt = $pdo->prepare(
                        "UPDATE wilayah
                         SET `$namaKolom` = ?
                         WHERE id = ?"
                    );
                    $stmt->execute([$nama, $id]);

                    $pesan = 'Wilayah berhasil diperbarui.';
                }

            } elseif ($aksi === 'hapus') {

                if ($id < 1) {
                    throw new Exception('ID wilayah tidak valid.');
                }

                $cek = $pdo->prepare(
                    "SELECT `$namaKolom`
                     FROM wilayah WHERE id = ?"
                );
                $cek->execute([$id]);

                $data = $cek->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    throw new Exception('Wilayah tidak ditemukan.');
                }

                $kolomWisata = $pdo->query(
                    'SHOW COLUMNS FROM wisata'
                )->fetchAll(PDO::FETCH_COLUMN);

                if (in_array('wilayah_id', $kolomWisata, true)) {

                    $cekWisata = $pdo->prepare(
                        'SELECT COUNT(*) FROM wisata
                         WHERE wilayah_id = ?'
                    );
                    $cekWisata->execute([$id]);

                    if ((int)$cekWisata->fetchColumn() > 0) {
                        throw new Exception(
                            'Wilayah masih digunakan oleh data wisata.'
                        );
                    }
                }

                $hapus = $pdo->prepare(
                    'DELETE FROM wilayah WHERE id = ?'
                );
                $hapus->execute([$id]);

                $pesan = 'Wilayah berhasil dihapus.';

            } else {
                throw new Exception('Aksi tidak dikenal.');
            }

        } catch (PDOException $e) {
            $error = 'Gagal memproses wilayah. Periksa database.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

try {
    $daftar = $pdo->query(
        "SELECT * FROM wilayah ORDER BY id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar = [];
    $error = 'Gagal mengambil daftar wilayah.';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Kelola Wilayah | JelajahBanten</title>

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
    background: #fff7fb;
    color: #59434f;
    line-height: 1.5;
}

button, input {
    font: inherit;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 230px;
    padding: 22px 14px;
    background: #e9a3da;
    overflow-y: auto;
}

.sidebar h2 {
    margin: 0 0 20px;
    color: white;
    font-size: 20px;
}

.sidebar a {
    display: block;
    padding: 12px;
    margin-bottom: 6px;
    border-radius: 9px;
    color: white;
    text-decoration: none;
    font-size: 13px;
}

.sidebar a:hover,
.sidebar a.aktif {
    background: #d88ac1;
}

.konten {
    margin-left: 230px;
    padding: 28px;
    min-width: 0;
}

.konten h1 {
    margin: 0 0 8px;
    color: #c85f89;
    overflow-wrap: anywhere;
}

.subjudul {
    margin-bottom: 23px;
    color: #927b87;
    font-size: 14px;
}

.panel {
    width: 100%;
    min-width: 0;
    padding: 22px;
    margin-bottom: 20px;
    border: 1px solid #f2d5e1;
    border-radius: 14px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.panel h2 {
    margin: 0 0 17px;
    color: #b7507b;
    font-size: 19px;
}

input[type="text"],
input[type="search"] {
    display: block;
    width: 100%;
    min-width: 0;
    padding: 12px;
    border: 1px solid #e9c6d5;
    border-radius: 9px;
    background: white;
    outline: none;
}

input:focus {
    border-color: #e78eaf;
    box-shadow: 0 0 0 3px #e78eaf25;
}

.form-tambah {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.form-tambah input {
    flex: 1 1 220px;
}

button {
    padding: 11px 15px;
    border: none;
    border-radius: 9px;
    background: #e78eaf;
    color: white;
    font-size: 13px;
    cursor: pointer;
}

button:hover {
    background: #c85f89;
}

.hapus {
    background: #d75c74;
}

.hapus:hover {
    background: #bd3d57;
}

.pesan, .error {
    padding: 13px;
    margin-bottom: 16px;
    border-radius: 9px;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.pesan {
    background: #e7f8ed;
    color: #21653e;
}

.error {
    background: #ffe6eb;
    color: #a52e49;
}

.cari-box {
    margin-bottom: 16px;
}

.cari-box label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: bold;
    color: #b7507b;
}

.jumlah-data {
    margin: 10px 0;
    color: #927b87;
    font-size: 12px;
}

.tabel-bungkus {
    width: 100%;
    min-width: 0;
}

.tabel-wilayah {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.tabel-wilayah th,
.tabel-wilayah td {
    padding: 13px 10px;
    border-bottom: 1px solid #f4e1e9;
    text-align: left;
    vertical-align: top;
    overflow-wrap: anywhere;
}

.tabel-wilayah th {
    background: #fff0f5;
    color: #b7507b;
    font-size: 13px;
}

.tabel-wilayah td {
    font-size: 13px;
}

.tabel-wilayah th:nth-child(1) {
    width: 65px;
}

.tabel-wilayah th:nth-child(3) {
    width: 270px;
}

.nama-data {
    color: #59434f;
    font-weight: bold;
}

.aksi-detail {
    width: 100%;
    min-width: 0;
}

.aksi-detail summary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
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

.isi-aksi {
    min-width: 0;
    margin-top: 12px;
    padding: 12px;
    border: 1px solid #f2d5e1;
    border-radius: 9px;
    background: #fffafd;
}

.form-edit {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-edit input,
.form-edit button,
.form-hapus button {
    width: 100%;
}

.form-hapus {
    margin-top: 9px;
}

.kosong {
    text-align: center;
    color: #927b87;
}

#tidakDitemukan {
    display: none;
    padding: 18px;
    color: #927b87;
    text-align: center;
    font-size: 13px;
}

/* TABLET */

@media (max-width: 950px) {

    .sidebar {
        width: 190px;
    }

    .konten {
        margin-left: 190px;
        padding: 18px;
    }
}

/* HP: TABEL MENJADI KARTU */

@media (max-width: 750px) {

    .sidebar {
        position: static;
        width: 100%;
        padding: 14px;
        overflow: visible;
    }

    .sidebar h2 {
        margin-bottom: 12px;
        font-size: 17px;
    }

    .sidebar-menu {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }

    .sidebar a {
        margin: 0;
        padding: 10px;
        font-size: 11px;
        overflow-wrap: anywhere;
    }

    .konten {
        width: 100%;
        margin-left: 0;
        padding: 13px;
    }

    .konten h1 {
        font-size: 23px;
    }

    .panel {
        padding: 13px;
    }

    .panel h2 {
        font-size: 17px;
    }

    .form-tambah {
        flex-direction: column;
        align-items: stretch;
    }

    .form-tambah input {
        flex: initial;
        width: 100%;
    }

    .form-tambah button {
        width: 100%;
    }

    .tabel-wilayah,
    .tabel-wilayah tbody {
        display: block;
        width: 100%;
    }

    .tabel-wilayah thead {
        display: none;
    }

    .tabel-wilayah tr.baris-data {
        display: block;
        width: 100%;
        margin-bottom: 12px;
        padding: 12px;
        border: 1px solid #f2d5e1;
        border-radius: 11px;
        background: #fffafd;
    }

    .tabel-wilayah tr.baris-data td {
        display: block;
        width: 100%;
        padding: 7px 0;
        border-bottom: 1px solid #f5e5ec;
    }

    .tabel-wilayah tr.baris-data td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 4px;
        color: #c85f89;
        font-size: 11px;
        font-weight: bold;
    }

    .tabel-wilayah tr.baris-data td:last-child {
        border-bottom: none;
    }

    .aksi-detail summary {
        width: 100%;
        justify-content: space-between;
    }

    .tabel-wilayah tr.baris-kosong {
        display: block;
    }

    .tabel-wilayah tr.baris-kosong td {
        display: block;
        width: 100%;
    }
}

@media (max-width: 360px) {
    .sidebar-menu {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<aside class="sidebar">

    <h2>💐 Admin JelajahBanten</h2>

    <div class="sidebar-menu">

        <a href="admin.php">🏠 Dashboard</a>
        <a href="kelola_wisata.php">🏝️ Kelola Wisata</a>
        <a href="admin_pesanan.php">🛒 Kelola Pesanan</a>
        <a href="admin_pengguna.php">👥 Kelola Pengguna</a>
        <a href="admin_kategori.php">🗂️ Kelola Kategori</a>
        <a href="admin_wilayah.php" class="aktif">📍 Kelola Wilayah</a>
        <a href="beranda.php">🌐 Lihat Website</a>
        <a href="logout.php">↪ Keluar</a>

    </div>

</aside>

<main class="konten">

    <h1>Kelola Wilayah Wisata</h1>

    <p class="subjudul">
        Tambahkan, cari, edit, dan hapus wilayah wisata.
    </p>

    <?php if ($pesan !== ''): ?>
        <div class="pesan">
            <?= amanWilayah($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= amanWilayah($error) ?>
        </div>
    <?php endif; ?>

    <section class="panel">

        <h2>Tambah Wilayah</h2>

        <form method="POST" class="form-tambah">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= amanWilayah($_SESSION['csrf_wilayah']) ?>"
            >

            <input type="hidden" name="aksi" value="tambah">

            <input
                type="text"
                name="nama"
                maxlength="150"
                placeholder="Contoh: Kabupaten Tangerang"
                aria-label="Nama wilayah baru"
                required
            >

            <button type="submit">
                + Tambah Wilayah
            </button>

        </form>

    </section>

    <section class="panel">

        <h2>Daftar Wilayah</h2>

        <div class="cari-box">

            <label for="cariWilayah">
                🔍 Cari Wilayah
            </label>

            <input
                type="search"
                id="cariWilayah"
                placeholder="Ketik nama atau ID wilayah..."
                autocomplete="off"
            >

        </div>

        <p class="jumlah-data" id="jumlahWilayah">
            Total <?= count($daftar) ?> wilayah
        </p>

        <div class="tabel-bungkus">

            <table class="tabel-wilayah">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Wilayah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($daftar): ?>

                    <?php foreach ($daftar as $w): ?>

                        <tr
                            class="baris-data"
                            data-cari="<?= amanWilayah(
                                $w['id'] . ' ' . $w[$namaKolom]
                            ) ?>"
                        >

                            <td data-label="ID">
                                <?= (int)$w['id'] ?>
                            </td>

                            <td data-label="Nama Wilayah">

                                <span class="nama-data">
                                    <?= amanWilayah($w[$namaKolom]) ?>
                                </span>

                            </td>

                            <td data-label="Aksi">

                                <details class="aksi-detail">

                                    <summary>Lihat Aksi</summary>

                                    <div class="isi-aksi">

                                        <form
                                            method="POST"
                                            class="form-edit"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= amanWilayah(
                                                    $_SESSION['csrf_wilayah']
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$w['id'] ?>"
                                            >

                                            <input
                                                type="text"
                                                name="nama"
                                                maxlength="150"
                                                value="<?= amanWilayah(
                                                    $w[$namaKolom]
                                                ) ?>"
                                                aria-label="Edit nama wilayah"
                                                required
                                            >

                                            <button
                                                type="submit"
                                                name="aksi"
                                                value="edit"
                                            >
                                                Simpan Edit
                                            </button>

                                        </form>

                                        <form
                                            method="POST"
                                            class="form-hapus"
                                            onsubmit="return confirm('Yakin ingin menghapus wilayah ini?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= amanWilayah(
                                                    $_SESSION['csrf_wilayah']
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$w['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="aksi"
                                                value="hapus"
                                                class="hapus"
                                            >
                                                Hapus Wilayah
                                            </button>

                                        </form>

                                    </div>

                                </details>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr class="baris-kosong">
                        <td colspan="3" class="kosong">
                            Belum ada data wilayah.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

            <p id="tidakDitemukan">
                Wilayah yang dicari tidak ditemukan.
            </p>

        </div>

    </section>

</main>

<script>
const inputWilayah = document.getElementById('cariWilayah');
const barisWilayah = document.querySelectorAll(
    '.tabel-wilayah .baris-data'
);
const jumlahWilayah = document.getElementById('jumlahWilayah');
const tidakDitemukanWilayah = document.getElementById(
    'tidakDitemukan'
);

function filterWilayah() {
    const kata = inputWilayah.value.toLowerCase().trim();
    let terlihat = 0;

    barisWilayah.forEach(function(baris) {
        const teks = (baris.dataset.cari || '').toLowerCase();
        const cocok = teks.includes(kata);

        baris.style.display = cocok ? '' : 'none';

        if (cocok) {
            terlihat++;
        }
    });

    jumlahWilayah.textContent =
        'Menampilkan ' + terlihat +
        ' dari ' + barisWilayah.length + ' wilayah';

    tidakDitemukanWilayah.style.display =
        barisWilayah.length > 0 && terlihat === 0
        ? 'block'
        : 'none';
}

inputWilayah.addEventListener('input', filterWilayah);
</script>

</body>
</html>
