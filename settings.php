<?php
require_once __DIR__ . '/includes/functions.php';
require_login(['admin']);

$flash = flash_get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_setting('school_name', $_POST['school_name'] ?? '');
    set_setting('school_address', $_POST['school_address'] ?? '');
    set_setting('school_phone', $_POST['school_phone'] ?? '');

    set_setting('anti_titip_selfie', isset($_POST['anti_titip_selfie']) ? '1' : '0');
    set_setting('cooldown_minutes', $_POST['cooldown_minutes'] ?? '5');

    set_setting('wa_mode', $_POST['wa_mode'] ?? 'direct');
    set_setting('wa_api_url', $_POST['wa_api_url'] ?? '');
    set_setting('wa_api_token', $_POST['wa_api_token'] ?? '');
    set_setting('wa_template_in', $_POST['wa_template_in'] ?? '');
    set_setting('wa_template_out', $_POST['wa_template_out'] ?? '');
    set_setting('wa_template_alpa', $_POST['wa_template_alpa'] ?? '');

    flash_set('success', 'Semua pengaturan sistem berhasil disimpan!');
    header('Location: settings.php');
    exit;
}

$page_title = 'Pengaturan Sistem';
$page_heading = 'Pengaturan Sekolah & Notifikasi WhatsApp';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-wrapper">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body" style="max-width: 1000px;">
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i data-feather="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
                <div><?= htmlspecialchars($flash['message']) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="settings.php">
            
            <!-- School Profile -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="home"></i> Identitas & Profil Sekolah
                    </span>
                </div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Nama Sekolah / Lembaga Pendidikan</label>
                            <input type="text" name="school_name" class="form-control" required value="<?= htmlspecialchars(get_setting('school_name')) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nomor Telepon Sekolah</label>
                            <input type="text" name="school_phone" class="form-control" value="<?= htmlspecialchars(get_setting('school_phone')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap</label>
                        <input type="text" name="school_address" class="form-control" value="<?= htmlspecialchars(get_setting('school_address')) ?>">
                    </div>
                </div>
            </div>

            <!-- Anti-Titip Absen & Security Settings -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="shield"></i> Keamanan Scanner & Anti-Titip Absen
                    </span>
                </div>
                <div class="card-body">
                    <div class="form-group" style="padding:16px; background:#eff6ff; border-radius:var(--radius-md); border:1px solid #bfdbfe;">
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                            <input type="checkbox" name="anti_titip_selfie" value="1" <?= get_setting('anti_titip_selfie', '1') === '1' ? 'checked' : '' ?> style="margin-top:4px;">
                            <div>
                                <strong style="font-size:14px; color:#1e3a8a;">Aktifkan Pengambilan Foto Snapshot Wajah (Selfie Capture) Saat Scan</strong>
                                <p style="font-size:12px; color:#1e40af; margin-top:4px;">
                                    Kamera akan secara otomatis memotret wajah siswa tepat saat QR code terdeteksi. Bukti foto tersimpan di server dan dapat dilihat oleh guru untuk memvalidasi bahwa siswa benar-benar hadir secara fisik.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="form-group" style="max-width:300px; margin-top:16px;">
                        <label class="form-label">Cooldown Anti Double-Scan (Menit)</label>
                        <input type="number" name="cooldown_minutes" class="form-control" value="<?= htmlspecialchars(get_setting('cooldown_minutes', '5')) ?>" min="1" max="60">
                        <small style="color:var(--text-muted); font-size:11px;">Mencegah pemindaian berulang dalam jeda waktu singkat.</small>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Notification Configuration -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <i data-feather="message-circle" style="color:#25d366;"></i> Pengaturan Notifikasi WhatsApp ke Orang Tua
                    </span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Mode Pengiriman WhatsApp</label>
                        <select name="wa_mode" class="form-select" id="waModeSelect" onchange="toggleWaApi(this.value)">
                            <option value="direct" <?= get_setting('wa_mode') === 'direct' ? 'selected' : '' ?>>
                                Mode Direct wa.me (Tombol Pintas 1-Klik Kirim Tanpa Biaya API)
                            </option>
                            <option value="api" <?= get_setting('wa_mode') === 'api' ? 'selected' : '' ?>>
                                Mode WhatsApp Gateway API (Otomatis via Webhook: Fonnte / Wablas / UltraMsg)
                            </option>
                        </select>
                    </div>

                    <div id="waApiFields" style="display:<?= get_setting('wa_mode') === 'api' ? 'block' : 'none' ?>;">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">API Endpoint URL</label>
                                <input type="url" name="wa_api_url" class="form-control" placeholder="https://api.fonnte.com/send" value="<?= htmlspecialchars(get_setting('wa_api_url')) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">API Key / Token</label>
                                <input type="password" name="wa_api_token" class="form-control" placeholder="Token rahasia gateway" value="<?= htmlspecialchars(get_setting('wa_api_token')) ?>">
                            </div>
                        </div>
                    </div>

                    <h4 style="font-size:14px; font-weight:700; color:var(--text-main); margin:20px 0 10px;">
                        Template Pesan WhatsApp
                    </h4>
                    <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                        Gunakan tag variabel: <code>{parent_name}</code>, <code>{student_name}</code>, <code>{time}</code>, <code>{status}</code>, <code>{date}</code>
                    </p>

                    <div class="form-group">
                        <label class="form-label">Template Pesan Presensi Masuk</label>
                        <textarea name="wa_template_in" rows="2" class="form-control"><?= htmlspecialchars(get_setting('wa_template_in')) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Template Pesan Presensi Pulang</label>
                        <textarea name="wa_template_out" rows="2" class="form-control"><?= htmlspecialchars(get_setting('wa_template_out')) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Template Pesan Tidak Hadir (Alpa)</label>
                        <textarea name="wa_template_alpa" rows="2" class="form-control"><?= htmlspecialchars(get_setting('wa_template_alpa')) ?></textarea>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:40px;">
                <button type="submit" class="btn btn-primary" style="padding:12px 32px; font-size:15px;">
                    <i data-feather="check"></i> Simpan Semua Pengaturan
                </button>
            </div>

        </form>

    </div>
</div>

<script>
function toggleWaApi(val) {
    document.getElementById('waApiFields').style.display = (val === 'api') ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
