<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user adalah admin
requireAdmin();

$conn = getConnection();

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk mengambil semua user
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR nama_lengkap LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
}

// Hitung total user
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Administrasi Guru</title>
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
                            <h1>👥 Manajemen User Guru</h1>
                            <p class="welcome-text">Kelola akun user guru</p>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <span class="btn-icon">←</span> Kembali
                    </a>
                    <a href="user_form.php" class="btn btn-primary">
                        <span class="btn-icon">+</span> Tambah User Baru
                    </a>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <?php
                    if ($_GET['success'] == 'add') {
                        echo "User berhasil ditambahkan!";
                    } elseif ($_GET['success'] == 'edit') {
                        echo "User berhasil diupdate!";
                    } elseif ($_GET['success'] == 'delete') {
                        echo "User berhasil dihapus!";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠</span>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="search-filter-bar">
                <form method="GET" action="users.php" class="search-form">
                    <div class="search-input-group">
                        <span class="search-icon">🔍</span>
                        <input type="text" 
                               name="search" 
                               placeholder="Cari username atau nama lengkap..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="search-input">
                    </div>
                    <button type="submit" class="btn btn-search">Cari</button>
                    <?php if (!empty($search)): ?>
                        <a href="users.php" class="btn btn-clear">✕ Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Statistik -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_users; ?></h3>
                        <p class="stat-label">Total User</p>
                    </div>
                </div>
            </div>

            <!-- Tabel User -->
            <div class="section-title">
                <h2>📋 Daftar User</h2>
                <span class="section-count">(<?php echo $total_users; ?> user)</span>
            </div>

            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>No WhatsApp</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td>
                                        <?php if (!empty($row['no_whatsapp'])): ?>
                                            <span class="wa-number"><?php echo htmlspecialchars($row['no_whatsapp']); ?></span>
                                        <?php else: ?>
                                            <span class="wa-missing">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($row['role'] ?? 'user') == 'admin'): ?>
                                            <span class="badge-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="badge-user">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="user_form.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                            <a href="user_delete.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-delete"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">Hapus</a>
                                        <?php else: ?>
                                            <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;">Hapus</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">👤</div>
                        <h3>Belum Ada User</h3>
                        <p>Belum ada user terdaftar. Silakan tambah user baru untuk memulai.</p>
                        <a href="user_form.php" class="btn btn-primary" style="margin-top: 20px;">+ Tambah User Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $conn->close(); ?>
</body>
</html>

