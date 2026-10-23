
<?php
require 'config.php';

$error = '';

// Pilihan login ditampilkan pada halaman yang sama
$mode = $_POST['mode'] ?? $_GET['mode'] ?? 'user';

if (!in_array($mode, ['user', 'admin'], true)) {
    $mode = 'user';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['identitas'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users
         WHERE username = ? OR email = ?
         LIMIT 1"
    );

    $stmt->execute([$identitas, $identitas]);
    $user = $stmt->fetch();

    if (
        $user &&
        password_verify($password, $user['password']) &&
        ($user['role'] ?? 'user') === $mode
    ) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'user';

        if ($mode === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: beranda.php');
        }

        exit;
    } else {
        $error = $mode === 'admin'
            ? 'Login gagal. Periksa akun admin dan password kamu.'
            : 'Login gagal. Periksa username dan password kamu.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login | Pariwisata Banten</title>

    <link rel="stylesheet" href="assets/css/style.css?v=7">

    <style>
        .pilihan-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 22px 0;
        }

        .pilihan-login button {
            padding: 14px 8px;
            border: 2px solid #f0d4e0;
            border-radius: 12px;
            background: white;
            color: #70465a;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        .pilihan-login button.aktif {
            border-color: #d85b91;
            background: #fff0f6;
            color: #b83d75;
        }

        .pilihan-login button span {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            font-weight: normal;
        }
    </style>
</head>

<body class="auth-page">

<div class="auth-box">

    <a class="brand" href="beranda.php">
        💐Pariwisata <span>Banten</span>
    </a>

    <h1>Selamat Datang!</h1>
    <p>Pilih jenis akun untuk melanjutkan.</p>

    <?php if (isset($_GET['daftar'])): ?>
        <div class="success">
            Pendaftaran berhasil! Silakan login.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">

        <div class="pilihan-login">

            <button
                type="button"
                id="pilihUser"
                class="<?= $mode === 'user' ? 'aktif' : '' ?>"
                onclick="pilihMode('user')"
            >
                👤 Login User
                <span>Pembeli tiket</span>
            </button>

            <button
                type="button"
                id="pilihAdmin"
                class="<?= $mode === 'admin' ? 'aktif' : '' ?>"
                onclick="pilihMode('admin')"
            >
                🛡️ Login Admin
                <span>Pengelola website</span>
            </button>

        </div>

        <input
            type="hidden"
            name="mode"
            id="mode"
            value="<?= e($mode) ?>"
        >

        <h3 id="judulLogin">
            <?= $mode === 'admin'
                ? 'Masuk sebagai Admin'
                : 'Masuk sebagai User' ?>
        </h3>

        <label for="identitas">Username atau email</label>

        <input
            type="text"
            id="identitas"
            name="identitas"
            placeholder="Masukkan username atau email"
            value="<?= e($_POST['identitas'] ?? '') ?>"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
        >

        <button class="btn full" type="submit">
            Masuk
        </button>

    </form>

    <p class="center">
        Belum punya akun?
        <a href="register.php">Daftar sekarang</a>
    </p>

    <p class="center">
        <a href="beranda.php">← Kembali ke Beranda</a>
    </p>

</div>

<script>
function pilihMode(mode) {
    document.getElementById('mode').value = mode;

    document.getElementById('pilihUser')
        .classList.toggle('aktif', mode === 'user');

    document.getElementById('pilihAdmin')
        .classList.toggle('aktif', mode === 'admin');

    document.getElementById('judulLogin').textContent =
        mode === 'admin'
            ? 'Masuk sebagai Admin'
            : 'Masuk sebagai User';
}
</script>

</body>
</html>