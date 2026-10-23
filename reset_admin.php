
<?php
require_once 'config.php';

$nama = 'Administrator';
$username = 'admin';
$email = 'admin@pariwisatabanten.com';
$password = 'Admin12345';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Cek apakah username admin sudah ada
    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Perbaiki akun admin yang sudah ada
        $stmt = $pdo->prepare(
            "UPDATE users
             SET nama = ?, password = ?, role = 'admin'
             WHERE id = ?"
        );
        $stmt->execute([$nama, $hash, $user['id']]);
    } else {
        // Pastikan email belum digunakan akun lain
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            exit('GAGAL: Email admin sudah digunakan akun lain.');
        }

        // Buat akun admin baru
        $stmt = $pdo->prepare(
            "INSERT INTO users
             (nama, username, email, password, role)
             VALUES (?, ?, ?, ?, 'admin')"
        );
        $stmt->execute([
            $nama,
            $username,
            $email,
            $hash
        ]);
    }

    echo '<h2>Akun admin berhasil disiapkan!</h2>';
    echo 'Username: admin<br>';
    echo 'Password: Admin12345<br>';
    echo '<a href="login.php">Klik di sini untuk login</a>';

} catch (PDOException $e) {
    echo 'GAGAL: ' . htmlspecialchars($e->getMessage());
}
?>
