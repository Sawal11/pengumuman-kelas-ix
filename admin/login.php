<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

if (admin_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = pdo()->prepare('SELECT id, username, password, nama FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['nama'];
        redirect('dashboard.php');
    }

    $error = 'Username atau password tidak sesuai.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= (int) (@filemtime(__DIR__ . '/../assets/css/admin.css') ?: time()) ?>">
</head>
<body class="login-body">
<div id="pageLoader" class="page-loader" aria-hidden="true">
    <div class="page-loader-card">
        <img src="../assets/img/logo-kemenag.png" alt="">
        <div class="page-loader-ring"></div>
        <span>Memuat halaman...</span>
    </div>
</div>

<main class="login-card">
    <img class="login-logo" src="../assets/img/logo-kemenag.png" alt="Logo Kementerian Agama">
    <p class="admin-eyebrow">Panel Admin</p>
    <h1>Portal Kelulusan MTsN 1 Pohuwato</h1>
    <p class="text-muted">Masuk untuk mengelola waktu pengumuman, data siswa, dan log akses.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input class="form-control form-control-lg" type="text" name="username" id="username" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control form-control-lg" type="password" name="password" id="password" required>
        </div>
        <button class="btn btn-admin-primary btn-lg w-100" type="submit">Masuk</button>
    </form>
</main>
<script src="../assets/js/admin.js?v=<?= (int) (@filemtime(__DIR__ . '/../assets/js/admin.js') ?: time()) ?>"></script>
</body>
</html>
