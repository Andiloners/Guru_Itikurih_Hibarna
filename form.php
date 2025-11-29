<?php
require_once 'config.php';
require_once 'auth.php';

// Cek apakah user sudah login
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$data = null;
$isEdit = false;

if ($id > 0) {
    $isEdit = true;
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM administrasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Cek apakah user memiliki akses untuk edit data ini
        if (!isAdmin() && $data['nama'] != $_SESSION['nama_lengkap']) {
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
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Tambah'; ?> Data Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo $isEdit ? '✏️ Edit Data Administrasi' : '➕ Tambah Data Administrasi'; ?></h1>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </header>

        <div class="content">
            <form action="process.php" method="POST" enctype="multipart/form-data" class="form-container">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label for="nama">Nama Guru <span class="required">*</span></label>
                    <?php if (isAdmin()): ?>
                        <input type="text" 
                               id="nama" 
                               name="nama" 
                               required 
                               value="<?php echo $data ? htmlspecialchars($data['nama']) : ''; ?>"
                               placeholder="Masukkan nama guru">
                    <?php else: ?>
                        <input type="text" 
                               id="nama" 
                               name="nama" 
                               required 
                               value="<?php echo $data ? htmlspecialchars($data['nama']) : htmlspecialchars($_SESSION['nama_lengkap']); ?>"
                               placeholder="Masukkan nama guru"
                               readonly
                               style="background: #f0f0f0;">
                        <small class="help-text">Nama otomatis terisi dengan nama Anda</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mata_pelajaran">Mata Pelajaran <span class="required">*</span></label>
                    <input type="text" 
                           id="mata_pelajaran" 
                           name="mata_pelajaran" 
                           required 
                           value="<?php echo $data ? htmlspecialchars($data['mata_pelajaran']) : ''; ?>"
                           placeholder="Masukkan mata pelajaran">
                </div>

                <div class="form-group">
                    <label for="pertemuan_ke">Pertemuan Ke <span class="required">*</span></label>
                    <input type="number" 
                           id="pertemuan_ke" 
                           name="pertemuan_ke" 
                           required 
                           min="1"
                           value="<?php echo $data ? $data['pertemuan_ke'] : ''; ?>"
                           placeholder="Masukkan nomor pertemuan">
                </div>

                <div class="form-group">
                    <label for="tanggal">Tanggal <span class="required">*</span></label>
                    <input type="date" 
                           id="tanggal" 
                           name="tanggal" 
                           required 
                           value="<?php echo $data ? $data['tanggal'] : date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="materi">Materi yang Disampaikan <span class="required">*</span></label>
                    <textarea id="materi" 
                              name="materi" 
                              required 
                              rows="5"
                              placeholder="Masukkan materi yang disampaikan"><?php echo $data ? htmlspecialchars($data['materi']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="foto">Foto (Maksimal 2MB)</label>
                    <input type="file" 
                           id="foto" 
                           name="foto" 
                           accept="image/*"
                           onchange="validateFile(this)">
                    <small class="help-text">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                    <?php if ($data && $data['foto']): ?>
                        <div class="current-photo">
                            <div class="photo-header">
                                <p>Foto saat ini:</p>
                                <a href="download_foto.php?id=<?php echo $data['id']; ?>" 
                                   class="btn btn-download" 
                                   title="Download Foto">
                                    <span class="btn-icon">📥</span> Download
                                </a>
                            </div>
                            <img src="<?php echo UPLOAD_DIR . $data['foto']; ?>" alt="Current photo" class="preview-photo">
                        </div>
                    <?php endif; ?>
                    <div id="fileError" class="error-message"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? 'Update Data' : 'Simpan Data'; ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateFile(input) {
            const file = input.files[0];
            const errorDiv = document.getElementById('fileError');
            const maxSize = 2 * 1024 * 1024; // 2MB

            errorDiv.textContent = '';

            if (file) {
                if (file.size > maxSize) {
                    errorDiv.textContent = 'Ukuran file terlalu besar! Maksimal 2MB.';
                    input.value = '';
                    return false;
                }

                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    errorDiv.textContent = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.';
                    input.value = '';
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>

