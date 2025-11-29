<?php
/**
 * File untuk test apakah password hash di database valid
 * Jalankan file ini melalui browser untuk melihat hasil test
 */

require_once 'config.php';

$conn = getConnection();

// Ambil semua user
$result = $conn->query("SELECT id, username, password, nama_lengkap FROM users");

echo "<h2>Test Password Hash</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Password Hash</th><th>Test admin123</th><th>Status</th></tr>";

while ($user = $result->fetch_assoc()) {
    $test_password = 'admin123';
    $is_valid = password_verify($test_password, $user['password']);
    
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
    echo "<td>" . substr($user['password'], 0, 30) . "...</td>";
    echo "<td>" . ($is_valid ? "✅ VALID" : "❌ TIDAK VALID") . "</td>";
    echo "<td>" . ($is_valid ? "<span style='color: green;'>Password cocok</span>" : "<span style='color: red;'>Password tidak cocok</span>") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Generate hash baru untuk admin123
echo "<br><br><h3>Hash baru untuk password 'admin123':</h3>";
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "<code>" . $new_hash . "</code>";

echo "<br><br><p><strong>Jika hash tidak valid, gunakan file fix_password.php untuk memperbaiki password.</strong></p>";

$conn->close();
?>

