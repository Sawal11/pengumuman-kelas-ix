<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$message = '';
$error = '';
$admin = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $stmt = pdo()->prepare('SELECT password FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $admin['id']]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify($currentPassword, $hash)) {
        $error = 'Password lama tidak sesuai.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru tidak sama.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = pdo()->prepare('UPDATE admins SET password = :password WHERE id = :id');
        $update->execute([
            'password' => $newHash,
            'id' => $admin['id'],
        ]);
        $message = 'Password admin berhasil diperbarui.';
    }
}

admin_header('Ganti Password', 'password');
?>
<div class="page-heading">
    <div>
        <p class="admin-eyebrow">Keamanan</p>
        <h1>Ganti Password Admin</h1>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<section class="admin-card password-card">
    <h2>Perbarui Password</h2>
    <p class="text-muted">Admin default adalah username <strong>admin</strong> dan password <strong>admin123</strong>. Segera ganti setelah instalasi pertama.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label" for="current_password">Password Lama</label>
            <input class="form-control form-control-lg" type="password" name="current_password" id="current_password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="new_password">Password Baru</label>
            <input class="form-control form-control-lg" type="password" name="new_password" id="new_password" required minlength="6">
        </div>
        <div class="mb-4">
            <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
            <input class="form-control form-control-lg" type="password" name="confirm_password" id="confirm_password" required minlength="6">
        </div>
        <button class="btn btn-admin-primary btn-lg" type="submit">Simpan Password</button>
    </form>
</section>
<?php admin_footer(); ?>
