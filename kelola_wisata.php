
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

if (empty($_SESSION['csrf_wisata'])) {
    $_SESSION['csrf_wisata'] = bin2hex(random_bytes(32));
}

$error = '';

function bersihkan($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

// Tambah, edit, atau hapus wisata
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_wisata'], $_POST['csrf_token'])
    ) {
        exit('Permintaan tidak valid. Silakan muat ulang halaman.');
    }

    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'hapus') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new Exception('ID wisata tidak valid.');
            }

            $stmt = $pdo->prepare('DELETE FROM wisata WHERE id = ?');
            $stmt->execute([$id]);

            header('Location: kelola_wisata.php?pesan=hapus');
            exit;
        }

        if ($aksi === 'simpan') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $nama = trim($_POST['nama'] ?? '');
            $lokasi = trim($_POST['lokasi'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $harga = $_POST['harga'] ?? '';
            $gambar = trim($_POST['gambar'] ?? '');
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if (
                $nama === '' || $lokasi === '' ||
                $kategori === '' || $harga === '' ||
                $gambar === '' || $deskripsi === ''
            ) {
                throw new Exception('Semua kolom wajib diisi.');
            }

            if (!is_numeric($harga) || (float)$harga < 0) {
                throw new Exception('Harga harus berupa angka nol atau lebih.');
            }

            if (strlen($nama) > 150 || strlen($lokasi) > 150 ||
                strlen($kategori) > 50 || strlen($gambar) > 500) {
                throw new Exception('Ada teks yang melebihi batas panjang kolom.');
            }

            if ($id && $id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE wisata
                     SET nama = ?, lokasi = ?, kategori = ?,
                         harga = ?, gambar = ?, deskripsi = ?
                     WHERE id = ?'
                );

                $stmt->execute([
                    $nama, $lokasi, $kategori,
                    $harga, $gambar, $deskripsi, $id
                ]);

                header('Location: kelola_wisata.php?pesan=edit');
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO wisata
                 (nama, lokasi, kategori, harga, gambar, deskripsi)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $nama, $lokasi, $kategori,
                $harga, $gambar, $deskripsi
            ]);

            header('Location: kelola_wisata.php?pesan=tambah');
            exit;
        }
    } catch (Throwable $e) {
        if ($e instanceof PDOException) {
            $error = 'Data gagal diproses. Wisata mungkin masih digunakan dalam pesanan.';
        } else {
            $error = $e->getMessage();
        }
    }
}

// Data untuk formulir edit
$edit = null;
if (isset($_GET['edit'])) {
    $idEdit = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($idEdit && $idEdit > 0) {
        $stmt = $pdo->prepare('SELECT * FROM wisata WHERE id = ?');
        $stmt->execute([$idEdit]);
        $edit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Ambil semua wisata
$stmt = $pdo->query('SELECT * FROM wisata ORDER BY id DESC');
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Wisata - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #dfb8d5;
            color: #8d8b8d;
        }
        header {
            background: #d88ac1;
            color: white;
            padding: 20px 5%;
        }
        header a { color: white; margin-right: 18px; }
        main { max-width: 1150px; margin: 25px auto; padding: 0 16px; }
        .panel {
            background: white;
            padding: 22px;
            margin-bottom: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 14px #0000000b;
        }
        label { display: block; margin: 13px 0 6px; font-weight: bold; }
        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd5e0;
            border-radius: 7px;
            font: inherit;
        }
        textarea { min-height: 100px; resize: vertical; }
        button, .btn {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: 10px 14px;
            background: #e794d5;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font: inherit;
        }
        .hapus { background: #c0392b; }
        .batal { background: #64748b; }
        .success { background: #e1f7e8; padding: 12px; border-radius: 7px; }
        .error { background: #ffe7e7; padding: 12px; border-radius: 7px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e4e9ef; }
        th { background: #edf3f8; }
        .actions { display: flex; gap: 7px; flex-wrap: wrap; }
        .foto { width: 85px; height: 60px; object-fit: cover; border-radius: 6px; }
    </style>
</head>
<body>

<header>
    <h2>Panel Admin — Kelola Wisata</h2>
    <p>Halo, <?= bersihkan($_SESSION['nama'] ?? 'Administrator') ?>!</p>
    <a href="admin.php">Dashboard Admin</a>
    <a href="beranda.php">Lihat Website</a>
    <a href="logout.php">Logout</a>
</header>

<main>
    <?php if ($error !== ''): ?>
        <p class="error"><?= bersihkan($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['pesan'], $pesan[$_GET['pesan']])): ?>
        <p class="success"><?= bersihkan($pesan[$_GET['pesan']]) ?></p>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $edit ? 'Edit Data Wisata' : 'Tambah Wisata Baru' ?></h2>

        <form method="post" action="kelola_wisata.php">
            <input type="hidden" name="csrf_token"
                   value="<?= bersihkan($_SESSION['csrf_wisata']) ?>">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id"
                   value="<?= bersihkan($edit['id'] ?? '') ?>">

            <label>Nama wisata</label>
            <input name="nama" maxlength="150" required
                   value="<?= bersihkan($edit['nama'] ?? '') ?>">

            <label>Lokasi</label>
            <input name="lokasi" maxlength="150" required
                   value="<?= bersihkan($edit['lokasi'] ?? '') ?>">

            <label>Kategori</label>
            <input name="kategori" maxlength="50" required
                   placeholder="Contoh: Pantai, Alam, Sejarah"
                   value="<?= bersihkan($edit['kategori'] ?? '') ?>">

            <label>Harga tiket (Rp)</label>
            <input type="number" name="harga" min="0" step="0.01" required
                   value="<?= bersihkan($edit['harga'] ?? '') ?>">

            <label>Lokasi gambar</label>
            <input name="gambar" maxlength="500" required
                   placeholder="Contoh: assets/css/images/pantai.jpg"
                   value="<?= bersihkan($edit['gambar'] ?? '') ?>">
            <small>Isi dengan alamat gambar atau path file gambar yang tersedia di website.</small>

            <label>Deskripsi</label>
            <textarea name="deskripsi" required><?= bersihkan($edit['deskripsi'] ?? '') ?></textarea>

            <br><br>
            <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Wisata' ?></button>

            <?php if ($edit): ?>
                <a class="btn batal" href="kelola_wisata.php">Batal Edit</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Wisata (<?= count($wisataList) ?>)</h2>

        <div class="table-wrap">
            <table>
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
                    <tr><td colspan="7">Belum ada data wisata.</td></tr>
                <?php endif; ?>

                <?php foreach ($wisataList as $w): ?>
                    <tr>
                        <td><?= (int)$w['id'] ?></td>
                        <td>
                            <?php if ($w['gambar'] !== ''): ?>
                                <img class="foto"
                                     src="<?= bersihkan($w['gambar']) ?>"
                                     alt="Foto wisata"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                        </td>
                        <td><?= bersihkan($w['nama']) ?></td>
                        <td><?= bersihkan($w['lokasi']) ?></td>
                        <td><?= bersihkan($w['kategori']) ?></td>
                        <td>Rp <?= number_format((float)$w['harga'], 0, ',', '.') ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn"
                                   href="kelola_wisata.php?edit=<?= (int)$w['id'] ?>">Edit</a>

                                <form method="post" action="kelola_wisata.php"
                                      onsubmit="return confirm('Yakin ingin menghapus wisata ini?')">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= bersihkan($_SESSION['csrf_wisata']) ?>">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id"
                                           value="<?= (int)$w['id'] ?>">
                                    <button class="hapus" type="submit">Hapus</button>
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
