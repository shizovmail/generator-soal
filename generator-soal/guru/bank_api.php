<?php
require_once __DIR__ . '/../config.php';
$u = require_role('guru');

header('Content-Type: application/json; charset=utf-8');

if (!csrf_check()) {
    json_response(['ok' => false, 'error' => 'csrf'], 403);
}

$body = json_input();
$id = (int)($body['id'] ?? 0);
$data = $body['data'] ?? [];
$meta = $body['meta'] ?? [];

if (!is_array($data)) $data = [];
if (!is_array($meta)) $meta = [];

$stmt = db()->prepare("SELECT id FROM soal_banks WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $u['id']]);
if (!$stmt->fetch()) {
    json_response(['ok' => false, 'error' => 'not_found'], 404);
}

$stmt = db()->prepare("UPDATE soal_banks SET data_json = ?, meta_json = ?, updated_at = datetime('now','localtime') WHERE id = ? AND user_id = ?");
$stmt->execute([
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    $id, $u['id'],
]);

json_response(['ok' => true]);
