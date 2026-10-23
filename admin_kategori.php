
<?php
require_once 'config.php';

// Pastikan hanya admin yang bisa membuka halaman ini
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

if (empty($_SESSION['csrf_kategori'])) {
    $_SESSION['csrf_kategori'] = bin2hex(random_bytes(32));
}

function amanKategori($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Proses tambah, edit, dan hapus kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_kategori'], $token)) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';
        $nama = trim($_POST['nama'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        try {
            if ($aksi === 'tambah') {
                if ($nama === '') {
                    throw new Exception('Nama kategori wajib diisi.');
                }

                if (mb_strlen($nama) > 50) {
                    throw new Exception('Nama kategori maksimal 50 karakter.');
                }

                $cek = $pdo->prepare(
                    'SELECT COUNT(*) FROM kategori WHERE nama = ?'
                );
                $cek->execute([$nama]);

                if ((int) $cek->fetchColumn() > 0) {
                    throw new Exception('Kategori tersebut sudah ada.');
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO kategori (nama) VALUES (?)'
                );
                $stmt->execute([$nama]);

                $pesan = 'Kategori berhasil ditambahkan.';

            } elseif ($aksi === 'edit') {
                if ($id < 1 || $nama === '') {
                    throw new Exception('Data kategori belum lengkap.');
                }

                if (mb_strlen($nama) > 50) {
                    throw new Exception('Nama kategori maksimal 50 karakter.');
                }

                $cek = $pdo->prepare(
                    'SELECT COUNT(*) FROM kategori
                     WHERE nama = ? AND id != ?'
                );
                $cek->execute([$nama, $id]);

                if ((int) $cek->fetchColumn() > 0) {
                    throw new Exception('Nama kategori sudah digunakan.');
                }

                $stmt = $pdo->prepare(
                    'UPDATE kategori SET nama = ? WHERE id = ?'
                );
                $stmt->execute([$nama, $id]);

                if ($stmt->rowCount() === 0) {
                    $cekId = $pdo->prepare(
                        'SELECT COUNT(*) FROM kategori WHERE id = ?'
                    );
                    $cekId->execute([$id]);

                    if ((int) $cekId->fetchColumn() === 0) {
                        throw new Exception('Kategori tidak ditemukan.');
                    }
                }

                $pesan = 'Kategori berhasil diperbarui.';

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

                // Cegah penghapusan jika kategori masih dipakai wisata
                $cekWisata = $pdo->prepare(
                    'SELECT COUNT(*) FROM wisata WHERE kategori = ?'
                );
                $cekWisata->execute([$kategori['nama']]);

                if ((int) $cekWisata->fetchColumn() > 0) {
                    throw new Exception(
                        'Kategori masih digunakan oleh data wisata. ' .
                        'Ubah kategori wisata tersebut terlebih dahulu.'
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
            $error = 'Terjadi masalah pada database. Periksa struktur tabel kategori.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Ambil daftar kategori
$daftar = $pdo->query(
    'SELECT * FROM kategori ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

// Deteksi kolom kategori wisata
$kolomKategori = $pdo->query('SHOW COLUMNS FROM wisata')
    ->fetchAll(PDO::FETCH_COLUMN);

$kolomKategori = in_array('kategori', $kolomKategori, true)
    ? 'kategori'
    : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Kategori</title>

<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #8fe3fd;
    color: #e7bae1;
}
.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 230px;
    padding: 25px 15px;
    background: #e473d1;
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
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px #00000008;
}
input {
    width: 100%;
    max-width: 400px;
    padding: 11px;
    border: 1px solid #d4dae3;
    border-radius: 6px;
}
button {
    padding: 10px 14px;
    border: 0;
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
.tabel-bungkus { overflow-x: auto; }
.form-edit { display: flex; gap: 8px; flex-wrap: wrap; }
.form-edit input { width: 200px; }
@media (max-width: 750px) {
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
    <a href="admin_kategori.php" class="aktif">Kelola Kategori</a>
    <a href="beranda.php">Lihat Website</a>
    <a href="logout.php">Keluar</a>
</aside>

<main class="konten">
    <h1>Kelola Kategori Wisata</h1>
    <p>Tambahkan dan atur kategori untuk tempat wisata.</p>

    <?php if ($pesan !== ''): ?>
        <div class="pesan"><?= amanKategori($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= amanKategori($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Tambah Kategori</h2>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= amanKategori($_SESSION['csrf_kategori']) ?>">
            <input type="hidden" name="aksi" value="tambah">

            <p>
                <input type="text" name="nama" maxlength="50"
                       placeholder="Contoh: Pantai" required>
            </p>

            <button type="submit">Tambah Kategori</button>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Kategori</h2>

        <div class="tabel-bungkus">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($daftar) > 0): ?>
                    <?php foreach ($daftar as $k): ?>
                        <tr>
                            <td><?= (int) $k['id'] ?></td>
                            <td><?= amanKategori($k['nama']) ?></td>
                            <td>
                                <form method="POST" class="form-edit">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= amanKategori($_SESSION['csrf_kategori']) ?>">
                                    <input type="hidden" name="id"
                                           value="<?= (int) $k['id'] ?>">
                                    <input type="text" name="nama"
                                           maxlength="50"
                                           value="<?= amanKategori($k['nama']) ?>"
                                           required>
                                    <button type="submit" name="aksi"
                                            value="edit">Simpan Edit</button>
                                </form>

                                <form method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus kategori ini?')"
                                      style="margin-top:8px">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= amanKategori($_SESSION['csrf_kategori']) ?>">
                                    <input type="hidden" name="id"
                                           value="<?= (int) $k['id'] ?>">
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
                        <td colspan="3">Belum ada kategori.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
