<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$school_name = get_setting('school_name', 'SMK BINA BANGSA NUSANTARA');
$school_address = get_setting('school_address', 'Jl. Pendidikan Merdeka No. 108');

// Filter parameters
$selected_student_id = !empty($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$selected_class_id = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

$query = "
    SELECT s.*, c.name as class_name, c.academic_year 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.is_active = 1
";
$params = [];

if ($selected_student_id) {
    $query .= " AND s.id = ?";
    $params[] = $selected_student_id;
} elseif ($selected_class_id) {
    $query .= " AND s.class_id = ?";
    $params[] = $selected_class_id;
} else {
    // Default limit to 8 if no filter, or all
    $query .= " ORDER BY s.name ASC LIMIT 12";
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$cards = $stmt->fetchAll();

// Classes list for filter
$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();
// Students list for filter
$all_students = $db->query("SELECT id, name, nis FROM students ORDER BY name ASC")->fetchAll();

$page_title = 'Cetak Kartu Pelajar & QR Code';
$page_heading = 'Generator & Cetak Kartu Pelajar QR';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body">
        
        <!-- Controls Toolbar (Hidden on print) -->
        <div class="card no-print" style="margin-bottom:24px;">
            <div class="card-body" style="padding:18px 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                    <div>
                        <h3 style="font-size:17px; font-weight:800; color:var(--text-main);">
                            Generator & Cetak Kartu Pelajar dengan QR Code
                        </h3>
                        <p style="font-size:12px; color:var(--text-muted);">
                            Pilih siswa perorangan atau cetak massal satu kelas sekaligus dalam format siap cetak.
                        </p>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i data-feather="printer"></i> Cetak Sekarang (Print / PDF)
                        </button>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid var(--border-color); margin:16px 0;">

                <!-- Filter Forms -->
                <form method="GET" action="qr_generator.php" style="display:flex; gap:16px; flex-wrap:wrap; align-items:center;">
                    <div style="width:240px;">
                        <label class="form-label">Cetak Per Kelas (Batch)</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selected_class_id == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="width:260px;">
                        <label class="form-label">Atau Pilih Siswa Perorangan</label>
                        <select name="student_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($all_students as $st): ?>
                                <option value="<?= $st['id'] ?>" <?= $selected_student_id == $st['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($st['name']) ?> (<?= $st['nis'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($selected_class_id || $selected_student_id): ?>
                        <div style="margin-top:22px;">
                            <a href="qr_generator.php" class="btn btn-outline btn-sm">Tampilkan Semua</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Print Container / Grid of ID Cards -->
        <?php if (empty($cards)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center; padding:40px; color:var(--text-muted);">
                    <i data-feather="inbox" style="width:48px;height:48px;margin-bottom:12px;color:var(--text-light);"></i>
                    <h3>Tidak ada data siswa untuk dicetak</h3>
                    <p style="font-size:13px;">Silakan pilih kelas atau tambahkan data siswa terlebih dahulu.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="id-cards-grid">
                <?php foreach ($cards as $idx => $st): ?>
                    <div class="id-card" id="card-<?= $st['id'] ?>">
                        <!-- Header -->
                        <div class="id-card-header">
                            <div>
                                <div class="id-school-title"><?= htmlspecialchars($school_name) ?></div>
                                <div style="font-size:8px; opacity:0.85;">KARTU TANDA PELAJAR</div>
                            </div>
                            <span class="id-card-badge">RESMI</span>
                        </div>

                        <!-- Body -->
                        <div class="id-card-body">
                            <!-- Photo -->
                            <img src="<?= !empty($st['photo']) ? htmlspecialchars($st['photo']) : 'assets/images/default-avatar.svg' ?>" class="id-photo" alt="Photo">

                            <!-- Info -->
                            <div class="id-details">
                                <div class="id-student-name"><?= htmlspecialchars($st['name']) ?></div>
                                <div class="id-detail-row">NIS: <b><?= htmlspecialchars($st['nis']) ?></b></div>
                                <div class="id-detail-row">NISN: <?= htmlspecialchars($st['nisn'] ?: '-') ?></div>
                                <div class="id-detail-row">Kelas: <?= htmlspecialchars($st['class_name'] ?? 'Umum') ?></div>
                            </div>

                            <!-- QR Code Container -->
                            <div class="id-qr-box" id="qrcode-box-<?= $st['id'] ?>">
                                <!-- Generated by QRCode.js -->
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="id-card-footer">
                            <span>Th. Ajaran <?= htmlspecialchars($st['academic_year'] ?? '2024/2025') ?></span>
                            <span>Token: <?= htmlspecialchars(substr($st['qr_token'], 0, 10)) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Generate QR Code for each student card
    <?php foreach ($cards as $st): ?>
        new QRCode(document.getElementById("qrcode-box-<?= $st['id'] ?>"), {
            text: "<?= addslashes($st['qr_token']) ?>",
            width: 60,
            height: 60,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    <?php endforeach; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
