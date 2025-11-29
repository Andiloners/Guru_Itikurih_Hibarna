<?php
require_once 'config.php';
require_once 'auth.php';

// Jika sudah login, redirect ke index
redirectIfLoggedIn();

$error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <div class="logo-circle">
                    <?php 
                    $logo_path = 'assets/images/logo.png';
                    if (!file_exists($logo_path)) {
                        $logo_path = 'assets/images/logo.jpg';
                    }
                    if (file_exists($logo_path)):
                    ?>
                        <img src="<?php echo $logo_path; ?>" alt="Logo SMK ITIKURIH HIBARNA" class="logo-image-large">
                    <?php else: ?>
                        <span class="logo-icon-large">📚</span>
                    <?php endif; ?>
                </div>
                <h1 class="logo-title">SMK ITIKURIH HIBARNA</h1>
                <p class="logo-subtitle">Sistem Administrasi Guru</p>
            </div>
            <div class="login-header">
                <p>Silakan login untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           autofocus
                           placeholder="Masukkan username"
                           value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           placeholder="Masukkan password">
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    Masuk
                </button>
            </form>

            <div class="login-footer">
                <p class="login-info">
                    <small>Default: username: <strong>admin</strong>, password: <strong>admin123</strong></small>
                </p>
                <?php if ($error && strpos($error, 'salah') !== false): ?>
                    <p style="margin-top: 15px; text-align: center;">
                        <a href="fix_password.php" style="color: #667eea; text-decoration: none; font-size: 13px;">
                            🔧 Perbaiki Password User
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

