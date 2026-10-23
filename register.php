
<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$nama || !$username || !$email || !$password) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (nama, username, email, password, role)
                VALUES (?, ?, ?, ?, 'user')"
            );

            $stmt->execute([
                $nama,
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            header('Location: pilih_login.php?daftar=berhasil');
            exit;

        } catch (PDOException $e) {
            $error = 'Username atau email sudah digunakan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar | Pariwisata Banten</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">

<div class="auth-box">

    <a class="brand" href="beranda.php">
        💐 Pariwisata <span>Banten</span>
    </a>

    <h1>Buat Akun Baru</h1>
    <p>Daftar dulu, lalu pilih jenis login kamu.</p>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">

        <label>Nama lengkap</label>
        <input
            type="text"
            name="nama"
            required
            value="<?= e($_POST['nama'] ?? '') ?>"
        >

        <label>Username</label>
        <input
            type="text"
            name="username"
            required
            value="<?= e($_POST['username'] ?? '') ?>"
        >

        <label>Email</label>
        <input
            type="email"
            name="email"
            required
            value="<?= e($_POST['email'] ?? '') ?>"
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            minlength="8"
            required
        >

        <button class="btn full" type="submit">
            Daftar Sekarang
        </button>

    </form>

    <p class="center">
        Sudah punya akun?
        <a href="pilih_login.php">Masuk</a>
    </p>

</div>

</body>
</html>
```