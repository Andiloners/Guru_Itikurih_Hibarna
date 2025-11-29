<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user adalah admin
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$id = intval($_GET['id']);

// Cegah user menghapus dirinya sendiri
if ($id == $_SESSION['user_id']) {
    header('Location: users.php?error=' . urlencode('Anda tidak dapat menghapus akun sendiri!'));
    exit;
}

$conn = getConnection();

// Cek apakah user ada
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header('Location: users.php?error=' . urlencode('User tidak ditemukan!'));
    exit;
}

// Hapus user
$stmt->close();
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: users.php?success=delete');
    exit;
} else {
    $error_msg = $conn->error;
    $stmt->close();
    $conn->close();
    header('Location: users.php?error=' . urlencode('Gagal menghapus user: ' . $error_msg));
    exit;
}
?>

