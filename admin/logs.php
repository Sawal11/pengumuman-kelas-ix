<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$search = trim((string) ($_GET['q'] ?? ''));
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(l.nisn LIKE :search OR s.nama LIKE :search OR s.kelas LIKE :search OR l.ip_address LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$sql = 'SELECT l.*, s.nama, s.kelas
        FROM access_logs l
        LEFT JOIN students s ON s.id = l.student_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY l.accessed_at DESC LIMIT 500';

$stmt = pdo()->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

admin_header('Log Akses', 'logs');
?>
<div class="page-heading">
    <div>
        <p class="admin-eyebrow">Monitoring</p>
        <h1>Log Akses Siswa</h1>
    </div>
    <button class="btn btn-outline-secondary" onclick="window.print()">Cetak Log</button>
</div>

<section class="admin-card">
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-9">
            <input class="form-control" type="search" name="q" value="<?= h($search) ?>" placeholder="Cari nama, NISN, kelas, atau IP address">
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-outline-success" type="submit">Cari Log</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
            <tr>
                <th>Nama</th>
                <th>NISN</th>
                <th>Kelas</th>
                <th>Waktu Akses</th>
                <th>IP Address</th>
                <th>Perangkat</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= h($log['nama'] ?? '-') ?></td>
                    <td><?= h($log['nisn']) ?></td>
                    <td><?= h($log['kelas'] ?? '-') ?></td>
                    <td><?= h($log['accessed_at']) ?> WITA</td>
                    <td><?= h($log['ip_address']) ?></td>
                    <td class="user-agent-cell"><?= h($log['user_agent'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="6" class="text-center text-muted">Log akses belum tersedia.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_footer(); ?>
