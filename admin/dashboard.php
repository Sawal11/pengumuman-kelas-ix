<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$totalStudents = (int) pdo()->query('SELECT COUNT(*) FROM students')->fetchColumn();
$accessedStudents = (int) pdo()->query('SELECT COUNT(*) FROM students WHERE sudah_akses = 1')->fetchColumn();
$totalLogs = (int) pdo()->query('SELECT COUNT(*) FROM access_logs')->fetchColumn();
$announcement = announcement_datetime();

$byClass = pdo()->query('SELECT kelas, COUNT(*) total FROM students GROUP BY kelas ORDER BY kelas ASC')->fetchAll();
$byDrama = pdo()->query('SELECT mode_drama, COUNT(*) total FROM students GROUP BY mode_drama ORDER BY mode_drama ASC')->fetchAll();
$latestLogs = pdo()->query(
    'SELECT l.nisn, l.ip_address, l.accessed_at, s.nama, s.kelas
     FROM access_logs l
     LEFT JOIN students s ON s.id = l.student_id
     ORDER BY l.accessed_at DESC
     LIMIT 8'
)->fetchAll();

admin_header('Dashboard', 'dashboard');
?>
<div class="page-heading">
    <div>
        <p class="admin-eyebrow">Dashboard</p>
        <h1>Ringkasan Pengumuman</h1>
    </div>
    <a class="btn btn-admin-primary" href="../index.php" target="_blank">Lihat Halaman Siswa</a>
</div>

<div class="stats-grid">
    <article class="stat-card">
        <span>Seluruh Siswa</span>
        <strong><?= number_format($totalStudents) ?></strong>
    </article>
    <article class="stat-card">
        <span>Sudah Mengakses</span>
        <strong><?= number_format($accessedStudents) ?></strong>
    </article>
    <article class="stat-card">
        <span>Total Log Akses</span>
        <strong><?= number_format($totalLogs) ?></strong>
    </article>
    <article class="stat-card">
        <span>Pengumuman Aktif</span>
        <strong class="stat-time"><?= h($announcement->format('d/m/Y H:i')) ?> WITA</strong>
    </article>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <section class="admin-card">
            <div class="card-title-row">
                <h2>Jumlah Siswa per Kelas</h2>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                    <tr>
                        <th>Kelas</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($byClass as $row): ?>
                        <tr>
                            <td><?= h($row['kelas']) ?></td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$byClass): ?>
                        <tr><td colspan="2" class="text-center text-muted">Belum ada data siswa.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="admin-card">
            <div class="card-title-row">
                <h2>Statistik Mode Drama</h2>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                    <tr>
                        <th>Mode</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($byDrama as $row): ?>
                        <tr>
                            <td><?= h($row['mode_drama']) ?></td>
                            <td class="text-end fw-bold"><?= number_format((int) $row['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$byDrama): ?>
                        <tr><td colspan="2" class="text-center text-muted">Belum ada data mode drama.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<section class="admin-card mt-4">
    <div class="card-title-row">
        <h2>Akses Terbaru</h2>
        <a href="logs.php" class="btn btn-sm btn-outline-success">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Nama</th>
                <th>NISN</th>
                <th>Kelas</th>
                <th>IP Address</th>
                <th>Waktu</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($latestLogs as $log): ?>
                <tr>
                    <td><?= h($log['nama'] ?? '-') ?></td>
                    <td><?= h($log['nisn']) ?></td>
                    <td><?= h($log['kelas'] ?? '-') ?></td>
                    <td><?= h($log['ip_address']) ?></td>
                    <td><?= h($log['accessed_at']) ?> WITA</td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$latestLogs): ?>
                <tr><td colspan="5" class="text-center text-muted">Belum ada akses siswa.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_footer(); ?>
