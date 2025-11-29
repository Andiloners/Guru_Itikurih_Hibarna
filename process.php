<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user sudah login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nama = trim($_POST['nama'] ?? '');
$mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
$pertemuan_ke = intval($_POST['pertemuan_ke'] ?? 0);
$tanggal = $_POST['tanggal'] ?? '';
$materi = trim($_POST['materi'] ?? '');

// Validasi input
if (empty($nama) || empty($mata_pelajaran) || $pertemuan_ke <= 0 || empty($tanggal) || empty($materi)) {
    header('Location: form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Semua field wajib diisi!'));
    exit;
}

$conn = getConnection();

// Jika edit, cek apakah user memiliki akses untuk update data ini
if ($id > 0) {
    $stmt = $conn->prepare("SELECT nama FROM administrasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing_data = $result->fetch_assoc();
        
        // Cek apakah user memiliki akses untuk update data ini
        if (!isAdmin() && $existing_data['nama'] != $_SESSION['nama_lengkap']) {
            $stmt->close();
            $conn->close();
            header('Location: index.php?error=' . urlencode('Anda tidak memiliki akses untuk mengedit data ini!'));
            exit;
        }
    } else {
        $stmt->close();
        $conn->close();
        header('Location: index.php?error=' . urlencode('Data tidak ditemukan!'));
        exit;
    }
    $stmt->close();
}

// Jika insert, pastikan nama sesuai dengan user yang login (kecuali admin)
if ($id == 0 && !isAdmin()) {
    $nama = $_SESSION['nama_lengkap'];
}

$foto = null;

// Handle file upload
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto'];
    
    // Validasi ukuran file (2MB)
    if ($file['size'] > MAX_FILE_SIZE) {
        header('Location: form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Ukuran file terlalu besar! Maksimal 2MB.'));
        exit;
    }

    // Validasi tipe file
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        header('Location: form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Format file tidak didukung!'));
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $foto = uniqid('foto_', true) . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $foto;

    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        header('Location: form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Gagal mengupload file!'));
        exit;
    }

    // Jika edit, hapus foto lama
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT foto FROM administrasi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc() && $row['foto']) {
            $oldPhoto = UPLOAD_DIR . $row['foto'];
            if (file_exists($oldPhoto)) {
                unlink($oldPhoto);
            }
        }
        $stmt->close();
    }
}

// Insert or Update
if ($id > 0) {
    // Update
    if ($foto) {
        $stmt = $conn->prepare("UPDATE administrasi SET nama = ?, mata_pelajaran = ?, pertemuan_ke = ?, tanggal = ?, materi = ?, foto = ? WHERE id = ?");
        $stmt->bind_param("ssisssi", $nama, $mata_pelajaran, $pertemuan_ke, $tanggal, $materi, $foto, $id);
    } else {
        $stmt = $conn->prepare("UPDATE administrasi SET nama = ?, mata_pelajaran = ?, pertemuan_ke = ?, tanggal = ?, materi = ? WHERE id = ?");
        $stmt->bind_param("ssissi", $nama, $mata_pelajaran, $pertemuan_ke, $tanggal, $materi, $id);
    }
} else {
    // Insert
    if ($foto) {
        $stmt = $conn->prepare("INSERT INTO administrasi (nama, mata_pelajaran, pertemuan_ke, tanggal, materi, foto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $nama, $mata_pelajaran, $pertemuan_ke, $tanggal, $materi, $foto);
    } else {
        $stmt = $conn->prepare("INSERT INTO administrasi (nama, mata_pelajaran, pertemuan_ke, tanggal, materi) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $nama, $mata_pelajaran, $pertemuan_ke, $tanggal, $materi);
    }
}

if ($stmt->execute()) {
    $conn->close();
    header('Location: index.php?success=' . ($id > 0 ? 'edit' : 'add'));
    exit;
} else {
    $conn->close();
    header('Location: form.php?' . ($id > 0 ? 'id=' . $id . '&' : '') . 'error=' . urlencode('Gagal menyimpan data!'));
    exit;
}
?>

