<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    flash_set('danger', 'Data siswa tidak ditemukan!');
    header('Location: students.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? 'L';
    $class_id = (int)($_POST['class_id'] ?? 0);
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $regen_qr = !empty($_POST['regen_qr']);

    if (empty($nis) || empty($name) || empty($class_id)) {
        $error = 'NIS, Nama Siswa, dan Kelas wajib diisi!';
    } else {
        try {
            // Check NIS uniqueness for other students
            $check = $db->prepare("SELECT COUNT(*) FROM students WHERE nis = ? AND id != ?");
            $check->execute([$nis, $id]);
            if ($check->fetchColumn() > 0) {
                $error = 'NIS ' . htmlspecialchars($nis) . ' sudah digunakan oleh siswa lain!';
            } else {
                $photo_path = $student['photo'];
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

                $qr_token = $student['qr_token'];
                if ($regen_qr || empty($qr_token)) {
                    $qr_token = generate_student_qr_token($nis);
                }

                $stmt = $db->prepare("
                    UPDATE students 
                    SET nis = ?, nisn = ?, name = ?, gender = ?, class_id = ?, parent_name = ?, parent_phone = ?, photo = ?, qr_token = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$nis, $nisn, $name, $gender, $class_id, $parent_name, $parent_phone, $photo_path, $qr_token, $id]);

                flash_set('success', 'Data siswa ' . htmlspecialchars($name) . ' berhasil diperbarui!');
                header('Location: students.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();

$page_title = 'Edit Data Siswa';
$page_heading = 'Edit Data Siswa';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body" style="max-width: 900px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i data-feather="edit-2"></i> Edit Siswa: <?= htmlspecialchars($student['name']) ?>
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

                <form method="POST" action="student_edit.php?id=<?= $id ?>" enctype="multipart/form-data">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Nomor Induk Siswa (NIS)</label>
                            <input type="text" name="nis" class="form-control" required value="<?= htmlspecialchars($student['nis']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">NISN</label>
                            <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($student['nisn'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($student['name']) ?>">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="L" <?= $student['gender'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= $student['gender'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kelas</label>
                            <select name="class_id" class="form-select" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $student['class_id'] == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Nama Orang Tua / Wali</label>
                            <input type="text" name="parent_name" class="form-control" value="<?= htmlspecialchars($student['parent_name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp Orang Tua</label>
                            <input type="text" name="parent_phone" class="form-control" value="<?= htmlspecialchars($student['parent_phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Foto Siswa</label>
                        <?php if (!empty($student['photo'])): ?>
                            <div style="margin-bottom:8px;">
                                <img src="<?= htmlspecialchars($student['photo']) ?>" style="width:60px; height:60px; border-radius:8px; object-fit:cover; border:1px solid #cbd5e1;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="form-group" style="padding:14px; background:#f8fafc; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px;">
                            <input type="checkbox" name="regen_qr" value="1">
                            <span>Perbarui / Buat Ulang Token QR Code Unik (Reset Kode QR)</span>
                        </label>
                        <p style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                            Token QR saat ini: <code><?= htmlspecialchars($student['qr_token']) ?></code>
                        </p>
                    </div>

                    <div style="margin-top:24px; display:flex; gap:12px;">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                            <i data-feather="check"></i> Simpan Perubahan
                        </button>
                        <a href="students.php" class="btn btn-outline">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
