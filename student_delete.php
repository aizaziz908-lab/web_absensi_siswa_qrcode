<?php
require_once __DIR__ . '/includes/functions.php';
require_login(['admin', 'guru_piket']);

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Data siswa dan riwayat presensinya berhasil dihapus.');
    } catch (Exception $e) {
        flash_set('danger', 'Gagal menghapus siswa: ' . $e->getMessage());
    }
}

header('Location: students.php');
exit;
