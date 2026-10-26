
<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hanya admin
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

// Token keamanan
if (empty($_SESSION['csrf_admin_pengguna'])) {
    $_SESSION['csrf_admin_pengguna'] = bin2hex(random_bytes(32));
}

function aman($teks) {
    return htmlspecialchars(
        (string)$teks,
        ENT_QUOTES,
        'UTF-8'
    );
}

$pesan = '';
$error = '';

// PROSES UBAH PERAN / HAPUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_admin_pengguna'], $token)
    ) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';

    } else {

        $aksi = $_POST['aksi'] ?? '';
        $id = filter_var(
            $_POST['id'] ?? '',
            FILTER_VALIDATE_INT
        );

        try {

            if (!$id || $id < 1) {
                throw new Exception('ID akun tidak valid.');
            }

            // UBAH PERAN
            if ($aksi === 'ubah_peran') {

                $peran = $_POST['role'] ?? '';

                if (
                    !in_array(
                        $peran,
                        ['admin', 'user'],
                        true
                    )
                ) {
                    throw new Exception(
                        'Peran yang dipilih tidak valid.'
                    );
                }

                if ($id === (int)$_SESSION['user_id']) {
                    throw new Exception(
                        'Peran akun sendiri tidak dapat diubah.'
                    );
                }

                $cek = $pdo->prepare(
                    'SELECT id FROM users WHERE id = ?'
                );

                $cek->execute([$id]);

                if (!$cek->fetch()) {
                    throw new Exception('Akun tidak ditemukan.');
                }

                $ubah = $pdo->prepare(
                    'UPDATE users SET role = ? WHERE id = ?'
                );

                $ubah->execute([$peran, $id]);

                $pesan = 'Peran pengguna berhasil diperbarui.';

            // HAPUS PENGGUNA
            } elseif ($aksi === 'hapus') {

                if ($id === (int)$_SESSION['user_id']) {
                    throw new Exception(
                        'Kamu tidak dapat menghapus akun sendiri.'
                    );
                }

                $hapus = $pdo->prepare(
                    "DELETE FROM users
                     WHERE id = ? AND role = 'user'"
                );

                $hapus->execute([$id]);

                if ($hapus->rowCount() > 0) {
                    $pesan = 'Akun pengguna berhasil dihapus.';
                } else {
                    throw new Exception(
                        'Akun tidak ditemukan atau merupakan admin.'
                    );
                }

            } else {
                throw new Exception('Aksi tidak dikenali.');
            }

        } catch (PDOException $e) {

            $error = 'Terjadi kesalahan database. Akun mungkin masih memiliki data terkait.';

        } catch (Exception $e) {

            $error = $e->getMessage();
        }
    }
}

// PENCARIAN
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {

    $stmt = $pdo->prepare(
        'SELECT id, nama, username, email, role, dibuat_pada
         FROM users
         WHERE nama LIKE ?
            OR username LIKE ?
            OR email LIKE ?
         ORDER BY id DESC'
    );

    $kata = '%' . $cari . '%';

    $stmt->execute([
        $kata,
        $kata,
        $kata
    ]);

} else {

    $stmt = $pdo->query(
        'SELECT id, nama, username, email, role, dibuat_pada
         FROM users
         ORDER BY id DESC'
    );
}

$pengguna = $stmt->fetchAll(PDO::FETCH_ASSOC);

// RINGKASAN
$totalPengguna = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'user'"
)->fetchColumn();

$totalAdmin = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kelola Pengguna - Admin</title>

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

/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 235px;
    height: 100vh;
    padding: 23px 15px;
    background: #e9a3da;
    color: white;
    overflow-y: auto;
}

.sidebar h2 {
    margin: 0 0 25px;
    font-size: 21px;
}

.sidebar a {
    display: block;
    padding: 11px 12px;
    margin-bottom: 6px;
    border-radius: 8px;
    color: white;
    font-size: 13px;
    text-decoration: none;
}

.sidebar a:hover,
.sidebar a.aktif {
    background: #d88ac1;
}

/* KONTEN */

.konten {
    margin-left: 235px;
    padding: 25px;
    min-width: 0;
}

.konten h1 {
    margin: 0 0 5px;
    color: #b7507b;
    font-size: 24px;
}

.konten > p {
    margin: 0 0 15px;
    color: #927b87;
    font-size: 13px;
}

/* RINGKASAN */

.ringkasan {
    display: flex;
    flex-wrap: wrap;
    gap: 13px;
    margin: 18px 0;
}

.kartu {
    flex: 1 1 160px;
    min-width: 0;
    padding: 17px;
    border: 1px solid #f2d5e1;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
    font-size: 13px;
}

.angka {
    margin-top: 7px;
    color: #c85f89;
    font-size: 26px;
    font-weight: bold;
}

/* PANEL */

.panel {
    width: 100%;
    min-width: 0;
    padding: 19px;
    border: 1px solid #f2d5e1;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 14px rgba(160,91,120,.08);
}

.panel h2 {
    margin: 0 0 15px;
    color: #c85f89;
    font-size: 19px;
}

/* PENCARIAN */

.pencarian {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 17px;
}

input[type="text"],
select {
    min-width: 0;
    padding: 9px;
    border: 1px solid #f2d5e1;
    border-radius: 7px;
    background: #fffafd;
    color: #59434f;
    font: inherit;
    font-size: 12px;
}

.pencarian input[type="text"] {
    width: 100%;
    max-width: 350px;
    flex: 1 1 200px;
}

/* TOMBOL */

button,
.tombol {
    display: inline-block;
    width: auto;
    padding: 9px 12px;
    border: none;
    border-radius: 7px;
    background: #e78eaf;
    color: white;
    font: inherit;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
}

button:hover,
.tombol:hover {
    background: #c85f89;
}

.hapus {
    background: #c75b68;
}

.hapus:hover {
    background: #a83e4c;
}

/* PESAN */

.pesan,
.error {
    padding: 11px 13px;
    margin-bottom: 13px;
    border-radius: 8px;
    font-size: 12px;
}

.pesan {
    background: #fff0f5;
    color: #a63f6d;
}

.error {
    background: #ffe7e7;
    color: #a83e4c;
}

/* TABEL DESKTOP */

.tabel-bungkus {
    width: 100%;
    overflow-x: auto;
}

.tabel-pengguna {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.tabel-pengguna th,
.tabel-pengguna td {
    padding: 11px 9px;
    border-bottom: 1px solid #f4e1e9;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.tabel-pengguna th {
    background: #fff0f5;
    color: #b7507b;
    white-space: nowrap;
}

.tabel-pengguna td {
    overflow-wrap: anywhere;
}

/* PERAN */

.lencana {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 15px;
    background: #ffeaf2;
    color: #c85f89;
    font-size: 11px;
}

.lencana-admin {
    background: #fff0d3;
    color: #885900;
}

/* AKSI */

.aksi {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    align-items: center;
}

.aksi form {
    margin: 0;
}

.form-peran {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.form-peran select {
    max-width: 120px;
}

.aksi button {
    padding: 7px 10px;
    font-size: 11px;
}

.kosong {
    padding: 20px;
    text-align: center;
    color: #927b87;
}

/* HP - TANPA GESER SAMPING */

@media (max-width: 750px) {

    .sidebar {
        position: static;
        width: 100%;
        height: auto;
        padding: 14px;
    }

    .sidebar h2 {
        margin-bottom: 12px;
        font-size: 18px;
    }

    .sidebar a {
        display: inline-block;
        margin: 3px;
        padding: 7px 9px;
        font-size: 11px;
    }

    .konten {
        width: 100%;
        margin-left: 0;
        padding: 12px;
    }

    .konten h1 {
        font-size: 21px;
    }

    .konten > p {
        font-size: 12px;
    }

    .ringkasan {
        gap: 9px;
        margin: 13px 0;
    }

    .kartu {
        flex: 1 1 120px;
        padding: 13px;
        font-size: 12px;
    }

    .angka {
        font-size: 23px;
    }

    .panel {
        padding: 12px;
    }

    .panel h2 {
        font-size: 17px;
    }

    .pencarian input[type="text"] {
        flex: 1 1 100%;
        max-width: 100%;
    }

    .tabel-bungkus {
        width: 100%;
        overflow-x: visible;
    }

    .tabel-pengguna {
        display: block;
        width: 100%;
        min-width: 0;
    }

    .tabel-pengguna thead {
        display: none;
    }

    .tabel-pengguna tbody {
        display: block;
        width: 100%;
    }

    .tabel-pengguna tbody tr {
        display: block;
        width: 100%;
        margin-bottom: 11px;
        padding: 11px;
        border: 1px solid #f2d5e1;
        border-radius: 11px;
        background: #fffafd;
    }

    .tabel-pengguna tbody td {
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

    .tabel-pengguna tbody td::before {
        content: attr(data-label);
        flex: 0 0 88px;
        color: #c85f89;
        font-weight: bold;
        text-align: left;
    }

    .tabel-pengguna tbody td:last-child {
        border-bottom: none;
    }

    .tabel-pengguna .aksi {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 7px;
        min-width: 0;
        max-width: 100%;
    }

    .tabel-pengguna .form-peran {
        justify-content: flex-end;
        max-width: 100%;
    }

    .tabel-pengguna .form-peran select {
        max-width: 105px;
        font-size: 11px;
    }

    .tabel-pengguna .aksi button {
        padding: 7px 9px;
        font-size: 11px;
    }

    .tabel-pengguna td[colspan] {
        display: block;
        text-align: center;
    }

    .tabel-pengguna td[colspan]::before {
        display: none;
    }
}

/* HP SANGAT KECIL */

@media (max-width: 360px) {

    .tabel-pengguna tbody td::before {
        flex-basis: 68px;
    }

    .tabel-pengguna tbody td {
        gap: 6px;
        font-size: 11px;
    }

    .tabel-pengguna .form-peran select {
        max-width: 95px;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <h2>Admin Wisata</h2>

    <a href="admin.php">
        Dashboard
    </a>

    <a href="kelola_wisata.php">
        Kelola Wisata
    </a>

    <a href="admin_pesanan.php">
        Kelola Pesanan
    </a>

    <a href="admin_pengguna.php" class="aktif">
        Kelola Pengguna
    </a>

    <a href="beranda.php">
        Lihat Website
    </a>

    <a href="logout.php">
        Keluar
    </a>

</aside>

<!-- KONTEN -->

<main class="konten">

    <h1>Kelola Pengguna</h1>

    <p>
        Lihat dan atur akun yang terdaftar
        di website wisata.
    </p>

    <?php if ($pesan !== ''): ?>

        <div class="pesan">
            <?= aman($pesan) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= aman($error) ?>
        </div>

    <?php endif; ?>

    <!-- RINGKASAN -->

    <section class="ringkasan">

        <div class="kartu">
            Total Pengguna

            <div class="angka">
                <?= $totalPengguna ?>
            </div>
        </div>

        <div class="kartu">
            Total Admin

            <div class="angka">
                <?= $totalAdmin ?>
            </div>
        </div>

    </section>

    <!-- DAFTAR AKUN -->

    <section class="panel">

        <h2>Daftar Akun</h2>

        <form method="GET" class="pencarian">

            <input
                type="text"
                name="cari"
                placeholder="Cari nama, username, atau email..."
                value="<?= aman($cari) ?>"
            >

            <button type="submit">
                Cari
            </button>

            <a href="admin_pengguna.php" class="tombol">
                Reset
            </a>

        </form>

        <div class="tabel-bungkus">

            <table class="tabel-pengguna">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($pengguna) > 0): ?>

                    <?php foreach ($pengguna as $i => $p): ?>

                        <tr>

                            <td data-label="No.">
                                <?= $i + 1 ?>
                            </td>

                            <td data-label="Nama">
                                <?= aman($p['nama']) ?>
                            </td>

                            <td data-label="Username">
                                <?= aman($p['username']) ?>
                            </td>

                            <td data-label="Email">
                                <?= aman($p['email']) ?>
                            </td>

                            <td data-label="Peran">

                                <?php if ($p['role'] === 'admin'): ?>

                                    <span class="lencana lencana-admin">
                                        Admin
                                    </span>

                                <?php else: ?>

                                    <span class="lencana">
                                        Pengguna
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td data-label="Tanggal Daftar">
                                <?= aman($p['dibuat_pada']) ?>
                            </td>

                            <td data-label="Aksi">

                                <div class="aksi">

                                    <?php if (
                                        (int)$p['id'] !==
                                        (int)$_SESSION['user_id']
                                    ): ?>

                                        <!-- UBAH PERAN -->

                                        <form
                                            method="POST"
                                            class="form-peran"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= aman($_SESSION['csrf_admin_pengguna']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$p['id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="ubah_peran"
                                            >

                                            <select name="role">

                                                <option
                                                    value="user"
                                                    <?= $p['role'] === 'user' ? 'selected' : '' ?>
                                                >
                                                    Pengguna
                                                </option>

                                                <option
                                                    value="admin"
                                                    <?= $p['role'] === 'admin' ? 'selected' : '' ?>
                                                >
                                                    Admin
                                                </option>

                                            </select>

                                            <button type="submit">
                                                Simpan
                                            </button>

                                        </form>

                                        <!-- HAPUS AKUN -->

                                        <?php if ($p['role'] === 'user'): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus akun pengguna ini?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= aman($_SESSION['csrf_admin_pengguna']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$p['id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="aksi"
                                                    value="hapus"
                                                >

                                                <button
                                                    type="submit"
                                                    class="hapus"
                                                >
                                                    Hapus
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <span>Akun kamu</span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="kosong">
                            Pengguna tidak ditemukan.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
