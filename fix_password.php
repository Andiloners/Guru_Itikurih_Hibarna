<?php
/**
 * File untuk memperbaiki password user yang sudah ada
 * Jalankan file ini melalui browser untuk update password user
 */

require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $conn = getConnection();
        
        // Cek apakah user ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "User dengan username tersebut tidak ditemukan!";
            $stmt->close();
        } else {
            // Hash password baru
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $password_hash, $username);
            
            if ($stmt->execute()) {
                $message = "Password berhasil diupdate untuk user: " . htmlspecialchars($username);
            } else {
                $error = "Gagal update password: " . $conn->error;
            }
            $stmt->close();
        }
        
        $conn->close();
    }
}

// Ambil daftar user
$conn = getConnection();
$users = $conn->query("SELECT id, username, nama_lengkap FROM users ORDER BY id");
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Password - Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box" style="max-width: 600px;">
            <div class="login-header">
                <h1>🔧 Fix Password</h1>
                <p>Update password user yang sudah ada</p>
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

                <?php if ($users->num_rows > 0): ?>
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <strong>User yang tersedia:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <li><?php echo htmlspecialchars($user['username']); ?> - <?php echo htmlspecialchars($user['nama_lengkap']); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="fix_password.php" method="POST" class="login-form">
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
                        <label for="password">Password Baru</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Masukkan password baru"
                               value="admin123">
                    </div>

                    <button type="submit" class="btn btn-primary btn-login">
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

