<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Kiosk Pemindai QR Code';
$page_heading = 'Kiosk Pemindai Presensi Siswa';
require_once __DIR__ . '/includes/header.php';

$with_sidebar = is_logged_in();
if ($with_sidebar) {
    require_once __DIR__ . '/includes/sidebar.php';
}
?>

<div class="main-wrapper" style="<?= $with_sidebar ? '' : 'margin-left: 0; width: 100%;' ?>">
    <header class="top-navbar">
        <div class="top-title">
            <h1 style="display:flex; align-items:center; gap:10px;">
                <i data-feather="camera" style="color:var(--accent-emerald);"></i> 
                Scanner Presensi QR Code Siswa
            </h1>
            <p>
                <?= format_date_id(date('Y-m-d')) ?> &bull; 
                <span id="live-clock" style="color:var(--primary); font-weight:700;"><?= date('H:i:s') ?> WIB</span>
            </p>
        </div>
        <div class="top-actions">
            <?php if ($with_sidebar): ?>
                <a href="dashboard.php" class="btn btn-outline btn-sm">
                    <i data-feather="grid"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">
                    <i data-feather="log-in"></i> Login Petugas
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="content-body" style="padding: 24px;">
        
        <!-- Mode Switch: Masuk / Pulang -->
        <div class="scan-mode-toggle" style="margin-bottom:8px;">
            <button type="button" id="btn-mode-in" class="mode-btn active-in" onclick="setScanMode('in')">
                <i data-feather="log-in" style="width:16px;height:16px;vertical-align:middle;"></i> PRESENSI MASUK
            </button>
            <button type="button" id="btn-mode-out" class="mode-btn inactive" onclick="setScanMode('out')">
                <i data-feather="log-out" style="width:16px;height:16px;vertical-align:middle;"></i> PRESENSI PULANG
            </button>
        </div>

        <!-- Mode Indicator Alert -->
        <div style="text-align:center; margin-bottom:20px;">
            <span id="mode-status-text" class="badge badge-success" style="padding:6px 18px; font-size:13px; font-weight:700;">
                <i data-feather="check-circle" style="width:14px;height:14px;vertical-align:middle;"></i> MODE PRESENSI MASUK AKTIF - Kamera Siap Memindai Otomatis
            </span>
        </div>

        <div class="kiosk-container">
            <!-- Left Side: Camera Scanner Viewport -->
            <div class="scanner-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <div style="text-align:left;">
                        <h3 style="font-size:16px; font-weight:700; color:var(--text-main);">Arahkan QR Code ke Kamera</h3>
                        <p style="font-size:12px; color:var(--text-muted);">Posisikan QR Code di tengah kotak scanner</p>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <select id="camera-select" class="form-select" style="max-width:200px; padding:6px 10px; font-size:12px;">
                            <option value="">Memuat kamera...</option>
                        </select>
                        <button type="button" id="toggle-audio-btn" class="btn btn-outline btn-sm" onclick="toggleAudio()" title="Suara Feedback">
                            <i data-feather="volume-2" id="audio-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Camera Container -->
                <div class="scanner-viewport">
                    <div id="reader"></div>
                </div>

                <!-- Hidden Canvas for Anti-Titip Snapshot -->
                <canvas id="snapshot-canvas" style="display:none;"></canvas>

                <!-- Manual Input Fallback -->
                <div style="margin-top:20px; display:flex; gap:10px; justify-content:center; max-width:480px; margin-left:auto; margin-right:auto;">
                    <input type="text" id="manual-nis-input" class="form-control" placeholder="Input manual NIS atau Token QR..." style="text-align:center; font-weight:700;" onkeypress="if(event.key === 'Enter') submitManualScan()">
                    <button type="button" class="btn btn-primary" onclick="submitManualScan()">
                        <i data-feather="arrow-right"></i> Proses
                    </button>
                </div>

                <!-- Quick Test Buttons for Testing -->
                <div style="margin-top:14px; font-size:12px; color:var(--text-muted); text-align:center;">
                    <span style="font-weight:600;">Klik untuk Uji Coba Cepat:</span>
                    <button type="button" class="btn btn-outline btn-sm" onclick="quickScan('10106')" style="margin:2px 4px; padding:3px 8px; font-size:11px;" title="Belum absen hari ini">
                        NIS 10106 (Fanya)
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="quickScan('10107')" style="margin:2px 4px; padding:3px 8px; font-size:11px;" title="Belum absen hari ini">
                        NIS 10107 (Gilang)
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="quickScan('10108')" style="margin:2px 4px; padding:3px 8px; font-size:11px;" title="Belum absen hari ini">
                        NIS 10108 (Hafizh)
                    </button>
                </div>

                <!-- Scan from Image File -->
                <div style="margin-top:10px; text-align:center;">
                    <input type="file" id="qr-file-input" accept="image/*" style="display:none;" onchange="scanQrFromFile(this.files[0])">
                    <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('qr-file-input').click()" style="color:var(--primary); font-weight:600;">
                        <i data-feather="image"></i> Atau Unggah Foto / Screenshot QR Code
                    </button>
                </div>
            </div>

            <!-- Right Side: Live Result & Today's Attendance Stream -->
            <div class="live-feed-sidebar">
                <!-- Result Box -->
                <div id="scan-result-container" class="scan-result-card" style="display:none;">
                    <div id="result-status-badge" style="margin-bottom:8px;"></div>
                    <img id="result-avatar" src="assets/images/default-avatar.svg" class="scan-avatar-preview" alt="Foto Siswa">
                    <h3 id="result-name" style="font-size:18px; font-weight:800; color:var(--text-main);">-</h3>
                    <p id="result-meta" style="font-size:13px; color:var(--text-muted); font-weight:600;">NIS: - | Kelas: -</p>
                    <div class="scan-time-badge" id="result-time">--:--:--</div>
                    <p id="result-message" style="font-size:12px; color:var(--text-muted); margin-top:4px;"></p>
                    
                    <div id="result-wa-btn-container" style="margin-top:14px; display:none;">
                        <a id="result-wa-btn" href="#" target="_blank" class="btn btn-sm" style="background:#25d366; color:#fff; font-weight:700; width:100%;">
                            <i data-feather="send"></i> Kirim WhatsApp ke Orang Tua
                        </a>
                    </div>
                </div>

                <!-- Live Stream Table of Today's Scans -->
                <div class="card" style="margin-bottom:0;">
                    <div class="card-header">
                        <span class="card-title">
                            <i data-feather="activity"></i> Aktivitas Terkini Hari Ini
                        </span>
                        <span class="badge badge-primary" id="total-scanned-count">0 Siswa</span>
                    </div>
                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table-custom" id="stream-table">
                            <thead>
                                <tr>
                                    <th>Siswa</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="stream-tbody">
                                <?php
                                $today = date('Y-m-d');
                                $stmt = get_db()->prepare("
                                    SELECT a.*, s.name, s.nis, c.name as class_name 
                                    FROM attendance a 
                                    JOIN students s ON a.student_id = s.id 
                                    LEFT JOIN classes c ON s.class_id = c.id 
                                    WHERE a.date = ? 
                                    ORDER BY a.updated_at DESC 
                                    LIMIT 10
                                ");
                                $stmt->execute([$today]);
                                $recent_logs = $stmt->fetchAll();
                                if (empty($recent_logs)): ?>
                                    <tr id="empty-stream-row">
                                        <td colspan="3" style="text-align:center; color:var(--text-muted); padding:20px;">
                                            Belum ada presensi yang tercatat hari ini
                                        </td>
                                    </tr>
                                <?php else: 
                                    foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700;"><?= htmlspecialchars($log['name']) ?></div>
                                            <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($log['class_name'] ?? 'Umum') ?></div>
                                        </td>
                                        <td style="font-weight:700; color:var(--primary);">
                                            <?= $log['time_in'] ? date('H:i', strtotime($log['time_in'])) : ($log['time_out'] ? date('H:i', strtotime($log['time_out'])) : '-') ?>
                                        </td>
                                        <td>
                                            <?= render_status_badge($log['status']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="assets/js/html5-qrcode.min.js"></script>
<script>
let currentMode = 'in'; // 'in' or 'out'
let isAudioEnabled = true;
let isProcessingScan = false;
let html5QrCode = null;

function setScanMode(mode) {
    currentMode = mode;
    const btnIn = document.getElementById('btn-mode-in');
    const btnOut = document.getElementById('btn-mode-out');
    const statusText = document.getElementById('mode-status-text');
    
    if (mode === 'in') {
        btnIn.className = 'mode-btn active-in';
        btnOut.className = 'mode-btn inactive';
        if (statusText) {
            statusText.className = 'badge badge-success';
            statusText.innerHTML = '<i data-feather="check-circle" style="width:14px;height:14px;vertical-align:middle;"></i> MODE PRESENSI MASUK AKTIF - Kamera Siap Memindai Otomatis';
        }
    } else {
        btnIn.className = 'mode-btn inactive';
        btnOut.className = 'mode-btn active-out';
        if (statusText) {
            statusText.className = 'badge badge-warning';
            statusText.innerHTML = '<i data-feather="log-out" style="width:14px;height:14px;vertical-align:middle;"></i> MODE PRESENSI PULANG AKTIF - Kamera Siap Memindai Otomatis';
        }
    }
    feather.replace();
}

function quickScan(nis) {
    document.getElementById('manual-nis-input').value = nis;
    submitManualScan();
}

function toggleAudio() {
    isAudioEnabled = !isAudioEnabled;
    const icon = document.getElementById('audio-icon');
    if (isAudioEnabled) {
        icon.setAttribute('data-feather', 'volume-2');
    } else {
        icon.setAttribute('data-feather', 'volume-x');
    }
    feather.replace();
}

// Sound Feedback
function playSound(type) {
    if (!isAudioEnabled) return;
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (type === 'success') {
            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
            osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.35);
        } else {
            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
            osc.frequency.setValueAtTime(150, audioCtx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.4);
        }
    } catch (e) {
        console.warn('AudioContext not supported or blocked:', e);
    }
}

// Voice synthesis feedback in Indonesian
function speakFeedback(text) {
    if (!isAudioEnabled || !('speechSynthesis' in window)) return;
    try {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 1.05;
        window.speechSynthesis.speak(utterance);
    } catch (e) {}
}

// Anti-Titip Snapshot: Capture frame from camera video tag
function captureCameraFrame() {
    try {
        const video = document.querySelector('#reader video');
        if (!video) return null;
        
        const canvas = document.getElementById('snapshot-canvas');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/jpeg', 0.7);
    } catch (err) {
        console.warn('Could not capture frame snapshot:', err);
        return null;
    }
}

// Handle QR Scan
async function onScanSuccess(decodedText, decodedResult) {
    if (isProcessingScan) return;
    isProcessingScan = true;

    // Grab selfie snapshot immediately
    const snapshotBase64 = captureCameraFrame();

    try {
        const response = await fetch('api/scan_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                qr_token: decodedText,
                scan_mode: currentMode,
                photo_snapshot: snapshotBase64
            })
        });

        const res = await response.json();
        showScanResult(res);
    } catch (err) {
        showScanResult({
            success: false,
            message: 'Koneksi ke server gagal: ' + err.message
        });
    }

    // Cooldown 2.5 seconds before next scan
    setTimeout(() => {
        isProcessingScan = false;
    }, 2500);
}

function showScanResult(res) {
    const container = document.getElementById('scan-result-container');
    const badge = document.getElementById('result-status-badge');
    const nameEl = document.getElementById('result-name');
    const metaEl = document.getElementById('result-meta');
    const timeEl = document.getElementById('result-time');
    const msgEl = document.getElementById('result-message');
    const avatarEl = document.getElementById('result-avatar');
    const waContainer = document.getElementById('result-wa-btn-container');
    const waBtn = document.getElementById('result-wa-btn');

    container.style.display = 'block';

    if (res.success) {
        playSound('success');
        badge.innerHTML = `<span class="badge badge-success"><i data-feather="check-circle"></i> Berhasil (${res.status_label})</span>`;
        nameEl.textContent = res.student.name;
        metaEl.textContent = `NIS: ${res.student.nis} | Kelas: ${res.student.class_name}`;
        timeEl.textContent = res.formatted_time;
        if (res.student && res.student.photo) {
            avatarEl.src = res.student.photo;
        } else if (res.snapshot_url) {
            avatarEl.src = res.snapshot_url;
        } else {
            avatarEl.src = 'assets/images/default-avatar.svg';
        }

        if (res.wa_link) {
            waContainer.style.display = 'block';
            waBtn.href = res.wa_link;
        } else {
            waContainer.style.display = 'none';
        }

        // Voice message
        speakFeedback(`Presensi ${currentMode === 'in' ? 'masuk' : 'pulang'} berhasil, ${res.student.name}`);

        // Prepend to live stream table
        addStreamRow(res.student.name, res.student.class_name, res.formatted_time, res.status);
    } else if (res.already_done) {
        playSound('error');
        badge.innerHTML = `<span class="badge badge-warning"><i data-feather="alert-circle"></i> Sudah Presensi</span>`;
        nameEl.textContent = res.student ? res.student.name : 'Siswa';
        metaEl.textContent = res.student ? `NIS: ${res.student.nis} | Kelas: ${res.student.class_name}` : '';
        timeEl.textContent = res.time_in ? res.time_in.substring(0, 5) + ' WIB' : '--:--';
        msgEl.textContent = res.message;
        avatarEl.src = (res.student && res.student.photo) ? res.student.photo : 'assets/images/default-avatar.svg';
        waContainer.style.display = 'none';

        speakFeedback(res.message);
    } else {
        playSound('error');
        badge.innerHTML = `<span class="badge badge-danger"><i data-feather="alert-triangle"></i> Gagal</span>`;
        nameEl.textContent = res.student ? res.student.name : 'Tidak Terdaftar';
        metaEl.textContent = res.student ? `NIS: ${res.student.nis}` : 'Data tidak valid';
        timeEl.textContent = '--:--:--';
        msgEl.textContent = res.message;
        avatarEl.src = 'assets/images/default-avatar.svg';
        waContainer.style.display = 'none';

        speakFeedback(res.message);
    }

    feather.replace();
}

function addStreamRow(name, className, timeStr, status) {
    const emptyRow = document.getElementById('empty-stream-row');
    if (emptyRow) emptyRow.remove();

    const tbody = document.getElementById('stream-tbody');
    const tr = document.createElement('tr');
    tr.style.backgroundColor = '#ecfdf5';
    tr.innerHTML = `
        <td>
            <div style="font-weight:700;">${name}</div>
            <div style="font-size:11px; color:var(--text-muted);">${className}</div>
        </td>
        <td style="font-weight:700; color:var(--primary);">${timeStr}</td>
        <td><span class="badge badge-success">${status.toUpperCase()}</span></td>
    `;
    tbody.prepend(tr);
    setTimeout(() => { tr.style.backgroundColor = 'transparent'; }, 2000);
}

function submitManualScan() {
    const input = document.getElementById('manual-nis-input');
    const val = input.value.trim();
    if (!val) return;
    onScanSuccess(val, null);
    input.value = '';
}

// Resilient Html5Qrcode initialization
let scannerInstance = null;

function initCamera() {
    const readerEl = document.getElementById('reader');
    const select = document.getElementById('camera-select');

    if (!scannerInstance) {
        scannerInstance = new Html5Qrcode("reader");
    }

    Html5Qrcode.getCameras().then(cameras => {
        select.innerHTML = '';
        if (cameras && cameras.length > 0) {
            cameras.forEach((camera, index) => {
                const opt = document.createElement('option');
                opt.value = camera.id;
                opt.text = camera.label || `Kamera ${index + 1}`;
                select.appendChild(opt);
            });

            startCamera(cameras[0].id);

            select.onchange = (e) => {
                stopAndStartCamera(e.target.value);
            };
        } else {
            // Fallback to default user camera without ID
            startCamera({ facingMode: "user" });
        }
    }).catch(err => {
        console.warn("getCameras failed, trying facingMode fallback:", err);
        startCamera({ facingMode: "user" });
    });
}

function stopAndStartCamera(cameraConfig) {
    if (scannerInstance && scannerInstance.isScanning) {
        scannerInstance.stop().then(() => {
            startCamera(cameraConfig);
        }).catch(err => {
            startCamera(cameraConfig);
        });
    } else {
        startCamera(cameraConfig);
    }
}

function startCamera(cameraConfig) {
    if (!scannerInstance) {
        scannerInstance = new Html5Qrcode("reader");
    }

    const config = {
        fps: 15,
        qrbox: { width: 250, height: 250 }
    };

    scannerInstance.start(
        cameraConfig,
        config,
        onScanSuccess,
        (errorMessage) => {
            // normal frame scan loop
        }
    ).catch(err => {
        console.error("Camera start error:", err);
        showCameraError(err);
    });
}

function scanQrFromFile(file) {
    if (!file) return;
    if (!scannerInstance) {
        scannerInstance = new Html5Qrcode("reader");
    }
    scannerInstance.scanFile(file, true)
        .then(decodedText => {
            onScanSuccess(decodedText, null);
        })
        .catch(err => {
            showScanResult({
                success: false,
                message: 'Tidak ada QR Code yang terdeteksi di foto tersebut.'
            });
        });
}

function showCameraError(err) {
    const readerEl = document.getElementById('reader');
    let msg = 'Izin Kamera Belum Aktif di Browser';
    let tip = 'Browser Chrome/Edge Anda membutuhkan izin kamera yang harus disetujui secara manual.';

    if (err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError' || (err.message && err.message.includes('dismissed')))) {
        msg = 'Izin Akses Kamera Perlu Diaktifkan';
        tip = 'Di Chrome/Edge: Klik ikon <b>gembok 🔒</b> atau ikon <b>setelan 🎛️</b> di sebelah kiri URL <code>http://localhost:8000</code> &rarr; lalu ubah <b>Kamera</b> menjadi <b>"Izinkan / Allow"</b>.';
    } else if (err && (err.name === 'NotReadableError' || err.name === 'TrackStartError')) {
        msg = 'Kamera Sedang Dipakai Aplikasi Lain';
        tip = 'Tutup aplikasi seperti Zoom, Google Meet, Teams, atau tab browser lain yang sedang memakai webcam.';
    }

    readerEl.innerHTML = `
        <div style="padding:24px 18px; color:#fff; text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">
            <div style="font-size:36px; margin-bottom:8px;">📷</div>
            <h4 style="font-size:15px; font-weight:700; color:#f87171; margin-bottom:6px;">${msg}</h4>
            <p style="font-size:12px; color:#cbd5e1; max-width:360px; line-height:1.5; margin-bottom:16px;">${tip}</p>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn btn-primary btn-sm" onclick="retryCamera()">
                    <i data-feather="refresh-cw"></i> Nyalakan Ulang Kamera
                </button>
            </div>
        </div>
    `;
    feather.replace();
}

async function retryCamera() {
    const readerEl = document.getElementById('reader');
    readerEl.innerHTML = '<div style="color:#fff; padding:40px; text-align:center;"><div style="font-size:24px;margin-bottom:8px;">⏳</div>Meminta izin kamera ke browser...</div>';
    
    if (scannerInstance) {
        try {
            if (scannerInstance.isScanning) {
                await scannerInstance.stop();
            }
        } catch (e) {}
        scannerInstance = null;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        stream.getTracks().forEach(track => track.stop());
        readerEl.innerHTML = '';
        initCamera();
    } catch (err) {
        showCameraError(err);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initCamera();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
