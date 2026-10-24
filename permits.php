<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$db = get_db();
$flash = flash_get();
$user = current_user();

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $permit_id = (int)$_POST['permit_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    $stmt = $db->prepare("SELECT * FROM permits WHERE id = ?");
    $stmt->execute([$permit_id]);
    $permit = $stmt->fetch();

    if ($permit) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        $update = $db->prepare("UPDATE permits SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$new_status, $user['id'], $permit_id]);

        // If approved, automatically insert / update attendance table for all dates in range!
        if ($new_status === 'approved') {
            $start = new DateTime($permit['start_date']);
            $end = new DateTime($permit['end_date']);
            $end->modify('+1 day');
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            $stmt_att = $db->prepare("
                INSERT INTO attendance (student_id, date, status, notes) 
                VALUES (?, ?, ?, ?) 
                ON CONFLICT(student_id, date) DO UPDATE SET status = excluded.status, notes = excluded.notes
            ");

            foreach ($period as $dt) {
                $d_str = $dt->format('Y-m-d');
                $note = ucfirst($permit['type']) . ': ' . $permit['reason'];
                try {
                    $stmt_att->execute([$permit['student_id'], $d_str, $permit['type'], $note]);
                } catch (Exception $e) {
                    // Fallback for MySQL
                    $stmt_mysql = $db->prepare("
                        INSERT INTO attendance (student_id, date, status, notes) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)
                    ");
                    $stmt_mysql->execute([$permit['student_id'], $d_str, $permit['type'], $note]);
                }
            }
        }

        flash_set('success', 'Permohonan berhasil ' . ($new_status === 'approved' ? 'DISETUJUI' : 'DITOLAK') . '!');
    }
    header('Location: permits.php');
    exit;
}

// Fetch Permits
$sql = "
    SELECT p.*, s.name as student_name, s.nis, s.parent_name, s.parent_phone, c.name as class_name, u.name as reviewer_name 
    FROM permits p 
    JOIN students s ON p.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON p.reviewed_by = u.id 
    ORDER BY p.created_at DESC
";
$permits = $db->query($sql)->fetchAll();

$page_title = 'Verifikasi Izin & Sakit';
$page_heading = 'Pengajuan & Verifikasi Izin / Sakit';
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

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
            <div>
                <h2 style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--text-main);">
                    Daftar Surat Izin & Sakit Siswa
                </h2>
                <p style="font-size:13px; color:var(--text-muted);">
                    Verifikasi surat izin atau surat keterangan dokter yang diajukan oleh siswa / wali murid.
                </p>
            </div>

            <a href="permit_apply.php" class="btn btn-primary">
                <i data-feather="plus"></i> Ajukan Izin Baru
            </a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Siswa</th>
                            <th>Jenis</th>
                            <th>Periode Tanggal</th>
                            <th>Alasan / Keterangan</th>
                            <th>Lampiran Surat</th>
                            <th>Status Verifikasi</th>
                            <th style="text-align:right;">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($permits)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted);">
                                    Belum ada pengajuan izin atau sakit.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($permits as $idx => $p): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div style="font-weight:700;"><?= htmlspecialchars($p['student_name']) ?></div>
                                        <div style="font-size:11px; color:var(--text-muted);">NIS: <?= htmlspecialchars($p['nis']) ?> | <?= htmlspecialchars($p['class_name'] ?? 'Umum') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['type'] === 'sakit' ? 'badge-danger' : 'badge-info' ?>">
                                            <i data-feather="<?= $p['type'] === 'sakit' ? 'activity' : 'file-text' ?>"></i>
                                            <?= strtoupper($p['type']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:12px; font-weight:600;">
                                        <?= date('d/m/Y', strtotime($p['start_date'])) ?> s/d <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                                    </td>
                                    <td style="font-size:13px; max-width:260px;">
                                        <?= htmlspecialchars($p['reason']) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['attachment'])): ?>
                                            <a href="<?= htmlspecialchars($p['attachment']) ?>" target="_blank" class="btn btn-outline btn-sm">
                                                <i data-feather="paperclip"></i> Lihat Bukti
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-light);">(Tidak ada lampiran)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <span class="badge badge-warning"><i data-feather="clock"></i> Menunggu Verifikasi</span>
                                        <?php elseif ($p['status'] === 'approved'): ?>
                                            <span class="badge badge-success"><i data-feather="check"></i> Disetujui (<?= htmlspecialchars($p['reviewer_name'] ?? 'Guru') ?>)</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i data-feather="x"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <form method="POST" action="permits.php" style="display:inline-flex; gap:6px;">
                                                <input type="hidden" name="permit_id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" title="Setujui">
                                                    <i data-feather="check"></i> Terima
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" title="Tolak">
                                                    <i data-feather="x"></i> Tolak
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-muted); font-weight:600;">Selesai</span>
                                        <?php endif; ?>
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
