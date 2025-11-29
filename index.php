<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user sudah login
requireLogin();

$conn = getConnection();

// Handle search dan filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_mapel = isset($_GET['mapel']) ? trim($_GET['mapel']) : '';
$filter_tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : '';

// Build query dengan filter
$where_conditions = [];
$params = [];
$types = '';

// Filter berdasarkan user (kecuali admin)
if (!isAdmin()) {
    $where_conditions[] = "nama = ?";
    $params[] = $_SESSION['nama_lengkap'];
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(nama LIKE ? OR mata_pelajaran LIKE ? OR materi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($filter_mapel)) {
    $where_conditions[] = "mata_pelajaran = ?";
    $params[] = $filter_mapel;
    $types .= 's';
}

if (!empty($filter_tanggal)) {
    $where_conditions[] = "tanggal = ?";
    $params[] = $filter_tanggal;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query untuk data dengan filter
if (!empty($params)) {
    $stmt = $conn->prepare("SELECT * FROM administrasi $where_clause ORDER BY tanggal DESC, pertemuan_ke DESC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM administrasi $where_clause ORDER BY tanggal DESC, pertemuan_ke DESC";
    $result = $conn->query($query);
}

// Filter untuk statistik (user biasa hanya lihat data sendiri)
$user_filter = isAdmin() ? "" : "nama = '" . $conn->real_escape_string($_SESSION['nama_lengkap']) . "'";
$stat_filter_where = isAdmin() ? "" : "WHERE " . $user_filter;
$stat_filter_and = isAdmin() ? "" : "AND " . $user_filter;

// Hitung statistik lengkap
$total_data = $conn->query("SELECT COUNT(*) as count FROM administrasi $stat_filter_where")->fetch_assoc()['count'];

// Data hari ini
$hari_ini = date('Y-m-d');
$data_hari_ini = $conn->query("SELECT COUNT(*) as count FROM administrasi WHERE tanggal = '$hari_ini' $stat_filter_and")->fetch_assoc()['count'];

// Data minggu ini
$minggu_ini_start = date('Y-m-d', strtotime('monday this week'));
$minggu_ini_end = date('Y-m-d', strtotime('sunday this week'));
$data_minggu_ini = $conn->query("SELECT COUNT(*) as count FROM administrasi WHERE tanggal BETWEEN '$minggu_ini_start' AND '$minggu_ini_end' $stat_filter_and")->fetch_assoc()['count'];

// Data bulan ini
$bulan_ini = date('Y-m');
$data_bulan_ini = $conn->query("SELECT COUNT(*) as count FROM administrasi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini' $stat_filter_and")->fetch_assoc()['count'];

// Total mata pelajaran unik
$total_mapel = $conn->query("SELECT COUNT(DISTINCT mata_pelajaran) as count FROM administrasi $stat_filter_where")->fetch_assoc()['count'];

// Total guru unik
$total_guru = $conn->query("SELECT COUNT(DISTINCT nama) as count FROM administrasi $stat_filter_where")->fetch_assoc()['count'];

// Data dengan foto
$data_dengan_foto = $conn->query("SELECT COUNT(*) as count FROM administrasi WHERE foto IS NOT NULL AND foto != '' $stat_filter_and")->fetch_assoc()['count'];

// Top 5 mata pelajaran
$top_mapel = $conn->query("SELECT mata_pelajaran, COUNT(*) as jumlah FROM administrasi $stat_filter_where GROUP BY mata_pelajaran ORDER BY jumlah DESC LIMIT 5");

// Data per bulan (6 bulan terakhir)
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $bulan_label = date('M Y', strtotime("-$i months"));
    $count = $conn->query("SELECT COUNT(*) as count FROM administrasi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan' $stat_filter_and")->fetch_assoc()['count'];
    $chart_data[] = ['label' => $bulan_label, 'value' => $count, 'month' => $bulan];
}

// Max value untuk chart
$max_chart_value = max(array_column($chart_data, 'value'));
$max_chart_value = $max_chart_value > 0 ? $max_chart_value : 1;

// Top 5 guru (hanya untuk admin)
if (isAdmin()) {
    $top_guru = $conn->query("SELECT nama, COUNT(*) as jumlah FROM administrasi GROUP BY nama ORDER BY jumlah DESC LIMIT 5");
    
    // Statistik guru (hanya untuk admin)
    // Total guru keseluruhan (user dengan role 'user')
    $total_guru_keseluruhan = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
    
    // Total guru yang sudah upload (guru yang memiliki minimal 1 data dengan foto)
    $guru_sudah_upload = $conn->query("
        SELECT COUNT(DISTINCT a.nama) as count 
        FROM administrasi a
        INNER JOIN users u ON u.nama_lengkap = a.nama
        WHERE u.role = 'user' 
        AND a.foto IS NOT NULL 
        AND a.foto != ''
    ")->fetch_assoc()['count'];
    
    // Total guru yang belum upload (guru yang tidak memiliki data dengan foto)
    $guru_belum_upload = $total_guru_keseluruhan - $guru_sudah_upload;
} else {
    $top_guru = $conn->query("SELECT nama, COUNT(*) as jumlah FROM administrasi WHERE nama = '" . $conn->real_escape_string($_SESSION['nama_lengkap']) . "' GROUP BY nama ORDER BY jumlah DESC LIMIT 5");
    $total_guru_keseluruhan = 0;
    $guru_sudah_upload = 0;
    $guru_belum_upload = 0;
}

// Ambil semua mata pelajaran untuk filter (reset query)
$all_mapel = $conn->query("SELECT DISTINCT mata_pelajaran FROM administrasi $stat_filter_where ORDER BY mata_pelajaran");
$all_mapel_for_filter = $conn->query("SELECT DISTINCT mata_pelajaran FROM administrasi $stat_filter_where ORDER BY mata_pelajaran");

// Data terbaru (5 data)
$query_terbaru = "SELECT * FROM administrasi $stat_filter_where ORDER BY tanggal DESC, pertemuan_ke DESC LIMIT 5";
$result_terbaru = $conn->query($query_terbaru);

// Data yang belum upload (7 hari terakhir)
$tanggal_7_hari = date('Y-m-d', strtotime('-7 days'));
$data_belum_upload_count = $conn->query("
    SELECT COUNT(*) as count FROM administrasi 
    WHERE tanggal >= '$tanggal_7_hari' 
    AND (foto IS NULL OR foto = '')
    $stat_filter_and
")->fetch_assoc()['count'];

// Reset result untuk tabel
$result->data_seek(0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Pengelolaan Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="logo-container">
                        <div class="logo-icon">
                            <?php 
                            $logo_path = 'assets/images/logo.png';
                            if (!file_exists($logo_path)) {
                                $logo_path = 'assets/images/logo.jpg';
                            }
                            if (file_exists($logo_path)):
                            ?>
                                <img src="<?php echo $logo_path; ?>" alt="Logo SMK ITIKURIH HIBARNA" class="logo-image">
                            <?php else: ?>
                                <div class="logo-fallback">📚</div>
                            <?php endif; ?>
                        </div>
                        <div class="logo-text">
                            <h1>Dashboard Administrasi Guru</h1>
                            <p class="welcome-text">
                                Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                                <?php if (isAdmin()): ?>
                                    <span style="background: #667eea; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-left: 10px; font-weight: normal;">ADMIN</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if (isAdmin()): ?>
                    <a href="notifikasi.php" class="btn btn-whatsapp-header" style="background: rgba(37, 211, 102, 0.2); border: 2px solid rgba(37, 211, 102, 0.4);">
                        <span class="btn-icon">📱</span> Notifikasi
                    </a>
                    <a href="users.php" class="btn btn-secondary" style="background: rgba(255, 255, 255, 0.15); border: 2px solid rgba(255, 255, 255, 0.3);">
                        <span class="btn-icon">👥</span> User
                    </a>
                    <?php endif; ?>
                    <a href="form.php" class="btn btn-primary">
                        <span class="btn-icon">+</span> Tambah Data Baru
                    </a>
                    <a href="logout.php" class="btn btn-logout" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                        <span class="btn-icon">🚪</span> Logout
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
                        echo "Data berhasil ditambahkan!";
                    } elseif ($_GET['success'] == 'edit') {
                        echo "Data berhasil diupdate!";
                    } elseif ($_GET['success'] == 'delete') {
                        echo "Data berhasil dihapus!";
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

            <!-- Search & Filter Bar -->
            <div class="search-filter-bar">
                <form method="GET" action="index.php" class="search-form">
                    <div class="search-input-group">
                        <span class="search-icon">🔍</span>
                        <input type="text" 
                               name="search" 
                               placeholder="Cari nama, mata pelajaran, atau materi..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="search-input">
                    </div>
                    <select name="mapel" class="filter-select">
                        <option value="">Semua Mata Pelajaran</option>
                        <?php while ($mapel = $all_mapel_for_filter->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($mapel['mata_pelajaran']); ?>" 
                                    <?php echo $filter_mapel == $mapel['mata_pelajaran'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mapel['mata_pelajaran']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <input type="date" 
                           name="tanggal" 
                           value="<?php echo htmlspecialchars($filter_tanggal); ?>"
                           class="filter-date">
                    <button type="submit" class="btn btn-search">Cari</button>
                    <?php if (!empty($search) || !empty($filter_mapel) || !empty($filter_tanggal)): ?>
                        <a href="index.php" class="btn btn-clear">✕ Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Statistik Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_data; ?></h3>
                        <p class="stat-label">Total Data</p>
                        <span class="stat-trend">Semua waktu</span>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $data_hari_ini; ?></h3>
                        <p class="stat-label">Data Hari Ini</p>
                        <span class="stat-trend"><?php echo date('d M Y'); ?></span>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">📆</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $data_minggu_ini; ?></h3>
                        <p class="stat-label">Data Minggu Ini</p>
                        <span class="stat-trend">7 hari terakhir</span>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $data_bulan_ini; ?></h3>
                        <p class="stat-label">Data Bulan Ini</p>
                        <span class="stat-trend"><?php echo date('M Y'); ?></span>
                    </div>
                </div>
                <div class="stat-card stat-purple">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_mapel; ?></h3>
                        <p class="stat-label">Mata Pelajaran</p>
                        <span class="stat-trend">Unik</span>
                    </div>
                </div>
                <div class="stat-card stat-teal">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_guru; ?></h3>
                        <p class="stat-label">Total Guru</p>
                        <span class="stat-trend">Aktif</span>
                    </div>
                </div>
                <div class="stat-card stat-pink">
                    <div class="stat-icon">📷</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $data_dengan_foto; ?></h3>
                        <p class="stat-label">Data dengan Foto</p>
                        <span class="stat-trend"><?php echo $total_data > 0 ? round(($data_dengan_foto / $total_data) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <div class="stat-card stat-orange">
                    <div class="stat-icon">👤</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h3>
                        <p class="stat-label">User Aktif</p>
                        <span class="stat-trend">Online</span>
                    </div>
                </div>
                <?php if ($data_belum_upload_count > 0): ?>
                <div class="stat-card stat-danger">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $data_belum_upload_count; ?></h3>
                        <p class="stat-label">Belum Upload</p>
                        <span class="stat-trend">7 hari terakhir</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                <!-- Statistik Guru (Hanya Admin) -->
                <div class="stat-card stat-teal">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_guru_keseluruhan; ?></h3>
                        <p class="stat-label">Total Guru</p>
                        <span class="stat-trend">Keseluruhan</span>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $guru_sudah_upload; ?></h3>
                        <p class="stat-label">Guru Sudah Upload</p>
                        <span class="stat-trend"><?php echo $total_guru_keseluruhan > 0 ? round(($guru_sudah_upload / $total_guru_keseluruhan) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <div class="stat-card stat-danger">
                    <div class="stat-icon">❌</div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $guru_belum_upload; ?></h3>
                        <p class="stat-label">Guru Belum Upload</p>
                        <span class="stat-trend"><?php echo $total_guru_keseluruhan > 0 ? round(($guru_belum_upload / $total_guru_keseluruhan) * 100) : 0; ?>%</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Dashboard Widgets -->
            <div class="dashboard-widgets">
                <!-- Chart Widget -->
                <div class="widget widget-chart">
                    <div class="widget-header">
                        <h3>📈 Grafik Data (6 Bulan Terakhir)</h3>
                    </div>
                    <div class="widget-body">
                        <div class="chart-container">
                            <div class="chart-bars">
                                <?php foreach ($chart_data as $data): ?>
                                    <div class="chart-bar-wrapper">
                                        <div class="chart-bar" style="height: <?php echo ($data['value'] / $max_chart_value) * 100; ?>%">
                                            <span class="chart-value"><?php echo $data['value']; ?></span>
                                        </div>
                                        <span class="chart-label"><?php echo $data['label']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Mata Pelajaran Widget -->
                <div class="widget widget-list">
                    <div class="widget-header">
                        <h3>🏆 Top 5 Mata Pelajaran</h3>
                    </div>
                    <div class="widget-body">
                        <?php if ($top_mapel->num_rows > 0): ?>
                            <ul class="top-list">
                                <?php 
                                $rank = 1;
                                while ($row = $top_mapel->fetch_assoc()): 
                                ?>
                                    <li class="top-item">
                                        <span class="rank-badge rank-<?php echo $rank; ?>"><?php echo $rank; ?></span>
                                        <div class="top-item-content">
                                            <strong><?php echo htmlspecialchars($row['mata_pelajaran']); ?></strong>
                                            <span class="top-item-count"><?php echo $row['jumlah']; ?> data</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo ($row['jumlah'] / $total_data) * 100; ?>%"></div>
                                        </div>
                                    </li>
                                <?php 
                                    $rank++;
                                endwhile; 
                                ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-widget">Belum ada data</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Guru Widget -->
                <div class="widget widget-list">
                    <div class="widget-header">
                        <h3>⭐ Top 5 Guru</h3>
                    </div>
                    <div class="widget-body">
                        <?php if ($top_guru->num_rows > 0): ?>
                            <ul class="top-list">
                                <?php 
                                $rank = 1;
                                while ($row = $top_guru->fetch_assoc()): 
                                ?>
                                    <li class="top-item">
                                        <span class="rank-badge rank-<?php echo $rank; ?>"><?php echo $rank; ?></span>
                                        <div class="top-item-content">
                                            <strong><?php echo htmlspecialchars($row['nama']); ?></strong>
                                            <span class="top-item-count"><?php echo $row['jumlah']; ?> data</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo ($row['jumlah'] / $total_data) * 100; ?>%"></div>
                                        </div>
                                    </li>
                                <?php 
                                    $rank++;
                                endwhile; 
                                ?>
                            </ul>
                        <?php else: ?>
                            <p class="empty-widget">Belum ada data</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Widget -->
                <div class="widget widget-actions">
                    <div class="widget-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="widget-body">
                        <div class="quick-actions">
                            <a href="form.php" class="quick-action-btn">
                                <span class="action-icon">➕</span>
                                <span>Tambah Data</span>
                            </a>
                            <a href="index.php" class="quick-action-btn">
                                <span class="action-icon">🔄</span>
                                <span>Refresh</span>
                            </a>
                            <button onclick="window.print()" class="quick-action-btn">
                                <span class="action-icon">🖨️</span>
                                <span>Print</span>
                            </button>
                            <a href="index.php?export=csv" class="quick-action-btn">
                                <span class="action-icon">📥</span>
                                <span>Export CSV</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Terbaru Section -->
            <?php if ($result_terbaru->num_rows > 0): ?>
            <div class="section-title">
                <h2>📋 Data Terbaru</h2>
            </div>
            <div class="recent-cards">
                <?php while ($row = $result_terbaru->fetch_assoc()): ?>
                <div class="data-card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <h3><?php echo htmlspecialchars($row['nama']); ?></h3>
                            <span class="card-badge"><?php echo htmlspecialchars($row['mata_pelajaran']); ?></span>
                        </div>
                        <span class="card-date"><?php echo date('d M Y', strtotime($row['tanggal'])); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="card-info">
                            <span class="info-item">
                                <strong>Pertemuan:</strong> <?php echo $row['pertemuan_ke']; ?>
                            </span>
                        </div>
                        <p class="card-materi">
                            <?php 
                            $materi = htmlspecialchars($row['materi']);
                            echo strlen($materi) > 100 ? substr($materi, 0, 100) . '...' : $materi;
                            ?>
                        </p>
                        <?php if ($row['foto']): ?>
                        <div class="card-photo">
                            <div class="photo-wrapper">
                                <img src="<?php echo UPLOAD_DIR . $row['foto']; ?>" 
                                     alt="Foto" 
                                     class="card-thumbnail"
                                     onclick="showImage('<?php echo UPLOAD_DIR . $row['foto']; ?>', <?php echo $row['id']; ?>)">
                                <div class="photo-actions">
                                    <a href="download_foto.php?id=<?php echo $row['id']; ?>" 
                                       class="btn-download-photo" 
                                       title="Download Foto">
                                        📥
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <?php if (isAdmin() || $row['nama'] == $_SESSION['nama_lengkap']): ?>
                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-edit-small">✏️ Edit</a>
                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                           class="btn btn-delete-small"
                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">🗑️ Hapus</a>
                        <?php else: ?>
                        <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;">Tidak dapat diubah</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>

            <!-- Semua Data Section -->
            <div class="section-title">
                <h2>📑 Semua Data Administrasi</h2>
                <span class="section-count">(<?php echo $total_data; ?> data)</span>
            </div>
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Pertemuan Ke</th>
                                <th>Tanggal</th>
                                <th>Materi</th>
                                <th>Foto</th>
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
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($row['mata_pelajaran']); ?></td>
                                    <td><?php echo $row['pertemuan_ke']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td class="materi-cell">
                                        <?php 
                                        $materi = htmlspecialchars($row['materi']);
                                        echo strlen($materi) > 50 ? substr($materi, 0, 50) . '...' : $materi;
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($row['foto']): ?>
                                            <div class="photo-cell">
                                                <img src="<?php echo UPLOAD_DIR . $row['foto']; ?>" 
                                                     alt="Foto" 
                                                     class="thumbnail"
                                                     onclick="showImage('<?php echo UPLOAD_DIR . $row['foto']; ?>', <?php echo $row['id']; ?>)">
                                                <a href="download_foto.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn-download-small" 
                                                   title="Download Foto">
                                                    📥
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-photo">Tidak ada foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if (isAdmin() || $row['nama'] == $_SESSION['nama_lengkap']): ?>
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</a>
                                        <?php else: ?>
                                        <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed; font-size: 11px;">Tidak dapat diubah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Belum Ada Data</h3>
                        <p>Belum ada data administrasi. Silakan tambah data baru untuk memulai.</p>
                        <a href="form.php" class="btn btn-primary" style="margin-top: 20px;">+ Tambah Data Pertama</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan foto -->
    <div id="imageModal" class="modal" onclick="closeImage()">
        <span class="close">&times;</span>
        <div class="modal-content-wrapper" onclick="event.stopPropagation();">
            <img class="modal-content" id="modalImage">
            <div class="modal-actions" id="modalActions">
                <a href="#" id="downloadLink" class="btn btn-download-modal" onclick="event.stopPropagation();">
                    <span class="btn-icon">📥</span> Download Foto
                </a>
            </div>
        </div>
    </div>

    <script>
        function showImage(src, id) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const downloadLink = document.getElementById('downloadLink');
            
            modal.style.display = 'block';
            modalImg.src = src;
            
            if (id) {
                downloadLink.href = 'download_foto.php?id=' + id;
                downloadLink.style.display = 'inline-flex';
            } else {
                downloadLink.style.display = 'none';
            }
        }

        function closeImage() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Close modal dengan ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImage();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

