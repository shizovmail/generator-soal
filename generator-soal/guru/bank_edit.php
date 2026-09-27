<?php
require_once __DIR__ . '/../config.php';
$u = require_role('guru');
$active = 'guru_dash';

$bankId = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM soal_banks WHERE id = ? AND user_id = ?");
$stmt->execute([$bankId, $u['id']]);
$bank = $stmt->fetch();
if (!$bank) { flash_set('Bank soal tidak ditemukan.', 'error'); header('Location: ' . base_url('guru/index.php')); exit; }

$pageTitle = 'Edit: ' . $bank['judul'];
$initialData = $bank['data_json'];
$initialMeta = $bank['meta_json'];

include __DIR__ . '/../includes/header.php';
?>
<div class="flex justify-between items-center mb-4">
  <div>
    <h1 class="text-xl font-bold text-slate-800"><?= h($bank['judul']) ?></h1>
    <p class="text-xs text-slate-400"><?= h($bank['mapel'] ?: '-') ?> · tersimpan otomatis di akun Anda</p>
  </div>
  <div class="flex gap-2">
    <span id="save-indicator" class="text-xs text-slate-400 self-center"></span>
    <a href="<?= base_url('guru/index.php') ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold px-3 py-2 rounded-lg">← Kembali</a>
    <a href="<?= base_url('guru/generate.php?id=' . $bank['id']) ?>" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-2 rounded-lg">🚀 Generate Ujian</a>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <!-- KOLOM KIRI: FORM SOAL -->
  <section class="lg:col-span-7 space-y-6">

    <!-- DATA KELAS & ROSTER -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <span class="text-lg">🏷️</span>
        <h2 class="text-lg font-bold text-slate-800">Data Kelas & Roster Murid</h2>
      </div>
      <p class="text-xs text-slate-500">Opsional. Isi jika ingin murid login dengan memilih Kelas & Nama dari daftar (bukan mengetik manual), dan/atau ingin token pribadi per murid.</p>
      <div class="flex gap-2">
        <input type="text" id="kelas-input" placeholder="e.g. VII-A" class="flex-1 rounded-lg border-slate-200 shadow-sm p-2.5 border text-sm">
        <button type="button" onclick="tambahKelas()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 rounded-lg text-sm whitespace-nowrap">+ Tambah Kelas</button>
      </div>
      <div id="daftar-kelas-list" class="flex flex-wrap gap-2">
        <p id="daftar-kelas-empty" class="text-xs text-slate-400 italic">Belum ada kelas ditambahkan.</p>
      </div>
      <div id="roster-section" class="hidden border-t border-slate-100 pt-4 space-y-3">
        <h3 class="text-sm font-bold text-slate-700">Daftar Murid per Kelas</h3>
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase">Pilih Kelas</label>
          <select id="roster-kelas-select" class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm p-2.5 border text-sm" onchange="renderRosterTable()"></select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase">Tempel Nama Murid (1 nama per baris)</label>
          <textarea id="roster-names-input" rows="5" placeholder="Ahmad Fauzi&#10;Siti Nurhaliza" class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm p-2.5 border text-sm font-mono"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <button type="button" onclick="generateRosterUntukKelas()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-lg text-sm">🔑 Generate Ulang (Ganti Token)</button>
          <button type="button" onclick="tambahMuridBaruKeKelas()" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 rounded-lg text-sm">➕ Tambah Murid (Token Lama Tetap)</button>
        </div>
        <div id="roster-table-wrap" class="hidden">
          <p class="text-xs text-slate-500 mb-2">Hasil untuk kelas <strong id="roster-table-kelas-label"></strong>:</p>
          <table class="w-full text-xs border-collapse">
            <thead><tr class="bg-slate-50 text-left">
              <th class="p-2 border border-slate-200">No</th><th class="p-2 border border-slate-200">Nama</th><th class="p-2 border border-slate-200">Token</th>
            </tr></thead>
            <tbody id="roster-table-body"></tbody>
          </table>
          <button type="button" onclick="cetakRosterKelas(document.getElementById('roster-kelas-select').value)" class="w-full bg-slate-700 hover:bg-slate-800 text-white font-bold py-2.5 rounded-lg text-sm mt-2">🖨️ Cetak/Download PDF Kelas Ini</button>
        </div>
      </div>
    </div>

    <!-- PANEL SOAL -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
      <div class="flex justify-between items-center border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2"><span class="text-lg">✍️</span><h2 class="text-lg font-bold text-slate-800">Tambah / Edit Soal</h2></div>
      </div>
      <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
        <button type="button" id="tab-pg" onclick="switchTab('pg')" class="tab-btn py-2 text-xs font-semibold rounded-lg text-center transition bg-blue-600 text-white">PG</button>
        <button type="button" id="tab-isian" onclick="switchTab('isian')" class="tab-btn py-2 text-xs font-semibold rounded-lg text-center transition bg-gray-100 text-gray-700">Isian</button>
        <button type="button" id="tab-essay" onclick="switchTab('essay')" class="tab-btn py-2 text-xs font-semibold rounded-lg text-center transition bg-gray-100 text-gray-700">Essay</button>
        <button type="button" id="tab-jodoh" onclick="switchTab('jodoh')" class="tab-btn py-2 text-xs font-semibold rounded-lg text-center transition bg-gray-100 text-gray-700">Jodoh</button>
        <button type="button" id="tab-multi" onclick="switchTab('multi')" class="tab-btn py-2 text-xs font-semibold rounded-lg text-center transition bg-gray-100 text-gray-700">Multi Jwb</button>
        <button type="button" id="tab-pernyataan" onclick="switchTab('pernyataan')" class="tab-btn py-2 text-xs font-semibold rounded-lg text-center transition bg-gray-100 text-gray-700">B/S</button>
      </div>

      <div class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase">Pertanyaan Utama</label>
          <textarea id="question-text" rows="3" placeholder="Tuliskan soal Anda di sini..." class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm p-3 border text-sm"></textarea>
        </div>
        <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
          <span class="text-sm">📷</span>
          <div class="flex-1">
            <p class="text-xs font-semibold text-slate-700">Gambar Pendukung Soal (Opsional)</p>
            <input type="file" id="question-image" accept="image/*" class="text-xs" onchange="uploadQuestionImage(this)">
          </div>
          <div id="q-img-preview-container" class="hidden flex items-center gap-2">
            <img id="q-img-preview" src="" class="h-10 w-10 object-cover rounded border">
            <button type="button" onclick="removeQuestionImage()" class="text-red-500 font-bold text-xs">✕</button>
          </div>
        </div>
      </div>

      <div class="border-t border-slate-100 pt-4">
        <!-- PG -->
        <div id="panel-pg" class="tab-content-panel space-y-4">
          <p class="text-xs font-bold text-slate-700 uppercase">Opsi Jawaban & Poin Per Opsi</p>
          <div class="space-y-3">
            <?php foreach (['a','b','c','d'] as $k): $K = strtoupper($k); ?>
            <div class="space-y-1 bg-slate-50 p-3 rounded-lg border border-slate-200">
              <div class="flex items-center gap-3">
                <span class="font-bold text-slate-700 text-sm"><?= $K ?></span>
                <input type="text" id="pg-opt-<?= $k ?>" placeholder="Opsi <?= $K ?>" class="flex-1 rounded-lg border-slate-200 p-2 border text-sm">
                <input type="number" id="pg-point-<?= $k ?>" value="0" oninput="calculateTotalPoints()" class="w-16 rounded-lg border-slate-200 p-2 border text-sm text-center font-semibold">
              </div>
              <div class="flex items-center gap-3 pl-7">
                <input type="file" id="pg-img-file-<?= $k ?>" accept="image/*" class="text-[10px]" onchange="uploadPgImage(this, '<?= $k ?>')">
                <img id="pg-img-prev-<?= $k ?>" class="h-8 w-8 object-cover rounded border hidden">
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase">Kunci Jawaban Benar</label>
              <select id="pg-correct" class="mt-1 block w-full rounded-lg border-slate-200 p-2 border text-sm">
                <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase">Total Poin</label>
              <div class="mt-1 bg-blue-50 border border-blue-200 text-blue-700 font-bold p-2 rounded-lg text-center text-sm"><span id="pg-total-points">0</span> Poin</div>
            </div>
          </div>
        </div>
        <!-- ISIAN -->
        <div id="panel-isian" class="tab-content-panel space-y-4 hidden">
          <div class="flex justify-between items-center">
            <p class="text-xs font-bold text-slate-700 uppercase">Jawaban Titik-titik + Poin</p>
            <button type="button" onclick="addIsianRow()" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded border">+ Tambah Isian</button>
          </div>
          <div id="isian-rows" class="space-y-3">
            <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
              <span class="text-xs font-bold text-slate-400 w-4 shrink-0">1</span>
              <input type="text" placeholder="Jawaban Benar" class="isian-jawaban-input flex-1 p-2 border rounded-lg text-xs">
              <input type="number" placeholder="Poin" value="10" oninput="calculateTotalPoints()" class="isian-poin-input w-16 p-2 border rounded-lg text-xs text-center font-bold">
              <button type="button" onclick="this.parentElement.remove(); renumberIsianRows(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>
            </div>
          </div>
          <div class="flex justify-between items-center border-t border-slate-100 pt-3">
            <span class="text-xs text-slate-500">Total poin isian.</span>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 font-bold px-4 py-2 rounded-lg text-sm"><span id="isian-total-display">10</span> Poin</div>
          </div>
        </div>
        <!-- ESSAY -->
        <div id="panel-essay" class="tab-content-panel space-y-4 hidden">
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase">Rambu/Pedoman Penilaian</label>
            <textarea id="essay-guide" rows="2" class="mt-1 block w-full rounded-lg border-slate-200 p-2 border text-sm"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase">Poin Maksimal</label>
              <input type="number" id="essay-point" value="20" oninput="calculateTotalPoints()" class="mt-1 block w-full rounded-lg border-slate-200 p-2 border text-sm font-semibold text-center">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase">Total Poin</label>
              <div class="mt-1 bg-blue-50 border border-blue-200 text-blue-700 font-bold p-2.5 rounded-lg text-center text-sm"><span id="essay-total-display">20</span> Poin</div>
            </div>
          </div>
        </div>
        <!-- JODOH -->
        <div id="panel-jodoh" class="tab-content-panel space-y-4 hidden">
          <div class="flex justify-between items-center">
            <p class="text-xs font-bold text-slate-700 uppercase">Pasangan Kiri & Kanan + Poin</p>
            <button type="button" onclick="addJodohRow()" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded border">+ Tambah Pasangan</button>
          </div>
          <div id="jodoh-rows" class="space-y-3">
            <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
              <input type="text" placeholder="Baris Kiri" class="jodoh-left flex-1 p-2 border rounded-lg text-xs">
              <span class="text-slate-400">➡️</span>
              <input type="text" placeholder="Pasangan Kanan" class="jodoh-right flex-1 p-2 border rounded-lg text-xs">
              <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="jodoh-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
              <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>
            </div>
          </div>
          <div class="flex justify-between items-center border-t border-slate-100 pt-3">
            <span class="text-xs text-slate-500">Total poin.</span>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 font-bold px-4 py-2 rounded-lg text-sm"><span id="jodoh-total-points">5</span> Poin</div>
          </div>
        </div>
        <!-- MULTI -->
        <div id="panel-multi" class="tab-content-panel space-y-4 hidden">
          <div class="flex justify-between items-center">
            <p class="text-xs font-bold text-slate-700 uppercase">Opsi Jawaban & Centang yang Benar</p>
            <button type="button" onclick="addMultiRow()" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded border">+ Tambah Pilihan</button>
          </div>
          <div id="multi-rows" class="space-y-3">
            <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
              <input type="checkbox" class="multi-check h-4 w-4 text-blue-600 rounded">
              <input type="text" placeholder="Teks Pilihan" class="multi-text flex-1 p-2 border rounded-lg text-xs">
              <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="multi-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
              <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>
            </div>
          </div>
          <div class="flex justify-between items-center border-t border-slate-100 pt-3">
            <span class="text-xs text-slate-500">Nilai maksimal jika semua benar dicentang.</span>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 font-bold px-4 py-2 rounded-lg text-sm"><span id="multi-total-points">5</span> Poin</div>
          </div>
        </div>
        <!-- PERNYATAAN -->
        <div id="panel-pernyataan" class="tab-content-panel space-y-4 hidden">
          <div class="flex justify-between items-center">
            <p class="text-xs font-bold text-slate-700 uppercase">Pernyataan Benar / Salah</p>
            <button type="button" onclick="addPernyataanRow()" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded border">+ Tambah Pernyataan</button>
          </div>
          <div id="pernyataan-rows" class="space-y-3">
            <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
              <input type="text" placeholder="Teks Pernyataan" class="pernyataan-text flex-1 p-2 border rounded-lg text-xs">
              <select class="pernyataan-correct p-2 border rounded-lg text-xs"><option value="Benar">Benar</option><option value="Salah">Salah</option></select>
              <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="pernyataan-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
              <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>
            </div>
          </div>
          <div class="flex justify-between items-center border-t border-slate-100 pt-3">
            <span class="text-xs text-slate-500">Poin dihitung per pernyataan.</span>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 font-bold px-4 py-2 rounded-lg text-sm"><span id="pernyataan-total-points">5</span> Poin</div>
          </div>
        </div>
      </div>

      <button type="button" id="save-question-btn" onclick="addQuestionToBank()" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-lg shadow transition">💾 Masukkan ke Bank Soal</button>
      <button type="button" id="cancel-edit-btn" onclick="cancelEditBank()" class="hidden w-full bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 rounded-lg text-sm">Batal Edit</button>
    </div>
  </section>

  <!-- KOLOM KANAN: DAFTAR BANK SOAL -->
  <section class="lg:col-span-5 space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col max-h-[80vh]">
      <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
        <div class="flex items-center gap-2"><span class="text-lg">📁</span><h2 class="text-lg font-bold text-slate-800">Daftar Soal (<span id="total-bank-count" class="text-blue-600">0</span>)</h2></div>
        <button type="button" onclick="clearBank()" class="text-xs text-red-500 hover:underline">Hapus Semua</button>
      </div>
      <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 mb-3">
        <span class="text-xs font-semibold text-blue-700">Total Skor Keseluruhan</span>
        <span class="text-sm font-extrabold text-blue-700" id="bank-total-score">0 Poin</span>
      </div>
      <div id="bank-empty-placeholder" class="text-center py-12 text-slate-400 space-y-2">
        <span class="text-3xl">📭</span><p class="text-xs">Belum ada soal.</p>
      </div>
      <div id="bank-list" class="flex-1 overflow-y-auto space-y-3"></div>
    </div>
  </section>
</div>

<script>
const BANK_ID = <?= (int)$bank['id'] ?>;
const SAVE_URL = '<?= base_url('guru/bank_api.php') ?>';
const CSRF = '<?= h(csrf_token()) ?>';

let activeType = 'pg';
let questionImageBase64 = '';
let pgImages = { a:'', b:'', c:'', d:'' };
let bankSoal = <?= $initialData ?: '[]' ?>;
let editingBankIndex = -1;
let daftarKelas = [];
let rosterKelas = {};
(function initMeta(){
  try {
    const meta = <?= $initialMeta ?: '{}' ?>;
    daftarKelas = meta.daftar_kelas || [];
    rosterKelas = meta.roster_kelas || {};
  } catch(e) {}
})();

function switchTab(type) {
  activeType = type;
  document.querySelectorAll('.tab-btn').forEach(btn => { btn.classList.remove('bg-blue-600','text-white'); btn.classList.add('bg-gray-100','text-gray-700'); });
  const activeBtn = document.getElementById(`tab-${type}`);
  if (activeBtn) { activeBtn.classList.remove('bg-gray-100','text-gray-700'); activeBtn.classList.add('bg-blue-600','text-white'); }
  document.querySelectorAll('.tab-content-panel').forEach(p => p.classList.add('hidden'));
  const panel = document.getElementById(`panel-${type}`);
  if (panel) panel.classList.remove('hidden');
  calculateTotalPoints();
}

function calculateTotalPoints() {
  let total = 0;
  if (activeType === 'pg') {
    ['a','b','c','d'].forEach(k => total += parseFloat(document.getElementById('pg-point-'+k).value) || 0);
    document.getElementById('pg-total-points').innerText = total;
  } else if (activeType === 'isian') {
    document.querySelectorAll('.isian-poin-input').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('isian-total-display').innerText = total;
  } else if (activeType === 'essay') {
    total = parseFloat(document.getElementById('essay-point').value) || 0;
    document.getElementById('essay-total-display').innerText = total;
  } else if (activeType === 'jodoh') {
    document.querySelectorAll('.jodoh-point-input').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('jodoh-total-points').innerText = total;
  } else if (activeType === 'multi') {
    document.querySelectorAll('.multi-point-input').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('multi-total-points').innerText = total;
  } else if (activeType === 'pernyataan') {
    document.querySelectorAll('.pernyataan-point-input').forEach(i => total += parseFloat(i.value) || 0);
    document.getElementById('pernyataan-total-points').innerText = total;
  }
}

function uploadQuestionImage(input) {
  const file = input.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = e => { questionImageBase64 = e.target.result; document.getElementById('q-img-preview-container').classList.remove('hidden'); document.getElementById('q-img-preview').src = questionImageBase64; };
  reader.readAsDataURL(file);
}
function removeQuestionImage() { questionImageBase64=''; document.getElementById('question-image').value=''; document.getElementById('q-img-preview-container').classList.add('hidden'); }
function uploadPgImage(input, opt) {
  const file = input.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = e => { pgImages[opt] = e.target.result; const p = document.getElementById('pg-img-prev-'+opt); p.src = pgImages[opt]; p.classList.remove('hidden'); };
  reader.readAsDataURL(file);
}

function addIsianRow() {
  const c = document.getElementById('isian-rows'); const row = document.createElement('div');
  row.className = 'flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
  row.innerHTML = `<span class="text-xs font-bold text-slate-400 w-4 shrink-0">${c.children.length+1}</span>
    <input type="text" placeholder="Jawaban Benar" class="isian-jawaban-input flex-1 p-2 border rounded-lg text-xs">
    <input type="number" placeholder="Poin" value="10" oninput="calculateTotalPoints()" class="isian-poin-input w-16 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); renumberIsianRows(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
  c.appendChild(row); calculateTotalPoints();
}
function renumberIsianRows() { document.querySelectorAll('#isian-rows > div').forEach((row,i) => { const s = row.querySelector('span'); if (s) s.innerText = i+1; }); }
function addJodohRow() {
  const c = document.getElementById('jodoh-rows'); const row = document.createElement('div');
  row.className = 'flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
  row.innerHTML = `<input type="text" placeholder="Baris Kiri" class="jodoh-left flex-1 p-2 border rounded-lg text-xs">
    <span class="text-slate-400">➡️</span>
    <input type="text" placeholder="Pasangan Kanan" class="jodoh-right flex-1 p-2 border rounded-lg text-xs">
    <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="jodoh-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
  c.appendChild(row); calculateTotalPoints();
}
function addMultiRow() {
  const c = document.getElementById('multi-rows'); const row = document.createElement('div');
  row.className = 'flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
  row.innerHTML = `<input type="checkbox" class="multi-check h-4 w-4 text-blue-600 rounded">
    <input type="text" placeholder="Teks Pilihan" class="multi-text flex-1 p-2 border rounded-lg text-xs">
    <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="multi-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
  c.appendChild(row); calculateTotalPoints();
}
function addPernyataanRow() {
  const c = document.getElementById('pernyataan-rows'); const row = document.createElement('div');
  row.className = 'flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
  row.innerHTML = `<input type="text" placeholder="Teks Pernyataan" class="pernyataan-text flex-1 p-2 border rounded-lg text-xs">
    <select class="pernyataan-correct p-2 border rounded-lg text-xs"><option value="Benar">Benar</option><option value="Salah">Salah</option></select>
    <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="pernyataan-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
  c.appendChild(row); calculateTotalPoints();
}

function resetFormOnly() {
  document.getElementById('question-text').value = '';
  questionImageBase64 = ''; document.getElementById('question-image').value='';
  document.getElementById('q-img-preview-container').classList.add('hidden');
  pgImages = { a:'', b:'', c:'', d:'' };
  ['a','b','c','d'].forEach(k => {
    document.getElementById('pg-opt-'+k).value=''; document.getElementById('pg-point-'+k).value=0;
    const p = document.getElementById('pg-img-prev-'+k); p.classList.add('hidden'); p.src='';
    document.getElementById('pg-img-file-'+k).value='';
  });
  document.getElementById('pg-correct').value='A';
  document.getElementById('isian-rows').innerHTML = `<div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
    <span class="text-xs font-bold text-slate-400 w-4 shrink-0">1</span>
    <input type="text" placeholder="Jawaban Benar" class="isian-jawaban-input flex-1 p-2 border rounded-lg text-xs">
    <input type="number" placeholder="Poin" value="10" oninput="calculateTotalPoints()" class="isian-poin-input w-16 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); renumberIsianRows(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button></div>`;
  document.getElementById('essay-guide').value=''; document.getElementById('essay-point').value=20;
  document.getElementById('jodoh-rows').innerHTML = `<div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
    <input type="text" placeholder="Baris Kiri" class="jodoh-left flex-1 p-2 border rounded-lg text-xs">
    <span class="text-slate-400">➡️</span><input type="text" placeholder="Pasangan Kanan" class="jodoh-right flex-1 p-2 border rounded-lg text-xs">
    <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="jodoh-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button></div>`;
  document.getElementById('multi-rows').innerHTML = `<div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
    <input type="checkbox" class="multi-check h-4 w-4 text-blue-600 rounded">
    <input type="text" placeholder="Teks Pilihan" class="multi-text flex-1 p-2 border rounded-lg text-xs">
    <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="multi-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button></div>`;
  document.getElementById('pernyataan-rows').innerHTML = `<div class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
    <input type="text" placeholder="Teks Pernyataan" class="pernyataan-text flex-1 p-2 border rounded-lg text-xs">
    <select class="pernyataan-correct p-2 border rounded-lg text-xs"><option value="Benar">Benar</option><option value="Salah">Salah</option></select>
    <input type="number" placeholder="Poin" value="5" oninput="calculateTotalPoints()" class="pernyataan-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
    <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button></div>`;
  calculateTotalPoints();
}

function addQuestionToBank() {
  const questionText = document.getElementById('question-text').value.trim();
  if (!questionText) return alert('Pertanyaan utama tidak boleh kosong!');
  let q = { no: bankSoal.length+1, tipe: activeType, pertanyaan: questionText, gambar_soal: questionImageBase64, total_poin: 0 };

  if (activeType === 'pg') {
    const a=document.getElementById('pg-opt-a').value.trim(), b=document.getElementById('pg-opt-b').value.trim(),
          c=document.getElementById('pg-opt-c').value.trim(), d=document.getElementById('pg-opt-d').value.trim();
    if (!a||!b||!c||!d) return alert('Isi semua opsi A, B, C, D!');
    const pa=parseFloat(document.getElementById('pg-point-a').value)||0, pb=parseFloat(document.getElementById('pg-point-b').value)||0,
          pc=parseFloat(document.getElementById('pg-point-c').value)||0, pd=parseFloat(document.getElementById('pg-point-d').value)||0;
    q.opsi = { A:{teks:a,gambar:pgImages.a,poin:pa}, B:{teks:b,gambar:pgImages.b,poin:pb}, C:{teks:c,gambar:pgImages.c,poin:pc}, D:{teks:d,gambar:pgImages.d,poin:pd} };
    q.kunci_jawaban = document.getElementById('pg-correct').value;
    q.total_poin = pa+pb+pc+pd;
  } else if (activeType === 'isian') {
    const jEls=document.querySelectorAll('.isian-jawaban-input'), pEls=document.querySelectorAll('.isian-poin-input');
    const correct=[], poin=[];
    jEls.forEach((el,i)=>{ const v=el.value.trim(); if(!v) return; correct.push(v); poin.push(parseFloat(pEls[i].value)||0); });
    if (!correct.length) return alert('Isi minimal 1 kunci jawaban isian!');
    q.kunci_jawaban = correct; q.poin_isian = poin; q.total_poin = poin.reduce((a,b)=>a+b,0);
  } else if (activeType === 'essay') {
    q.pedoman_nilai = document.getElementById('essay-guide').value.trim();
    q.total_poin = parseFloat(document.getElementById('essay-point').value)||0;
  } else if (activeType === 'jodoh') {
    const L=document.querySelectorAll('.jodoh-left'), R=document.querySelectorAll('.jodoh-right'), P=document.querySelectorAll('.jodoh-point-input');
    let pasangan=[], total=0;
    L.forEach((el,i)=>{ const l=el.value.trim(), r=R[i].value.trim(), p=parseFloat(P[i].value)||0; if(l&&r){pasangan.push({kiri:l,kanan:r,poin:p}); total+=p;} });
    if (!pasangan.length) return alert('Tambahkan minimal 1 pasangan!');
    q.pasangan = pasangan; q.total_poin = total;
  } else if (activeType === 'multi') {
    const C=document.querySelectorAll('.multi-check'), T=document.querySelectorAll('.multi-text'), P=document.querySelectorAll('.multi-point-input');
    let pilihan=[], total=0;
    T.forEach((el,i)=>{ const t=el.value.trim(), b=C[i].checked, p=parseFloat(P[i].value)||0; if(t){pilihan.push({teks:t,benar:b,poin:p}); total+=p;} });
    if (!pilihan.length) return alert('Tambahkan minimal 1 opsi!');
    q.pilihan = pilihan; q.total_poin = total;
  } else if (activeType === 'pernyataan') {
    const T=document.querySelectorAll('.pernyataan-text'), C=document.querySelectorAll('.pernyataan-correct'), P=document.querySelectorAll('.pernyataan-point-input');
    let pernyataan=[], total=0;
    T.forEach((el,i)=>{ const t=el.value.trim(), c=C[i].value, p=parseFloat(P[i].value)||0; if(t){pernyataan.push({teks:t,benar:c,poin:p}); total+=p;} });
    if (!pernyataan.length) return alert('Tambahkan minimal 1 pernyataan!');
    q.pernyataan = pernyataan; q.total_poin = total;
  }

  if (editingBankIndex >= 0 && editingBankIndex < bankSoal.length) { q.no = editingBankIndex+1; bankSoal[editingBankIndex] = q; }
  else bankSoal.push(q);
  cancelEditBank(false);
  renderBank();
  resetFormOnly();
  saveBank();
}

function editFromBank(index) {
  const q = bankSoal[index]; if (!q) return;
  switchTab(q.tipe); editingBankIndex = index;
  document.getElementById('question-text').value = q.pertanyaan || '';
  questionImageBase64 = q.gambar_soal || '';
  if (questionImageBase64) { document.getElementById('q-img-preview-container').classList.remove('hidden'); document.getElementById('q-img-preview').src = questionImageBase64; }
  else document.getElementById('q-img-preview-container').classList.add('hidden');

  if (q.tipe === 'pg') {
    ['a','b','c','d'].forEach(k => {
      const K = k.toUpperCase();
      document.getElementById('pg-opt-'+k).value = q.opsi[K].teks;
      document.getElementById('pg-point-'+k).value = q.opsi[K].poin;
      pgImages[k] = q.opsi[K].gambar || '';
      const p = document.getElementById('pg-img-prev-'+k);
      if (pgImages[k]) { p.src = pgImages[k]; p.classList.remove('hidden'); } else p.classList.add('hidden');
    });
    document.getElementById('pg-correct').value = q.kunci_jawaban;
  } else if (q.tipe === 'isian') {
    const kj = Array.isArray(q.kunci_jawaban) ? q.kunci_jawaban : [q.kunci_jawaban||''];
    const poin = Array.isArray(q.poin_isian) && q.poin_isian.length===kj.length ? q.poin_isian : kj.map(()=> (q.total_poin||0)/kj.length);
    const c = document.getElementById('isian-rows'); c.innerHTML='';
    kj.forEach((jwb,i)=>{
      const row=document.createElement('div'); row.className='flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
      row.innerHTML = `<span class="text-xs font-bold text-slate-400 w-4 shrink-0">${i+1}</span>
        <input type="text" value="${jwb}" class="isian-jawaban-input flex-1 p-2 border rounded-lg text-xs">
        <input type="number" value="${poin[i]}" oninput="calculateTotalPoints()" class="isian-poin-input w-16 p-2 border rounded-lg text-xs text-center font-bold">
        <button type="button" onclick="this.parentElement.remove(); renumberIsianRows(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
      c.appendChild(row);
    });
  } else if (q.tipe === 'essay') {
    document.getElementById('essay-guide').value = q.pedoman_nilai || '';
    document.getElementById('essay-point').value = q.total_poin || 0;
  } else if (q.tipe === 'jodoh') {
    const c = document.getElementById('jodoh-rows'); c.innerHTML='';
    (q.pasangan||[]).forEach(p => {
      const row=document.createElement('div'); row.className='flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
      row.innerHTML = `<input type="text" value="${p.kiri}" class="jodoh-left flex-1 p-2 border rounded-lg text-xs">
        <span class="text-slate-400">➡️</span><input type="text" value="${p.kanan}" class="jodoh-right flex-1 p-2 border rounded-lg text-xs">
        <input type="number" value="${p.poin||5}" oninput="calculateTotalPoints()" class="jodoh-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
        <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
      c.appendChild(row);
    });
  } else if (q.tipe === 'multi') {
    const c = document.getElementById('multi-rows'); c.innerHTML='';
    (q.pilihan||[]).forEach(p => {
      const row=document.createElement('div'); row.className='flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
      row.innerHTML = `<input type="checkbox" ${p.benar?'checked':''} class="multi-check h-4 w-4 text-blue-600 rounded">
        <input type="text" value="${p.teks}" class="multi-text flex-1 p-2 border rounded-lg text-xs">
        <input type="number" value="${p.poin||5}" oninput="calculateTotalPoints()" class="multi-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
        <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
      c.appendChild(row);
    });
  } else if (q.tipe === 'pernyataan') {
    const c = document.getElementById('pernyataan-rows'); c.innerHTML='';
    (q.pernyataan||[]).forEach(p => {
      const row=document.createElement('div'); row.className='flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200';
      row.innerHTML = `<input type="text" value="${p.teks}" class="pernyataan-text flex-1 p-2 border rounded-lg text-xs">
        <select class="pernyataan-correct p-2 border rounded-lg text-xs"><option value="Benar" ${p.benar==='Benar'?'selected':''}>Benar</option><option value="Salah" ${p.benar==='Salah'?'selected':''}>Salah</option></select>
        <input type="number" value="${p.poin||5}" oninput="calculateTotalPoints()" class="pernyataan-point-input w-12 p-2 border rounded-lg text-xs text-center font-bold">
        <button type="button" onclick="this.parentElement.remove(); calculateTotalPoints();" class="text-red-500 text-xs px-1">✕</button>`;
      c.appendChild(row);
    });
  }
  calculateTotalPoints();
  document.getElementById('save-question-btn').innerText = '💾 Simpan Perubahan Soal';
  document.getElementById('cancel-edit-btn').classList.remove('hidden');
  window.scrollTo({top:0, behavior:'smooth'});
}

function cancelEditBank(reRender = true) {
  editingBankIndex = -1;
  document.getElementById('save-question-btn').innerText = '💾 Masukkan ke Bank Soal';
  document.getElementById('cancel-edit-btn').classList.add('hidden');
  if (reRender) resetFormOnly();
}

function hapusDariBank(index) {
  if (!confirm('Hapus soal nomor ' + (index+1) + '?')) return;
  bankSoal.splice(index, 1);
  bankSoal.forEach((q,i) => q.no = i+1);
  renderBank(); saveBank();
}

function clearBank() {
  if (!bankSoal.length) return;
  if (!confirm('Hapus SEMUA soal dari bank ini?')) return;
  bankSoal = []; renderBank(); saveBank();
}

const TIPE_LABEL = { pg:'Pilihan Ganda', isian:'Isian Rumpang', essay:'Essay', jodoh:'Menjodohkan', multi:'Multi Jawaban', pernyataan:'Benar/Salah' };

function renderBank() {
  const list = document.getElementById('bank-list');
  const empty = document.getElementById('bank-empty-placeholder');
  document.getElementById('total-bank-count').innerText = bankSoal.length;
  document.getElementById('bank-total-score').innerText = bankSoal.reduce((a,q)=>a+(q.total_poin||0),0) + ' Poin';
  if (!bankSoal.length) { list.innerHTML=''; empty.classList.remove('hidden'); return; }
  empty.classList.add('hidden');
  list.innerHTML = bankSoal.map((q,i) => `
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 space-y-1">
      <div class="flex justify-between items-start gap-2">
        <span class="text-[10px] font-bold uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">${TIPE_LABEL[q.tipe]||q.tipe}</span>
        <span class="text-[10px] text-slate-400">${q.total_poin||0} poin</span>
      </div>
      <p class="text-xs text-slate-700 font-medium line-clamp-2">${i+1}. ${(q.pertanyaan||'').replace(/</g,'&lt;')}</p>
      <div class="flex gap-2 pt-1">
        <button onclick="editFromBank(${i})" class="text-[10px] bg-white border border-slate-200 hover:bg-slate-100 px-2 py-1 rounded">✏️ Edit</button>
        <button onclick="hapusDariBank(${i})" class="text-[10px] bg-white border border-red-200 text-red-500 hover:bg-red-50 px-2 py-1 rounded">🗑️ Hapus</button>
      </div>
    </div>
  `).join('');
}

// --- Kelas & Roster ---
function generateRandomToken(length=6) {
  const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; let t='';
  for (let i=0;i<length;i++) t += charset.charAt(Math.floor(Math.random()*charset.length));
  return t;
}
function tambahKelas() {
  const input = document.getElementById('kelas-input'); const nama = input.value.trim();
  if (!nama) return alert('Nama kelas tidak boleh kosong!');
  if (daftarKelas.includes(nama)) return alert("Kelas '"+nama+"' sudah ada!");
  daftarKelas.push(nama); input.value=''; input.focus(); renderDaftarKelas(); saveBank();
}
function hapusKelas(index) {
  const nama = daftarKelas[index]; delete rosterKelas[nama]; daftarKelas.splice(index,1);
  document.getElementById('roster-table-wrap').classList.add('hidden');
  renderDaftarKelas(); saveBank();
}
function renderDaftarKelas() {
  const list = document.getElementById('daftar-kelas-list'); const empty = document.getElementById('daftar-kelas-empty');
  const rosterSection = document.getElementById('roster-section');
  list.innerHTML = '';
  if (!daftarKelas.length) { list.appendChild(empty); rosterSection.classList.add('hidden'); return; }
  daftarKelas.forEach((kelas,index) => {
    const badge = document.createElement('span');
    badge.className = 'flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-full text-sm font-medium';
    badge.innerHTML = kelas + ' <button type="button" class="text-blue-400 hover:text-red-500 font-bold">✕</button>';
    badge.querySelector('button').onclick = () => hapusKelas(index);
    list.appendChild(badge);
  });
  rosterSection.classList.remove('hidden');
  renderRosterKelasDropdown();
}
function renderRosterKelasDropdown() {
  const select = document.getElementById('roster-kelas-select'); const cur = select.value;
  select.innerHTML = daftarKelas.map(k => `<option value="${k}">${k}</option>`).join('');
  if (daftarKelas.includes(cur)) select.value = cur;
  renderRosterTable();
}
function generateRosterUntukKelas() {
  const kelas = document.getElementById('roster-kelas-select').value;
  if (!kelas) return alert('Pilih kelas dahulu!');
  const raw = document.getElementById('roster-names-input').value;
  const namaList = raw.split('\n').map(n=>n.trim()).filter(n=>n.length>0);
  if (!namaList.length) return alert('Tempel minimal 1 nama murid!');
  if (rosterKelas[kelas] && rosterKelas[kelas].length) {
    if (!confirm("Kelas '"+kelas+"' sudah punya "+rosterKelas[kelas].length+" murid. Generate ulang akan MENGGANTI semua token lama. Lanjutkan?")) return;
  }
  rosterKelas[kelas] = namaList.map(n => ({ nama:n, token: generateRandomToken() }));
  document.getElementById('roster-names-input').value=''; renderRosterTable(); saveBank();
}
function tambahMuridBaruKeKelas() {
  const kelas = document.getElementById('roster-kelas-select').value;
  if (!kelas) return alert('Pilih kelas dahulu!');
  const raw = document.getElementById('roster-names-input').value;
  const baru = raw.split('\n').map(n=>n.trim()).filter(n=>n.length>0);
  if (!baru.length) return alert('Tempel minimal 1 nama!');
  if (!rosterKelas[kelas]) rosterKelas[kelas] = [];
  const sudahAda = rosterKelas[kelas].map(m=>m.nama.toLowerCase());
  const dup = baru.filter(n=>sudahAda.includes(n.toLowerCase()));
  const tambah = baru.filter(n=>!sudahAda.includes(n.toLowerCase()));
  if (dup.length && !confirm('Nama berikut sudah ada dan akan dilewati:\n'+dup.join('\n')+'\n\nLanjutkan menambah '+tambah.length+' nama baru?')) return;
  if (!tambah.length) return alert('Semua nama sudah terdaftar.');
  rosterKelas[kelas] = rosterKelas[kelas].concat(tambah.map(n=>({nama:n, token:generateRandomToken()})));
  document.getElementById('roster-names-input').value=''; renderRosterTable(); saveBank();
  alert(tambah.length + ' murid baru ditambahkan.');
}
function renderRosterTable() {
  const kelas = document.getElementById('roster-kelas-select').value;
  const wrap = document.getElementById('roster-table-wrap'); const body = document.getElementById('roster-table-body');
  const label = document.getElementById('roster-table-kelas-label');
  const murid = rosterKelas[kelas] || [];
  if (!kelas || !murid.length) { wrap.classList.add('hidden'); return; }
  label.innerText = kelas;
  body.innerHTML = murid.map((m,i)=>`<tr><td class="p-2 border border-slate-200">${i+1}</td><td class="p-2 border border-slate-200">${m.nama}</td><td class="p-2 border border-slate-200 font-mono font-bold">${m.token}</td></tr>`).join('');
  wrap.classList.remove('hidden');
}
function buildRosterPrintHTML(kelasList) {
  const school = '<?= h(get_setting('nama_sekolah','Sekolah')) ?>';
  const subject = '<?= h($bank['mapel'] ?: $bank['judul']) ?>';
  const sections = kelasList.map((kelas,idx) => {
    const murid = rosterKelas[kelas] || [];
    const rows = murid.map((m,i)=>`<tr><td>${i+1}</td><td>${m.nama}</td><td class="token">${m.token}</td></tr>`).join('');
    const brk = idx < kelasList.length-1 ? 'page-break-after: always;' : '';
    return `<div style="${brk}"><h1>${school}</h1><h2>Daftar Token Ujian &mdash; ${subject}</h2><h3>Kelas: ${kelas}</h3>
      <table><thead><tr><th>No</th><th>Nama</th><th>Token</th></tr></thead><tbody>${rows}</tbody></table></div>`;
  }).join('');
  return `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Cetak Token</title><style>
    body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#1e293b}
    h1{font-size:16px;margin:0}h2{font-size:12px;margin:2px 0 0;color:#475569;font-weight:normal}
    h3{font-size:14px;margin:14px 0 8px}table{width:100%;border-collapse:collapse;margin-bottom:10px}
    th,td{border:1px solid #94a3b8;padding:6px 10px;text-align:left;font-size:12px}th{background:#f1f5f9}
    .token{font-family:'Courier New',monospace;font-weight:bold;letter-spacing:1px}@page{size:A4;margin:15mm}
    </style></head><body>${sections}</body></html>`;
}
function bukaJendelaCetak(html) {
  const win = window.open('', '_blank'); if (!win) return alert('Popup diblokir browser.');
  win.document.open(); win.document.write(html); win.document.close();
  win.onload = () => { win.focus(); win.print(); };
}
function cetakRosterKelas(kelas) {
  if (!kelas) return alert('Pilih kelas dahulu!');
  if (!(rosterKelas[kelas]||[]).length) return alert('Kelas ini belum punya token. Generate dulu.');
  bukaJendelaCetak(buildRosterPrintHTML([kelas]));
}

// --- Simpan otomatis ke server ---
let saveTimer = null;
function saveBank() {
  const indicator = document.getElementById('save-indicator');
  indicator.innerText = 'Menyimpan...';
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => {
    fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({
        id: BANK_ID,
        data: bankSoal,
        meta: { daftar_kelas: daftarKelas, roster_kelas: rosterKelas }
      })
    }).then(r => r.json()).then(res => {
      indicator.innerText = res.ok ? ('Tersimpan ' + new Date().toLocaleTimeString('id-ID')) : 'Gagal menyimpan!';
    }).catch(() => { indicator.innerText = 'Gagal menyimpan (offline?)'; });
  }, 400);
}

renderBank();
renderDaftarKelas();
switchTab('pg');
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
