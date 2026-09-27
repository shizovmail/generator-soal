<?php
require_once __DIR__ . '/../config.php';
$u = require_role('guru');
$active = 'guru_exams';
$pageTitle = 'Ujian Ter-generate';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check() && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM exams WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $u['id']]);
    $exam = $stmt->fetch();
    if ($exam) {
        if (!empty($exam['file_path']) && is_file($exam['file_path'])) @unlink($exam['file_path']);
        db()->prepare("DELETE FROM exams WHERE id = ? AND user_id = ?")->execute([$id, $u['id']]);
        flash_set('Ujian "' . $exam['judul'] . '" beserta hasil jawabannya berhasil dihapus.');
    }
    header('Location: ' . base_url('guru/exams.php'));
    exit;
}

$stmt = db()->prepare("
    SELECT e.*, (SELECT COUNT(*) FROM submissions s WHERE s.exam_id = e.id) as total_submit
    FROM exams e WHERE e.user_id = ? ORDER BY e.id DESC
");
$stmt->execute([$u['id']]);
$exams = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-1">Ujian Ter-generate</h1>
<p class="text-sm text-slate-500 mb-6">Semua aplikasi ujian yang pernah Anda buat dari bank soal.</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach ($exams as $e): ?>
  <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div>
      <h3 class="font-bold text-slate-800"><?= h($e['judul']) ?></h3>
      <p class="text-[10px] text-slate-400 font-mono"><?= h($e['slug']) ?></p>
    </div>
    <div class="flex gap-3 text-xs text-slate-500">
      <span>📨 <?= (int)$e['total_submit'] ?> pengumpulan</span>
      <span class="<?= $e['active'] ? 'text-green-600' : 'text-red-500' ?>"><?= $e['active'] ? '● Aktif' : '● Nonaktif' ?></span>
    </div>
    <p class="text-[10px] text-slate-300">Dibuat: <?= h($e['created_at']) ?></p>
    <a href="<?= base_url('guru/exam_detail.php?id=' . $e['id']) ?>" class="block text-center bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold py-2 rounded-lg">Lihat Detail, QR & Hasil</a>
    <form method="post" onsubmit="return confirm('Hapus ujian ini beserta file HTML dan seluruh hasil jawaban murid?')">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
      <button class="w-full text-center bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold py-1.5 rounded-lg">🗑️ Hapus Ujian</button>
    </form>
  </div>
<?php endforeach; ?>
<?php if (!$exams): ?>
  <div class="col-span-full text-center py-16 text-slate-400">
    <span class="text-3xl">🚀</span>
    <p class="text-sm mt-2">Belum ada ujian yang digenerate. Buka Bank Soal lalu klik "Generate".</p>
  </div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
