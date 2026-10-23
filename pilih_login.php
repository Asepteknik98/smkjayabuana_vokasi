
<?php
require 'config.php';

$berhasilDaftar = isset($_GET['daftar'])
    && $_GET['daftar'] === 'berhasil';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pilih Login | Pariwisata Banten</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">

<div class="auth-box">

    <a class="brand" href="beranda.php">
        💐 Pariwisata <span>Banten</span>
    </a>

    <?php if ($berhasilDaftar): ?>
        <div class="success">
            Pendaftaran berhasil! Sekarang pilih jenis akun untuk masuk.
        </div>
    <?php endif; ?>

    <h1>Selamat Datang!</h1>

    <p>Kamu ingin masuk sebagai siapa?</p>

    <div style="display:grid; gap:15px; margin-top:25px;">

        <a
            href="login.php?mode=user"
            class="btn full"
            style="text-align:center; text-decoration:none;"
        >
            👤 Login sebagai User
        </a>

        <a
            href="login.php?mode=admin"
            class="btn full"
            style="text-align:center; text-decoration:none;"
        >
            🛡️ Login sebagai Admin
        </a>

    </div>

    <p class="center" style="margin-top:25px;">
        Belum punya akun?
        <a href="register.php">Daftar</a>
    </p>

    <p class="center">
        <a href="beranda.php">← Kembali ke Beranda</a>
    </p>

</div>

</body>
</html>