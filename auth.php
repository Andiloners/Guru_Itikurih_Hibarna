<?php
// File untuk pengecekan session dan autentikasi
session_start();

// Fungsi untuk memeriksa apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Fungsi untuk memeriksa apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fungsi untuk redirect ke halaman login jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Fungsi untuk redirect ke halaman utama jika belum login atau bukan admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php?error=' . urlencode('Akses ditolak! Hanya admin yang dapat mengakses halaman ini.'));
        exit;
    }
}

// Fungsi untuk redirect ke halaman utama jika sudah login
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}
?>

