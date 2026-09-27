<?php
// Endpoint penerima jawaban ujian dari file HTML hasil generate.
// Tidak butuh login - dipanggil langsung dari browser murid.
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'error' => 'method_not_allowed'], 405); }

$data = json_input();
$slug = trim($data['exam_slug'] ?? '');
if ($slug === '') json_response(['ok' => false, 'error' => 'missing_slug'], 400);

$stmt = db()->prepare("SELECT * FROM exams WHERE slug = ? AND active = 1");
$stmt->execute([$slug]);
$exam = $stmt->fetch();
if (!$exam) json_response(['ok' => false, 'error' => 'exam_not_found'], 404);

$nama = trim($data['nama'] ?? '');
$nisn = trim($data['nisn'] ?? '');
$nilai = (float)($data['nilai'] ?? 0);
$totalKecurangan = (int)($data['total_kecurangan'] ?? 0);
$waktuKirim = trim($data['waktu_kirim'] ?? date('c'));
$signature = trim($data['signature'] ?? '');
$detail = $data['detail_jawaban'] ?? [];

if ($nama === '') json_response(['ok' => false, 'error' => 'missing_nama'], 400);

$stmt = db()->prepare("INSERT INTO submissions (exam_id, nama, nisn, nilai, total_kecurangan, waktu_kirim, signature, detail_json, ip_address)
    VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->execute([
    $exam['id'], $nama, $nisn, $nilai, $totalKecurangan, $waktuKirim, $signature,
    json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    $_SERVER['REMOTE_ADDR'] ?? '',
]);

json_response(['ok' => true]);
