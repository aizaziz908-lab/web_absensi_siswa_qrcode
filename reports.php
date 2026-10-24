<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$school_name = get_setting('school_name', 'SMK BINA BANGSA NUSANTARA');
$school_address = get_setting('school_address', 'Jl. Merdeka No. 108, Jakarta Selatan');

// Report Filters
$type = $_GET['type'] ?? 'monthly'; // 'daily', 'weekly', 'monthly', 'custom'
$class_id = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

$today = date('Y-m-d');
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

if ($type === 'daily') {
    $start_date = $_GET['date'] ?? $today;
    $end_date = $start_date;
} elseif ($type === 'weekly') {
    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d', strtotime('sunday this week'));
} elseif ($type === 'monthly') {
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
} else { // custom
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? $today;
}

// Check if user requested Export to Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Rekap_Presensi_' . $start_date . '_sd_' . $end_date . '.xls"');
    
    // Output standard HTML table recognized by Excel
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Rekap</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    echo '<body>';
    echo '<h2>REKAPITULASI PRESENSI SISWA</h2>';
    echo '<p>' . htmlspecialchars($school_name) . '<br>Periode: ' . $start_date . ' s/d ' . $end_date . '</p>';
    
    $sql_export = "
        SELECT 
            s.nis, s.name, c.name as class_name,
            SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN a.status = 'alpa' THEN 1 ELSE 0 END) as alpa,
            COUNT(a.id) as total_rekam
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
        WHERE s.is_active = 1
    ";
    $params_exp = [$start_date, $end_date];
    if ($class_id) {
        $sql_export .= " AND s.class_id = ?";
        $params_exp[] = $class_id;
    }
    $sql_export .= " GROUP BY s.id ORDER BY c.name ASC, s.name ASC";
    $stmt_exp = $db->prepare($sql_export);
    $stmt_exp->execute($params_exp);
    $rows = $stmt_exp->fetchAll();

    echo '<table border="1" cellpadding="6" cellspacing="0">';
    echo '<tr style="background:#4f46e5; color:#fff; font-weight:bold;">
            <th>No</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th><th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>% Kehadiran</th>
          </tr>';
    foreach ($rows as $i => $r) {
        $effective_presence = $r['hadir'] + $r['terlambat'];
        $rate = $r['total_rekam'] > 0 ? round(($effective_presence / $r['total_rekam']) * 100, 1) : 0;
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo "<td>'" . htmlspecialchars($r['nis']) . "</td>";
        echo '<td>' . htmlspecialchars($r['name']) . '</td>';
        echo '<td>' . htmlspecialchars($r['class_name'] ?? 'Umum') . '</td>';
        echo '<td>' . $r['hadir'] . '</td>';
        echo '<td>' . $r['terlambat'] . '</td>';
        echo '<td>' . $r['izin'] . '</td>';
        echo '<td>' . $r['sakit'] . '</td>';
        echo '<td>' . $r['alpa'] . '</td>';
        echo '<td>' . $rate . '%</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

// Query for screen display
$sql_report = "
    SELECT 
        s.id, s.nis, s.name, c.name as class_name,
        SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as count_hadir,
        SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) as count_terlambat,
        SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as count_izin,
        SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as count_sakit,
        SUM(CASE WHEN a.status = 'alpa' THEN 1 ELSE 0 END) as count_alpa,
        COUNT(a.id) as total_recorded
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
    WHERE s.is_active = 1
";
$params = [$start_date, $end_date];

if ($class_id) {
    $sql_report .= " AND s.class_id = ?";
    $params[] = $class_id;
}

$sql_report .= " GROUP BY s.id ORDER BY c.name ASC, s.name ASC";
$stmt = $db->prepare($sql_report);
$stmt->execute($params);
$recap = $stmt->fetchAll();

$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();

$page_title = 'Rekapitulasi & Laporan Presensi';
$page_heading = 'Rekapitulasi & Laporan Kehadiran';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body">
        
        <!-- Controls Filter (Hidden on print) -->
        <div class="card no-print" style="margin-bottom:24px;">
            <div class="card-body" style="padding:20px 24px;">
                <form method="GET" action="reports.php" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
                    
                    <div style="width:160px;">
                        <label class="form-label">Jenis Periode</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="monthly" <?= $type === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                            <option value="weekly" <?= $type === 'weekly' ? 'selected' : '' ?>>Minggu Ini</option>
                            <option value="daily" <?= $type === 'daily' ? 'selected' : '' ?>>Harian</option>
                            <option value="custom" <?= $type === 'custom' ? 'selected' : '' ?>>Rentang Tanggal</option>
                        </select>
                    </div>

                    <?php if ($type === 'monthly'): ?>
                        <div style="width:140px;">
                            <label class="form-label">Bulan</label>
                            <select name="month" class="form-select" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): 
                                    $m_str = sprintf('%02d', $m); ?>
                                    <option value="<?= $m_str ?>" <?= $month == $m_str ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div style="width:110px;">
                            <label class="form-label">Tahun</label>
                            <select name="year" class="form-select" onchange="this.form.submit()">
                                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php elseif ($type === 'daily'): ?>
                        <div style="width:160px;">
                            <label class="form-label">Pilih Tanggal</label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" onchange="this.form.submit()">
                        </div>
                    <?php elseif ($type === 'custom'): ?>
                        <div style="width:150px;">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                        </div>
                        <div style="width:150px;">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                        </div>
                    <?php endif; ?>

                    <div style="width:200px;">
                        <label class="form-label">Filter Kelas</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $class_id == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="search"></i> Tampilkan
                        </button>

                        <?php 
                        $export_url = "reports.php?export=excel&type=$type&class_id=$class_id&start_date=$start_date&end_date=$end_date&month=$month&year=$year";
                        ?>
                        <a href="<?= $export_url ?>" class="btn btn-success">
                            <i data-feather="file-text"></i> Ekspor Excel
                        </a>

                        <button type="button" class="btn btn-outline" onclick="window.print()">
                            <i data-feather="printer"></i> Cetak / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Container (Printable) -->
        <div class="card" id="printable-report">
            <!-- Kop Surat Resmi (Visible on print & web) -->
            <div style="padding:24px 32px; border-bottom:2px solid var(--text-main); display:flex; align-items:center; gap:20px;">
                <div style="width:60px; height:60px; border-radius:12px; background:var(--primary-gradient); display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; font-weight:800;">
                    <i data-feather="book-open"></i>
                </div>
                <div>
                    <h2 style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; text-transform:uppercase; margin-bottom:2px;">
                        <?= htmlspecialchars($school_name) ?>
                    </h2>
                    <p style="font-size:12px; color:var(--text-muted);">
                        <?= htmlspecialchars($school_address) ?> &bull; Sistem Presensi Digital Siswa QR Code
                    </p>
                    <p style="font-size:13px; font-weight:700; color:var(--primary); margin-top:4px;">
                        Laporan Rekapitulasi Presensi: <?= format_date_id($start_date) ?> s/d <?= format_date_id($end_date) ?>
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th style="color:var(--accent-emerald);">Hadir</th>
                            <th style="color:var(--accent-amber);">Terlambat</th>
                            <th style="color:var(--accent-cyan);">Izin</th>
                            <th style="color:var(--accent-rose);">Sakit</th>
                            <th style="color:#64748b;">Alpa</th>
                            <th>% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recap)): ?>
                            <tr>
                                <td colspan="10" style="text-align:center; padding:32px; color:var(--text-muted);">
                                    Tidak ada data untuk periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recap as $idx => $r): 
                                $effective = $r['count_hadir'] + $r['count_terlambat'];
                                $total_events = $r['total_recorded'];
                                $pct = $total_events > 0 ? round(($effective / $total_events) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><b><?= htmlspecialchars($r['nis']) ?></b></td>
                                    <td>
                                        <div style="font-weight:700;"><?= htmlspecialchars($r['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= htmlspecialchars($r['class_name'] ?? 'Umum') ?></span>
                                    </td>
                                    <td style="font-weight:700; color:var(--accent-emerald);"><?= $r['count_hadir'] ?></td>
                                    <td style="font-weight:700; color:var(--accent-amber);"><?= $r['count_terlambat'] ?></td>
                                    <td style="font-weight:700; color:var(--accent-cyan);"><?= $r['count_izin'] ?></td>
                                    <td style="font-weight:700; color:var(--accent-rose);"><?= $r['count_sakit'] ?></td>
                                    <td style="font-weight:700; color:#64748b;"><?= $r['count_alpa'] ?></td>
                                    <td>
                                        <span class="badge <?= $pct >= 85 ? 'badge-success' : ($pct >= 70 ? 'badge-warning' : 'badge-danger') ?>">
                                            <?= $pct ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Signature Section (Visible on Print) -->
            <div style="display:none; margin-top:40px; padding:0 32px 32px;" class="print-only">
                <div style="display:flex; justify-content:space-between; text-align:center;">
                    <div>
                        <p style="margin-bottom:60px;">Mengetahui,<br>Kepala Sekolah</p>
                        <p style="font-weight:bold; text-decoration:underline;">Drs. H. Mulyadi, M.Pd</p>
                        <p style="font-size:11px;">NIP. 19750812 200003 1 002</p>
                    </div>
                    <div>
                        <p style="margin-bottom:60px;">Tanggal: <?= date('d/m/Y') ?><br>Petugas Guru Piket</p>
                        <p style="font-weight:bold; text-decoration:underline;"><?= htmlspecialchars(current_user()['name']) ?></p>
                        <p style="font-size:11px;">Guru Pembina / Wali</p>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<style>
@media print {
    .print-only { display: block !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
