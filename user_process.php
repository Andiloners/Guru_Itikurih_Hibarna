<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user adalah admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$username = trim($_POST['username'] ?? '');
$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$no_whatsapp = trim($_POST['no_whatsapp'] ?? '');
$role = trim($_POST['role'] ?? 'user');
$password = $_POST['password'] ?? '';
$change_password = isset($_POST['change_password']) && $_POST['change_password'] == '1';

// Validasi role
if (!in_array($role, ['admin', 'user'])) {
    $role = 'user';
}

// Bersihkan nomor WhatsApp (hapus karakter non-numeric)
$no_whatsapp = preg_replace('/[^0-9]/', '', $no_whatsapp);

// Validasi input
if (empty($username) || empty($nama_lengkap)) {
    header('Location: user_form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Username dan nama lengkap harus diisi!'));
    exit;
}

// Validasi password
if ($id == 0 && empty($password)) {
    header('Location: user_form.php?error=' . urlencode('Password harus diisi!'));
    exit;
}

if (!empty($password) && strlen($password) < 6) {
    header('Location: user_form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Password minimal 6 karakter!'));
    exit;
}

$conn = getConnection();

// Cek apakah username sudah ada (untuk user baru)
if ($id == 0) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        header('Location: user_form.php?error=' . urlencode('Username sudah digunakan!'));
        exit;
    }
    $stmt->close();
}

// Insert or Update
if ($id > 0) {
    // Update
    if ($change_password && !empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, no_whatsapp = ?, role = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nama_lengkap, $no_whatsapp, $role, $password_hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, no_whatsapp = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama_lengkap, $no_whatsapp, $role, $id);
    }
} else {
    // Insert
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, no_whatsapp, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $password_hash, $nama_lengkap, $no_whatsapp, $role);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: users.php?success=' . ($id > 0 ? 'edit' : 'add'));
    exit;
} else {
    $error_msg = $conn->error;
    $stmt->close();
    $conn->close();
    header('Location: user_form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Gagal menyimpan data: ' . $error_msg));
    exit;
}
?>

