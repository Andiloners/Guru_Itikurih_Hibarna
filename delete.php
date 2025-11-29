<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user sudah login
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

$conn = getConnection();

// Ambil data sebelum menghapus (termasuk nama untuk cek ownership)
$stmt = $conn->prepare("SELECT foto, nama FROM administrasi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Cek apakah user memiliki akses untuk menghapus data ini
    if (!isAdmin() && $row['nama'] != $_SESSION['nama_lengkap']) {
        $stmt->close();
        $conn->close();
        header('Location: index.php?error=' . urlencode('Anda tidak memiliki akses untuk menghapus data ini!'));
        exit;
    }
    
    // Hapus file foto jika ada
    if ($row['foto'] && file_exists(UPLOAD_DIR . $row['foto'])) {
        unlink(UPLOAD_DIR . $row['foto']);
    }
    
    // Hapus data dari database
    $stmt = $conn->prepare("DELETE FROM administrasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $conn->close();
        header('Location: index.php?success=delete');
        exit;
    }
}

$conn->close();
header('Location: index.php?error=' . urlencode('Gagal menghapus data!'));
exit;
?>

