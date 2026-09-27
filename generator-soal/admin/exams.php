<?php
require_once __DIR__ . '/../config.php';
$u = require_role('admin');
$active = 'admin_exams';
$pageTitle = 'Semua Ujian';

$exams = db()->query("
    SELECT e.*, u.name as guru_name,
        (SELECT COUNT(*) FROM submissions s WHERE s.exam_id = e.id) as total_submit
    FROM exams e JOIN users u ON u.id = e.user_id
    ORDER BY e.id DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-1">Semua Ujian Ter-generate</h1>
<p class="text-sm text-slate-500 mb-6">Rekap ujian dari seluruh akun guru.</p>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <table class="w-full text-sm">
    <thead><tr class="text-left text-xs text-slate-400 uppercase bg-slate-50 border-b">
      <th class="py-3 px-4">Judul</th><th>Guru</th><th>Link Ujian</th><th>Peserta Kirim</th><th>Dibuat</th>
    </tr></thead>
    <tbody>
    <?php foreach ($exams as $e): ?>
      <tr class="border-b border-slate-100">
        <td class="py-3 px-4 font-medium text-slate-700"><?= h($e['judul']) ?></td>
        <td class="text-slate-500"><?= h($e['guru_name']) ?></td>
        <td><a href="<?= h($e['exam_url']) ?>" target="_blank" class="text-blue-600 text-xs hover:underline"><?= h($e['exam_url']) ?></a></td>
        <td class="text-slate-600"><?= (int)$e['total_submit'] ?></td>
        <td class="text-slate-400 text-xs"><?= h($e['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$exams): ?>
      <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada ujian yang digenerate.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
