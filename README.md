# Website Absensi Siswa Berbasis QR Code (PHP Native)
**SMK BINA BANGSA NUSANTARA**

Aplikasi sistem absensi siswa berbasis web yang modern, interaktif, dan cerah menggunakan **PHP Native** dan **Vanilla CSS**. Dilengkapi dengan fitur pemindai kamera QR Code, sistem keamanan **Anti-Titip Absen** (selfie snapshot), notifikasi WhatsApp otomatis ke orang tua, rekapitulasi laporan, dan cetak kartu pelajar (ID Card).

---

## 🚀 Fitur Utama

### 1. Fitur Utama & Scanning
- **Kiosk Pemindai QR Code (`scan.php`)**:
  - Pilihan mode: **Presensi Masuk** & **Presensi Pulang**.
  - Deteksi otomatis kamera perangkat (depan/belakang/webcam) menggunakan `html5-qrcode`.
  - Umpan balik suara interaktif (Beep & Text-to-Speech bahasa Indonesia).
  - Input manual NIS / Token QR sebagai alternatif jika kamera silau atau perangkat tanpa kamera.
- **Sistem Anti-Titip Absen**:
  - Pengambilan foto snapshot wajah (selfie snapshot) otomatis saat QR code terdeteksi. Foto tersimpan di server dan terlampir pada riwayat presensi.
  - Token QR Code terenkripsi unik per siswa untuk mencegah duplikasi.
  - Cooldown anti double-scan.
- **Pencatatan Kehadiran Real-Time**:
  - Jam masuk dan keluar langsung tersimpan detik itu juga ke database.
  - Penentuan status otomatis: **Tepat Waktu** vs **Terlambat** berdasarkan batas waktu dan toleransi keterlambatan.

### 2. Manajemen Data & Administrasi
- **Dashboard Admin & Guru (`dashboard.php`)**:
  - Kartu metrik: Total Siswa, Hadir Tepat Waktu, Terlambat, Izin, Sakit, Alpa/Belum Hadir.
  - Grafik tren kehadiran 5 hari terakhir (Chart.js) & diagram donat rasio kehadiran hari ini.
  - Filter cepat per kelas.
  - Tabel aktivitas presensi terbaru hari ini dengan tombol kirim WhatsApp.
- **Manajemen Siswa & Kelas (`students.php`, `classes.php`)**:
  - Tambah, edit, hapus data siswa (NIS, NISN, Nama, Jenis Kelamin, Kelas, Kontak Orang Tua, Foto).
  - Fitur **Impor Siswa Massal via CSV** (`student_import.php`) dengan pembuatan token QR otomatis.
  - Manajemen kelas dan penugasan wali kelas.
- **Generator & Cetak Kartu Pelajar QR (`qr_generator.php`)**:
  - Pembuat kartu identitas pelajar (ID Card) resmi lengkap dengan foto, identitas siswa, dan kode QR beresolusi tinggi.
  - Fitur cetak kartu perorangan maupun **cetak massal 1 kelas sekaligus** siap potong/laminating (Print-Ready CSS).
- **Manajemen Hari Libur & Jam Masuk (`holidays.php`)**:
  - Konfigurasi jam buka scan masuk, batas tepat waktu, dan toleransi keterlambatan (menit).
  - Konfigurasi jam buka & tutup scan pulang.
  - Daftar hari libur nasional / cuti bersama serta opsi penguncian scanner pada tanggal merah.

### 3. Rekapitulasi & Laporan (`reports.php`)
- **Rekap Otomatis**: Laporan harian, mingguan, bulanan, maupun rentang tanggal kustom.
- **Ekspor Excel**: Unduh langsung laporan presensi dalam format `.xls` (Microsoft Excel).
- **Cetak / PDF Resmi**: Dilengkapi kop surat resmi sekolah, tabel rincian kehadiran, dan kolom tanda tangan kepala sekolah & guru piket.
- **Lembar Presensi Harian (`attendance.php`)**: Filter per tanggal & kelas, opsi ubah presensi manual, dan tautan kirim pesan ke wali murid.

### 4. Notifikasi WhatsApp & Pengajuan Izin
- **Notifikasi ke Orang Tua**:
  - Integrasi WhatsApp link langsung (`wa.me`) bebas biaya API.
  - Dukungan WhatsApp Gateway API (Fonnte, Wablas, UltraMsg) dengan template pesan otomatis.
- **Pengajuan Izin / Sakit Digital (`permit_apply.php`, `permits.php`)**:
  - Formulir digital pengajuan izin/sakit dengan lampiran surat dokter/surat izin.
  - Panel verifikasi wali kelas (Setujui / Tolak) yang langsung memperbarui catatan kehadiran siswa secara otomatis.
- **Manajemen Pengguna (`users.php`)**:
  - Multi-role: `admin` (Administrator), `guru_piket` (Guru Piket), `wali_kelas` (Wali Kelas).

---

## 📋 Akun Uji Coba Default

| Peran (Role) | Username | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Hak akses penuh sistem & pengaturan |
| **Guru Piket** | `gurupiket` | `guru123` | Akses scanner, presensi harian, rekap |
| **Wali Kelas** | `walikelas` | `wali123` | Verifikasi izin siswa & data kelas binaan |

---

## 💻 Cara Menjalankan Aplikasi

Aplikasi telah dikonfigurasi dengan **SQLite bawaan (Zero Setup)** sehingga dapat langsung dijalankan tanpa perlu membuat database MySQL terlebih dahulu.

### Menjalankan dengan PHP Built-in Server:
Jalankan perintah berikut pada terminal di folder proyek:
```bash
php -S localhost:8000
```
Buka browser dan akses:
- **Halaman Utama**: [http://localhost:8000](http://localhost:8000)
- **Kiosk Scanner QR**: [http://localhost:8000/scan.php](http://localhost:8000/scan.php)
- **Login Petugas**: [http://localhost:8000/login.php](http://localhost:8000/login.php)

---

## 🗄️ Opsi Database MySQL (Opsional)

Jika Anda ingin menggunakan MySQL (misalnya di phpMyAdmin / XAMPP):
1. Buat database baru bernama `absensi_sekolah`.
2. Impor file [schema.sql](file:///c:/Users/Lenovo%20Loq/Documents/absensi_sekolah_qrcode/database/schema.sql) ke phpMyAdmin.
3. Buka file [config/database.php](file:///c:/Users/Lenovo%20Loq/Documents/absensi_sekolah_qrcode/config/database.php) dan ubah baris:
   ```php
   define('DB_DRIVER', 'mysql');
   ```
