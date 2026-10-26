
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

if (empty($_SESSION['csrf_kategori'])) {
    $_SESSION['csrf_kategori'] = bin2hex(random_bytes(32));
}

function amanKategori($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_kategori'], $token)
    ) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {

        $aksi = $_POST['aksi'] ?? '';
        $nama = trim((string)($_POST['nama'] ?? ''));
        $id = (int)($_POST['id'] ?? 0);

        try {

            if ($aksi === 'tambah' || $aksi === 'edit') {

                if ($nama === '') {
                    throw new Exception('Nama kategori wajib diisi.');
                }

                if (mb_strlen($nama) > 50) {
                    throw new Exception(
                        'Nama kategori maksimal 50 karakter.'
                    );
                }

                if ($aksi === 'tambah') {

                    $cek = $pdo->prepare(
                        'SELECT COUNT(*) FROM kategori WHERE nama = ?'
                    );
                    $cek->execute([$nama]);

                    if ((int)$cek->fetchColumn() > 0) {
                        throw new Exception('Kategori tersebut sudah ada.');
                    }

                    $stmt = $pdo->prepare(
                        'INSERT INTO kategori (nama) VALUES (?)'
                    );
                    $stmt->execute([$nama]);

                    $pesan = 'Kategori berhasil ditambahkan.';

                } else {

                    if ($id < 1) {
                        throw new Exception('ID kategori tidak valid.');
                    }

                    $cekId = $pdo->prepare(
                        'SELECT nama FROM kategori WHERE id = ?'
                    );
                    $cekId->execute([$id]);

                    $lama = $cekId->fetch(PDO::FETCH_ASSOC);

                    if (!$lama) {
                        throw new Exception('Kategori tidak ditemukan.');
                    }

                    $cek = $pdo->prepare(
                        'SELECT COUNT(*) FROM kategori
                         WHERE nama = ? AND id != ?'
                    );
                    $cek->execute([$nama, $id]);

                    if ((int)$cek->fetchColumn() > 0) {
                        throw new Exception(
                            'Nama kategori sudah digunakan.'
                        );
                    }

                    $stmt = $pdo->prepare(
                        'UPDATE kategori SET nama = ? WHERE id = ?'
                    );
                    $stmt->execute([$nama, $id]);

                    $pesan = 'Kategori berhasil diperbarui.';
                }

            } elseif ($aksi === 'hapus') {

                if ($id < 1) {
                    throw new Exception('ID kategori tidak valid.');
                }

                $cek = $pdo->prepare(
                    'SELECT nama FROM kategori WHERE id = ?'
                );
                $cek->execute([$id]);

                $kategori = $cek->fetch(PDO::FETCH_ASSOC);

                if (!$kategori) {
                    throw new Exception('Kategori tidak ditemukan.');
                }

                $cekWisata = $pdo->prepare(
                    'SELECT COUNT(*) FROM wisata WHERE kategori = ?'
                );
                $cekWisata->execute([$kategori['nama']]);

                if ((int)$cekWisata->fetchColumn() > 0) {
                    throw new Exception(
                        'Kategori masih digunakan oleh data wisata. ' .
                        'Ubah kategori wisata terlebih dahulu.'
                    );
                }

                $stmt = $pdo->prepare(
                    'DELETE FROM kategori WHERE id = ?'
                );
                $stmt->execute([$id]);

                $pesan = 'Kategori berhasil dihapus.';

            } else {
                throw new Exception('Aksi tidak dikenal.');
            }

        } catch (PDOException $e) {
            $error = 'Terjadi masalah pada database kategori.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

try {
    $daftar = $pdo->query(
        'SELECT id, nama FROM kategori ORDER BY id DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar = [];
    $error = 'Gagal mengambil daftar kategori.';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Kelola Kategori | JelajahBanten</title>

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
    color: #c85f89;
    margin: 0 0 8px;
    overflow-wrap: anywhere;
}

.subjudul {
    color: #927b87;
    margin-bottom: 23px;
    font-size: 14px;
}

.panel {
    min-width: 0;
    width: 100%;
    background: white;
    padding: 22px;
    border: 1px solid #f2d5e1;
    border-radius: 14px;
    margin-bottom: 20px;
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
    background: #fff;
    outline: none;
}

input:focus {
    border-color: #e78eaf;
    box-shadow: 0 0 0 3px #e78eaf25;
}

.form-tambah {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.form-tambah input {
    flex: 1 1 220px;
}

button {
    border: 0;
    border-radius: 9px;
    padding: 11px 15px;
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
    font-weight: bold;
    font-size: 13px;
    color: #b7507b;
}

.jumlah-data {
    font-size: 12px;
    color: #927b87;
    margin: 10px 0;
}

.tabel-bungkus {
    width: 100%;
    min-width: 0;
}

.tabel-kategori {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.tabel-kategori th,
.tabel-kategori td {
    padding: 13px 10px;
    text-align: left;
    border-bottom: 1px solid #f4e1e9;
    vertical-align: top;
    overflow-wrap: anywhere;
}

.tabel-kategori th {
    background: #fff0f5;
    color: #b7507b;
    font-size: 13px;
}

.tabel-kategori td {
    font-size: 13px;
}

.tabel-kategori th:nth-child(1) {
    width: 65px;
}

.tabel-kategori th:nth-child(3) {
    width: 270px;
}

.nama-data {
    font-weight: bold;
    color: #59434f;
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
    margin-top: 12px;
    padding: 12px;
    border: 1px solid #f2d5e1;
    border-radius: 9px;
    background: #fffafd;
    min-width: 0;
}

.form-edit {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-edit input {
    width: 100%;
}

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
        font-size: 17px;
        margin-bottom: 12px;
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

    .tabel-kategori,
    .tabel-kategori tbody {
        display: block;
        width: 100%;
    }

    .tabel-kategori thead {
        display: none;
    }

    .tabel-kategori tr.baris-data {
        display: block;
        width: 100%;
        margin-bottom: 12px;
        padding: 12px;
        border: 1px solid #f2d5e1;
        border-radius: 11px;
        background: #fffafd;
    }

    .tabel-kategori tr.baris-data td {
        display: block;
        width: 100%;
        padding: 7px 0;
        border-bottom: 1px solid #f5e5ec;
    }

    .tabel-kategori tr.baris-data td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 4px;
        color: #c85f89;
        font-size: 11px;
        font-weight: bold;
    }

    .tabel-kategori tr.baris-data td:last-child {
        border-bottom: 0;
    }

    .aksi-detail summary {
        width: 100%;
        justify-content: space-between;
    }

    .tabel-kategori tr.baris-kosong {
        display: block;
    }

    .tabel-kategori tr.baris-kosong td {
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
        <a href="admin_kategori.php" class="aktif">🗂️ Kelola Kategori</a>
        <a href="admin_wilayah.php">📍 Kelola Wilayah</a>
        <a href="beranda.php">🌐 Lihat Website</a>
        <a href="logout.php">↪ Keluar</a>
    </div>
</aside>

<main class="konten">

    <h1>Kelola Kategori Wisata</h1>

    <p class="subjudul">
        Tambahkan, cari, edit, dan hapus kategori wisata.
    </p>

    <?php if ($pesan !== ''): ?>
        <div class="pesan">
            <?= amanKategori($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= amanKategori($error) ?>
        </div>
    <?php endif; ?>

    <section class="panel">

        <h2>Tambah Kategori</h2>

        <form method="POST" class="form-tambah">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= amanKategori($_SESSION['csrf_kategori']) ?>"
            >

            <input type="hidden" name="aksi" value="tambah">

            <input
                type="text"
                name="nama"
                maxlength="50"
                placeholder="Contoh: Pantai"
                aria-label="Nama kategori baru"
                required
            >

            <button type="submit">
                + Tambah Kategori
            </button>

        </form>

    </section>

    <section class="panel">

        <h2>Daftar Kategori</h2>

        <div class="cari-box">
            <label for="cariKategori">🔍 Cari Kategori</label>

            <input
                type="search"
                id="cariKategori"
                placeholder="Ketik nama atau ID kategori..."
                autocomplete="off"
            >
        </div>

        <p class="jumlah-data" id="jumlahKategori">
            Total <?= count($daftar) ?> kategori
        </p>

        <div class="tabel-bungkus">

            <table class="tabel-kategori">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($daftar): ?>

                    <?php foreach ($daftar as $k): ?>

                        <tr
                            class="baris-data"
                            data-cari="<?= amanKategori(
                                $k['id'] . ' ' . $k['nama']
                            ) ?>"
                        >

                            <td data-label="ID">
                                <?= (int)$k['id'] ?>
                            </td>

                            <td data-label="Nama Kategori">
                                <span class="nama-data">
                                    <?= amanKategori($k['nama']) ?>
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
                                                value="<?= amanKategori(
                                                    $_SESSION['csrf_kategori']
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$k['id'] ?>"
                                            >

                                            <input
                                                type="text"
                                                name="nama"
                                                maxlength="50"
                                                value="<?= amanKategori(
                                                    $k['nama']
                                                ) ?>"
                                                aria-label="Edit nama kategori"
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
                                            onsubmit="return confirm('Yakin ingin menghapus kategori ini?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= amanKategori(
                                                    $_SESSION['csrf_kategori']
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$k['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="aksi"
                                                value="hapus"
                                                class="hapus"
                                            >
                                                Hapus Kategori
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
                            Belum ada kategori.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

            <p id="tidakDitemukan">
                Kategori yang dicari tidak ditemukan.
            </p>

        </div>

    </section>

</main>

<script>
const inputKategori = document.getElementById('cariKategori');
const barisKategori = document.querySelectorAll(
    '.tabel-kategori .baris-data'
);
const jumlahKategori = document.getElementById('jumlahKategori');
const tidakDitemukanKategori = document.getElementById(
    'tidakDitemukan'
);

function filterKategori() {
    const kata = inputKategori.value.toLowerCase().trim();
    let terlihat = 0;

    barisKategori.forEach(function(baris) {
        const teks = (baris.dataset.cari || '').toLowerCase();
        const cocok = teks.includes(kata);

        baris.style.display = cocok ? '' : 'none';

        if (cocok) {
            terlihat++;
        }
    });

    jumlahKategori.textContent =
        'Menampilkan ' + terlihat +
        ' dari ' + barisKategori.length + ' kategori';

    tidakDitemukanKategori.style.display =
        barisKategori.length > 0 && terlihat === 0
        ? 'block'
        : 'none';
}

inputKategori.addEventListener('input', filterKategori);
</script>

</body>
</html>
