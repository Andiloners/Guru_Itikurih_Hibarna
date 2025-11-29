<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user sudah login
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: index.php?error=' . urlencode('ID tidak valid!'));
    exit;
}

$id = intval($_GET['id']);

$conn = getConnection();

// Ambil data foto
$stmt = $conn->prepare("SELECT foto, nama, mata_pelajaran FROM administrasi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header('Location: index.php?error=' . urlencode('Data tidak ditemukan!'));
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Cek apakah foto ada
if (empty($row['foto']) || !file_exists(UPLOAD_DIR . $row['foto'])) {
    header('Location: index.php?error=' . urlencode('File foto tidak ditemukan!'));
    exit;
}

$file_path = UPLOAD_DIR . $row['foto'];
$file_name = $row['foto'];

// Generate nama file untuk download (dengan nama guru dan mata pelajaran)
$nama_guru = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['nama']);
$mata_pelajaran = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['mata_pelajaran']);
$extension = pathinfo($file_name, PATHINFO_EXTENSION);
$download_name = $nama_guru . '_' . $mata_pelajaran . '_' . $id . '.' . $extension;

// Set headers untuk download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
readfile($file_path);
exit;
?>

