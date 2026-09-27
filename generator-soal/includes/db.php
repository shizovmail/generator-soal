<?php
// Koneksi database SQLite tunggal + inisialisasi skema otomatis.

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dataDir = dirname(DB_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    $isNew = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    init_schema($pdo);

    if ($isNew) {
        seed_default_data($pdo);
    }

    return $pdo;
}

function init_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'guru', -- 'admin' | 'guru'
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );

        -- Bank soal milik masing-masing guru. Isi soal disimpan sebagai JSON.
        CREATE TABLE IF NOT EXISTS soal_banks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            judul TEXT NOT NULL,
            mapel TEXT,
            data_json TEXT NOT NULL DEFAULT '[]', -- array soal
            meta_json TEXT NOT NULL DEFAULT '{}', -- info sekolah, kelas, roster, dsb
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        );

        -- Setiap kali guru menekan tombol Generate Ujian, 1 baris exam dibuat berikut file HTML statisnya.
        CREATE TABLE IF NOT EXISTS exams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bank_id INTEGER NOT NULL REFERENCES soal_banks(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            slug TEXT UNIQUE NOT NULL,
            judul TEXT NOT NULL,
            exam_token TEXT,
            secret_key TEXT NOT NULL,
            settings_json TEXT NOT NULL DEFAULT '{}',
            file_path TEXT NOT NULL,
            exam_url TEXT NOT NULL,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        );

        CREATE TABLE IF NOT EXISTS submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            exam_id INTEGER NOT NULL REFERENCES exams(id) ON DELETE CASCADE,
            nama TEXT,
            nisn TEXT,
            nilai REAL,
            total_kecurangan INTEGER DEFAULT 0,
            waktu_kirim TEXT,
            signature TEXT,
            detail_json TEXT,
            ip_address TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        );
    ");
}

function seed_default_data(PDO $pdo): void {
    // Akun admin default pertama kali dijalankan.
    $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?,?,?,?)");
    $stmt->execute(['Administrator', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);

    $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
    $stmt->execute(['exam_secret_key', DEFAULT_EXAM_SECRET_KEY]);
    $stmt->execute(['nama_sekolah', 'SMP Kartini 2 Batam']);
    $stmt->execute(['app_domain', APP_DOMAIN]);
}

function get_setting(string $key, $default = null) {
    $stmt = db()->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function set_setting(string $key, string $value): void {
    $stmt = db()->prepare("INSERT INTO settings (key, value) VALUES (?, ?)
        ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $stmt->execute([$key, $value]);
}
