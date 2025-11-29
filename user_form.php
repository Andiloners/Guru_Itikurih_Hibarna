<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user adalah admin
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$data = null;
$isEdit = false;

if ($id > 0) {
    $isEdit = true;
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Tambah'; ?> User - Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="logo-container">
                        <?php 
                        $logo_path = 'assets/images/logo.png';
                        if (!file_exists($logo_path)) {
                            $logo_path = 'assets/images/logo.jpg';
                        }
                        if (file_exists($logo_path)):
                        ?>
                            <div class="logo-icon">
                                <img src="<?php echo $logo_path; ?>" alt="Logo SMK ITIKURIH HIBARNA" class="logo-image">
                            </div>
                        <?php else: ?>
                            <div class="logo-icon">
                                <div class="logo-fallback">📚</div>
                            </div>
                        <?php endif; ?>
                        <div class="logo-text">
                            <h1><?php echo $isEdit ? '✏️ Edit User' : '➕ Tambah User Baru'; ?></h1>
                            <p class="welcome-text"><?php echo $isEdit ? 'Ubah data user' : 'Buat akun user guru baru'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="users.php" class="btn btn-secondary">
                        <span class="btn-icon">←</span> Kembali
                    </a>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠</span>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="user_process.php" method="POST" class="form-container">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           value="<?php echo $data ? htmlspecialchars($data['username']) : ''; ?>"
                           placeholder="Masukkan username"
                           <?php echo $isEdit ? 'readonly' : ''; ?>
                           style="<?php echo $isEdit ? 'background: #f0f0f0;' : ''; ?>">
                    <?php if ($isEdit): ?>
                        <small class="help-text">Username tidak dapat diubah</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" 
                           id="nama_lengkap" 
                           name="nama_lengkap" 
                           required 
                           value="<?php echo $data ? htmlspecialchars($data['nama_lengkap']) : ''; ?>"
                           placeholder="Masukkan nama lengkap">
                </div>

                <div class="form-group">
                    <label for="no_whatsapp">Nomor WhatsApp</label>
                    <input type="text" 
                           id="no_whatsapp" 
                           name="no_whatsapp" 
                           value="<?php echo $data ? htmlspecialchars($data['no_whatsapp'] ?? '') : ''; ?>"
                           placeholder="Contoh: 6281234567890"
                           pattern="[0-9]{10,15}">
                    <small class="help-text">Format: 62xxxxxxxxxxx (tanpa +, spasi, atau tanda minus). Contoh: 6281234567890</small>
                </div>

                <div class="form-group">
                    <label for="role">Role <span class="required">*</span></label>
                    <select id="role" name="role" required>
                        <option value="user" <?php echo ($data && ($data['role'] ?? 'user') == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($data && ($data['role'] ?? 'user') == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <small class="help-text">Pilih role untuk user ini</small>
                </div>

                <div class="form-group">
                    <label for="password">
                        Password <?php echo $isEdit ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?> 
                        <span class="required"><?php echo $isEdit ? '' : '*'; ?></span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           <?php echo $isEdit ? '' : 'required'; ?>
                           placeholder="<?php echo $isEdit ? 'Masukkan password baru (opsional)' : 'Masukkan password'; ?>"
                           minlength="6">
                    <small class="help-text">Minimal 6 karakter</small>
                </div>

                <?php if ($isEdit): ?>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="change_password" name="change_password" value="1">
                            Ubah password
                        </label>
                        <small class="help-text">Centang jika ingin mengubah password</small>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? 'Update User' : 'Simpan User'; ?>
                    </button>
                    <a href="users.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        <?php if ($isEdit): ?>
        // Disable password field jika checkbox tidak dicentang
        document.getElementById('change_password').addEventListener('change', function() {
            const passwordField = document.getElementById('password');
            if (this.checked) {
                passwordField.required = true;
                passwordField.disabled = false;
                passwordField.style.background = '';
            } else {
                passwordField.required = false;
                passwordField.disabled = true;
                passwordField.value = '';
                passwordField.style.background = '#f0f0f0';
            }
        });
        
        // Set initial state
        document.getElementById('password').disabled = true;
        document.getElementById('password').style.background = '#f0f0f0';
        <?php endif; ?>
    </script>
</body>
</html>

