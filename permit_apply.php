<?php
require_once __DIR__ . '/includes/functions.php';

$db = get_db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis_or_id = trim($_POST['student_id'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['izin', 'sakit']) ? $_POST['type'] : 'izin';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($nis_or_id) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error = 'Semua kolom bertanda bintang (*) wajib diisi!';
    } else {
        // Find student
        $stmt_s = $db->prepare("SELECT id, name FROM students WHERE id = ? OR nis = ? LIMIT 1");
        $stmt_s->execute([$nis_or_id, $nis_or_id]);
        $student = $stmt_s->fetch();

        if (!$student) {
            $error = 'Siswa dengan NIS tersebut tidak ditemukan!';
        } else {
            $attachment_path = null;
            if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $filename = 'permit_' . $student['id'] . '_' . time() . '.' . $ext;
                    $dest = __DIR__ . '/uploads/permits/' . $filename;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                        $attachment_path = 'uploads/permits/' . $filename;
                    }
                }
            }

            $stmt_ins = $db->prepare("
                INSERT INTO permits (student_id, type, start_date, end_date, reason, attachment, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt_ins->execute([$student['id'], $type, $start_date, $end_date, $reason, $attachment_path]);

            $success = 'Pengajuan ' . strtoupper($type) . ' untuk ' . htmlspecialchars($student['name']) . ' berhasil dikirimkan dan menunggu verifikasi guru.';
        }
    }
}

$all_students = $db->query("SELECT s.id, s.name, s.nis, c.name as class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.is_active = 1 ORDER BY s.name ASC")->fetchAll();

$page_title = 'Formulir Pengajuan Izin / Sakit';
$page_heading = 'Form Pengajuan Izin / Sakit Siswa';
require_once __DIR__ . '/includes/header.php';
if (is_logged_in()) {
    require_once __DIR__ . '/includes/sidebar.php';
}
?>

<div class="main-wrapper" style="<?= !is_logged_in() ? 'margin-left:0; width:100%;' : '' ?>">
    <?php if (is_logged_in()): ?>
        <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <?php else: ?>
        <header class="top-navbar" style="background:#ffffff; border-bottom:1px solid var(--border-color);">
            <div class="top-title">
                <h1>Pengajuan Surat Izin / Sakit</h1>
                <p><?= get_setting('school_name') ?></p>
            </div>
            <a href="login.php" class="btn btn-primary btn-sm">Login Guru</a>
        </header>
    <?php endif; ?>

    <div class="content-body" style="max-width: 800px; margin: 0 auto; padding: 32px 20px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i data-feather="file-text"></i> Formulir Pengajuan Izin & Sakit Siswa
                </span>
                <?php if (is_logged_in()): ?>
                    <a href="permits.php" class="btn btn-outline btn-sm">
                        <i data-feather="arrow-left"></i> Kembali ke Daftar
                    </a>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i data-feather="alert-circle"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i data-feather="check-circle"></i>
                        <div><?= htmlspecialchars($success) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="permit_apply.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Pilih Siswa <span style="color:red;">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Pilih Siswa Berdasarkan NIS / Nama --</option>
                            <?php foreach ($all_students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['name']) ?> (NIS: <?= $st['nis'] ?> - <?= htmlspecialchars($st['class_name'] ?? 'Umum') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Kategori <span style="color:red;">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mulai Tanggal <span style="color:red;">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sampai Tanggal <span style="color:red;">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alasan / Penjelasan Lengkap <span style="color:red;">*</span></label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Tuliskan keterangan secara rinci (contoh: Sakit demam berdarah dirawat / izin ada keperluan keluarga di luar kota)" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unggah Surat Dokter / Surat Keterangan (Opsional)</label>
                        <input type="file" name="attachment" class="form-control" accept="image/jpeg,image/png,application/pdf">
                        <small style="color:var(--text-muted); font-size:11px;">Format JPG, PNG, atau PDF (Maks. 3MB)</small>
                    </div>

                    <div style="margin-top:24px;">
                        <button type="submit" class="btn btn-primary" style="padding:11px 28px; font-size:14px;">
                            <i data-feather="send"></i> Kirim Pengajuan Izin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
