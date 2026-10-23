
<?php
require_once 'config.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
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
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Cari struktur tabel wilayah agar tidak menebak nama kolom
$kolom = $pdo->query('SHOW COLUMNS FROM wilayah')
    ->fetchAll(PDO::FETCH_COLUMN);

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
        'Tabel perlu kolom id dan kolom nama wilayah.'
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
        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');

        try {
            if ($aksi === 'tambah' || $aksi === 'edit') {
                if ($nama === '') {
                    throw new Exception('Nama wilayah wajib diisi.');
                }

                if (mb_strlen($nama) > 150) {
                    throw new Exception('Nama wilayah maksimal 150 karakter.');
                }

                if ($aksi === 'tambah') {
                    $cek = $pdo->prepare(
                        "SELECT COUNT(*) FROM wilayah
                         WHERE `$namaKolom` = ?"
                    );
                    $cek->execute([$nama]);

                    if ((int) $cek->fetchColumn() > 0) {
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

                    $cek = $pdo->prepare(
                        "SELECT COUNT(*) FROM wilayah
                         WHERE `$namaKolom` = ? AND id != ?"
                    );
                    $cek->execute([$nama, $id]);

                    if ((int) $cek->fetchColumn() > 0) {
                        throw new Exception('Nama wilayah sudah digunakan.');
                    }

                    $stmt = $pdo->prepare(
                        "UPDATE wilayah SET `$namaKolom` = ? WHERE id = ?"
                    );
                    $stmt->execute([$nama, $id]);
                    $pesan = 'Wilayah berhasil diperbarui.';
                }
            } elseif ($aksi === 'hapus') {
                if ($id < 1) {
                    throw new Exception('ID wilayah tidak valid.');
                }

                // Ambil nama wilayah sebelum menghapus
                $cek = $pdo->prepare(
                    "SELECT `$namaKolom` FROM wilayah WHERE id = ?"
                );
                $cek->execute([$id]);
                $data = $cek->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    throw new Exception('Wilayah tidak ditemukan.');
                }

                // Cek apakah kolom wilayah tersedia di tabel wisata
                $kolomWisata = $pdo->query('SHOW COLUMNS FROM wisata')
                    ->fetchAll(PDO::FETCH_COLUMN);

                if (in_array('wilayah_id', $kolomWisata, true)) {
                    $cekWisata = $pdo->prepare(
                        'SELECT COUNT(*) FROM wisata WHERE wilayah_id = ?'
                    );
                    $cekWisata->execute([$id]);

                    if ((int) $cekWisata->fetchColumn() > 0) {
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
            $error = 'Gagal memproses data wilayah. Periksa struktur tabel dan hubungan database.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$daftar = $pdo->query(
    "SELECT * FROM wilayah ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Wilayah</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f3f5f9;
    color: #253047;
}
.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 230px;
    padding: 25px 15px;
    background: #173b35;
}
.sidebar h2 { color: white; }
.sidebar a {
    display: block;
    padding: 13px;
    margin-bottom: 7px;
    color: white;
    text-decoration: none;
    border-radius: 7px;
}
.sidebar a:hover, .sidebar a.aktif { background: #2b6558; }
.konten { margin-left: 230px; padding: 30px; }
.panel {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px #00000008;
}
input {
    padding: 11px;
    border: 1px solid #d4dae3;
    border-radius: 6px;
}
input[type="text"] { width: 220px; }
button {
    padding: 10px 13px;
    border: none;
    border-radius: 6px;
    background: #216653;
    color: white;
    cursor: pointer;
}
.hapus { background: #c0392b; }
.pesan, .error {
    padding: 13px;
    border-radius: 7px;
    margin-bottom: 15px;
}
.pesan { background: #dff5e8; color: #185b39; }
.error { background: #fde7e7; color: #9b2525; }
table { width: 100%; border-collapse: collapse; }
th, td {
    padding: 13px 10px;
    border-bottom: 1px solid #e8ebf0;
    text-align: left;
}
th { background: #f7f9fb; }
.bungkus { overflow-x: auto; }
.form-edit { display: flex; gap: 7px; flex-wrap: wrap; }
@media(max-width:750px) {
    .sidebar { position: static; width: 100%; }
    .konten { margin-left: 0; padding: 16px; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <h2>Admin Wisata</h2>
    <a href="admin.php">Dashboard</a>
    <a href="kelola_wisata.php">Kelola Wisata</a>
    <a href="admin_pesanan.php">Kelola Pesanan</a>
    <a href="admin_pengguna.php">Kelola Pengguna</a>
    <a href="admin_kategori.php">Kelola Kategori</a>
    <a href="admin_wilayah.php" class="aktif">Kelola Wilayah</a>
    <a href="beranda.php">Lihat Website</a>
    <a href="logout.php">Keluar</a>
</aside>

<main class="konten">
    <h1>Kelola Wilayah Wisata</h1>
    <p>Atur daftar wilayah yang tersedia di website.</p>

    <?php if ($pesan !== ''): ?>
        <div class="pesan"><?= amanWilayah($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= amanWilayah($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Tambah Wilayah</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= amanWilayah($_SESSION['csrf_wilayah']) ?>">
            <input type="hidden" name="aksi" value="tambah">
            <input type="text" name="nama"
                   placeholder="Contoh: Kabupaten Tangerang"
                   maxlength="150" required>
            <button type="submit">Tambah</button>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Wilayah</h2>
        <div class="bungkus">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Wilayah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($daftar) > 0): ?>
                    <?php foreach ($daftar as $w): ?>
                        <tr>
                            <td><?= (int) $w['id'] ?></td>
                            <td><?= amanWilayah($w[$namaKolom]) ?></td>
                            <td>
                                <form method="POST" class="form-edit">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= amanWilayah($_SESSION['csrf_wilayah']) ?>">
                                    <input type="hidden" name="id"
                                           value="<?= (int) $w['id'] ?>">
                                    <input type="text" name="nama"
                                           value="<?= amanWilayah($w[$namaKolom]) ?>"
                                           maxlength="150" required>
                                    <button type="submit" name="aksi"
                                            value="edit">Simpan Edit</button>
                                </form>

                                <form method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus wilayah ini?')"
                                      style="margin-top:8px">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= amanWilayah($_SESSION['csrf_wilayah']) ?>">
                                    <input type="hidden" name="id"
                                           value="<?= (int) $w['id'] ?>">
                                    <button type="submit" name="aksi"
                                            value="hapus" class="hapus">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Belum ada data wilayah.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
