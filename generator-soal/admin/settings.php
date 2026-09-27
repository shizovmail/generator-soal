<?php
require_once __DIR__ . '/../config.php';
$u = require_role('admin');
$active = 'admin_settings';
$pageTitle = 'Pengaturan Aplikasi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    set_setting('nama_sekolah', trim($_POST['nama_sekolah'] ?? ''));
    set_setting('app_domain', rtrim(trim($_POST['app_domain'] ?? ''), '/'));
    $newKey = trim($_POST['exam_secret_key'] ?? '');
    if ($newKey !== '') set_setting('exam_secret_key', $newKey);
    flash_set('Pengaturan berhasil disimpan.');
    header('Location: ' . base_url('admin/settings.php'));
    exit;
}

$namaSekolah = get_setting('nama_sekolah', '');
$appDomain = get_setting('app_domain', APP_DOMAIN);
$examKey = get_setting('exam_secret_key', DEFAULT_EXAM_SECRET_KEY);

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-1">Pengaturan Aplikasi</h1>
<p class="text-sm text-slate-500 mb-6">Pengaturan ini berlaku untuk seluruh aplikasi (semua akun guru).</p>

<form method="post" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Sekolah</label>
    <input type="text" name="nama_sekolah" value="<?= h($namaSekolah) ?>" class="w-full p-2.5 border rounded-lg text-sm">
  </div>
  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Domain Aplikasi (untuk link ujian & QR code)</label>
    <input type="text" name="app_domain" value="<?= h($appDomain) ?>" placeholder="https://smp-kartini-dua.my.id" class="w-full p-2.5 border rounded-lg text-sm">
    <p class="text-[10px] text-slate-400 mt-1">Isi HANYA protokol + host, <strong>tanpa</strong> nama folder tempat aplikasi diletakkan — folder itu terdeteksi otomatis.
      Contoh benar: <code class="bg-slate-100 px-1 rounded">https://smp-kartini-dua.my.id</code> (aplikasi di root htdocs) atau
      <code class="bg-slate-100 px-1 rounded">http://localhost</code> (aplikasi di <code class="bg-slate-100 px-1 rounded">htdocs/app</code> — jangan tulis <code class="bg-slate-100 px-1 rounded">http://localhost/app</code>).</p>
    <p class="text-[10px] text-amber-600 mt-1">⚠️ Mengubah ini hanya berlaku untuk ujian yang di-generate SETELAH disimpan. Ujian yang sudah pernah digenerate perlu di-generate ulang agar link/QR-nya ikut berubah.</p>
  </div>
  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Kunci Enkripsi Ujian (EXAM_SECRET_KEY)</label>
    <input type="text" name="exam_secret_key" placeholder="<?= h($examKey) ?>" class="w-full p-2.5 border rounded-lg text-sm font-mono">
    <p class="text-[10px] text-amber-600 mt-1">⚠️ Mengubah kunci ini TIDAK akan mempengaruhi file ujian yang sudah pernah digenerate (kuncinya sudah tertanam di file HTML masing-masing). Hanya berlaku untuk generate baru setelah ini.</p>
  </div>
  <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-lg text-sm">Simpan Pengaturan</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
