<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user adalah admin
requireAdmin();

$conn = getConnection();

// Hitung data yang belum diupload (tidak ada foto) dalam 7 hari terakhir
$tanggal_7_hari = date('Y-m-d', strtotime('-7 days'));
$data_belum_upload = $conn->query("
    SELECT a.*, u.no_whatsapp 
    FROM administrasi a
    LEFT JOIN users u ON u.nama_lengkap = a.nama
    WHERE a.tanggal >= '$tanggal_7_hari' 
    AND (a.foto IS NULL OR a.foto = '')
    ORDER BY a.tanggal DESC, a.nama ASC
");

// Hitung data yang akan deadline (3 hari ke depan) - data yang tanggalnya sudah lewat atau akan datang dalam 3 hari
$tanggal_3_hari = date('Y-m-d', strtotime('+3 days'));
$tanggal_hari_ini = date('Y-m-d');
$data_deadline = $conn->query("
    SELECT DISTINCT a.nama, u.no_whatsapp, COUNT(*) as jumlah
    FROM administrasi a
    LEFT JOIN users u ON u.nama_lengkap = a.nama
    WHERE a.tanggal <= '$tanggal_3_hari' 
    AND a.tanggal >= '$tanggal_7_hari'
    AND (a.foto IS NULL OR a.foto = '')
    GROUP BY a.nama, u.no_whatsapp
    ORDER BY a.nama ASC
");

// Fungsi untuk generate pesan WhatsApp
function generateWhatsAppMessage($nama, $data_list) {
    $message = "Halo *" . $nama . "*,\n\n";
    $message .= "📢 *PEMBERITAHUAN UPLOAD ADMINISTRASI*\n";
    $message .= "SMK ITIKURIH HIBARNA\n\n";
    $message .= "Anda memiliki data administrasi yang *belum diupload*:\n\n";
    
    $no = 1;
    foreach ($data_list as $item) {
        $message .= $no . ". *" . $item['mata_pelajaran'] . "*\n";
        $message .= "   Pertemuan ke-" . $item['pertemuan_ke'] . "\n";
        $message .= "   Tanggal: " . date('d/m/Y', strtotime($item['tanggal'])) . "\n";
        if (!empty($item['materi'])) {
            $materi = substr($item['materi'], 0, 50);
            $message .= "   Materi: " . $materi . (strlen($item['materi']) > 50 ? '...' : '') . "\n";
        }
        $message .= "\n";
        $no++;
    }
    
    $message .= "⏰ *Segera upload sebelum waktu terlambat!*\n\n";
    $message .= "Terima kasih 🙏\n";
    $message .= "_Pesan otomatis dari Sistem Administrasi Guru_";
    
    return urlencode($message);
}

// Handle kirim notifikasi
if (isset($_GET['send']) && isset($_GET['nama'])) {
    $nama = urldecode($_GET['nama']);
    $no_whatsapp = $_GET['no_wa'] ?? '';
    
    // Ambil data yang belum diupload untuk guru tersebut
    $stmt = $conn->prepare("
        SELECT * FROM administrasi 
        WHERE nama = ? 
        AND tanggal >= '$tanggal_7_hari'
        AND (foto IS NULL OR foto = '')
        ORDER BY tanggal DESC
    ");
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result_data = $stmt->get_result();
    $data_list = [];
    while ($row = $result_data->fetch_assoc()) {
        $data_list[] = $row;
    }
    $stmt->close();
    
    if (!empty($data_list) && !empty($no_whatsapp)) {
        $message = generateWhatsAppMessage($nama, $data_list);
        $whatsapp_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $no_whatsapp) . "?text=" . $message;
        header('Location: ' . $whatsapp_url);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi WhatsApp - Administrasi Guru</title>
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
                            <h1>📱 Notifikasi WhatsApp</h1>
                            <p class="welcome-text">Kirim pemberitahuan kepada guru</p>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="setup_cron.php" class="btn btn-whatsapp-header" style="background: rgba(37, 211, 102, 0.2); border: 2px solid rgba(37, 211, 102, 0.4);">
                        <span class="btn-icon">⚙️</span> Setup Auto
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <span class="btn-icon">←</span> Kembali
                    </a>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    Notifikasi berhasil dikirim!
                </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="info-box">
                <div class="info-icon">ℹ️</div>
                <div class="info-content">
                    <h3>Cara Menggunakan</h3>
                    <p>1. Pastikan nomor WhatsApp sudah terdaftar di profil user</p>
                    <p>2. Klik tombol "Kirim Notifikasi" untuk mengirim pesan WhatsApp manual</p>
                    <p>3. Atau setup <strong>Auto Notifikasi</strong> untuk pengiriman otomatis</p>
                    <p>4. Sistem akan mengingatkan data yang belum diupload dalam 7 hari terakhir</p>
                    <p style="margin-top: 10px;">
                        <a href="setup_cron.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px;">
                            <span class="btn-icon">⚙️</span> Setup Auto Notifikasi
                        </a>
                    </p>
                </div>
            </div>

            <!-- Data Deadline -->
            <?php if ($data_deadline->num_rows > 0): ?>
            <div class="section-title">
                <h2>⏰ Data yang Perlu Diingatkan (3 Hari ke Depan)</h2>
            </div>
            <div class="dashboard-widgets" style="grid-template-columns: 1fr;">
                <div class="widget">
                    <div class="widget-header">
                        <h3>📋 Daftar Guru yang Perlu Diingatkan</h3>
                    </div>
                    <div class="widget-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Guru</th>
                                        <th>No WhatsApp</th>
                                        <th>Jumlah Data</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $data_deadline->data_seek(0);
                                    while ($row = $data_deadline->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong></td>
                                            <td>
                                                <?php if (!empty($row['no_whatsapp'])): ?>
                                                    <span class="wa-number"><?php echo htmlspecialchars($row['no_whatsapp']); ?></span>
                                                <?php else: ?>
                                                    <span class="wa-missing">Belum terdaftar</span>
                                                    <a href="user_form.php?search=<?php echo urlencode($row['nama']); ?>" class="btn-link-small">Daftarkan</a>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge-warning"><?php echo $row['jumlah']; ?> data</span></td>
                                            <td>
                                                <?php if (!empty($row['no_whatsapp'])): ?>
                                                    <a href="notifikasi.php?send=1&nama=<?php echo urlencode($row['nama']); ?>&no_wa=<?php echo urlencode($row['no_whatsapp']); ?>" 
                                                       class="btn btn-whatsapp" 
                                                       target="_blank">
                                                        <span class="btn-icon">📱</span> Kirim Notifikasi
                                                    </a>
                                                <?php else: ?>
                                                    <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;">Tidak ada nomor</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Data Belum Upload -->
            <div class="section-title">
                <h2>📋 Data Belum Upload (7 Hari Terakhir)</h2>
                <span class="section-count">(<?php echo $data_belum_upload->num_rows; ?> data)</span>
            </div>

            <div class="table-container">
                <?php if ($data_belum_upload->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Pertemuan</th>
                                <th>Tanggal</th>
                                <th>Materi</th>
                                <th>No WhatsApp</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $data_belum_upload->data_seek(0);
                            while ($row = $data_belum_upload->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['nama']); ?></strong></td>
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
                                        <?php if (!empty($row['no_whatsapp'])): ?>
                                            <span class="wa-number"><?php echo htmlspecialchars($row['no_whatsapp']); ?></span>
                                        <?php else: ?>
                                            <span class="wa-missing">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="form.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                                        <?php if (!empty($row['no_whatsapp'])): ?>
                                            <a href="notifikasi.php?send=1&nama=<?php echo urlencode($row['nama']); ?>&no_wa=<?php echo urlencode($row['no_whatsapp']); ?>" 
                                               class="btn btn-whatsapp" 
                                               target="_blank">
                                                📱 Kirim
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;" title="Nomor WhatsApp belum terdaftar">📱</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">✅</div>
                        <h3>Semua Data Sudah Upload</h3>
                        <p>Semua data administrasi dalam 7 hari terakhir sudah memiliki foto.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $conn->close(); ?>
</body>
</html>

