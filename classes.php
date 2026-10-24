<?php
require_once __DIR__ . '/includes/functions.php';
require_login(['admin', 'guru_piket']);

$db = get_db();
$flash = flash_get();
$error = '';

// Handle Add Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $grade = (int)($_POST['grade'] ?? 10);
    $academic_year = trim($_POST['academic_year'] ?? '2024/2025');
    $wali_id = !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null;

    if (empty($name)) {
        $error = 'Nama kelas wajib diisi!';
    } else {
        $stmt = $db->prepare("INSERT INTO classes (name, grade, academic_year, wali_kelas_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $grade, $academic_year, $wali_id]);
        flash_set('success', "Kelas '$name' berhasil ditambahkan!");
        header('Location: classes.php');
        exit;
    }
}

// Handle Delete Class
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    // Check if class has students
    $count = $db->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        flash_set('danger', 'Kelas tidak dapat dihapus karena masih memiliki data siswa!');
    } else {
        $stmt = $db->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Kelas berhasil dihapus!');
    }
    header('Location: classes.php');
    exit;
}

// Fetch classes with student counts
$sql = "
    SELECT c.*, u.name as wali_name, COUNT(s.id) as total_students
    FROM classes c
    LEFT JOIN users u ON c.wali_kelas_id = u.id
    LEFT JOIN students s ON c.id = s.class_id
    GROUP BY c.id
    ORDER BY c.grade ASC, c.name ASC
";
$classes = $db->query($sql)->fetchAll();

// Fetch potential wali kelas
$walis = $db->query("SELECT id, name FROM users WHERE role = 'wali_kelas' ORDER BY name ASC")->fetchAll();

$page_title = 'Data Kelas';
$page_heading = 'Manajemen Data Kelas';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body">
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i data-feather="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
                <div><?= htmlspecialchars($flash['message']) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i data-feather="alert-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 340px 1fr; gap:24px; align-items:start;">
            
            <!-- Left: Add Class Form -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="plus-circle"></i> Tambah Kelas Baru
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="classes.php">
                        <input type="hidden" name="action" value="add">

                        <div class="form-group">
                            <label class="form-label">Nama Kelas</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: X RPL 1">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tingkat / Grade</label>
                            <select name="grade" class="form-select">
                                <option value="10">Kelas 10 (Sepuluh)</option>
                                <option value="11">Kelas 11 (Sebelas)</option>
                                <option value="12">Kelas 12 (Dua Belas)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tahun Ajaran</label>
                            <input type="text" name="academic_year" class="form-control" required value="2024/2025" placeholder="Contoh: 2024/2025">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Wali Kelas (Opsional)</label>
                            <select name="wali_kelas_id" class="form-select">
                                <option value="">-- Belum Ditentukan --</option>
                                <?php foreach ($walis as $w): ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">
                            <i data-feather="plus"></i> Simpan Kelas
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Classes Table -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="layers"></i> Daftar Kelas (<?= count($classes) ?> Kelas)
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kelas</th>
                                <th>Tingkat</th>
                                <th>Tahun Ajaran</th>
                                <th>Wali Kelas</th>
                                <th>Jumlah Siswa</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">
                                        Belum ada data kelas terdaftar.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $idx => $c): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td style="font-weight:700; color:var(--primary);">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">Tingkat <?= $c['grade'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($c['academic_year']) ?></td>
                                        <td><?= htmlspecialchars($c['wali_name'] ?: '-') ?></td>
                                        <td>
                                            <a href="students.php?class_id=<?= $c['id'] ?>" style="font-weight:700; text-decoration:none; color:var(--accent-emerald);">
                                                <?= $c['total_students'] ?> Siswa &rarr;
                                            </a>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <a href="qr_generator.php?class_id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" title="Cetak ID Card 1 Kelas">
                                                    <i data-feather="printer"></i>
                                                </a>
                                                <a href="classes.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--accent-rose);" onclick="return confirm('Hapus kelas ini?')" title="Hapus Kelas">
                                                    <i data-feather="trash-2"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
