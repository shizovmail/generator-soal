<?php
require_once __DIR__ . '/../config.php';
$u = require_role('guru');
$active = 'guru_profile';
$pageTitle = 'Ganti Password';

$error = ''; $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    if (!password_verify($old, $u['password_hash'])) {
        $error = 'Password lama salah.';
    } elseif (strlen($new) < 4) {
        $error = 'Password baru minimal 4 karakter.';
    } else {
        db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        $ok = 'Password berhasil diubah.';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="text-xl font-bold text-slate-800 mb-6">Ganti Password</h1>
<?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg px-4 py-2 mb-4 max-w-md"><?= h($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="bg-green-50 border border-green-200 text-green-600 text-sm rounded-lg px-4 py-2 mb-4 max-w-md"><?= h($ok) ?></div><?php endif; ?>
<form method="post" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-md">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password Lama</label>
    <input type="password" name="old_password" required class="w-full p-2.5 border rounded-lg text-sm">
  </div>
  <div>
    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password Baru</label>
    <input type="password" name="new_password" required class="w-full p-2.5 border rounded-lg text-sm">
  </div>
  <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-lg text-sm">Simpan</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
