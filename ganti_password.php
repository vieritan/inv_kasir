<?php
// ganti_password.php - Ganti Password Pengguna
$pageTitle = "Ganti Password";
require_once __DIR__ . '/includes/header.php';

$message = '';
$error = '';
$currentUsername = $_SESSION['username'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
        $error = "Semua field wajib diisi!";
    } elseif ($newPass !== $confirmPass) {
        $error = "Konfirmasi password baru tidak cocok!";
    } else {
        $users = loadData('users.json');
        $found = false;

        if (is_array($users)) {
            foreach ($users as &$u) {
                if (strtolower($u['username']) === strtolower($currentUsername)) {
                    if ($u['password'] !== $oldPass) {
                        $error = "Password lama Anda salah!";
                    } else {
                        $u['password'] = $newPass;
                        $found = true;
                    }
                    break;
                }
            }
            unset($u);
        }

        if ($found) {
            saveData('users.json', $users);
            $message = "Password ID '" . htmlspecialchars($currentUsername) . "' berhasil diperbarui!";
        } elseif (empty($error)) {
            $error = "Pengguna ID '$currentUsername' tidak ditemukan!";
        }
    }
}
?>

<h1 class="page-title">Ganti Password Program</h1>
<p style="font-size: 0.9rem; color: #555; margin-bottom: 20px;">Ubah password untuk akun: <strong><?= htmlspecialchars($currentUsername) ?></strong></p>

<?php if ($message): ?>
    <div style="background-color: #d1e7dd; color: #0f5132; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background-color: #f8d7da; color: #842029; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="filter-card" style="max-width: 450px; background: #fff; border: 1px solid #ccc; padding: 20px; border-radius: 6px;">
    <form method="POST" action="ganti_password.php">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 6px;">ID USER / USERNAME</label>
            <input type="text" value="<?= htmlspecialchars($currentUsername) ?>" class="form-control" style="width: 100%; background-color: #e9ecef;" disabled>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 6px;">Password Lama</label>
            <input type="password" name="old_password" class="form-control" style="width: 100%; padding: 8px;" placeholder="Masukkan password lama..." required>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 6px;">Password Baru</label>
            <input type="password" name="new_password" class="form-control" style="width: 100%; padding: 8px;" placeholder="Masukkan password baru..." required>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 6px;">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" style="width: 100%; padding: 8px;" placeholder="Ulangi password baru..." required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: bold; background-color: #006600; color: #fff; border-color: #004d00;">UPDATE PASSWORD</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
