<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/exam_template.php';
$u = require_role('guru');
$active = 'guru_dash';

$bankId = (int)($_GET['id'] ?? $_POST['bank_id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM soal_banks WHERE id = ? AND user_id = ?");
$stmt->execute([$bankId, $u['id']]);
$bank = $stmt->fetch();
if (!$bank) { flash_set('Bank soal tidak ditemukan.', 'error'); header('Location: ' . base_url('guru/index.php')); exit; }

$pageTitle = 'Generate Ujian: ' . $bank['judul'];
$soal = json_decode($bank['data_json'], true) ?: [];
$meta = json_decode($bank['meta_json'], true) ?: [];

$createdExam = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    if (empty($soal)) {
        flash_set('Bank soal ini belum memiliki soal. Tambahkan soal terlebih dahulu.', 'error');
        header('Location: ' . base_url('guru/bank_edit.php?id=' . $bankId));
        exit;
    }

    $namaGuru = trim($_POST['nama_guru'] ?? $u['name']);
    $namaSekolah = trim($_POST['nama_sekolah'] ?? get_setting('nama_sekolah', ''));
    $mapel = trim($_POST['mapel'] ?? $bank['mapel']);
    $durasi = max(1, (int)($_POST['durasi_menit'] ?? 60));
    $modeLogin = in_array($_POST['mode_login'] ?? 'manual', ['manual','dropdown','dropdown_shared','dropdown_token']) ? $_POST['mode_login'] : 'manual';
    $examToken = trim($_POST['exam_token'] ?? '');
    $maxPelanggaran = max(1, (int)($_POST['max_pelanggaran'] ?? 3));
    $acakSoal = !empty($_POST['acak_soal']);
    $acakOpsi = !empty($_POST['acak_opsi']);
    $tampilkanNilai = !empty($_POST['tampilkan_nilai']);

    $logo = '';
    if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
        $mime = mime_content_type($_FILES['logo']['tmp_name']);
        if (strpos($mime, 'image/') === 0) {
            $logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($_FILES['logo']['tmp_name']));
        }
    }

    $slug = 'ujian-' . random_slug(10);
    $secretKey = get_setting('exam_secret_key', DEFAULT_EXAM_SECRET_KEY);
    // app_domain hanya berisi protokol+host (mis. https://smp-kartini-dua.my.id atau http://localhost).
    // Sub-folder tempat aplikasi diletakkan (mis. /app) dideteksi otomatis lewat base_url().
    $domainRoot = rtrim(get_setting('app_domain', APP_DOMAIN), '/');
    $submitUrl = $domainRoot . base_url('submit.php');
    $examUrl = $domainRoot . base_url('ujian/' . $slug . '.html');

    $html = render_exam_html([
        'judul' => $bank['judul'],
        'nama_sekolah' => $namaSekolah,
        'nama_guru' => $namaGuru,
        'mapel' => $mapel,
        'durasi_menit' => $durasi,
        'logo' => $logo,
        'soal' => $soal,
        'exam_token' => $examToken,
        'mode_login' => $modeLogin,
        'daftar_kelas' => $meta['daftar_kelas'] ?? [],
        'roster_kelas' => $meta['roster_kelas'] ?? [],
        'max_pelanggaran' => $maxPelanggaran,
        'acak_soal' => $acakSoal,
        'acak_opsi' => $acakOpsi,
        'tampilkan_nilai' => $tampilkanNilai,
        'submit_url' => $submitUrl,
        'slug' => $slug,
    ]);

    if (!is_dir(UJIAN_DIR)) mkdir(UJIAN_DIR, 0775, true);
    $filePath = UJIAN_DIR . '/' . $slug . '.html';
    file_put_contents($filePath, $html);

    $settingsJson = json_encode([
        'nama_guru' => $namaGuru, 'nama_sekolah' => $namaSekolah, 'mapel' => $mapel,
        'durasi_menit' => $durasi, 'mode_login' => $modeLogin, 'max_pelanggaran' => $maxPelanggaran,
        'acak_soal' => $acakSoal, 'acak_opsi' => $acakOpsi, 'tampilkan_nilai' => $tampilkanNilai,
    ], JSON_UNESCAPED_UNICODE);

    $stmt = db()->prepare("INSERT INTO exams (bank_id, user_id, slug, judul, exam_token, secret_key, settings_json, file_path, exam_url)
        VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$bank['id'], $u['id'], $slug, $bank['judul'], $examToken, $secretKey, $settingsJson, $filePath, $examUrl]);
    $examId = db()->lastInsertId();

    header('Location: ' . base_url('guru/exam_detail.php?id=' . $examId));
    exit;
}

include __DIR__ . '/../includes/header.php';
?>
<div class="flex justify-between items-center mb-6">
  <div>
    <h1 class="text-xl font-bold text-slate-800">Generate Ujian: <?= h($bank['judul']) ?></h1>
    <p class="text-sm text-slate-500"><?= count($soal) ?> soal siap digenerate menjadi aplikasi ujian anti-curang.</p>
  </div>
  <a href="<?= base_url('guru/bank_edit.php?id=' . $bank['id']) ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold px-3 py-2 rounded-lg">← Kembali Edit Soal</a>
</div>

<?php if (empty($soal)): ?>
  <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg p-4 max-w-xl">
    Bank soal ini masih kosong. Tambahkan soal terlebih dahulu sebelum generate ujian.
  </div>
<?php else: ?>
<form method="post" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 space-y-5 max-w-2xl">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="bank_id" value="<?= (int)$bank['id'] ?>">

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Sekolah</label>
      <input type="text" name="nama_sekolah" value="<?= h(get_setting('nama_sekolah','')) ?>" class="w-full p-2.5 border rounded-lg text-sm">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Nama Guru</label>
      <input type="text" name="nama_guru" value="<?= h($u['name']) ?>" class="w-full p-2.5 border rounded-lg text-sm">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Mata Pelajaran</label>
      <input type="text" name="mapel" value="<?= h($bank['mapel']) ?>" class="w-full p-2.5 border rounded-lg text-sm">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Durasi Ujian (menit)</label>
      <input type="number" name="durasi_menit" value="60" min="1" class="w-full p-2.5 border rounded-lg text-sm">
    </div>
  </div>

  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Logo Sekolah (opsional)</label>
    <input type="file" name="logo" accept="image/*" class="text-sm">
  </div>

  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-2">Mode Login Peserta</label>
    <div class="space-y-2">
      <label class="flex items-start gap-2 p-2.5 bg-slate-50 rounded-lg border cursor-pointer">
        <input type="radio" name="mode_login" value="manual" checked class="mt-0.5">
        <span class="text-xs text-slate-700"><span class="font-semibold block">Manual (ketik nama & kelas sendiri)</span></span>
      </label>
      <label class="flex items-start gap-2 p-2.5 bg-slate-50 rounded-lg border cursor-pointer">
        <input type="radio" name="mode_login" value="dropdown" class="mt-0.5">
        <span class="text-xs text-slate-700"><span class="font-semibold block">Pilih Kelas & Nama dari Daftar (tanpa token)</span></span>
      </label>
      <label class="flex items-start gap-2 p-2.5 bg-slate-50 rounded-lg border cursor-pointer">
        <input type="radio" name="mode_login" value="dropdown_shared" class="mt-0.5">
        <span class="text-xs text-slate-700"><span class="font-semibold block">Pilih Kelas & Nama + 1 Token Bersama</span></span>
      </label>
      <label class="flex items-start gap-2 p-2.5 bg-slate-50 rounded-lg border cursor-pointer">
        <input type="radio" name="mode_login" value="dropdown_token" class="mt-0.5">
        <span class="text-xs text-slate-700"><span class="font-semibold block">Pilih Kelas & Nama + Token Mandiri per Murid (paling aman)</span></span>
      </label>
    </div>
    <p class="text-[10px] text-amber-600 mt-2">⚠️ 3 mode di atas selain Manual butuh Data Kelas & Roster (atur di halaman Edit Soal).</p>
  </div>

  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Token/Kode Ujian Bersama (opsional)</label>
    <input type="text" name="exam_token" placeholder="e.g. UJIAN-FISIKA-2026" class="w-full p-2.5 border rounded-lg text-sm">
  </div>

  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Maks. Pelanggaran Sebelum Otomatis Dikirim</label>
    <input type="number" name="max_pelanggaran" value="3" min="1" class="w-full p-2.5 border rounded-lg text-sm">
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border cursor-pointer">
      <input type="checkbox" name="acak_soal" checked class="h-4 w-4"><span class="text-xs font-medium">Acak Urutan Soal</span>
    </label>
    <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border cursor-pointer">
      <input type="checkbox" name="acak_opsi" checked class="h-4 w-4"><span class="text-xs font-medium">Acak Opsi Jawaban</span>
    </label>
    <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border cursor-pointer col-span-full">
      <input type="checkbox" name="tampilkan_nilai" checked class="h-4 w-4"><span class="text-xs font-medium">Tampilkan Nilai ke Siswa di Akhir Ujian</span>
    </label>
  </div>

  <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg text-sm shadow">🚀 Generate Aplikasi Ujian</button>
</form>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
