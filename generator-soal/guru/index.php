<?php
require_once __DIR__ . '/../config.php';
$u = require_role('guru');
$active = 'guru_dash';
$pageTitle = 'Bank Soal Saya';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $judul = trim($_POST['judul'] ?? '') ?: 'Bank Soal Baru';
        $mapel = trim($_POST['mapel'] ?? '');
        $stmt = db()->prepare("INSERT INTO soal_banks (user_id, judul, mapel, data_json, meta_json) VALUES (?,?,?,'[]','{}')");
        $stmt->execute([$u['id'], $judul, $mapel]);
        $newId = db()->lastInsertId();
        header('Location: ' . base_url('guru/bank_edit.php?id=' . $newId));
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM soal_banks WHERE id = ? AND user_id = ?")->execute([$id, $u['id']]);
        flash_set('Bank soal dihapus.');
    } elseif ($action === 'duplicate') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = db()->prepare("SELECT * FROM soal_banks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $u['id']]);
        $bank = $stmt->fetch();
        if ($bank) {
            db()->prepare("INSERT INTO soal_banks (user_id, judul, mapel, data_json, meta_json) VALUES (?,?,?,?,?)")
                ->execute([$u['id'], $bank['judul'] . ' (Salinan)', $bank['mapel'], $bank['data_json'], $bank['meta_json']]);
            flash_set('Bank soal berhasil diduplikasi.');
        }
    }
    header('Location: ' . base_url('guru/index.php'));
    exit;
}

$stmt = db()->prepare("SELECT b.*, (SELECT COUNT(*) FROM exams e WHERE e.bank_id = b.id) as total_exam
    FROM soal_banks b WHERE b.user_id = ? ORDER BY b.updated_at DESC");
$stmt->execute([$u['id']]);
$banks = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="flex justify-between items-center mb-6">
  <div>
    <h1 class="text-xl font-bold text-slate-800">Bank Soal Saya</h1>
    <p class="text-sm text-slate-500">Setiap bank soal tersimpan khusus di akun Anda. Generate ujian untuk membuat aplikasi ujian anti-curang siap dibagikan.</p>
  </div>
  <button onclick="document.getElementById('modal-create').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-lg text-sm whitespace-nowrap">+ Bank Soal Baru</button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach ($banks as $b): $data = json_decode($b['data_json'], true) ?: []; ?>
  <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div>
      <h3 class="font-bold text-slate-800"><?= h($b['judul']) ?></h3>
      <p class="text-xs text-slate-400"><?= h($b['mapel'] ?: '-') ?></p>
    </div>
    <div class="flex gap-3 text-xs text-slate-500">
      <span>📄 <?= count($data) ?> soal</span>
      <span>🚀 <?= (int)$b['total_exam'] ?> ujian</span>
    </div>
    <p class="text-[10px] text-slate-300">Diubah: <?= h($b['updated_at']) ?></p>
    <div class="flex gap-2 pt-2 border-t border-slate-100">
      <a href="<?= base_url('guru/bank_edit.php?id=' . $b['id']) ?>" class="flex-1 text-center bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold py-2 rounded-lg">✏️ Edit Soal</a>
      <a href="<?= base_url('guru/generate.php?id=' . $b['id']) ?>" class="flex-1 text-center bg-green-50 hover:bg-green-100 text-green-700 text-xs font-semibold py-2 rounded-lg">🚀 Generate</a>
    </div>
    <div class="flex gap-2">
      <form method="post" class="flex-1">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="duplicate">
        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
        <button class="w-full text-center bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-semibold py-1.5 rounded-lg">Duplikat</button>
      </form>
      <form method="post" class="flex-1" onsubmit="return confirm('Hapus bank soal ini beserta seluruh ujian yang pernah digenerate darinya?')">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
        <button class="w-full text-center bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold py-1.5 rounded-lg">Hapus</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$banks): ?>
  <div class="col-span-full text-center py-16 text-slate-400">
    <span class="text-3xl">📭</span>
    <p class="text-sm mt-2">Belum ada bank soal. Klik "+ Bank Soal Baru" untuk mulai membuat soal.</p>
  </div>
<?php endif; ?>
</div>

<div id="modal-create" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-sm space-y-3">
    <h3 class="font-bold text-slate-800">Bank Soal Baru</h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">
      <input type="text" name="judul" placeholder="Judul Ujian (e.g. UAS Fisika Semester 1)" required class="w-full p-2.5 border rounded-lg text-sm">
      <input type="text" name="mapel" placeholder="Mata Pelajaran" class="w-full p-2.5 border rounded-lg text-sm">
      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')" class="flex-1 bg-slate-100 hover:bg-slate-200 py-2 rounded-lg text-sm font-semibold">Batal</button>
        <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold">Buat & Edit</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
