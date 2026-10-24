<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Data Siswa';
$page_heading = 'Manajemen Data Siswa';

$db = get_db();
$flash = flash_get();

// Filters & Search
$search = trim($_GET['q'] ?? '');
$class_filter = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

$query = "
    SELECT s.*, c.name as class_name 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.nis LIKE ? OR s.nisn LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($class_filter) {
    $query .= " AND s.class_id = ?";
    $params[] = $class_filter;
}

$query .= " ORDER BY s.name ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch classes for dropdown
$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();

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

        <!-- Action Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
            <div>
                <h2 style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--text-main);">
                    Daftar Siswa (<?= count($students) ?> Siswa)
                </h2>
                <p style="font-size:13px; color:var(--text-muted);">
                    Kelola data identitas siswa, kelas, dan kode QR unik presensi.
                </p>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="qr_generator.php" class="btn btn-outline">
                    <i data-feather="printer"></i> Cetak Kartu QR
                </a>
                <a href="student_import.php" class="btn btn-outline">
                    <i data-feather="upload"></i> Impor CSV
                </a>
                <a href="student_add.php" class="btn btn-primary">
                    <i data-feather="user-plus"></i> Tambah Siswa Baru
                </a>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:16px 20px;">
                <form method="GET" action="students.php" style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
                    <div style="flex:1; min-width:240px;">
                        <input type="text" name="q" class="form-control" placeholder="Cari nama siswa, NIS, atau NISN..." value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div style="width:220px;">
                        <select name="class_id" class="form-select">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $class_filter == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i data-feather="search"></i> Cari
                    </button>

                    <?php if (!empty($search) || $class_filter): ?>
                        <a href="students.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>NIS / NISN</th>
                            <th>Nama Lengkap</th>
                            <th>L/P</th>
                            <th>Kelas</th>
                            <th>Orang Tua & WhatsApp</th>
                            <th>Token QR</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center; padding:32px; color:var(--text-muted);">
                                    Tidak ada data siswa ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $idx => $s): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <img src="<?= !empty($s['photo']) ? htmlspecialchars($s['photo']) : 'assets/images/default-avatar.svg' ?>" 
                                             style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid #cbd5e1;" alt="Avatar">
                                    </td>
                                    <td>
                                        <div style="font-weight:700; color:var(--primary);"><?= htmlspecialchars($s['nis']) ?></div>
                                        <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($s['nisn'] ?: '-') ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:700;"><?= htmlspecialchars($s['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $s['gender'] === 'L' ? 'badge-primary' : 'badge-danger' ?>" style="padding:2px 8px; font-size:11px;">
                                            <?= $s['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= htmlspecialchars($s['class_name'] ?? 'Umum') ?></span>
                                    </td>
                                    <td>
                                        <div style="font-size:12px; font-weight:600;"><?= htmlspecialchars($s['parent_name'] ?: '-') ?></div>
                                        <?php if (!empty($s['parent_phone'])): ?>
                                            <a href="https://wa.me/<?= clean_phone_wa($s['parent_phone']) ?>" target="_blank" style="font-size:11px; color:#25d366; font-weight:700; text-decoration:none;">
                                                <i data-feather="message-circle" style="width:11px;height:11px;vertical-align:middle;"></i> <?= htmlspecialchars($s['parent_phone']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-light);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code style="background:#f1f5f9; padding:3px 6px; border-radius:4px; font-size:11px; font-weight:700; color:var(--primary);">
                                            <?= htmlspecialchars(substr($s['qr_token'], 0, 10)) ?>...
                                        </code>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; gap:6px;">
                                            <a href="qr_generator.php?student_id=<?= $s['id'] ?>" class="btn btn-outline btn-sm" title="Lihat Kartu QR">
                                                <i data-feather="credit-card"></i>
                                            </a>
                                            <a href="student_edit.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm" title="Edit Data">
                                                <i data-feather="edit-2"></i>
                                            </a>
                                            <a href="student_delete.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--accent-rose);" onclick="return confirm('Hapus siswa ini beserta riwayat presensinya?')" title="Hapus">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
