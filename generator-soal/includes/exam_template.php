<?php
/**
 * Menghasilkan HTML statis aplikasi ujian anti-curang (mirip Examkuro),
 * ditanami langsung dengan data soal + pengaturan dari 1 bank soal + 1 sesi generate.
 *
 * $cfg keys:
 *  judul, nama_sekolah, nama_guru, mapel, durasi_menit, logo (base64|''),
 *  soal (array soal), exam_token, mode_login, daftar_kelas, roster_kelas,
 *  max_pelanggaran, acak_soal, acak_opsi, tampilkan_nilai, submit_url, slug
 */
function render_exam_html(array $cfg): string {
    $judul = h($cfg['judul']);
    $namaSekolah = h($cfg['nama_sekolah']);
    $namaGuru = h($cfg['nama_guru']);
    $mapel = h($cfg['mapel']);
    $durasi = (int)$cfg['durasi_menit'];
    $logo = $cfg['logo'] ?? '';
    $soalJson = json_encode($cfg['soal'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $examToken = json_encode($cfg['exam_token'] ?? '');
    $modeLogin = json_encode($cfg['mode_login'] ?? 'manual');
    $daftarKelas = json_encode($cfg['daftar_kelas'] ?? []);
    $rosterKelas = json_encode($cfg['roster_kelas'] ?? [], JSON_UNESCAPED_UNICODE);
    $maxPelanggaran = (int)($cfg['max_pelanggaran'] ?? 3);
    $acakSoal = !empty($cfg['acak_soal']) ? 'true' : 'false';
    $acakOpsi = !empty($cfg['acak_opsi']) ? 'true' : 'false';
    $tampilkanNilai = !empty($cfg['tampilkan_nilai']) ? 'true' : 'false';
    $submitUrl = h($cfg['submit_url']);
    $slug = h($cfg['slug']);
    $logoHtml = $logo
        ? '<img id="kop-logo" src="' . h($logo) . '" class="h-14 w-14 object-contain rounded-lg bg-white/10 p-1">'
        : '<span id="kop-logo" class="text-4xl">🏫</span>';

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="robots" content="noindex, nofollow">
<title>{$judul} — Ujian Aman</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/crypto-js@4.2.0/crypto-js.min.js"></script>
<style>
  body{user-select:none;-webkit-user-select:none;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(180deg,#eef2ff 0%,#f8fafc 30%)}
  ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
  img{-webkit-user-drag:none;user-drag:none}
  .fade-in{animation:fadeIn .25s ease-out}
  @keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body class="min-h-screen p-4">

<div id="devtools-block" class="hidden fixed inset-0 bg-red-900 text-white z-[9999] flex items-center justify-center p-6 text-center">
  <div>
    <p class="text-2xl font-bold mb-2">⛔ Developer Tools Terdeteksi</p>
    <p class="text-sm">Tutup Developer Tools / Inspect Element untuk melanjutkan ujian. Aktivitas ini dicatat sebagai pelanggaran.</p>
  </div>
</div>

<div id="fs-warning-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-lg p-6 max-w-sm w-full text-center space-y-3 fade-in">
    <span class="text-4xl">⚠️</span>
    <h3 class="text-base font-bold text-slate-800">Anda Keluar dari Mode Layar Penuh!</h3>
    <p class="text-xs text-slate-500">Klik tombol di bawah untuk kembali ke ujian. Jika diabaikan dalam <span id="fs-warning-countdown">10</span> detik, ini akan dihitung sebagai pelanggaran.</p>
    <button onclick="returnToFullscreenExam()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-sm">Kembali ke Mode Ujian</button>
  </div>
</div>

<div id="submit-confirm-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-lg p-6 max-w-sm w-full space-y-3 fade-in">
    <div class="text-center space-y-1"><span class="text-4xl">📤</span><h3 class="text-base font-bold text-slate-800">Kirim Jawaban Ujian?</h3></div>
    <p id="submit-confirm-info" class="text-xs text-slate-600 text-center"></p>
    <label class="flex items-start gap-2 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-600 cursor-pointer">
      <input type="checkbox" id="submit-confirm-checkbox" onchange="toggleSubmitConfirmButton()" class="mt-0.5">
      <span>Saya yakin dan sudah memeriksa jawaban saya, kirim sekarang.</span>
    </label>
    <div class="flex gap-3">
      <button onclick="closeSubmitConfirmModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2.5 rounded-lg text-sm">Batal</button>
      <button id="submit-confirm-ok-btn" onclick="doSubmitExam()" disabled class="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-2.5 rounded-lg text-sm">Ya, Kirim</button>
    </div>
  </div>
</div>

<div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
  <div class="bg-gradient-to-r from-blue-700 to-indigo-700 text-white p-5 flex items-center gap-4">
    {$logoHtml}
    <div>
      <h1 class="text-lg font-bold">{$namaSekolah}</h1>
      <p class="text-xs text-blue-100">Mata Pelajaran: <strong>{$mapel}</strong> — {$judul}</p>
      <p class="text-[10px] text-blue-200">Guru: {$namaGuru} | Durasi: {$durasi} Menit</p>
    </div>
  </div>

  <div class="p-6 space-y-6">
    <div id="reg-area" class="space-y-4">
      <h2 class="text-sm font-bold text-slate-700 uppercase">Registrasi Peserta Ujian</h2>
      <div id="reg-fields"></div>
      <p class="text-[10px] text-red-500">Pastikan koneksi internet stabil & baterai perangkat mencukupi. Ujian berjalan dalam mode layar penuh dan terkunci, dengan sistem deteksi kecurangan aktif.</p>
      <button onclick="startExam()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-sm transition">Mulai Ujian (Masuk Mode Aman)</button>
    </div>

    <div id="exam-area" class="hidden space-y-6">
      <div class="flex justify-between items-center bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs text-slate-600">
        <span>👤 <strong id="display-student-name">-</strong></span>
        <span id="display-student-id" class="text-slate-400"></span>
      </div>
      <div class="flex justify-between items-center bg-blue-50 p-3 rounded-lg border border-blue-200 text-xs text-blue-700">
        <span class="font-semibold">⏱️ Sisa Waktu</span>
        <span class="font-mono font-bold text-sm" id="timer-display">--:--</span>
      </div>
      <div class="flex justify-between items-center bg-red-50 p-3 rounded-lg border border-red-200 text-xs text-red-700">
        <span id="kop-max-violations" class="font-semibold">⚠️ Fitur Anti-Curang Aktif!</span>
        <span class="font-mono" id="cheat-indicator">Pelanggaran: 0</span>
      </div>
      <div id="question-nav" class="flex flex-wrap gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200"></div>
      <div id="questions-container" class="min-h-[220px]"></div>
      <div class="flex justify-between items-center gap-3">
        <button id="btn-prev-q" onclick="goToQuestion(currentQIndex - 1)" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2.5 rounded-lg text-sm">← Kembali</button>
        <button id="btn-next-q" onclick="goToQuestion(currentQIndex + 1)" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-sm">Lanjut →</button>
      </div>
      <button id="btn-submit-exam" onclick="confirmSubmitExam()" class="hidden w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg text-sm">Kirim Lembar Ujian & Selesai</button>
    </div>

    <div id="finish-area" class="hidden text-center py-10 space-y-4">
      <span class="text-5xl">✅</span>
      <h2 class="text-lg font-bold text-slate-800">Ujian Telah Selesai Dikumpulkan</h2>
      <p id="finish-message" class="text-sm text-slate-500">Terima kasih, jawaban Anda sudah tersimpan.</p>
      <div id="finish-score-box" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 inline-block">
        <p class="text-xs text-blue-600 font-semibold uppercase">Nilai Anda</p>
        <p id="finish-score-value" class="text-3xl font-extrabold text-blue-700">-</p>
      </div>
      <p class="text-[10px] text-slate-400">Anda dapat menutup halaman ini sekarang.</p>
    </div>
  </div>
</div>
<footer class="text-center text-[10px] text-slate-300 py-4">Dibuat dengan Generator Soal Ujian Anti-Curang &mdash; {$namaSekolah}</footer>

<script>
const EXAM_SLUG = "{$slug}";
const SUBMIT_URL = "{$submitUrl}";
let originalSoal = {$soalJson};
let activeSoal = [];
let cheatCount = 0;
let currentQIndex = 0;
let jawabanTersimpan = [];
let lastViolationTime = 0;
let suppressCheckUntil = 0;
let examActive = false;
let examSubmitted = false;
const examToken = {$examToken};
const loginMode = {$modeLogin};
const dataKelasMuridRaw = {$daftarKelas};
const rosterKelasRaw = {$rosterKelas};
let dataKelasMurid = dataKelasMuridRaw.map(k => ({ kelas: k, murid: rosterKelasRaw[k] || [] }));
const MAX_VIOLATIONS = {$maxPelanggaran};
const DURATION_SECONDS = {$durasi} * 60;
let secondsLeft = DURATION_SECONDS;
let timerInterval = null;
const TAMPILKAN_NILAI = {$tampilkanNilai};
const EXAM_BACKUP_KEY = "generator_soal_backup_" + EXAM_SLUG;

function computeSignature(str) {
  let hash = 0;
  for (let i = 0; i < str.length; i++) { hash = ((hash << 5) - hash) + str.charCodeAt(i); hash |= 0; }
  return Math.abs(hash).toString(16);
}
function shuffleArray(array) {
  for (let i = array.length - 1; i > 0; i--) { const j = Math.floor(Math.random()*(i+1)); [array[i],array[j]]=[array[j],array[i]]; }
}
function acakSoalDanOpsi(acakSoal, acakOpsi) {
  activeSoal = JSON.parse(JSON.stringify(originalSoal));
  if (acakSoal) shuffleArray(activeSoal);
  activeSoal.forEach(q => {
    if (!acakOpsi) return;
    if (q.tipe === 'pg') { let keys=['A','B','C','D']; shuffleArray(keys); let n={}; keys.forEach(k=>n[k]=q.opsi[k]); q.opsi_shuffled=n; }
    else if (q.tipe === 'multi') { q.pilihan_shuffled=[...q.pilihan]; shuffleArray(q.pilihan_shuffled); }
    else if (q.tipe === 'pernyataan') { q.pernyataan_shuffled=[...q.pernyataan]; shuffleArray(q.pernyataan_shuffled); }
    else if (q.tipe === 'jodoh') { q.pasangan_shuffled=[...q.pasangan]; shuffleArray(q.pasangan_shuffled); q.kanan_shuffled=q.pasangan.map(p=>p.kanan); shuffleArray(q.kanan_shuffled); }
  });
  jawabanTersimpan = activeSoal.map(() => null);
}
acakSoalDanOpsi({$acakSoal}, {$acakOpsi});

// --- Anti-cheat: kunci interaksi browser dasar ---
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('dragstart', e => e.preventDefault());
document.addEventListener('keydown', e => {
  if (e.ctrlKey && ['c','v','u','i','p','s','a'].includes(e.key.toLowerCase())) { e.preventDefault(); flagViolation('Kombinasi tombol terlarang (Ctrl+' + e.key.toUpperCase() + ')'); }
  if (e.key === 'F12') { e.preventDefault(); flagViolation('Mencoba membuka Developer Tools (F12)'); }
  if (e.key === 'PrintScreen') { flagViolation('Mencoba Screenshot (Print Screen)'); }
});

// --- Anti-cheat: deteksi kasar Developer Tools terbuka (perbandingan ukuran window) ---
let devtoolsOpenState = false;
setInterval(() => {
  const threshold = 160;
  const widthDiff = window.outerWidth - window.innerWidth;
  const heightDiff = window.outerHeight - window.innerHeight;
  const suspect = widthDiff > threshold || heightDiff > threshold;
  const block = document.getElementById('devtools-block');
  if (suspect && examActive) {
    if (block) block.classList.remove('hidden');
    if (!devtoolsOpenState) { devtoolsOpenState = true; flagViolation('Developer Tools Terbuka'); }
  } else {
    if (block) block.classList.add('hidden');
    devtoolsOpenState = false;
  }
}, 1500);

function renderRegFields() {
  let html = '';
  if (loginMode === 'manual') {
    html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="text" id="student-name" placeholder="Nama Lengkap Siswa" class="p-2 border rounded-lg text-sm">
      <input type="text" id="student-id" placeholder="NISN / No Peserta" class="p-2 border rounded-lg text-sm"></div>`;
  } else {
    html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Kelas</label>
        <select id="student-kelas" class="w-full p-2 border rounded-lg text-sm" onchange="renderNamaOptions()"><option value="">-- Pilih Kelas --</option></select></div>
      <div><label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Nama</label>
        <select id="student-name-select" class="w-full p-2 border rounded-lg text-sm"><option value="">-- Pilih Kelas Dulu --</option></select></div>
      </div>
      <input type="text" id="student-id" placeholder="NISN / No Peserta (opsional)" class="w-full p-2 border rounded-lg text-sm">
      <input type="hidden" id="student-name">`;
  }
  if (loginMode === 'dropdown_token') {
    html += `<input type="text" id="student-token" placeholder="Masukkan Token Pribadi Anda dari Guru" class="w-full p-2 border rounded-lg text-sm">`;
  } else if (examToken) {
    html += `<input type="text" id="student-token" placeholder="Masukkan Kode/Token Ujian dari Guru" class="w-full p-2 border rounded-lg text-sm">`;
  }
  document.getElementById('reg-fields').innerHTML = html;
  if (loginMode !== 'manual') initRegistrasiDropdown();
}
function initRegistrasiDropdown() {
  const sel = document.getElementById('student-kelas'); if (!sel) return;
  dataKelasMurid.forEach(k => { if (!k.murid || !k.murid.length) return; const o=document.createElement('option'); o.value=k.kelas; o.textContent=k.kelas; sel.appendChild(o); });
}
function renderNamaOptions() {
  const kelasSel=document.getElementById('student-kelas'), namaSel=document.getElementById('student-name-select');
  if (!kelasSel||!namaSel) return;
  const kelas=kelasSel.value; namaSel.innerHTML='<option value="">-- Pilih Nama --</option>'; if (!kelas) return;
  const kd = dataKelasMurid.find(k=>k.kelas===kelas);
  (kd?kd.murid:[]).forEach(m=>{ const o=document.createElement('option'); o.value=m.nama; o.textContent=m.nama; namaSel.appendChild(o); });
}
renderRegFields();

function startExam() {
  let name, id;
  if (loginMode === 'manual') {
    name = document.getElementById('student-name').value.trim();
    id = document.getElementById('student-id').value.trim();
    if (!name || !id) return alert('Lengkapi data registrasi!');
    if (examToken) { const t=(document.getElementById('student-token')?.value||'').trim(); if (t!==examToken) return alert('Kode/Token Ujian salah.'); }
  } else {
    const kelas = document.getElementById('student-kelas').value;
    const namaTerpilih = document.getElementById('student-name-select').value;
    id = document.getElementById('student-id').value.trim();
    if (!kelas || !namaTerpilih) return alert('Pilih kelas dan nama Anda!');
    if (loginMode === 'dropdown_token') {
      const kd = dataKelasMurid.find(k=>k.kelas===kelas);
      const murid = kd ? kd.murid.find(m=>m.nama===namaTerpilih) : null;
      const t = (document.getElementById('student-token')?.value||'').trim();
      if (!murid || !murid.token || t!==murid.token) return alert('Token ujian salah/tidak sesuai.');
    } else if (loginMode === 'dropdown_shared') {
      const t=(document.getElementById('student-token')?.value||'').trim();
      if (!examToken || t!==examToken) return alert('Kode/Token Ujian salah.');
    }
    name = kelas + ' - ' + namaTerpilih;
    document.getElementById('student-name').value = name;
  }
  document.getElementById('display-student-name').innerText = (loginMode==='manual') ? name : (name.split(' - ').slice(1).join(' - ') || name);
  document.getElementById('display-student-id').innerText = (loginMode==='manual' ? (id?'No/NISN: '+id:'') : (name.split(' - ')[0] + (id?' • No: '+id:'')));

  examActive = true;
  document.getElementById('reg-area').classList.add('hidden');
  document.getElementById('exam-area').classList.remove('hidden');
  let elem = document.documentElement;
  if (elem.requestFullscreen) elem.requestFullscreen().catch(()=>{});
  renderExamQuestions();
  startTimer();
}

function startTimer() {
  updateTimerDisplay();
  timerInterval = setInterval(() => {
    secondsLeft--; updateTimerDisplay();
    if (secondsLeft <= 0) { clearInterval(timerInterval); alert('Waktu ujian telah habis. Jawaban dikirim otomatis.'); submitExam(); }
  }, 1000);
}
function updateTimerDisplay() {
  const m=Math.max(0,Math.floor(secondsLeft/60)).toString().padStart(2,'0');
  const s=Math.max(0,secondsLeft%60).toString().padStart(2,'0');
  const el=document.getElementById('timer-display'); if (el) el.innerText=m+':'+s;
}

const FS_GRACE_SECONDS = 10;
let fsWarningActive = false, fsWarningInterval=null, fsWarningTimeout=null;
document.addEventListener('fullscreenchange', () => {
  if (Date.now() < suppressCheckUntil) return;
  if (!document.fullscreenElement && examActive) showFsWarning();
  else if (document.fullscreenElement && fsWarningActive) clearFsWarning();
});
function showFsWarning() {
  if (fsWarningActive) return;
  fsWarningActive = true; let remaining = FS_GRACE_SECONDS;
  const modal=document.getElementById('fs-warning-modal'), cd=document.getElementById('fs-warning-countdown');
  if (modal) modal.classList.remove('hidden'); if (cd) cd.innerText=remaining;
  fsWarningInterval = setInterval(()=>{ remaining--; if (cd) cd.innerText=Math.max(0,remaining); }, 1000);
  fsWarningTimeout = setTimeout(()=>{ if (fsWarningActive) { clearFsWarning(); flagViolation('Keluar Fullscreen dan tidak kembali dalam ' + FS_GRACE_SECONDS + ' detik!'); } }, FS_GRACE_SECONDS*1000);
}
function clearFsWarning() {
  fsWarningActive=false; clearInterval(fsWarningInterval); clearTimeout(fsWarningTimeout);
  const modal=document.getElementById('fs-warning-modal'); if (modal) modal.classList.add('hidden');
}
function returnToFullscreenExam() { clearFsWarning(); let elem=document.documentElement; if (elem.requestFullscreen) elem.requestFullscreen().catch(()=>{}); }

window.addEventListener('blur', () => {
  if (Date.now() < suppressCheckUntil) return;
  if (examActive) { if (fsWarningActive) { clearFsWarning(); flagViolation('Pindah Tab/Aplikasi Saat Diperingatkan!'); } else flagViolation('Pindah Tab/Meninggalkan Aplikasi!'); }
});
document.addEventListener('visibilitychange', () => {
  if (Date.now() < suppressCheckUntil) return;
  if (document.hidden && examActive) { if (fsWarningActive) clearFsWarning(); flagViolation('Aplikasi Diminimalkan / Tab Disembunyikan!'); }
});
window.addEventListener('beforeunload', (e) => { if (examActive && !examSubmitted) { e.preventDefault(); e.returnValue=''; } });

function playAlarmSound() {
  try {
    const ctx = new (window.AudioContext||window.webkitAudioContext)();
    const duration=4, now=ctx.currentTime;
    const gain=ctx.createGain(); gain.gain.value=0.3; gain.connect(ctx.destination);
    const osc=ctx.createOscillator(); osc.type='square'; osc.connect(gain); osc.start(now);
    let toggle=false;
    for (let t=0;t<=duration;t+=0.3) { osc.frequency.setValueAtTime(toggle?660:880, now+t); toggle=!toggle; }
    osc.stop(now+duration); setTimeout(()=>ctx.close(), duration*1000+200);
  } catch(e) {}
}
function flagViolation(reason) {
  const now = Date.now();
  if (now - lastViolationTime < 1200) return;
  lastViolationTime = now; suppressCheckUntil = now + 1500;
  cheatCount++;
  document.getElementById('cheat-indicator').innerText = 'Pelanggaran: ' + cheatCount;
  playAlarmSound();
  if (cheatCount >= MAX_VIOLATIONS) { alert('Batas pelanggaran ('+MAX_VIOLATIONS+') tercapai. Ujian dikirim otomatis. ('+reason+')'); submitExam(); }
  else {
    alert('PERINGATAN '+cheatCount+'/'+MAX_VIOLATIONS+': Jangan Mencoba Curang! ('+reason+')');
    suppressCheckUntil = Date.now()+1500;
    setTimeout(()=>{ if (examActive && !document.fullscreenElement) showFsWarning(); }, 300);
  }
}

function saveJawabanSaatIni(idx) {
  const q = activeSoal[idx]; if (!q) return;
  if (q.tipe === 'pg') { const r=document.getElementsByName('q-'+idx); let sel=''; for (let x of r) if (x.checked) sel=x.value; jawabanTersimpan[idx]=sel; }
  else if (q.tipe === 'isian') { const n=Array.isArray(q.kunci_jawaban)?q.kunci_jawaban.length:1; const arr=[]; for (let b=0;b<n;b++){ const el=document.getElementById('ans-'+idx+'-'+b); arr.push(el?el.value:''); } jawabanTersimpan[idx]=arr; }
  else if (q.tipe === 'essay') { const el=document.getElementById('ans-'+idx); jawabanTersimpan[idx]=el?el.value:''; }
  else if (q.tipe === 'jodoh') { const list=q.pasangan_shuffled||q.pasangan; jawabanTersimpan[idx]=list.map((p,i)=>{ const el=document.getElementById(`ans-\${idx}-\${i}`); return el?el.value:''; }); }
  else if (q.tipe === 'multi') { const list=q.pilihan_shuffled||q.pilihan; jawabanTersimpan[idx]=list.map((p,i)=>{ const el=document.getElementById(`ans-\${idx}-\${i}`); return el?el.checked:false; }); }
  else if (q.tipe === 'pernyataan') { const list=q.pernyataan_shuffled||q.pernyataan; jawabanTersimpan[idx]=list.map((p,i)=>{ const el=document.getElementById(`ans-\${idx}-\${i}`); return el?el.value:''; }); }
}
function sudahDijawab(idx) {
  const v = jawabanTersimpan[idx];
  if (v===null||v===undefined||v==='') return false;
  if (Array.isArray(v)) return v.some(x=>x===true||(typeof x==='string'&&x!==''));
  return true;
}
function goToQuestion(t) { if (t<0||t>=activeSoal.length) return; saveJawabanSaatIni(currentQIndex); currentQIndex=t; renderExamQuestions(); }
function renderQuestionNav() {
  const nav = document.getElementById('question-nav'); nav.innerHTML='';
  activeSoal.forEach((q,idx) => {
    const btn=document.createElement('button'); btn.type='button'; btn.innerText=idx+1;
    const isActive=idx===currentQIndex, isAnswered=sudahDijawab(idx);
    btn.className='w-9 h-9 text-xs font-bold rounded-lg border '+(isActive?'bg-blue-600 text-white border-blue-600':isAnswered?'bg-green-100 text-green-700 border-green-300':'bg-white text-slate-600 border-slate-300');
    btn.onclick=()=>goToQuestion(idx); nav.appendChild(btn);
  });
}
function renderExamQuestions() {
  const container = document.getElementById('questions-container'); const idx=currentQIndex; const q=activeSoal[idx];
  let optionsHtml = '';
  if (q.tipe === 'pg') {
    const ops=q.opsi_shuffled||q.opsi; const saved=jawabanTersimpan[idx]; optionsHtml='<div class="space-y-2">';
    for (const [key,o] of Object.entries(ops)) {
      optionsHtml += `<label class="flex items-center gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded border cursor-pointer text-base">
        <input type="radio" name="q-\${idx}" value="\${key}" class="h-4 w-4 text-blue-600" \${saved===key?'checked':''}>
        <div><span>\${o.teks}</span>\${o.gambar?`<img src="\${o.gambar}" class="h-20 w-32 object-cover rounded mt-1 border">`:''}</div></label>`;
    }
    optionsHtml += '</div>';
  } else if (q.tipe === 'isian') {
    const n=Array.isArray(q.kunci_jawaban)?q.kunci_jawaban.length:1; const saved=Array.isArray(jawabanTersimpan[idx])?jawabanTersimpan[idx]:[];
    if (n<=1) optionsHtml = `<input type="text" id="ans-\${idx}-0" placeholder="Jawaban Anda..." value="\${saved[0]||''}" class="w-full p-3 border rounded-lg text-base">`;
    else { optionsHtml='<div class="space-y-2">'; for (let b=0;b<n;b++) optionsHtml += `<div class="flex items-center gap-2"><span class="text-sm font-semibold text-slate-500 w-16 shrink-0">Isian \${b+1}</span><input type="text" id="ans-\${idx}-\${b}" value="\${saved[b]||''}" class="w-full p-3 border rounded-lg text-base"></div>`; optionsHtml+='</div>'; }
  } else if (q.tipe === 'essay') {
    optionsHtml = `<textarea id="ans-\${idx}" rows="10" placeholder="Ketik jawaban Anda..." class="w-full p-3 border rounded-lg text-base leading-relaxed">\${jawabanTersimpan[idx]||''}</textarea>`;
  } else if (q.tipe === 'jodoh') {
    const pasangan=q.pasangan_shuffled||q.pasangan, kanan=q.kanan_shuffled||q.pasangan.map(p=>p.kanan), saved=jawabanTersimpan[idx]||[];
    optionsHtml = '<div class="space-y-2">';
    pasangan.forEach((p,i) => { optionsHtml += `<div class="flex items-center gap-2 text-base"><span class="bg-slate-100 p-2 rounded border flex-1">\${p.kiri}</span><span>➡️</span>
      <select id="ans-\${idx}-\${i}" class="p-2 border rounded-lg flex-1"><option value="">-- Pilih Jodoh --</option>\${kanan.map(k=>`<option value="\${k}" \${saved[i]===k?'selected':''}>\${k}</option>`).join('')}</select></div>`; });
    optionsHtml += '</div>';
  } else if (q.tipe === 'multi') {
    const list=q.pilihan_shuffled||q.pilihan, saved=jawabanTersimpan[idx]||[]; optionsHtml='<div class="space-y-2">';
    list.forEach((p,i) => { optionsHtml += `<label class="flex items-center gap-2 text-base cursor-pointer p-2 bg-slate-50 rounded border"><input type="checkbox" id="ans-\${idx}-\${i}" class="h-4 w-4" \${saved[i]?'checked':''}><span>\${p.teks}</span></label>`; });
    optionsHtml += '</div>';
  } else if (q.tipe === 'pernyataan') {
    const list=q.pernyataan_shuffled||q.pernyataan, saved=jawabanTersimpan[idx]||[]; optionsHtml='<div class="space-y-2">';
    list.forEach((p,i) => { optionsHtml += `<div class="flex items-center gap-2 text-base justify-between p-2 bg-slate-50 rounded border"><span class="flex-1">\${p.teks}</span>
      <select id="ans-\${idx}-\${i}" class="p-2 border rounded"><option value="">-- Pilih --</option><option value="Benar" \${saved[i]==='Benar'?'selected':''}>Benar</option><option value="Salah" \${saved[i]==='Salah'?'selected':''}>Salah</option></select></div>`; });
    optionsHtml += '</div>';
  }
  container.innerHTML = `<div class="space-y-4 fade-in"><p class="font-bold text-slate-800 text-lg">No. \${idx+1} dari \${activeSoal.length}: \${q.pertanyaan}</p>
    \${q.gambar_soal?`<img src="\${q.gambar_soal}" class="max-h-72 object-contain rounded border bg-slate-50">`:''}\${optionsHtml}</div>`;
  document.getElementById('btn-prev-q').disabled = idx===0;
  document.getElementById('btn-prev-q').classList.toggle('opacity-40', idx===0);
  document.getElementById('btn-next-q').disabled = idx===activeSoal.length-1;
  document.getElementById('btn-next-q').classList.toggle('opacity-40', idx===activeSoal.length-1);
  document.getElementById('btn-submit-exam').classList.toggle('hidden', idx!==activeSoal.length-1);
  renderQuestionNav();
}

function confirmSubmitExam() {
  saveJawabanSaatIni(currentQIndex);
  const belum = activeSoal.reduce((c,q,i)=>c+(sudahDijawab(i)?0:1),0);
  const pesan = belum>0 ? ('Masih ada '+belum+' dari '+activeSoal.length+' soal yang BELUM Anda jawab. ') : ('Semua '+activeSoal.length+' soal sudah dijawab. ');
  document.getElementById('submit-confirm-info').innerText = pesan + 'Jawaban tidak dapat diubah setelah dikirim.';
  const cb = document.getElementById('submit-confirm-checkbox'); cb.checked=false; toggleSubmitConfirmButton();
  document.getElementById('submit-confirm-modal').classList.remove('hidden');
}
function toggleSubmitConfirmButton() { document.getElementById('submit-confirm-ok-btn').disabled = !document.getElementById('submit-confirm-checkbox').checked; }
function closeSubmitConfirmModal() { document.getElementById('submit-confirm-modal').classList.add('hidden'); }
function doSubmitExam() { closeSubmitConfirmModal(); submitExam(); }

function submitExam() {
  if (examSubmitted) return;
  saveJawabanSaatIni(currentQIndex);
  examSubmitted = true; examActive = false;
  if (timerInterval) clearInterval(timerInterval);
  if (document.exitFullscreen && document.fullscreenElement) document.exitFullscreen().catch(()=>{});

  let totalGrade = 0; let analysis = {};
  activeSoal.forEach((q, idx) => {
    let userAnswers=''; let grade=0;
    if (q.tipe === 'pg') { const sel=jawabanTersimpan[idx]||''; userAnswers=sel; if (sel && q.opsi[sel]) grade=q.opsi[sel].poin||0; }
    else if (q.tipe === 'isian') {
      const kunci=Array.isArray(q.kunci_jawaban)?q.kunci_jawaban:[q.kunci_jawaban];
      const jwb=Array.isArray(jawabanTersimpan[idx])?jawabanTersimpan[idx]:[jawabanTersimpan[idx]||''];
      const poin=Array.isArray(q.poin_isian)&&q.poin_isian.length===kunci.length?q.poin_isian:kunci.map(()=>(q.total_poin||0)/kunci.length);
      kunci.forEach((k,ki) => { const a=(jwb[ki]||'').trim().toLowerCase().replace(/\\s+/g,''); const c=(k||'').trim().toLowerCase().replace(/\\s+/g,''); if (a!==''&&a===c) grade+=(poin[ki]||0); });
      userAnswers = jwb.join(', ');
    } else if (q.tipe === 'essay') { userAnswers = jawabanTersimpan[idx]||''; grade=0; }
    else if (q.tipe === 'jodoh') { const list=q.pasangan_shuffled||q.pasangan; const ans=jawabanTersimpan[idx]||[]; list.forEach((p,i)=>{ if (ans[i]===p.kanan) grade+=p.poin||0; }); userAnswers=ans.join(', '); }
    else if (q.tipe === 'multi') { const list=q.pilihan_shuffled||q.pilihan; const ans=jawabanTersimpan[idx]||[]; list.forEach((p,i)=>{ if (!!ans[i]===p.benar) grade+=p.poin||0; }); userAnswers=ans.map(v=>v?'Centang':'Kosong').join(', '); }
    else if (q.tipe === 'pernyataan') { const list=q.pernyataan_shuffled||q.pernyataan; const ans=jawabanTersimpan[idx]||[]; list.forEach((p,i)=>{ if (ans[i]===p.benar) grade+=p.poin||0; }); userAnswers=ans.join(', '); }
    totalGrade += grade;
    analysis['Q'+(idx+1)] = { tipe:q.tipe, jawaban_siswa:userAnswers, poin_diperoleh:grade };
  });

  const namaSiswa = document.getElementById('student-name').value;
  const nisnSiswa = document.getElementById('student-id').value;
  const waktuKirim = new Date().toISOString();
  const signature = computeSignature(namaSiswa+'|'+nisnSiswa+'|'+totalGrade+'|'+(examToken||'')+'|'+waktuKirim);
  const maxScore = activeSoal.reduce((s,q)=>s+(q.total_poin||0), 0);

  const payload = { exam_slug: EXAM_SLUG, nama: namaSiswa, nisn: nisnSiswa, nilai: totalGrade, max_nilai: maxScore, total_kecurangan: cheatCount, waktu_kirim: waktuKirim, signature, detail_jawaban: analysis };

  downloadLocalBackup(payload);

  fetch(SUBMIT_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
    .then(r => r.json()).then(() => {
      showFinishScreen(totalGrade, maxScore, 'Jawaban Anda berhasil disimpan ke server sekolah. Backup cadangan juga telah diunduh ke perangkat Anda.');
    }).catch(() => {
      showFinishScreen(totalGrade, maxScore, 'Koneksi ke server gagal. File backup jawaban telah diunduh — segera serahkan ke guru Anda.');
    });
}

function showFinishScreen(totalGrade, maxScore, message) {
  document.getElementById('exam-area').classList.add('hidden');
  document.getElementById('questions-container').innerHTML = '';
  document.getElementById('finish-message').innerText = message;
  if (TAMPILKAN_NILAI) {
    document.getElementById('finish-score-box').classList.remove('hidden');
    const persen = maxScore>0 ? (totalGrade/maxScore*100) : 0;
    document.getElementById('finish-score-value').innerText = (Math.round(persen*100)/100).toString().replace('.',',') + '%';
  }
  document.getElementById('finish-area').classList.remove('hidden');
}
function downloadLocalBackup(data) {
  const enc = CryptoJS.AES.encrypt(JSON.stringify(data), EXAM_BACKUP_KEY).toString();
  const dataStr = 'data:text/plain;charset=utf-8,' + encodeURIComponent(enc);
  const dl = document.createElement('a'); dl.setAttribute('href', dataStr);
  dl.setAttribute('download', 'Backup_Ujian_' + data.nama.replace(/\\s+/g,'_') + '.backup');
  document.body.appendChild(dl); dl.click(); dl.remove();
}
</script>
</body>
</html>
HTML;
}
