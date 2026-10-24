@echo off
title Menjalankan Sistem Absensi Siswa QR Code
echo ========================================================
echo   MEMULAI SERVER ABSENSI SISWA QR CODE
echo   SMK BINA BANGSA NUSANTARA
echo ========================================================
echo.
echo Server sedang berjalan di: http://localhost:8000
echo Membuka browser otomatis...
echo.
echo (JANGAN TUTUP JENDELA INI SELAMA APLIKASI DIGUNAKAN)
echo Tekan Ctrl + C untuk mematikan server.
echo.

start http://localhost:8000
php -S localhost:8000
pause
