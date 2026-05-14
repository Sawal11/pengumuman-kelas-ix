<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';

function admin_header(string $title, string $active = ''): void
{
    $admin = current_admin();
    $menu = [
        'dashboard' => ['Dashboard', 'dashboard.php'],
        'students' => ['Data Siswa', 'students.php'],
        'import' => ['Import', 'import.php'],
        'settings' => ['Pengaturan', 'settings.php'],
        'logs' => ['Log Akses', 'logs.php'],
        'password' => ['Password', 'change_password.php'],
    ];
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> - <?= h(APP_NAME) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/admin.css?v=<?= (int) (@filemtime(__DIR__ . '/../assets/css/admin.css') ?: time()) ?>">
    </head>
    <body class="admin-body">
    <div id="pageLoader" class="page-loader" aria-hidden="true">
        <div class="page-loader-card">
            <img src="../assets/img/logo-kemenag.png" alt="">
            <div class="page-loader-ring"></div>
            <span>Memuat halaman...</span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light admin-navbar sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img class="admin-brand-logo" src="../assets/img/logo-kemenag.png" alt="Logo Kementerian Agama" width="36" height="36" style="width:36px;height:36px;max-width:36px;max-height:36px;object-fit:contain;">
                <span><?= h(APP_NAME) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php foreach ($menu as $key => [$label, $url]): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= h($url) ?>"><?= h($label) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted"><?= h($admin['nama'] ?? 'Admin') ?></span>
                    <a class="btn btn-sm btn-outline-success" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="container-fluid admin-main">
    <?php
}

function admin_footer(): void
{
    ?>
    </main>
    <footer class="admin-footer">
        <span>Portal Kelulusan MTsN 1 Pohuwato</span>
        <span>&copy; 2026 MTs Negeri 1 Pohuwato</span>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js?v=<?= (int) (@filemtime(__DIR__ . '/../assets/js/admin.js') ?: time()) ?>"></script>
    </body>
    </html>
    <?php
}
