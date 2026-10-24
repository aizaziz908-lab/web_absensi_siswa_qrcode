<?php
require_once __DIR__ . '/includes/functions.php';
require_login(['admin', 'guru_piket']);

$db = get_db();
$flash = flash_get();
$error = '';

// Handle Schedule Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_schedule') {
    set_setting('time_in_start', $_POST['time_in_start'] ?? '06:00');
    set_setting('time_in_end', $_POST['time_in_end'] ?? '07:15');
    set_setting('time_late_tolerance', $_POST['time_late_tolerance'] ?? '15');
    set_setting('time_out_start', $_POST['time_out_start'] ?? '15:00');
    set_setting('time_out_end', $_POST['time_out_end'] ?? '18:00');
    set_setting('allow_holiday_scan', isset($_POST['allow_holiday_scan']) ? '1' : '0');

    flash_set('success', 'Jadwal jam masuk dan pengaturan hari libur berhasil disimpan!');
    header('Location: holidays.php');
    exit;
}

// Handle Add Holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_holiday') {
    $holiday_date = $_POST['holiday_date'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($holiday_date) || empty($name)) {
        $error = 'Tanggal dan nama hari libur wajib diisi!';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO holidays (holiday_date, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$holiday_date, $name, $description]);
            flash_set('success', "Hari libur '$name' berhasil ditambahkan!");
            header('Location: holidays.php');
            exit;
        } catch (Exception $e) {
            $error = 'Tanggal tersebut sudah terdaftar sebagai hari libur!';
        }
    }
}

// Handle Delete Holiday
if (isset($_GET['action']) && $_GET['action'] === 'delete_holiday') {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("DELETE FROM holidays WHERE id = ?");
    $stmt->execute([$id]);
    flash_set('success', 'Hari libur berhasil dihapus!');
    header('Location: holidays.php');
    exit;
}

// Fetch holidays
$holidays = $db->query("SELECT * FROM holidays ORDER BY holiday_date ASC")->fetchAll();

$page_title = 'Hari Libur & Jam Masuk';
$page_heading = 'Manajemen Hari Libur & Jadwal Masuk';
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

        <div style="display:grid; grid-template-columns: 1fr 1.2fr; gap:24px; align-items:start;">
            
            <!-- Left: Schedule & Time Rules -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="clock"></i> Pengaturan Jam Masuk & Pulang
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="holidays.php">
                        <input type="hidden" name="action" value="save_schedule">

                        <h4 style="font-size:13px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:12px;">
                            Presensi Masuk
                        </h4>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Mulai Buka Scan Masuk</label>
                                <input type="time" name="time_in_start" class="form-control" value="<?= htmlspecialchars(get_setting('time_in_start', '06:00')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Batas Tepat Waktu</label>
                                <input type="time" name="time_in_end" class="form-control" value="<?= htmlspecialchars(get_setting('time_in_end', '07:15')) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Toleransi Keterlambatan (Menit)</label>
                            <input type="number" name="time_late_tolerance" class="form-control" value="<?= htmlspecialchars(get_setting('time_late_tolerance', '15')) ?>" required min="0" max="60">
                            <small style="font-size:11px; color:var(--text-muted);">
                                Lewat dari batas tepat waktu s/d menit toleransi tercatat sebagai <b>Terlambat</b>.
                            </small>
                        </div>

                        <h4 style="font-size:13px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin:20px 0 12px;">
                            Presensi Pulang
                        </h4>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Mulai Buka Scan Pulang</label>
                                <input type="time" name="time_out_start" class="form-control" value="<?= htmlspecialchars(get_setting('time_out_start', '15:00')) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Batas Akhir Scan Pulang</label>
                                <input type="time" name="time_out_end" class="form-control" value="<?= htmlspecialchars(get_setting('time_out_end', '18:00')) ?>" required>
                            </div>
                        </div>

                        <div class="form-group" style="padding:14px; background:#f8fafc; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-top:10px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600; font-size:13px;">
                                <input type="checkbox" name="allow_holiday_scan" value="1" <?= get_setting('allow_holiday_scan', '0') === '1' ? 'checked' : '' ?>>
                                <span>Izinkan Presensi Pada Hari Libur / Tanggal Merah</span>
                            </label>
                            <small style="color:var(--text-muted); font-size:11px; display:block; margin-top:4px;">
                                Jika tidak dicentang, sistem scanner akan menolak presensi pada hari Minggu atau tanggal merah.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:16px;">
                            <i data-feather="save"></i> Simpan Pengaturan Jam
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Holiday Management -->
            <div>
                <!-- Add Holiday Box -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header">
                        <span class="card-title">
                            <i data-feather="calendar"></i> Tambah Hari Libur / Tanggal Merah
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="holidays.php">
                            <input type="hidden" name="action" value="add_holiday">

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label class="form-label">Tanggal Libur</label>
                                    <input type="date" name="holiday_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Nama Libur / Peringatan</label>
                                    <input type="text" name="name" class="form-control" required placeholder="Contoh: Hari Kemerdekaan RI">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Keterangan Tambahan (Opsional)</label>
                                <input type="text" name="description" class="form-control" placeholder="Contoh: Libur Nasional / Cuti Bersama">
                            </div>

                            <button type="submit" class="btn btn-success" style="padding:9px 20px;">
                                <i data-feather="plus"></i> Tambah ke Kalender Libur
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Holidays Table -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <i data-feather="sun"></i> Daftar Tanggal Merah Terdaftar
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama Hari Libur</th>
                                    <th>Keterangan</th>
                                    <th style="text-align:right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($holidays)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:24px; color:var(--text-muted);">
                                            Belum ada tanggal libur yang didaftarkan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($holidays as $h): ?>
                                        <tr>
                                            <td style="font-weight:700; color:var(--accent-rose);">
                                                <?= format_date_id($h->holiday_date ?? $h['holiday_date']) ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;"><?= htmlspecialchars($h['name']) ?></div>
                                            </td>
                                            <td style="font-size:12px; color:var(--text-muted);">
                                                <?= htmlspecialchars($h['description'] ?: '-') ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <a href="holidays.php?action=delete_holiday&id=<?= $h['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--accent-rose);" onclick="return confirm('Hapus hari libur ini?')" title="Hapus">
                                                    <i data-feather="trash-2"></i>
                                                </a>
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
