<?php
require_once __DIR__ . '/../config.php';
$u = require_role('guru');
$active = 'guru_exams';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM exams WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $u['id']]);
$exam = $stmt->fetch();
if (!$exam) { flash_set('Ujian tidak ditemukan.', 'error'); header('Location: ' . base_url('guru/exams.php')); exit; }

$pageTitle = 'Detail Ujian: ' . $exam['judul'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_active') {
        db()->prepare("UPDATE exams SET active = 1 - active WHERE id = ? AND user_id = ?")->execute([$id, $u['id']]);
        header('Location: ' . base_url('guru/exam_detail.php?id=' . $id));
        exit;
    } elseif ($action === 'delete') {
        // Hapus file HTML fisiknya, lalu baris DB (submissions ikut terhapus lewat ON DELETE CASCADE).
        if (!empty($exam['file_path']) && is_file($exam['file_path'])) {
            @unlink($exam['file_path']);
        }
        db()->prepare("DELETE FROM exams WHERE id = ? AND user_id = ?")->execute([$id, $u['id']]);
        flash_set('Ujian "' . $exam['judul'] . '" beserta seluruh hasil jawabannya berhasil dihapus.');
        header('Location: ' . base_url('guru/exams.php'));
        exit;
    }
}

$stmt = db()->prepare("SELECT * FROM submissions WHERE exam_id = ? ORDER BY id DESC");
$stmt->execute([$id]);
$submissions = $stmt->fetchAll();

$generatorAppUrl = rtrim(get_setting('app_domain', APP_DOMAIN), '/') . base_url('guru/generate.php?id=' . $exam['bank_id']);
// Catatan: exam_url yang tersimpan di DB dibuat saat generate dengan setting app_domain saat itu.
// Jika app_domain diubah admin setelahnya, link lama di DB tidak otomatis berubah — generate ulang jika perlu.

include __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<div class="flex justify-between items-center mb-6">
  <div>
    <h1 class="text-xl font-bold text-slate-800"><?= h($exam['judul']) ?></h1>
    <p class="text-xs text-slate-400">Digenerate: <?= h($exam['created_at']) ?> · Status:
      <span class="<?= $exam['active'] ? 'text-green-600' : 'text-red-500' ?> font-semibold"><?= $exam['active'] ? 'Aktif' : 'Nonaktif' ?></span>
    </p>
  </div>
  <div class="flex gap-2">
    <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle_active">
      <button class="text-xs font-semibold px-3 py-2 rounded-lg <?= $exam['active'] ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?>">
        <?= $exam['active'] ? 'Nonaktifkan Ujian' : 'Aktifkan Ujian' ?>
      </button>
    </form>
    <form method="post" onsubmit="return confirm('Hapus ujian ini beserta file HTML dan SELURUH hasil jawaban murid yang sudah masuk? Tindakan ini tidak bisa dibatalkan.')">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete">
      <button class="text-xs font-semibold px-3 py-2 rounded-lg bg-red-100 text-red-700 hover:bg-red-200">🗑️ Hapus Ujian</button>
    </form>
    <a href="<?= base_url('guru/exams.php') ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold px-3 py-2 rounded-lg">← Kembali</a>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
  <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
    <h2 class="font-bold text-slate-800 flex items-center gap-2">🔗 Link & QR Code Ujian (untuk Murid)</h2>
    <p class="text-xs text-slate-500 break-all bg-slate-50 border rounded-lg p-2 font-mono"><?= h($exam['exam_url']) ?></p>
    <div class="flex items-center gap-4">
      <div id="qr-exam" class="p-2 bg-white border rounded-lg"></div>
      <div class="space-y-2">
        <button onclick="copyText('<?= h($exam['exam_url']) ?>')" class="block text-xs bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg">📋 Salin Link</button>
        <button onclick="downloadQr('qr-exam','qr-ujian-<?= h($exam['slug']) ?>')" class="block text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1.5 rounded-lg">⬇️ Unduh QR (PNG)</button>
        <a href="<?= h($exam['exam_url']) ?>" target="_blank" class="block text-xs bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg text-center">🚀 Buka Ujian</a>
      </div>
    </div>
    <p class="text-[10px] text-slate-400">Tempel link/QR ini di grup kelas atau cetak untuk dibagikan ke murid. Halaman ujian akan berjalan langsung dari domain sekolah: <?= h(get_setting('app_domain', APP_DOMAIN)) ?></p>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
    <h2 class="font-bold text-slate-800 flex items-center gap-2">⚙️ Link & QR Code Generator Soal (untuk Guru)</h2>
    <p class="text-xs text-slate-500 break-all bg-slate-50 border rounded-lg p-2 font-mono"><?= h($generatorAppUrl) ?></p>
    <div class="flex items-center gap-4">
      <div id="qr-gen" class="p-2 bg-white border rounded-lg"></div>
      <div class="space-y-2">
        <button onclick="copyText('<?= h($generatorAppUrl) ?>')" class="block text-xs bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg">📋 Salin Link</button>
        <button onclick="downloadQr('qr-gen','qr-generator-<?= h($exam['slug']) ?>')" class="block text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1.5 rounded-lg">⬇️ Unduh QR (PNG)</button>
      </div>
    </div>
    <p class="text-[10px] text-slate-400">QR/link ini mengarah ke halaman generate ulang bank soal ini (butuh login guru).</p>
  </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6">
  <h2 class="font-bold text-slate-800 mb-4">Hasil Pengumpulan Jawaban (<?= count($submissions) ?>)</h2>
  <?php if (!$submissions): ?>
    <p class="text-sm text-slate-400">Belum ada murid yang mengumpulkan jawaban.</p>
  <?php else: ?>
  <div class="overflow-x-auto">
  <table class="w-full text-sm">
    <thead><tr class="text-left text-xs text-slate-400 uppercase border-b">
      <th class="py-2">Nama</th><th>NISN</th><th>Nilai</th><th>Pelanggaran</th><th>Waktu Kirim</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($submissions as $s): ?>
      <tr class="border-b border-slate-100">
        <td class="py-2 font-medium text-slate-700"><?= h($s['nama']) ?></td>
        <td class="text-slate-500"><?= h($s['nisn']) ?></td>
        <td class="font-bold text-blue-700"><?= h($s['nilai']) ?></td>
        <td><?= (int)$s['total_kecurangan'] > 0 ? '<span class="text-red-500 font-semibold">'.(int)$s['total_kecurangan'].'</span>' : '0' ?></td>
        <td class="text-slate-400 text-xs"><?= h($s['waktu_kirim']) ?></td>
        <td><button onclick='openDetail(<?= h(json_encode($s['nama'])) ?>, <?= h($s['detail_json'] ?: '{}') ?>)' class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-2 py-1 rounded">🔍 Lihat Jawaban</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<div id="modal-detail" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto space-y-3">
    <div class="flex justify-between items-center">
      <h3 class="font-bold text-slate-800">Detail Jawaban: <span id="detail-nama"></span></h3>
      <button onclick="document.getElementById('modal-detail').classList.add('hidden')" class="text-slate-400 hover:text-red-500 font-bold">✕</button>
    </div>
    <div id="detail-body" class="space-y-2 text-sm"></div>
  </div>
</div>

<script>
new QRCode(document.getElementById('qr-exam'), { text: <?= json_encode($exam['exam_url']) ?>, width: 140, height: 140 });
new QRCode(document.getElementById('qr-gen'), { text: <?= json_encode($generatorAppUrl) ?>, width: 140, height: 140 });
function copyText(t) { navigator.clipboard.writeText(t).then(()=>alert('Link disalin!')); }
function downloadQr(containerId, filename) {
  const canvas = document.querySelector('#'+containerId+' canvas');
  if (!canvas) return alert('QR belum siap.');
  const link = document.createElement('a');
  link.download = filename + '.png';
  link.href = canvas.toDataURL('image/png');
  link.click();
}
function openDetail(nama, detail) {
  document.getElementById('detail-nama').innerText = nama;
  const body = document.getElementById('detail-body');
  const keys = Object.keys(detail);
  if (!keys.length) { body.innerHTML = '<p class="text-slate-400 text-xs">Tidak ada detail jawaban tersimpan.</p>'; }
  else {
    body.innerHTML = keys.map(k => {
      const d = detail[k];
      return `<div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
        <div class="flex justify-between items-start gap-2">
          <span class="text-xs font-bold text-blue-700">${k}</span>
          <span class="text-[10px] text-slate-400 uppercase">${d.tipe||''} · ${d.poin_diperoleh||0} poin</span>
        </div>
        <p class="text-xs text-slate-600 mt-1 whitespace-pre-wrap">${(d.jawaban_siswa===''||d.jawaban_siswa==null)?'<i class=\\'text-slate-300\\'>Tidak dijawab</i>':String(d.jawaban_siswa).replace(/</g,'&lt;')}</p>
      </div>`;
    }).join('');
  }
  document.getElementById('modal-detail').classList.remove('hidden');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
