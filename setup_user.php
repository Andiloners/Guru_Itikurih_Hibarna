<?php
/**
 * File untuk setup user pertama kali
 * Jalankan file ini melalui browser untuk membuat user admin
 * atau untuk generate password hash baru
 */

require_once 'config.php';

// Jika sudah ada user, jangan jalankan lagi
$conn = getConnection();
$check = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $check->fetch_assoc();

if ($row['count'] > 0 && !isset($_GET['force'])) {
    die("User sudah ada di database. Jika ingin membuat user baru, tambahkan ?force=1 di URL");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    
    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        $error = "Semua field harus diisi!";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password_hash, $nama_lengkap);
        
        if ($stmt->execute()) {
            $message = "User berhasil dibuat! Username: " . htmlspecialchars($username);
        } else {
            $error = "Gagal membuat user: " . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup User - Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🔧 Setup User</h1>
                <p>Buat user pertama untuk login</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <br><br>
                    <a href="login.php" class="btn btn-primary">Login Sekarang</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="setup_user.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required 
                               autofocus
                               placeholder="Masukkan username"
                               value="admin">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Masukkan password"
                               value="admin123">
                    </div>

                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" 
                               id="nama_lengkap" 
                               name="nama_lengkap" 
                               required 
                               placeholder="Masukkan nama lengkap"
                               value="Administrator">
                    </div>

                    <button type="submit" class="btn btn-primary btn-login">
                        Buat User
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

