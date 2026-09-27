<?php
// Fungsi bantu umum.

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    // Cari root aplikasi (folder tempat config.php berada) relatif ke document root.
    $root = detect_app_root();
    return $root . '/' . ltrim($path, '/');
}

function detect_app_root(): string {
    // Path folder aplikasi relatif ke document root, dihitung dari lokasi file ini.
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    if ($docRoot && strpos($appDir, $docRoot) === 0) {
        return substr($appDir, strlen($docRoot));
    }
    return '';
}

function random_token(int $length = 6): string {
    $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa 0/O, 1/I yang mirip
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $charset[random_int(0, strlen($charset) - 1)];
    }
    return $out;
}

function random_slug(int $length = 10): string {
    $charset = 'abcdefghijkmnpqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $charset[random_int(0, strlen($charset) - 1)];
    }
    return $out;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): bool {
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}

function flash_set(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
