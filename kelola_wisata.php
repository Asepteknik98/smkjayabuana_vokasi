
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya admin yang boleh mengakses
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

// Token keamanan
if (empty($_SESSION['csrf_wisata'])) {
    $_SESSION['csrf_wisata'] = bin2hex(random_bytes(32));
}

function bersihkan($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

$error = '';

// PROSES TAMBAH, EDIT, HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_wisata'], $token)
    ) {
        exit('Permintaan tidak valid. Muat ulang halaman.');
    }

    $aksi = $_POST['aksi'] ?? '';

    try {

        // HAPUS WISATA
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

        // SIMPAN WISATA
        if ($aksi === 'simpan') {

            $idInput = $_POST['id'] ?? '';
            $id = $idInput === ''
                ? null
                : filter_var($idInput, FILTER_VALIDATE_INT);

            if (
                $idInput !== '' &&
                (!$id || $id < 1)
            ) {
                throw new Exception('ID wisata tidak valid.');
            }

            $nama = trim($_POST['nama'] ?? '');
            $lokasi = trim($_POST['lokasi'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $harga = $_POST['harga'] ?? '';
            $gambar = trim($_POST['gambar'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if (
                $nama === '' ||
                $lokasi === '' ||
                $kategori === '' ||
                $harga === '' ||
                $gambar === '' ||
                $deskripsi === ''
            ) {
                throw new Exception('Semua kolom wajib diisi.');
            }

            if (
                !is_numeric($harga) ||
                (float)$harga < 0
            ) {
                throw new Exception(
                    'Harga harus berupa angka nol atau lebih.'
                );
            }

            if (
                strlen($nama) > 150 ||
                strlen($lokasi) > 150 ||
                strlen($kategori) > 50 ||
                strlen($gambar) > 500
            ) {
                throw new Exception(
                    'Ada teks yang melebihi batas panjang.'
                );
            }

            // EDIT
            if ($id !== null) {

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

                header('Location: kelola_wisata.php?pesan=edit');
                exit;
            }

            // TAMBAH
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

            header('Location: kelola_wisata.php?pesan=tambah');
            exit;
        }

        throw new Exception('Aksi tidak dikenali.');

    } catch (Throwable $e) {

        if ($e instanceof PDOException) {
            $error = 'Data gagal diproses. Wisata mungkin masih digunakan dalam pesanan.';
        } else {
            $error = $e->getMessage();
        }
    }
}

// DATA EDIT
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
        $edit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// DAFTAR WISATA
$stmt = $pdo->query(
    'SELECT * FROM wisata ORDER BY id DESC'
);

$wisataList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesan = [
    'tambah' => 'Wisata berhasil ditambahkan.',
    'edit' => 'Data wisata berhasil diperbarui.',
    'hapus' => 'Data wisata berhasil dihapus.'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola Wisata - Admin</title>

<style>

/* DASAR */

* {
    box-sizing: border-box;
}

html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    font-size: 14px;
    line-height: 1.5;
    background: #fff7fb;
    color: #59434f;
}

a {
    text-decoration: none;
}

/* HEADER */

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
    gap: 9px 16px;
}

header a {
    color: white;
    font-size: 13px;
}

/* KONTEN */

main {
    width: calc(100% - 32px);
    max-width: 1100px;
    margin: 22px auto;
}

.panel {
    padding: 20px;
    margin-bottom: 20px;
    background: white;
    border: 1px solid #f2d5e1;
    border-radius: 13px;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.panel h2 {
    margin: 0 0 15px;
    color: #c85f89;
    font-size: 19px;
}

/* FORM */

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
    padding: 10px;
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

/* TOMBOL */

button,
.btn {
    display: inline-block;
    width: auto;
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

/* PESAN */

.success,
.error {
    padding: 11px 13px;
    margin-bottom: 15px;
    border-radius: 9px;
    font-size: 12px;
}

.success {
    background: #fff0f5;
    color: #a63f6d;
}

.error {
    background: #ffe7e7;
    color: #a83e4c;
}

/* TABEL DESKTOP */

.table-wrap {
    width: 100%;
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
    object-fit: cover;
    border-radius: 7px;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.actions form {
    margin: 0;
}

.actions .btn,
.actions button {
    padding: 7px 10px;
    font-size: 11px;
}

.kosong {
    padding: 18px;
    text-align: center;
    color: #927b87;
}

/* TABLET */

@media (max-width: 850px) {

    .form-grid {
        grid-template-columns: 1fr;
    }
}

/* HP - TABEL MENJADI KARTU */

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
        padding: 9px;
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

    .tabel-admin tbody tr {
        display: block;
        width: 100%;
        margin-bottom: 11px;
        padding: 11px;
        border: 1px solid #f2d5e1;
        border-radius: 11px;
        background: #fffafd;
    }

    .tabel-admin tbody td {
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

    .tabel-admin tbody td::before {
        content: attr(data-label);
        flex: 0 0 83px;
        color: #c85f89;
        font-weight: bold;
        text-align: left;
    }

    .tabel-admin tbody td:last-child {
        border-bottom: none;
    }

    .tabel-admin .foto {
        width: 85px;
        height: 60px;
    }

    .tabel-admin .actions {
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

/* HP KECIL */

@media (max-width: 360px) {

    .tabel-admin tbody td::before {
        flex-basis: 70px;
    }

    .tabel-admin tbody td {
        font-size: 11px;
    }
}

</style>
</head>

<body>

<header>
    <h2>Panel Admin — Kelola Wisata</h2>

    <p>
        Halo,
        <?= bersihkan($_SESSION['nama'] ?? 'Administrator') ?>!
    </p>

    <nav>
        <a href="admin.php">Dashboard Admin</a>
        <a href="beranda.php">Lihat Website</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>

    <?php if ($error !== ''): ?>
        <p class="error">
            <?= bersihkan($error) ?>
        </p>
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

            <input
                type="hidden"
                name="aksi"
                value="simpan"
            >

            <input
                type="hidden"
                name="id"
                value="<?= bersihkan($edit['id'] ?? '') ?>"
            >

            <div class="form-grid">

                <div>
                    <label>Nama Wisata</label>
                    <input
                        name="nama"
                        maxlength="150"
                        required
                        value="<?= bersihkan($edit['nama'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Lokasi</label>
                    <input
                        name="lokasi"
                        maxlength="150"
                        required
                        value="<?= bersihkan($edit['lokasi'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Kategori</label>
                    <input
                        name="kategori"
                        maxlength="50"
                        required
                        placeholder="Pantai, Alam, Sejarah"
                        value="<?= bersihkan($edit['kategori'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label>Harga Tiket (Rp)</label>
                    <input
                        type="number"
                        name="harga"
                        min="0"
                        step="0.01"
                        required
                        value="<?= bersihkan($edit['harga'] ?? '') ?>"
                    >
                </div>

            </div>

            <label>Lokasi Gambar</label>
            <input
                name="gambar"
                maxlength="500"
                required
                placeholder="assets/css/images/pantai.jpg"
                value="<?= bersihkan($edit['gambar'] ?? '') ?>"
            >

            <small>
                Masukkan alamat atau lokasi file gambar wisata.
            </small>

            <label>Deskripsi</label>
            <textarea
                name="deskripsi"
                required
            ><?= bersihkan($edit['deskripsi'] ?? '') ?></textarea>

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

                    <tr>

                        <td data-label="ID">
                            <?= (int)$w['id'] ?>
                        </td>

                        <td data-label="Gambar">

                            <?php if ($w['gambar'] !== ''): ?>

                                <img
                                    class="foto"
                                    src="<?= bersihkan($w['gambar']) ?>"
                                    alt="Foto wisata"
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
                                        value="<?= bersihkan($_SESSION['csrf_wisata']) ?>"
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

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
