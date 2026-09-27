<?php
require_once __DIR__ . '/../config.php';
$u = require_role('admin');
$active = 'admin_guru';
$pageTitle = 'Kelola Akun Guru';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role = ($_POST['role'] ?? 'guru') === 'admin' ? 'admin' : 'guru';
        if ($name && $username && strlen($password) >= 4) {
            try {
                $stmt = db()->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?,?,?,?)");
                $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash_set('Akun berhasil dibuat.');
            } catch (Exception $e) {
                flash_set('Gagal membuat akun: username mungkin sudah dipakai.', 'error');
            }
        } else {
            flash_set('Lengkapi data dengan benar (password minimal 4 karakter).', 'error');
        }
    } elseif ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');
        if ($id && strlen($password) >= 4) {
            db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            flash_set('Password akun berhasil direset.');
        } else {
            flash_set('Password minimal 4 karakter.', 'error');
        }
    } elseif ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id !== (int)$u['id']) {
            db()->prepare("UPDATE users SET active = 1 - active WHERE id = ?")->execute([$id]);
            flash_set('Status akun diperbarui.');
        } else {
            flash_set('Tidak bisa menonaktifkan akun sendiri.', 'error');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id !== (int)$u['id']) {
            db()->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$id]);
            flash_set('Akun guru dihapus beserta seluruh bank soal & ujiannya.');
        } else {
            flash_set('Tidak bisa menghapus akun sendiri.', 'error');
        }
    }
    header('Location: ' . base_url('admin/guru.php'));
    exit;
}

$users = db()->query("SELECT * FROM users ORDER BY role DESC, name ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="flex justify-between items-center mb-6">
  <div>
    <h1 class="text-xl font-bold text-slate-800">Kelola Akun Guru & Admin</h1>
    <p class="text-sm text-slate-500">Admin dapat membuat, menonaktifkan, reset password, atau menghapus akun.</p>
  </div>
  <button onclick="document.getElementById('modal-create').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-lg text-sm">+ Tambah Akun</button>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
  <table class="w-full text-sm">
    <thead><tr class="text-left text-xs text-slate-400 uppercase bg-slate-50 border-b">
      <th class="py-3 px-4">Nama</th><th>Username</th><th>Role</th><th>Status</th><th class="px-4">Aksi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($users as $row): ?>
      <tr class="border-b border-slate-100">
        <td class="py-3 px-4 font-medium text-slate-700"><?= h($row['name']) ?></td>
        <td class="text-slate-500"><?= h($row['username']) ?></td>
        <td><span class="text-xs px-2 py-0.5 rounded-full <?= $row['role']==='admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>"><?= h($row['role']) ?></span></td>
        <td><span class="text-xs px-2 py-0.5 rounded-full <?= $row['active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $row['active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
        <td class="px-4 py-2">
          <div class="flex gap-2 flex-wrap">
            <button onclick="openReset(<?= (int)$row['id'] ?>, '<?= h(addslashes($row['name'])) ?>')" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded">Reset Password</button>
            <?php if ((int)$row['id'] !== (int)$u['id']): ?>
            <form method="post" class="inline">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 px-2 py-1 rounded"><?= $row['active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
            </form>
            <form method="post" class="inline" onsubmit="return confirm('Hapus akun ini beserta semua data bank soal & ujiannya? Tindakan ini tidak bisa dibatalkan.')">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">Hapus</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah Akun -->
<div id="modal-create" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-sm space-y-3">
    <h3 class="font-bold text-slate-800">Tambah Akun Baru</h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">
      <input type="text" name="name" placeholder="Nama Lengkap" required class="w-full p-2.5 border rounded-lg text-sm">
      <input type="text" name="username" placeholder="Username" required class="w-full p-2.5 border rounded-lg text-sm">
      <input type="password" name="password" placeholder="Password (min. 4 karakter)" required class="w-full p-2.5 border rounded-lg text-sm">
      <select name="role" class="w-full p-2.5 border rounded-lg text-sm">
        <option value="guru">Guru</option>
        <option value="admin">Admin</option>
      </select>
      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')" class="flex-1 bg-slate-100 hover:bg-slate-200 py-2 rounded-lg text-sm font-semibold">Batal</button>
        <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Reset Password -->
<div id="modal-reset" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-sm space-y-3">
    <h3 class="font-bold text-slate-800">Reset Password: <span id="reset-name"></span></h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="id" id="reset-id">
      <input type="password" name="password" placeholder="Password baru (min. 4 karakter)" required class="w-full p-2.5 border rounded-lg text-sm">
      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-reset').classList.add('hidden')" class="flex-1 bg-slate-100 hover:bg-slate-200 py-2 rounded-lg text-sm font-semibold">Batal</button>
        <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openReset(id, name) {
  document.getElementById('reset-id').value = id;
  document.getElementById('reset-name').innerText = name;
  document.getElementById('modal-reset').classList.remove('hidden');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
