<?php
require_once 'config.php';
require_once 'auth.php';

// Jika sudah login, redirect ke index
redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($username) || empty($password)) {
    header('Location: login.php?error=' . urlencode('Username dan password harus diisi!'));
    exit;
}

$conn = getConnection();

// Cari user berdasarkan username
$stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header('Location: login.php?error=' . urlencode('Username atau password salah!') . '&username=' . urlencode($username));
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verifikasi password
if (!password_verify($password, $user['password'])) {
    $conn->close();
    header('Location: login.php?error=' . urlencode('Username atau password salah!') . '&username=' . urlencode($username));
    exit;
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['role'] = $user['role'] ?? 'user';

$conn->close();

// Redirect ke halaman utama
header('Location: index.php');
exit;
?>

