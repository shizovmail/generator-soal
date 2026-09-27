<?php
// Konfigurasi utama Generator Soal Ujian Anti-Curang
// ---------------------------------------------------
// Ganti sesuai domain sekolah Anda. Dipakai untuk membangun link ujian & QR code.
define('APP_DOMAIN', 'https://smp-kartini-dua.my.id');

// Lokasi database (SQLite, 1 file saja) - simpan di luar folder publik idealnya,
// tapi untuk kemudahan XAMPP disimpan di /data/app.sqlite (folder ini diberi .htaccess deny all).
define('DB_PATH', __DIR__ . '/data/app.sqlite');

// Folder tempat file ujian hasil generate (.html) disimpan agar bisa diakses murid via domain sekolah.
// Link publiknya dibangun dari setting "app_domain" (bisa diubah admin), bukan konstanta ini,
// supaya otomatis menyesuaikan jika aplikasi dipindah ke subfolder lain (mis. htdocs/app).
define('UJIAN_DIR', __DIR__ . '/ujian');

// Kunci enkripsi bawaan (bisa ditimpa lewat pengaturan admin, disimpan di tabel settings).
define('DEFAULT_EXAM_SECRET_KEY', 'kuro_sensei_ganti_ini');

// Nama aplikasi
define('APP_NAME', 'Generator Soal Ujian Anti-Curang');

date_default_timezone_set('Asia/Jakarta');
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
