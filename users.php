<?php
require_once __DIR__ . '/includes/functions.php';
require_login(['admin']);

$db = get_db();
$flash = flash_get();
$error = '';
$curr_user = current_user();

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'guru_piket';
    $phone = trim($_POST['phone'] ?? '');
    $class_id = !empty($_POST['assigned_class_id']) ? (int)$_POST['assigned_class_id'] : null;

    if (empty($name) || empty($username) || empty($password)) {
        $error = 'Nama, username, dan password wajib diisi!';
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, username, password, role, phone, assigned_class_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $username, $hashed, $role, $phone, $class_id]);
            flash_set('success', "Pengguna '$name' berhasil ditambahkan!");
            header('Location: users.php');
            exit;
        } catch (Exception $e) {
            $error = 'Username sudah digunakan oleh akun lain!';
        }
    }
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    if ($id === (int)$curr_user['id']) {
        flash_set('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Pengguna berhasil dihapus!');
    }
    header('Location: users.php');
    exit;
}

// Fetch all users
$sql = "
    SELECT u.*, c.name as class_name 
    FROM users u 
    LEFT JOIN classes c ON u.assigned_class_id = c.id 
    ORDER BY u.role ASC, u.name ASC
";
$users = $db->query($sql)->fetchAll();
$classes = $db->query("SELECT * FROM classes ORDER BY grade ASC, name ASC")->fetchAll();

$page_title = 'Manajemen Pengguna';
$page_heading = 'Manajemen Pengguna & Hak Akses';
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

        <div style="display:grid; grid-template-columns: 360px 1fr; gap:24px; align-items:start;">
            
            <!-- Left: Add User Form -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="user-plus"></i> Tambah Pengguna Baru
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="users.php">
                        <input type="hidden" name="action" value="add_user">

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap & Gelar</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: Drs. Bambang, M.Pd">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required placeholder="Contoh: gurubambang">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Kata Sandi (Password)</label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Peran / Hak Akses</label>
                            <select name="role" id="roleSelect" class="form-select" onchange="toggleWaliClass(this.value)">
                                <option value="guru_piket">Guru Piket (Pemindai & Presensi)</option>
                                <option value="wali_kelas">Wali Kelas</option>
                                <option value="admin">Administrator Utama (Akses Penuh)</option>
                            </select>
                        </div>

                        <div class="form-group" id="waliClassGroup" style="display:none;">
                            <label class="form-label">Tugaskan sebagai Wali Kelas</label>
                            <select name="assigned_class_id" class="form-select">
                                <option value="">-- Pilih Kelas Binaan --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor HP / WhatsApp (Opsional)</label>
                            <input type="text" name="phone" class="form-control" placeholder="Contoh: 08123456789">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:8px;">
                            <i data-feather="check"></i> Simpan Pengguna
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Users Table -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="users"></i> Daftar Pengguna Sistem (<?= count($users) ?> Akun)
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Username</th>
                                <th>Peran</th>
                                <th>Kelas Binaan</th>
                                <th>Kontak</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--text-main);"><?= htmlspecialchars($u['name']) ?></div>
                                        <?php if ($u['id'] == $curr_user['id']): ?>
                                            <span class="badge badge-success" style="padding:1px 6px; font-size:10px;">Akun Anda</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($u['username']) ?></code>
                                    </td>
                                    <td>
                                        <?php 
                                            $role_badges = [
                                                'admin' => ['label' => 'Admin Utama', 'class' => 'badge-danger'],
                                                'guru_piket' => ['label' => 'Guru Piket', 'class' => 'badge-primary'],
                                                'wali_kelas' => ['label' => 'Wali Kelas', 'class' => 'badge-info'],
                                            ];
                                            $b = $role_badges[$u['role']] ?? ['label' => $u['role'], 'class' => 'badge-dark'];
                                        ?>
                                        <span class="badge <?= $b['class'] ?>"><?= $b['label'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($u['class_name'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                                    <td style="text-align:right;">
                                        <?php if ($u['id'] != $curr_user['id']): ?>
                                            <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--accent-rose);" onclick="return confirm('Hapus pengguna ini?')" title="Hapus Pengguna">
                                                <i data-feather="trash-2"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size:11px; color:var(--text-light);">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function toggleWaliClass(role) {
    const group = document.getElementById('waliClassGroup');
    group.style.display = (role === 'wali_kelas') ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
