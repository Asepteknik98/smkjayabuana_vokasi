
<?php
require_once 'config.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: login.php');
    exit;
}

function amanPesanan($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function rupiahPesanan($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

if (empty($_SESSION['csrf_admin_pesanan'])) {
    $_SESSION['csrf_admin_pesanan'] = bin2hex(random_bytes(32));
}

$error = '';
$pesan = '';

$statusPilihan = [
    'Menunggu pembayaran',
    'Menunggu konfirmasi',
    'Lunas',
    'Dibatalkan'
];

// Proses perubahan status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_admin_pesanan'], $token)
    ) {
        $error = 'Permintaan tidak valid. Muat ulang halaman.';
    } else {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $statusBaru = $_POST['status'] ?? '';

        if (!$id || $id < 1 || !in_array($statusBaru, $statusPilihan, true)) {
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
                    $error = 'Sebelum memilih Lunas, pastikan pembayaran sudah benar-benar diterima dan diverifikasi.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE pesanan SET status = ? WHERE id = ?'
                    );
                    $stmt->execute([$statusBaru, $id]);

                    header('Location: admin_pesanan.php?pesan=berhasil');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Status gagal diperbarui. Silakan coba lagi.';
            }
        }
    }
}

if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil') {
    $pesan = 'Status pesanan berhasil diperbarui.';
}

// Cari pesanan
$cari = trim($_GET['cari'] ?? '');

try {
    if ($cari !== '') {
        $stmt = $pdo->prepare(
            "SELECT * FROM pesanan
             WHERE kode_pesanan LIKE ?
             ORDER BY id DESC"
        );
        $stmt->execute(['%' . $cari . '%']);
    } else {
        $stmt = $pdo->query(
            'SELECT * FROM pesanan ORDER BY id DESC'
        );
    }

    $daftarPesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $daftarPesanan = [];
    $error = 'Data pesanan gagal dimuat. Periksa struktur tabel database.';
}

// Ambil detail tiket setiap pesanan
$detailSemua = [];

if ($daftarPesanan) {
    $ids = array_map(
        static fn($p) => (int)$p['id'],
        $daftarPesanan
    );

    $tanda = implode(',', array_fill(0, count($ids), '?'));

    try {
        $stmt = $pdo->prepare(
            "SELECT dp.pesanan_id, dp.jumlah, dp.harga,
                    w.nama AS nama_wisata
             FROM detail_pesanan dp
             LEFT JOIN wisata w ON w.id = dp.wisata_id
             WHERE dp.pesanan_id IN ($tanda)
             ORDER BY dp.id ASC"
        );
        $stmt->execute($ids);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $detailSemua[$d['pesanan_id']][] = $d;
        }
    } catch (PDOException $e) {
        $error = 'Detail tiket gagal dimuat.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Pesanan | JelajahBanten</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body { background: #f4f7fb; }
        .panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin: 22px 0;
        }
        .pesanan {
            border: 1px solid #e0e7ef;
            border-radius: 10px;
            padding: 18px;
            margin: 16px 0;
            overflow-wrap: anywhere;
        }
        .baris {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .cari {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        input, select {
            padding: 10px;
            border: 1px solid #ccd5df;
            border-radius: 7px;
            font: inherit;
        }
        .cari input { flex: 1; min-width: 180px; }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            border: 0;
            border-radius: 7px;
            background: #1769aa;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
        }
        .error, .success, .peringatan {
            padding: 12px;
            border-radius: 7px;
            margin: 14px 0;
        }
        .error { background: #ffe7e7; }
        .success { background: #e1f7e8; }
        .peringatan { background: #fff4d6; }
        .detail-tiket {
            background: #f5f8fb;
            padding: 12px;
            border-radius: 8px;
            margin: 12px 0;
        }
        .status-form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        @media (max-width: 600px) {
            .status-form select { max-width: 100%; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="brand" href="admin.php">💐Jelajah<span>Banten</span></a>
    <div class="nav-links">
        <a href="admin.php">Dashboard</a>
        <a class="nav-cta" href="logout.php">Keluar</a>
    </div>
</nav>

<main class="container">
    <p class="eyebrow">ADMIN PANEL</p>
    <h1 class="section-title">Kelola Pesanan</h1>
    <p>Lihat pesanan tiket dan perbarui statusnya.</p>

    <?php if ($error !== ''): ?>
        <div class="error"><?= amanPesanan($error) ?></div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="success"><?= amanPesanan($pesan) ?></div>
    <?php endif; ?>

    <div class="peringatan">
        <strong>Penting:</strong> ubah status menjadi Lunas hanya setelah pembayaran benar-benar diterima dan diverifikasi. Fitur ini belum memverifikasi transaksi secara otomatis.
    </div>

    <section class="panel">
        <form method="get" class="cari">
            <input
                type="search"
                name="cari"
                placeholder="Cari berdasarkan kode pesanan..."
                value="<?= amanPesanan($cari) ?>"
            >
            <button class="btn" type="submit">Cari</button>
            <a class="btn" href="admin_pesanan.php">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Pesanan (<?= count($daftarPesanan) ?>)</h2>

        <?php if (!$daftarPesanan): ?>
            <p>Belum ada pesanan atau kode pesanan tidak ditemukan.</p>
        <?php endif; ?>

        <?php foreach ($daftarPesanan as $p): ?>
            <article class="pesanan">
                <div class="baris">
                    <div>
                        <h3><?= amanPesanan($p['kode_pesanan']) ?></h3>
                        <p>
                            ID Pesanan: <?= (int)$p['id'] ?><br>
                            Tanggal kunjungan:
                            <?= amanPesanan($p['tanggal_kunjungan']) ?><br>
                            Dibuat pada:
                            <?= amanPesanan($p['dibuat_pada']) ?>
                        </p>
                    </div>
                    <div>
                        <strong><?= rupiahPesanan($p['total']) ?></strong>
                        <p>Metode: <?= amanPesanan($p['metode']) ?></p>
                        <p>Status: <strong><?= amanPesanan($p['status']) ?></strong></p>
                    </div>
                </div>

                <div class="detail-tiket">
                    <strong>Detail tiket</strong>

                    <?php if (!empty($detailSemua[$p['id']])): ?>
                        <ul>
                        <?php foreach ($detailSemua[$p['id']] as $d): ?>
                            <li>
                                <?= amanPesanan($d['nama_wisata'] ?? 'Wisata tidak ditemukan') ?>
                                — <?= (int)$d['jumlah'] ?> tiket
                                × <?= rupiahPesanan($d['harga']) ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Detail tiket tidak tersedia.</p>
                    <?php endif; ?>
                </div>

                <form method="post" class="status-form"
                      onsubmit="return confirm('Yakin ingin mengubah status pesanan ini?')">
                    <input type="hidden" name="csrf_token"
                           value="<?= amanPesanan($_SESSION['csrf_admin_pesanan']) ?>">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

                    <label for="status-<?= (int)$p['id'] ?>">Ubah status:</label>
                    <select name="status" id="status-<?= (int)$p['id'] ?>" required>
                        <?php
                        $statusSaatIni = $p['status'];
                        $opsiStatus = array_unique(
                            array_merge($statusPilihan, [$statusSaatIni])
                        );
                        foreach ($opsiStatus as $s):
                        ?>
                            <option
                                value="<?= amanPesanan($s) ?>"
                                <?= $s === $statusSaatIni ? 'selected' : '' ?>
                            >
                                <?= amanPesanan($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn">Simpan Status</button>
                </form>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<footer>
    <p>JelajahBanten &copy; <?= date('Y') ?></p>
</footer>

</body>
</html>
