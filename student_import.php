<?php
require_once __DIR__ . '/includes/functions.php';
require_login(['admin', 'guru_piket']);

$db = get_db();
$message = '';
$error = '';
$imported_count = 0;

// Download CSV Template
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_import_siswa.csv"');
    $output = fopen('php://output', 'w');
    // Header
    fputcsv($output, ['nis', 'nisn', 'name', 'gender', 'class_name', 'parent_name', 'parent_phone']);
    // Sample rows
    fputcsv($output, ['10201', '0054321021', 'Rian Hidayat', 'L', 'X Rekayasa Perangkat Lunak 1', 'Bambang', '081234567800']);
    fputcsv($output, ['10202', '0054321022', 'Siti Zahra', 'P', 'X Rekayasa Perangkat Lunak 1', 'Zulkifli', '081234567801']);
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Gagal mengunggah file CSV.';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, 'r')) !== false) {
            // Read header
            $header = fgetcsv($handle, 1000, ',');
            
            // Build class map (name -> id)
            $class_map = [];
            $all_classes = $db->query("SELECT id, name FROM classes")->fetchAll();
            foreach ($all_classes as $c) {
                $class_map[strtolower(trim($c['name']))] = $c['id'];
            }

            $stmt_insert = $db->prepare("
                INSERT OR IGNORE INTO students (nis, nisn, name, gender, class_id, parent_name, parent_phone, qr_token) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($row) < 3 || empty($row[0])) continue;
                
                $nis = trim($row[0]);
                $nisn = trim($row[1] ?? '');
                $name = trim($row[2] ?? '');
                $gender = strtoupper(trim($row[3] ?? 'L')) === 'P' ? 'P' : 'L';
                $class_str = strtolower(trim($row[4] ?? ''));
                $parent_name = trim($row[5] ?? '');
                $parent_phone = trim($row[6] ?? '');

                // Find or assign class
                $class_id = $class_map[$class_str] ?? ($all_classes[0]['id'] ?? 1);
                $qr_token = generate_student_qr_token($nis);

                $stmt_insert->execute([$nis, $nisn, $name, $gender, $class_id, $parent_name, $parent_phone, $qr_token]);
                if ($stmt_insert->rowCount() > 0) {
                    $imported_count++;
                }
            }
            fclose($handle);

            flash_set('success', "Berhasil mengimpor $imported_count data siswa baru!");
            header('Location: students.php');
            exit;
        } else {
            $error = 'File CSV tidak dapat dibaca.';
        }
    }
}

$page_title = 'Impor Data Siswa';
$page_heading = 'Impor Siswa dari CSV / Excel';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body" style="max-width: 800px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i data-feather="upload-cloud"></i> Unggah File Data Siswa (CSV)
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

                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:var(--radius-md); padding:18px; margin-bottom:24px;">
                    <h4 style="font-size:14px; font-weight:700; color:#1e40af; margin-bottom:6px;">
                        <i data-feather="info" style="width:16px;height:16px;vertical-align:middle;"></i> Panduan Format CSV:
                    </h4>
                    <p style="font-size:13px; color:#1e3a8a; line-height:1.6;">
                        Pastikan file CSV memiliki kolom: <b>nis, nisn, name, gender (L/P), class_name, parent_name, parent_phone</b>.<br>
                        Sistem akan secara otomatis membuatkan <b>Token QR Code yang aman</b> untuk masing-masing siswa yang diimpor.
                    </p>
                    <div style="margin-top:12px;">
                        <a href="student_import.php?action=download_template" class="btn btn-sm btn-outline" style="background:#fff; color:#1e40af; border-color:#93c5fd; font-weight:700;">
                            <i data-feather="download"></i> Unduh Contoh Format Template CSV
                        </a>
                    </div>
                </div>

                <form method="POST" action="student_import.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Pilih File CSV (*.csv)</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <div style="margin-top:24px;">
                        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                            <i data-feather="upload"></i> Mulai Impor Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
