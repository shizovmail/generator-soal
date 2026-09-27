<?php
require_once __DIR__ . '/config.php';

if (current_user()) {
    $u = current_user();
    header('Location: ' . base_url($u['role'] === 'admin' ? 'admin/index.php' : 'guru/index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Sesi kedaluwarsa, silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if (attempt_login($username, $password)) {
            $u = current_user();
            header('Location: ' . base_url($u['role'] === 'admin' ? 'admin/index.php' : 'guru/index.php'));
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — <?= h(APP_NAME) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}</style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8 space-y-5">
    <div class="text-center space-y-1">
      <span class="text-4xl">📝</span>
      <h1 class="text-lg font-bold text-slate-800"><?= h(APP_NAME) ?></h1>
      <p class="text-xs text-slate-500"><?= h(get_setting('nama_sekolah', 'Sekolah')) ?></p>
    </div>
    <?php if ($error): ?>
      <div class="bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg px-3 py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Username</label>
        <input type="text" name="username" required class="w-full p-2.5 border rounded-lg text-sm border-slate-200">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password</label>
        <input type="password" name="password" required class="w-full p-2.5 border rounded-lg text-sm border-slate-200">
      </div>
      <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-sm transition">Masuk</button>
    </form>
    <p class="text-[10px] text-slate-400 text-center">Akun admin default: <strong>admin / admin123</strong> — segera ganti password setelah login pertama.</p>
  </div>
</body>
</html>
