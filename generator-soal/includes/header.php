<?php
/** @var array|null $u current user, provided by including page */
$u = current_user();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?><?= h(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f8fafc}
  .sidebar-link.active{background:#2563eb;color:#fff}
  .sidebar-link:hover:not(.active){background:#eff6ff}
  ::-webkit-scrollbar{width:6px;height:6px}
  ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
</style>
</head>
<body class="min-h-screen">
<?php if ($u): ?>
<div class="flex min-h-screen">
  <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">
    <div class="p-5 border-b border-slate-100">
      <p class="font-extrabold text-slate-800 leading-tight">📝 <?= h(APP_NAME) ?></p>
      <p class="text-[11px] text-slate-400 mt-1"><?= h(get_setting('nama_sekolah','')) ?></p>
    </div>
    <nav class="flex-1 p-3 space-y-1 text-sm">
      <?php if ($u['role'] === 'admin'): ?>
        <a href="<?= base_url('admin/index.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='admin_dash'?'active':'' ?>">📊 Dashboard Admin</a>
        <a href="<?= base_url('admin/guru.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='admin_guru'?'active':'' ?>">👩‍🏫 Kelola Akun Guru</a>
        <a href="<?= base_url('admin/exams.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='admin_exams'?'active':'' ?>">🗂️ Semua Ujian</a>
        <a href="<?= base_url('admin/settings.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='admin_settings'?'active':'' ?>">⚙️ Pengaturan Aplikasi</a>
      <?php else: ?>
        <a href="<?= base_url('guru/index.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='guru_dash'?'active':'' ?>">📚 Bank Soal Saya</a>
        <a href="<?= base_url('guru/exams.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='guru_exams'?'active':'' ?>">🚀 Ujian Ter-generate</a>
        <a href="<?= base_url('guru/profile.php') ?>" class="sidebar-link block px-3 py-2 rounded-lg font-medium text-slate-600 <?= ($active ?? '')==='guru_profile'?'active':'' ?>">🔑 Ganti Password</a>
      <?php endif; ?>
    </nav>
    <div class="p-3 border-t border-slate-100">
      <div class="px-3 py-2 mb-2">
        <p class="text-xs font-semibold text-slate-700"><?= h($u['name']) ?></p>
        <p class="text-[10px] text-slate-400 uppercase"><?= h($u['role']) ?></p>
      </div>
      <a href="<?= base_url('logout.php') ?>" class="block text-center bg-slate-100 hover:bg-red-100 hover:text-red-600 text-slate-600 text-xs font-semibold py-2 rounded-lg">Keluar</a>
    </div>
  </aside>
  <main class="flex-1 min-w-0">
    <div class="max-w-6xl mx-auto p-6">
    <?php if ($flash): ?>
      <div class="mb-4 rounded-lg px-4 py-3 text-sm font-medium <?= $flash['type']==='error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' ?>">
        <?= h($flash['msg']) ?>
      </div>
    <?php endif; ?>
<?php else: ?>
  <div class="max-w-6xl mx-auto p-6">
  <?php if ($flash): ?>
    <div class="mb-4 rounded-lg px-4 py-3 text-sm font-medium <?= $flash['type']==='error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' ?>">
      <?= h($flash['msg']) ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
