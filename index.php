<?php
require_once __DIR__ . '/includes/functions.php';

$school_name = get_setting('school_name', 'SMK BINA BANGSA NUSANTARA');
$school_address = get_setting('school_address', 'Jl. Merdeka No. 108, Jakarta Selatan');
$time_in_end = get_setting('time_in_end', '07:15');
$time_out_start = get_setting('time_out_start', '15:00');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Siswa QR Code - <?= htmlspecialchars($school_name) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/feather.min.js"></script>
    <style>
        .hero-section {
            background: linear-gradient(135deg, #3730a3 0%, #4f46e5 50%, #06b6d4 100%);
            color: #ffffff;
            padding: 70px 24px 90px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 100px;
            background: var(--bg-page);
            border-radius: 50% 50% 0 0;
        }
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: -50px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        .portal-card {
            background: #ffffff;
            border-radius: var(--radius-xl);
            padding: 36px 28px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .portal-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 45px rgba(79, 70, 229, 0.16);
            border-color: var(--primary-light);
        }
        .portal-icon {
            width: 72px;
            height: 72px;
            border-radius: var(--radius-lg);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        .portal-icon svg {
            width: 34px;
            height: 34px;
        }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <header class="hero-section">
        <div style="max-width:800px; margin:0 auto; position:relative; z-index:2;">
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,0.18); backdrop-filter:blur(8px); padding:6px 16px; border-radius:30px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:16px;">
                <i data-feather="check-circle" style="width:14px;height:14px;"></i> Smart Attendance System v2.0
            </div>
            <h1 style="font-family:'Outfit',sans-serif; font-size:36px; font-weight:800; line-height:1.2; margin-bottom:14px;">
                <?= htmlspecialchars($school_name) ?>
            </h1>
            <p style="font-size:16px; opacity:0.92; line-height:1.6; max-width:650px; margin:0 auto 20px;">
                Presensi Siswa Cepat, Akurat & Anti-Titip Absen dengan Pemindaian QR Code, Snapshot Kamera Wajah, dan Notifikasi Otomatis ke Orang Tua.
            </p>
            <div style="display:flex; justify-content:center; align-items:center; gap:16px; font-size:13px; font-weight:600; opacity:0.85;">
                <span><i data-feather="clock" style="width:14px;height:14px;vertical-align:middle;"></i> Batas Masuk: <?= $time_in_end ?> WIB</span>
                &bull;
                <span><i data-feather="home" style="width:14px;height:14px;vertical-align:middle;"></i> Jam Pulang: <?= $time_out_start ?> WIB</span>
            </div>
        </div>
    </header>

    <!-- Main Portals -->
    <main class="portal-grid">
        
        <!-- Portal 1: Scan QR Kiosk -->
        <div class="portal-card">
            <div>
                <div class="portal-icon" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i data-feather="maximize"></i>
                </div>
                <h3 style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--text-main); margin-bottom:8px;">
                    Pemindai QR Code
                </h3>
                <p style="font-size:13.5px; color:var(--text-muted); line-height:1.5; margin-bottom:24px;">
                    Mode Kiosk untuk pemindaian kartu tanda pelajar siswa saat tiba di sekolah (Masuk) dan saat pulang.
                </p>
            </div>
            <a href="scan.php" class="btn btn-success" style="padding:12px; font-size:14px;">
                <i data-feather="camera"></i> Buka Kamera Scanner &rarr;
            </a>
        </div>

        <!-- Portal 2: Permit Application -->
        <div class="portal-card">
            <div>
                <div class="portal-icon" style="background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                    <i data-feather="file-text"></i>
                </div>
                <h3 style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--text-main); margin-bottom:8px;">
                    Pengajuan Izin / Sakit
                </h3>
                <p style="font-size:13.5px; color:var(--text-muted); line-height:1.5; margin-bottom:24px;">
                    Kirim permohonan surat izin atau surat keterangan sakit dari dokter secara digital untuk diverifikasi wali kelas.
                </p>
            </div>
            <a href="permit_apply.php" class="btn" style="background:#0284c7; color:#fff; padding:12px; font-size:14px;">
                <i data-feather="send"></i> Formulir Surat Izin &rarr;
            </a>
        </div>

        <!-- Portal 3: Teacher / Admin Dashboard -->
        <div class="portal-card">
            <div>
                <div class="portal-icon" style="background:var(--primary-gradient);">
                    <i data-feather="shield"></i>
                </div>
                <h3 style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:var(--text-main); margin-bottom:8px;">
                    Panel Guru & Admin
                </h3>
                <p style="font-size:13.5px; color:var(--text-muted); line-height:1.5; margin-bottom:24px;">
                    Masuk ke dashboard untuk memantau grafik kehadiran, rekap laporan, data siswa, dan cetak kartu pelajar.
                </p>
            </div>
            <a href="login.php" class="btn btn-primary" style="padding:12px; font-size:14px;">
                <i data-feather="log-in"></i> Masuk Dashboard &rarr;
            </a>
        </div>

    </main>

    <!-- Footer Info -->
    <footer style="text-align:center; padding:32px 20px; color:var(--text-muted); font-size:13px;">
        <p style="font-weight:700; color:var(--text-main); margin-bottom:4px;"><?= htmlspecialchars($school_name) ?></p>
        <p><?= htmlspecialchars($school_address) ?></p>
        <p style="margin-top:12px; font-size:12px; color:var(--text-light);">&copy; <?= date('Y') ?> Sistem Absensi QR Code Berbasis Web PHP &bull; Anti-Titip Absen</p>
    </footer>

    <script>
        feather.replace();
    </script>
</body>
</html>
