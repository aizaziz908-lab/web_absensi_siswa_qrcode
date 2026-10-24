<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$flash = flash_get();
$error = '';

$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_class = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;
$filter_status = !empty($_GET['status']) ? $_GET['status'] : null;

// Handle manual attendance update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_attendance') {
    $att_id = (int)($_POST['attendance_id'] ?? 0);
    $student_id = (int)($_POST['student_id'] ?? 0);
    $status = $_POST['status'] ?? 'hadir';
    $notes = trim($_POST['notes'] ?? '');
    $time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : null;
    $time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : null;

    if ($att_id > 0) {
        $stmt = $db->prepare("UPDATE attendance SET status = ?, time_in = ?, time_out = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $time_in, $time_out, $notes, $att_id]);
        flash_set('success', 'Status presensi berhasil diperbarui!');
    } elseif ($student_id > 0) {
        $stmt = $db->prepare("INSERT INTO attendance (student_id, date, status, time_in, time_out, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $filter_date, $status, $time_in, $time_out, $notes]);
        flash_set('success', 'Presensi baru berhasil dicatat!');
    }
    header("Location: attendance.php?date=$filter_date" . ($filter_class ? "&class_id=$filter_class" : ""));
    exit;
}

// Fetch all students in the selected class (or all) to show complete attendance sheet
$sql_sheet = "
    SELECT 
        s.id as student_id, s.name, s.nis, s.gender, s.parent_name, s.parent_phone,
        c.name as class_name,
        a.id as attendance_id, a.date, a.time_in, a.time_out, a.status, a.snapshot_in, a.snapshot_out, a.notes
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ?
    WHERE s.is_active = 1
";
$params = [$filter_date];

if ($filter_class) {
    $sql_sheet .= " AND s.class_id = ?";
    $params[] = $filter_class;
}

if ($filter_status) {
    if ($filter_status === 'unrecorded') {
        $sql_sheet .= " AND a.id IS NULL";
    } else {
        $sql_sheet .= " AND a.status = ?";
        $params[] = $filter_status;
    }
}

$sql_sheet .= " ORDER BY c.name ASC, s.name ASC";
$stmt = $db->prepare($sql_sheet);
$stmt->execute($params);
$sheet = $stmt->fetchAll();

$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();

$page_title = 'Presensi Harian Siswa';
$page_heading = 'Rekap Presensi Harian';
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

        <!-- Toolbar Filter -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:18px 24px;">
                <form method="GET" action="attendance.php" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
                    <div>
                        <label class="form-label">Tanggal Presensi</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()">
                    </div>

                    <div style="width:200px;">
                        <label class="form-label">Filter Kelas</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $filter_class == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="width:180px;">
                        <label class="form-label">Status Kehadiran</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="hadir" <?= $filter_status === 'hadir' ? 'selected' : '' ?>>Hadir Tepat Waktu</option>
                            <option value="terlambat" <?= $filter_status === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                            <option value="izin" <?= $filter_status === 'izin' ? 'selected' : '' ?>>Izin</option>
                            <option value="sakit" <?= $filter_status === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                            <option value="alpa" <?= $filter_status === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                            <option value="unrecorded" <?= $filter_status === 'unrecorded' ? 'selected' : '' ?>>Belum Scan</option>
                        </select>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="filter"></i> Terapkan
                        </button>
                        <a href="attendance.php" class="btn btn-outline">Hari Ini</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Sheet Table -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i data-feather="calendar"></i> Lembar Presensi: <?= format_date_id($filter_date) ?>
                </span>
                <span class="badge badge-primary"><?= count($sheet) ?> Siswa Tercatat</span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                            <th>Foto Selfie</th>
                            <th>Keterangan</th>
                            <th style="text-align:right;">Aksi & WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sheet)): ?>
                            <tr>
                                <td colspan="10" style="text-align:center; padding:32px; color:var(--text-muted);">
                                    Tidak ada data siswa ditemukan untuk kriteria ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sheet as $idx => $row): 
                                $status = $row['status'] ?: 'Belum Scan';
                            ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><b><?= htmlspecialchars($row['nis']) ?></b></td>
                                    <td>
                                        <div style="font-weight:700;"><?= htmlspecialchars($row['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= htmlspecialchars($row['class_name'] ?? 'Umum') ?></span>
                                    </td>
                                    <td style="font-weight:700; color:var(--accent-emerald);">
                                        <?= format_time($row['time_in']) ?>
                                    </td>
                                    <td style="font-weight:700; color:var(--accent-amber);">
                                        <?= format_time($row['time_out']) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status']): ?>
                                            <?= render_status_badge($row['status']) ?>
                                        <?php else: ?>
                                            <span class="badge badge-dark"><i data-feather="clock"></i> Belum Scan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['snapshot_in'])): ?>
                                            <a href="<?= htmlspecialchars($row['snapshot_in']) ?>" target="_blank">
                                                <img src="<?= htmlspecialchars($row['snapshot_in']) ?>" style="width:36px; height:36px; border-radius:6px; object-fit:cover; border:1px solid #cbd5e1;" title="Klik untuk memperbesar">
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-light); font-size:11px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px; color:var(--text-muted);">
                                        <?= htmlspecialchars($row['notes'] ?: '-') ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; gap:6px;">
                                            <!-- Edit Modal Button -->
                                            <button type="button" class="btn btn-outline btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" title="Ubah Presensi Manual">
                                                <i data-feather="edit-2"></i>
                                            </button>

                                            <!-- WA Button -->
                                            <?php if (!empty($row['parent_phone'])): 
                                                $wa_msg = "Pemberitahuan Sekolah: Ananda *" . $row['name'] . "* pada tanggal " . format_date_id($filter_date) . " status: *" . strtoupper($row['status'] ?: 'BELUM HADIR') . "*.";
                                                $wa_url = generate_wa_link($row['parent_phone'], $wa_msg);
                                            ?>
                                                <a href="<?= $wa_url ?>" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff;" title="Kirim WA ke Orang Tua">
                                                    <i data-feather="message-circle"></i>
                                                </a>
                                            <?php endif; ?>
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

<!-- Edit Attendance Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); z-index:999; align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:var(--radius-xl); max-width:480px; width:90%; padding:24px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size:17px; font-weight:800; color:var(--text-main);" id="modalStudentName">Ubah Presensi Manual</h3>
            <button type="button" onclick="closeEditModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form method="POST" action="attendance.php?date=<?= urlencode($filter_date) ?>">
            <input type="hidden" name="action" value="update_attendance">
            <input type="hidden" name="attendance_id" id="modalAttId">
            <input type="hidden" name="student_id" id="modalStudentId">

            <div class="form-group">
                <label class="form-label">Status Presensi</label>
                <select name="status" id="modalStatus" class="form-select" required>
                    <option value="hadir">Hadir Tepat Waktu</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="alpa">Alpa / Tanpa Keterangan</option>
                </select>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Jam Masuk (HH:MM)</label>
                    <input type="time" name="time_in" id="modalTimeIn" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Pulang (HH:MM)</label>
                    <input type="time" name="time_out" id="modalTimeOut" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Catatan / Keterangan</label>
                <input type="text" name="notes" id="modalNotes" class="form-control" placeholder="Contoh: Sakit demam dokter atau izin dinas">
            </div>

            <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(row) {
    document.getElementById('modalStudentName').textContent = 'Presensi: ' + row.name;
    document.getElementById('modalAttId').value = row.attendance_id || '';
    document.getElementById('modalStudentId').value = row.student_id;
    document.getElementById('modalStatus').value = row.status || 'hadir';
    document.getElementById('modalTimeIn').value = row.time_in ? row.time_in.substring(0, 5) : '';
    document.getElementById('modalTimeOut').value = row.time_out ? row.time_out.substring(0, 5) : '';
    document.getElementById('modalNotes').value = row.notes || '';

    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
