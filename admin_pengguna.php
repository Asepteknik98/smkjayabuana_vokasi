
<?php
require_once 'config.php';
// Hanya admin yang boleh membuka halaman ini
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_admin_pengguna'])) {
    $_SESSION['csrf_admin_pengguna'] = bin2hex(random_bytes(32));
}

$pesan = '';
$error = '';

// Proses perubahan akun
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_admin_pengguna'], $token)
    ) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {
        $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        try {
            if ($aksi === 'ubah_peran' && $id > 0) {
                $peran = isset($_POST['role']) ? $_POST['role'] : '';

                if (!in_array($peran, ['admin', 'user'], true)) {
                    throw new Exception('Peran yang dipilih tidak valid.');
                }

                if ($id === (int) $_SESSION['user_id']) {
                    throw new Exception(
                        'Peran akun yang sedang kamu gunakan tidak dapat diubah.'
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

            } elseif ($aksi === 'hapus' && $id > 0) {
                if ($id === (int) $_SESSION['user_id']) {
                    throw new Exception('Kamu tidak dapat menghapus akun sendiri.');
                }

                // Hanya akun user biasa yang bisa dihapus dari halaman ini
                $hapus = $pdo->prepare(
                    "DELETE FROM users WHERE id = ? AND role = 'user'"
                );
                $hapus->execute([$id]);

                if ($hapus->rowCount() > 0) {
                    $pesan = 'Akun pengguna berhasil dihapus.';
                } else {
                    throw new Exception(
                        'Akun tidak ditemukan atau merupakan akun admin.'
                    );
                }
            } else {
                throw new Exception('Aksi tidak dikenali.');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan database. Silakan coba lagi.';
        }
    }
}

// Pencarian akun
$cari = trim(isset($_GET['cari']) ? $_GET['cari'] : '');

if ($cari !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, nama, username, email, role, dibuat_pada
         FROM users
         WHERE nama LIKE ? OR username LIKE ? OR email LIKE ?
         ORDER BY id DESC'
    );

    $kata = '%' . $cari . '%';
    $stmt->execute([$kata, $kata, $kata]);
} else {
    $stmt = $pdo->query(
        'SELECT id, nama, username, email, role, dibuat_pada
         FROM users
         ORDER BY id DESC'
    );
}

$pengguna = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPengguna = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'user'"
)->fetchColumn();

$totalAdmin = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

function aman($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            color: #253047;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 235px;
            height: 100vh;
            padding: 25px 15px;
            background: #e9a3da;
            color: white;
        }

        .sidebar h2 {
            font-size: 21px;
            margin: 0 0 30px;
        }

        .sidebar a {
            display: block;
            padding: 13px;
            margin-bottom: 7px;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

        .sidebar a:hover,
        .sidebar a.aktif {
            background: #f5d0fa;
        }

        .konten {
            margin-left: 235px;
            padding: 30px;
        }

        h1 {
            margin-top: 0;
        }

        .ringkasan {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin: 25px 0;
        }

        .kartu {
            flex: 1;
            min-width: 180px;
            padding: 22px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .angka {
            font-size: 28px;
            font-weight: bold;
            margin-top: 10px;
            color: #f5c7f5;
        }

        .panel {
            padding: 22px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000008;
        }

        .pencarian {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        input[type="text"],
        select {
            padding: 10px;
            border: 1px solid #d4dae3;
            border-radius: 6px;
        }

        input[type="text"] {
            width: 100%;
            max-width: 350px;
        }

        button,
        .tombol {
            display: inline-block;
            border: none;
            padding: 10px 13px;
            border-radius: 6px;
            background: #dda8e7;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
        }

        .hapus {
            background: #c0392b;
        }

        .pesan {
            padding: 13px;
            background: #dff5e8;
            color: #dfbaeb;
            border-radius: 7px;
            margin-bottom: 15px;
        }

        .error {
            padding: 13px;
            background: #fde7e7;
            color: #9b2525;
            border-radius: 7px;
            margin-bottom: 15px;
        }

        .tabel-bungkus {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th,
        td {
            padding: 13px 10px;
            border-bottom: 1px solid #e8ebf0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f7f9fb;
        }

        .form-peran {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .lencana {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 15px;
            background: #e1f3e9;
            color: #e6a3e0;
            font-size: 12px;
        }

        .lencana-admin {
            background: #fff0d3;
            color: #885900;
        }

        .aksi {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .kosong {
            padding: 25px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 750px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .konten {
                margin-left: 0;
                padding: 18px;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <h2>Admin Wisata</h2>

        <a href="admin.php">Dashboard</a>
        <a href="kelola_wisata.php">Kelola Wisata</a>
        <a href="admin_pesanan.php">Kelola Pesanan</a>
        <a href="admin_pengguna.php" class="aktif">Kelola Pengguna</a>
        <a href="beranda.php">Lihat Website</a>
        <a href="logout.php">Keluar</a>
    </aside>

    <main class="konten">
        <h1>Kelola Pengguna</h1>
        <p>Lihat dan atur akun yang terdaftar di website wisata.</p>

        <?php if ($pesan !== ''): ?>
            <div class="pesan"><?= aman($pesan) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error"><?= aman($error) ?></div>
        <?php endif; ?>

        <section class="ringkasan">
            <div class="kartu">
                Total Pengguna
                <div class="angka"><?= $totalPengguna ?></div>
            </div>

            <div class="kartu">
                Total Admin
                <div class="angka"><?= $totalAdmin ?></div>
            </div>
        </section>

        <section class="panel">
            <h2>Daftar Akun</h2>

            <form method="GET" class="pencarian">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari nama, username, atau email..."
                    value="<?= aman($cari) ?>"
                >
                <button type="submit">Cari</button>
                <a href="admin_pengguna.php" class="tombol">Reset</a>
            </form>

            <div class="tabel-bungkus">
                <table>
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
                                <td><?= $i + 1 ?></td>
                                <td><?= aman($p['nama']) ?></td>
                                <td><?= aman($p['username']) ?></td>
                                <td><?= aman($p['email']) ?></td>

                                <td>
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

                                <td><?= aman($p['dibuat_pada']) ?></td>

                                <td>
                                    <div class="aksi">
                                        <?php if ((int) $p['id'] !== (int) $_SESSION['user_id']): ?>
                                            <form method="POST" class="form-peran">
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= aman($_SESSION['csrf_admin_pengguna']) ?>"
                                                >
                                                <input type="hidden" name="id"
                                                       value="<?= (int) $p['id'] ?>">
                                                <input type="hidden" name="aksi"
                                                       value="ubah_peran">

                                                <select name="role">
                                                    <option value="user"
                                                        <?= $p['role'] === 'user' ? 'selected' : '' ?>>
                                                        Pengguna
                                                    </option>
                                                    <option value="admin"
                                                        <?= $p['role'] === 'admin' ? 'selected' : '' ?>>
                                                        Admin
                                                    </option>
                                                </select>

                                                <button type="submit">Simpan</button>
                                            </form>

                                            <?php if ($p['role'] === 'user'): ?>
                                                <form method="POST"
                                                      onsubmit="return confirm('Yakin ingin menghapus akun pengguna ini?');">
                                                    <input type="hidden"
                                                           name="csrf_token"
                                                           value="<?= aman($_SESSION['csrf_admin_pengguna']) ?>">
                                                    <input type="hidden" name="id"
                                                           value="<?= (int) $p['id'] ?>">
                                                    <input type="hidden" name="aksi"
                                                           value="hapus">
                                                    <button type="submit" class="hapus">
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
