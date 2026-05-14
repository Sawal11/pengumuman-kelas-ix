<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$pdo = pdo();
$message = '';
$error = '';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query(
        'SELECT nama, nisn, tanggal_lahir, kelas, status_kelulusan, mode_drama, pesan_khusus, sudah_akses, jumlah_akses, akses_terakhir
         FROM students
         ORDER BY kelas ASC, nama ASC'
    )->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="data-siswa-kelulusan.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['nama', 'nisn', 'tanggal_lahir', 'kelas', 'status_kelulusan', 'mode_drama', 'pesan_khusus', 'sudah_akses', 'jumlah_akses', 'akses_terakhir']);
    foreach ($rows as $row) {
        $row['status_kelulusan'] = 'LULUS';
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $nisn = trim((string) ($_POST['nisn'] ?? ''));
        $tanggalLahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));
        $kelas = trim((string) ($_POST['kelas'] ?? ''));
        $modeDrama = normalize_drama_mode((string) ($_POST['mode_drama'] ?? 'Normal'));
        $pesanKhusus = trim((string) ($_POST['pesan_khusus'] ?? ''));
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $tanggalLahir);

        if ($nama === '' || $nisn === '' || $kelas === '' || !$date || $date->format('Y-m-d') !== $tanggalLahir) {
            $error = 'Nama, NISN, tanggal lahir valid, dan kelas wajib diisi.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE students
                         SET nama = :nama,
                             nisn = :nisn,
                             tanggal_lahir = :tanggal_lahir,
                             kelas = :kelas,
                             status_kelulusan = :status_kelulusan,
                             mode_drama = :mode_drama,
                             pesan_khusus = :pesan_khusus
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'nama' => $nama,
                        'nisn' => $nisn,
                        'tanggal_lahir' => $tanggalLahir,
                        'kelas' => $kelas,
                        'status_kelulusan' => 'LULUS',
                        'mode_drama' => $modeDrama,
                        'pesan_khusus' => $pesanKhusus,
                        'id' => $id,
                    ]);
                    $message = 'Data siswa berhasil diperbarui.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO students (nama, nisn, tanggal_lahir, kelas, status_kelulusan, mode_drama, pesan_khusus)
                         VALUES (:nama, :nisn, :tanggal_lahir, :kelas, :status_kelulusan, :mode_drama, :pesan_khusus)'
                    );
                    $stmt->execute([
                        'nama' => $nama,
                        'nisn' => $nisn,
                        'tanggal_lahir' => $tanggalLahir,
                        'kelas' => $kelas,
                        'status_kelulusan' => 'LULUS',
                        'mode_drama' => $modeDrama,
                        'pesan_khusus' => $pesanKhusus,
                    ]);
                    $message = 'Data siswa berhasil ditambahkan.';
                }
            } catch (PDOException $e) {
                $error = 'Data gagal disimpan. Pastikan NISN belum digunakan siswa lain.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM students WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $message = 'Data siswa berhasil dihapus.';
        }
    }
}

$editStudent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editStudent = $stmt->fetch() ?: null;
}

$search = trim((string) ($_GET['q'] ?? ''));
$kelasFilter = trim((string) ($_GET['kelas'] ?? ''));
$where = [];
$params = [];

if ($search !== '') {
    $searchLower = strtolower($search);
    $searchCompact = preg_replace('/\s+/', '', $searchLower);
    $where[] = '(
        LOWER(nama) LIKE :search_nama
        OR nisn LIKE :search_nisn
        OR LOWER(kelas) LIKE :search_kelas
        OR LOWER(REPLACE(nama, " ", "")) LIKE :search_compact
    )';
    $params['search_nama'] = '%' . $searchLower . '%';
    $params['search_nisn'] = '%' . $search . '%';
    $params['search_kelas'] = '%' . $searchLower . '%';
    $params['search_compact'] = '%' . $searchCompact . '%';
}

if ($kelasFilter !== '') {
    $where[] = 'kelas = :kelas';
    $params['kelas'] = $kelasFilter;
}

$sql = 'SELECT * FROM students';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY kelas ASC, nama ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
$classes = $pdo->query('SELECT DISTINCT kelas FROM students ORDER BY kelas ASC')->fetchAll(PDO::FETCH_COLUMN);

admin_header('Data Siswa', 'students');
?>
<div class="page-heading">
    <div>
        <p class="admin-eyebrow">Manajemen Siswa</p>
        <h1>Data Peserta Didik</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-success" href="students.php?export=csv">Export CSV</a>
        <button class="btn btn-outline-secondary" onclick="window.print()">Cetak Data</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<section class="admin-card mb-4">
    <div class="card-title-row">
        <h2><?= $editStudent ? 'Edit Data Siswa' : 'Tambah Data Siswa' ?></h2>
        <?php if ($editStudent): ?>
            <a href="students.php" class="btn btn-sm btn-outline-secondary">Batal Edit</a>
        <?php endif; ?>
    </div>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h((string) ($editStudent['id'] ?? 0)) ?>">
        <div class="col-md-4">
            <label class="form-label" for="nama">Nama</label>
            <input class="form-control" type="text" name="nama" id="nama" required value="<?= h($editStudent['nama'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="nisn">NISN</label>
            <input class="form-control" type="text" name="nisn" id="nisn" required value="<?= h($editStudent['nisn'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
            <input class="form-control" type="date" name="tanggal_lahir" id="tanggal_lahir" required value="<?= h($editStudent['tanggal_lahir'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="kelas">Kelas</label>
            <input class="form-control" type="text" name="kelas" id="kelas" required placeholder="IX A" value="<?= h($editStudent['kelas'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="mode_drama">Mode Drama</label>
            <select class="form-select" name="mode_drama" id="mode_drama">
                <?php foreach (drama_modes() as $mode): ?>
                    <option value="<?= h($mode) ?>" <?= ($editStudent['mode_drama'] ?? 'Normal') === $mode ? 'selected' : '' ?>><?= h($mode) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label" for="pesan_khusus">Pesan Khusus</label>
            <input class="form-control" type="text" name="pesan_khusus" id="pesan_khusus" value="<?= h($editStudent['pesan_khusus'] ?? '') ?>" placeholder="Pesan motivasi untuk siswa">
        </div>
        <div class="col-12">
            <button class="btn btn-admin-primary" type="submit"><?= $editStudent ? 'Simpan Perubahan' : 'Tambah Siswa' ?></button>
        </div>
    </form>
</section>

<section class="admin-card">
    <div class="card-title-row">
        <h2>Daftar Siswa</h2>
    </div>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-6">
            <input class="form-control" type="search" name="q" value="<?= h($search) ?>" placeholder="Cari nama, NISN, atau kelas">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="kelas">
                <option value="">Semua Kelas</option>
                <?php foreach ($classes as $kelas): ?>
                    <option value="<?= h($kelas) ?>" <?= $kelasFilter === $kelas ? 'selected' : '' ?>><?= h($kelas) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-outline-success" type="submit">Terapkan Filter</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
            <tr>
                <th>Nama</th>
                <th>NISN</th>
                <th>Tanggal Lahir</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Mode Drama</th>
                <th>Akses</th>
                <th class="text-end">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= h($student['nama']) ?></td>
                    <td><?= h($student['nisn']) ?></td>
                    <td><?= h(format_date_id($student['tanggal_lahir'])) ?></td>
                    <td><?= h($student['kelas']) ?></td>
                    <td><span class="badge text-bg-success">LULUS</span></td>
                    <td><?= h($student['mode_drama']) ?></td>
                    <td><?= $student['sudah_akses'] ? 'Sudah' : 'Belum' ?> (<?= (int) $student['jumlah_akses'] ?>)</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="students.php?edit=<?= (int) $student['id'] ?>">Edit</a>
                        <form method="post" class="d-inline js-delete-form">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?>
                <tr><td colspan="8" class="text-center text-muted">Data siswa tidak ditemukan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_footer(); ?>
