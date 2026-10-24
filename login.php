<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$school_name = get_setting('school_name', 'SMK BINA BANGSA NUSANTARA');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan kata sandi wajib diisi!';
    } else {
        try {
            $db = get_db();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_assigned_class_id'] = $user['assigned_class_id'] ?? null;

                $redirect = $_GET['redirect'] ?? 'dashboard.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Username atau kata sandi tidak cocok!';
            }
        } catch (Exception $e) {
            $error = 'Kesalahan database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/feather.min.js"></script>
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-brand-icon">
        <i data-feather="camera"></i>
    </div>
    <h2 style="font-family:'Outfit',sans-serif; font-weight:800; font-size:22px; color:var(--text-main);">
        Portal Absensi Siswa
    </h2>
    <p style="font-size:13px; color:var(--text-muted); margin-bottom:24px;">
        <?= htmlspecialchars($school_name) ?>
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="text-align:left;">
            <i data-feather="alert-circle" style="flex-shrink:0;"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" style="text-align:left;">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required value="admin123">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; font-size:15px; margin-top:8px;">
            <i data-feather="log-in"></i> Masuk Sekarang
        </button>
    </form>

    <div style="margin-top:24px; padding-top:18px; border-top:1px solid var(--border-color); text-align:left;">
        <p style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase;">
            Pintasan Akun Uji Coba:
        </p>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <button type="button" class="btn btn-outline btn-sm" onclick="quickFill('admin', 'admin123')" style="justify-content:flex-start;">
                <span class="badge badge-primary" style="padding:2px 6px;">Admin</span> admin / admin123
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="quickFill('gurupiket', 'guru123')" style="justify-content:flex-start;">
                <span class="badge badge-success" style="padding:2px 6px;">Guru Piket</span> gurupiket / guru123
            </button>
            <button type="button" class="btn btn-outline btn-sm" onclick="quickFill('walikelas', 'wali123')" style="justify-content:flex-start;">
                <span class="badge badge-warning" style="padding:2px 6px;">Wali Kelas</span> walikelas / wali123
            </button>
        </div>
    </div>

    <div style="margin-top:20px; text-align:center;">
        <a href="scan.php" style="font-size:13px; font-weight:700; color:var(--primary); text-decoration:none;">
            <i data-feather="maximize" style="width:14px;height:14px;vertical-align:middle;"></i> Buka Kiosk Scanner (Tanpa Login)
        </a>
    </div>
</div>

<script>
    feather.replace();
    function quickFill(user, pass) {
        document.getElementById('username').value = user;
        document.getElementById('password').value = pass;
    }
</script>
</body>
</html>
