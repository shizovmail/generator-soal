<?php
require_once __DIR__ . '/../config.php';
$u = require_role('admin');
$active = 'admin_dash';
$pageTitle = 'Dashboard Admin';

$totalGuru = db()->query("SELECT COUNT(*) c FROM users WHERE role='guru'")->fetch()['c'];
$totalBank = db()->query("SELECT COUNT(*) c FROM soal_banks")->fetch()['c'];
$totalExam = db()->query("SELECT COUNT(*) c FROM exams")->fetch()['c'];
$totalSubmission = db()->query("SELECT COUNT(*) c FROM submissions")->fetch()['c'];

$recentExams = db()->query("
    SELECT e.*, u.name as guru_name FROM exams e
    JOIN users u ON u.id = e.user_id
    ORDER BY e.id DESC LIMIT 8
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-1">Dashboard Admin</h1>
<p class="text-sm text-slate-500 mb-6">Ringkasan penggunaan aplikasi generator soal.</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <p class="text-xs text-slate-500">Akun Guru</p>
    <p class="text-2xl font-extrabold text-slate-800"><?= (int)$totalGuru ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <p class="text-xs text-slate-500">Bank Soal</p>
    <p class="text-2xl font-extrabold text-slate-800"><?= (int)$totalBank ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <p class="text-xs text-slate-500">Ujian Digenerate</p>
    <p class="text-2xl font-extrabold text-slate-800"><?= (int)$totalExam ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <p class="text-xs text-slate-500">Total Pengumpulan Jawaban</p>
    <p class="text-2xl font-extrabold text-slate-800"><?= (int)$totalSubmission ?></p>
  </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6">
  <h2 class="font-bold text-slate-800 mb-4">Ujian Terbaru</h2>
  <?php if (!$recentExams): ?>
    <p class="text-sm text-slate-400">Belum ada ujian yang digenerate.</p>
  <?php else: ?>
  <table class="w-full text-sm">
    <thead><tr class="text-left text-xs text-slate-400 uppercase border-b">
      <th class="py-2">Judul</th><th>Guru</th><th>Slug</th><th>Dibuat</th>
    </tr></thead>
    <tbody>
    <?php foreach ($recentExams as $e): ?>
      <tr class="border-b border-slate-100">
        <td class="py-2 font-medium text-slate-700"><?= h($e['judul']) ?></td>
        <td class="text-slate-500"><?= h($e['guru_name']) ?></td>
        <td class="text-slate-400 font-mono text-xs"><?= h($e['slug']) ?></td>
        <td class="text-slate-400 text-xs"><?= h($e['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
