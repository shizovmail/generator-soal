# Generator Soal Ujian Anti-Curang

Aplikasi web (PHP + SQLite, 1 file database) untuk membuat bank soal, lalu
men-generate-nya menjadi **aplikasi ujian mandiri** (mirip Examkuro) yang
berisi sistem keamanan ujian anti-curang. Setiap ujian yang digenerate
langsung bisa dibuka murid lewat domain sekolah, lengkap dengan **QR code**
untuk link ujian maupun link generator soalnya.

## Fitur

- **Login multi-role**: Admin & Guru.
  - **Admin**: kelola akun guru (buat/reset password/nonaktifkan/hapus),
    lihat semua ujian & rekap, atur pengaturan aplikasi (nama sekolah, domain,
    kunci enkripsi).
  - **Guru**: kelola bank soal miliknya sendiri (tersimpan per akun),
    generate ujian, lihat hasil pengumpulan jawaban murid.
- **Bank soal** mendukung 6 tipe soal: Pilihan Ganda, Isian Rumpang, Essay,
  Menjodohkan, Multi Jawaban, dan Benar/Salah — dengan poin per opsi/bagian.
- **Data Kelas & Roster Murid** + generate token pribadi per murid, bisa
  dicetak/PDF.
- **Generate Ujian** → menghasilkan 1 file HTML mandiri di folder `/ujian/`
  yang bisa langsung diakses lewat domain sekolah, berisi:
  - Timer ujian, mode layar penuh wajib, deteksi keluar fullscreen (dengan
    masa tenggang sebelum dihitung pelanggaran).
  - Blokir klik kanan, copy/paste, shortcut Ctrl+C/V/U/I/P/S/A, F12,
    Print Screen.
  - Deteksi kasar pembukaan Developer Tools (overlay blokir otomatis).
  - Deteksi pindah tab/aplikasi & minimize (blur/visibilitychange).
  - Auto-submit saat batas pelanggaran tercapai atau waktu habis.
  - Login peserta: manual, atau pilih Kelas & Nama dari daftar (+ token
    bersama / token mandiri per murid).
  - Backup jawaban terenkripsi otomatis terunduh ke perangkat murid sebagai
    jaring pengaman bila koneksi ke server gagal.
  - Setelah submit, soal tidak lagi ditampilkan (mencegah screenshot untuk
    dibagikan ke murid lain).
- **QR Code otomatis**: setiap ujian yang digenerate menampilkan QR code untuk
  (1) link ujian yang dibagikan ke murid, dan (2) link ke halaman generate
  ulang bank soal tsb (untuk guru).
- **Database tunggal**: seluruh data (akun, bank soal, ujian, hasil jawaban)
  disimpan dalam **1 file** `data/app.sqlite`.

## Struktur Folder

```
generator-soal/
├── config.php              # Konfigurasi domain, path DB, kunci enkripsi default
├── index.php                # Halaman login
├── logout.php
├── submit.php                # Endpoint publik penerima jawaban ujian murid
├── includes/
│   ├── db.php                # Koneksi SQLite + skema tabel + seed data awal
│   ├── auth.php               # Session login/role guard
│   ├── helpers.php            # csrf, base_url, response helper, dll
│   ├── header.php / footer.php  # Layout sidebar admin/guru
│   └── exam_template.php      # Generator HTML aplikasi ujian anti-curang
├── admin/
│   ├── index.php               # Dashboard admin
│   ├── guru.php                 # CRUD akun guru & admin
│   ├── exams.php                 # Rekap semua ujian semua guru
│   └── settings.php               # Pengaturan nama sekolah, domain, kunci enkripsi
├── guru/
│   ├── index.php                # Daftar bank soal milik guru
│   ├── bank_edit.php              # Editor soal (6 tipe soal) + Data Kelas/Roster
│   ├── bank_api.php                # Endpoint AJAX simpan otomatis bank soal
│   ├── generate.php                 # Form pengaturan lalu generate file ujian
│   ├── exam_detail.php               # Link, QR code, & hasil jawaban per ujian
│   ├── exams.php                      # Daftar semua ujian yang pernah digenerate
│   └── profile.php                     # Ganti password sendiri
├── ujian/                    # Folder output file HTML ujian hasil generate
│   └── (kosong sampai ada ujian yang digenerate)
└── data/
    └── app.sqlite            # Database SQLite tunggal (dibuat otomatis)
```

## Instalasi di XAMPP (Server Sekolah)

1. Salin seluruh folder `generator-soal` ke dalam `htdocs` XAMPP, misalnya
   menjadi `C:\xampp\htdocs\generator-soal` (atau langsung ke root `htdocs`
   jika domain `smp-kartini-dua.my.id` diarahkan langsung ke folder ini).
2. Pastikan ekstensi PHP **pdo_sqlite** aktif (`php.ini` → hilangkan `;` pada
   `extension=pdo_sqlite`), lalu restart Apache.
3. Beri izin tulis (write) pada folder `data/` dan `ujian/` agar aplikasi bisa
   membuat database dan file ujian.
4. Buka `https://smp-kartini-dua.my.id/` (atau
   `https://smp-kartini-dua.my.id/generator-soal/` sesuai lokasi folder).
   Database `data/app.sqlite` akan otomatis dibuat saat pertama kali diakses.
5. Login dengan akun admin default:
   - **Username:** `admin`
   - **Password:** `admin123`
   - **⚠️ Segera ganti password ini** dan buat akun guru baru dari menu
     "Kelola Akun Guru".
6. Buka menu **Pengaturan Aplikasi** (khusus admin) dan sesuaikan:
   - Nama Sekolah
   - Domain Aplikasi (harus sama persis dengan domain yang dipakai murid,
     contoh: `https://smp-kartini-dua.my.id`) — dipakai untuk membangun
     link ujian & QR code.
   - Kunci Enkripsi Ujian (opsional, untuk memperkuat backup lokal).

## Alur Pemakaian oleh Guru

1. Login sebagai guru → menu **Bank Soal Saya** → **+ Bank Soal Baru**.
2. Tambahkan soal (6 tipe tersedia) di halaman editor — tersimpan otomatis.
3. (Opsional) isi **Data Kelas & Roster Murid** jika ingin murid login
   dengan memilih nama dari daftar dan/atau pakai token pribadi per murid.
4. Klik **🚀 Generate** → isi pengaturan ujian (durasi, mode login, token,
   batas pelanggaran, acak soal/opsi, tampilkan nilai) → **Generate Aplikasi
   Ujian**.
5. Anda akan diarahkan ke halaman detail ujian yang menampilkan:
   - Link + QR Code ujian untuk dibagikan ke murid.
   - Link + QR Code untuk membuka kembali halaman generate bank soal ini.
   - Tabel hasil jawaban murid (nama, nilai, jumlah pelanggaran, waktu kirim).
6. Bagikan link/QR ke murid. Murid membuka link tersebut langsung dari
   domain sekolah, tanpa perlu login akun.

## Catatan Keamanan & Pengembangan Lanjutan

- Sistem anti-curang di sini adalah pencegahan tingkat browser (fullscreen,
  blokir shortcut, deteksi blur/visibility, deteksi kasar DevTools). Ini
  **bukan** pengganti pengawasan langsung saat ujian berlangsung — tetap
  perlu ada guru pengawas di kelas.
- File `data/app.sqlite` sebaiknya dicadangkan (backup) secara berkala oleh
  admin sekolah karena berisi seluruh data aplikasi.
- Untuk pengembangan lanjutan yang bisa ditambahkan: fitur AI Assist (soal
  otomatis via Gemini API), ekspor nilai ke Excel/PDF, halaman khusus
  pemeriksaan essay manual oleh guru, log detail waktu pengerjaan per soal,
  serta mode ujian luring (PWA/offline).
- Silakan cek & uji ulang aplikasi ini sebelum dipakai untuk ujian
  sesungguhnya, dan sesuaikan/tambahkan fitur sesuai kebutuhan sekolah.
