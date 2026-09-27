<?php
// Autentikasi sederhana berbasis session + password_hash.

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['uid']]);
    $u = $stmt->fetch();
    $cache = $u ?: null;
    return $cache;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        header('Location: ' . base_url('index.php'));
        exit;
    }
    return $u;
}

function require_role(string $role): array {
    $u = require_login();
    if ($u['role'] !== $role) {
        http_response_code(403);
        die('Akses ditolak. Halaman ini khusus untuk role: ' . h($role));
    }
    return $u;
}

function attempt_login(string $username, string $password): bool {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        $_SESSION['role'] = $u['role'];
        return true;
    }
    return false;
}

function do_logout(): void {
    $_SESSION = [];
    session_destroy();
}
