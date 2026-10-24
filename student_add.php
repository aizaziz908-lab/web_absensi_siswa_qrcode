<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? 'L';
    $class_id = (int)($_POST['class_id'] ?? 0);
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');

    if (empty($nis) || empty($name) || empty($class_id)) {
        $error = 'NIS, Nama Siswa, dan Kelas wajib diisi!';
    } else {
        try {
            // Check NIS uniqueness
            $check = $db->prepare("SELECT COUNT(*) FROM students WHERE nis = ?");
            $check->execute([$nis]);
            if ($check->fetchColumn() > 0) {
                $error = 'NIS ' . htmlspecialchars($nis) . ' sudah terdaftar!';
            } else {
                $photo_path = null;
                if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $filename = 'student_' . preg_replace('/[^a-zA-Z0-9]/', '', $nis) . '_' . time() . '.' . $ext;
                        $dest = __DIR__ . '/uploads/photos/' . $filename;
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                            $photo_path = 'uploads/photos/' . $filename;
                        }
                    }
                }

                $qr_token = generate_student_qr_token($nis);

                $stmt = $db->prepare("
                    INSERT INTO students (nis, nisn, name, gender, class_id, parent_name, parent_phone, photo, qr_token) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nis, $nisn, $name, $gender, $class_id, $parent_name, $parent_phone, $photo_path, $qr_token]);

                flash_set('success', 'Siswa ' . htmlspecialchars($name) . ' berhasil ditambahkan!');
                header('Location: students.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();

$page_title = 'Tambah Siswa Baru';
$page_heading = 'Tambah Siswa Baru';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body" style="max-width: 900px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i data-feather="user-plus"></i> Formulir Tambah Siswa
                </span>
                <a href="students.php" class="btn btn-outline btn-sm">
                    <i data-feather="arrow-left"></i> Kembali
                </a>
            </div>

            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i data-feather="alert-circle"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student_add.php" enctype="multipart/form-data">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Nomor Induk Siswa (NIS) <span style="color:red;">*</span></label>
                            <input type="text" name="nis" class="form-control" required placeholder="Contoh: 10115" value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">NISN (Opsional)</label>
                            <input type="text" name="nisn" class="form-control" placeholder="Contoh: 0054321015" value="<?= htmlspecialchars($_POST['nisn'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Siswa <span style="color:red;">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Masukkan nama lengkap siswa" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="L" <?= ($_POST['gender'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($_POST['gender'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kelas <span style="color:red;">*</span></label>
                            <select name="class_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($_POST['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <input type="text" name="parent_name" class="form-control" placeholder="Nama ayah / ibu / wali" value="<?= htmlspecialchars($_POST['parent_name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp Orang Tua</label>
                            <input type="text" name="parent_phone" class="form-control" placeholder="Contoh: 08123456789" value="<?= htmlspecialchars($_POST['parent_phone'] ?? '') ?>">
                            <small style="color:var(--text-muted); font-size:11px;">Digunakan untuk pengiriman notifikasi kehadiran otomatis.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Foto Siswa (Opsional)</label>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small style="color:var(--text-muted); font-size:11px;">Format JPG, PNG, atau WebP (Maks. 2MB)</small>
                    </div>

                    <div style="margin-top:24px; display:flex; gap:12px;">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                            <i data-feather="save"></i> Simpan Data Siswa
                        </button>
                        <a href="students.php" class="btn btn-outline">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
