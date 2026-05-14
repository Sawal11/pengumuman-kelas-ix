<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode tidak valid.'], 405);
}

if (!announcement_is_open()) {
    json_response([
        'success' => false,
        'message' => 'Pengumuman belum dibuka. Mohon menunggu hingga waktu resmi.',
    ], 403);
}

$nisn = trim((string) ($_POST['nisn'] ?? ''));
$tanggalLahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));

if ($nisn === '' || $tanggalLahir === '') {
    json_response([
        'success' => false,
        'message' => 'NISN dan tanggal lahir wajib diisi.',
    ], 422);
}

$date = DateTimeImmutable::createFromFormat('Y-m-d', $tanggalLahir);
if (!$date || $date->format('Y-m-d') !== $tanggalLahir) {
    json_response([
        'success' => false,
        'message' => 'Format tanggal lahir tidak valid.',
    ], 422);
}

$stmt = pdo()->prepare(
    'SELECT id, nama, nisn, tanggal_lahir, kelas, status_kelulusan, mode_drama, pesan_khusus
     FROM students
     WHERE nisn = :nisn AND tanggal_lahir = :tanggal_lahir
     LIMIT 1'
);
$stmt->execute([
    'nisn' => $nisn,
    'tanggal_lahir' => $tanggalLahir,
]);
$student = $stmt->fetch();

if (!$student) {
    json_response([
        'success' => false,
        'message' => 'Data tidak ditemukan. Pastikan NISN dan tanggal lahir sesuai data madrasah.',
    ], 404);
}

$pdo = pdo();
$pdo->beginTransaction();

try {
    $update = $pdo->prepare(
        'UPDATE students
         SET status_kelulusan = :status_kelulusan,
             sudah_akses = 1,
             jumlah_akses = jumlah_akses + 1,
             akses_terakhir = NOW()
         WHERE id = :id'
    );
    $update->execute([
        'status_kelulusan' => graduation_status($student['status_kelulusan']),
        'id' => $student['id'],
    ]);

    $log = $pdo->prepare(
        'INSERT INTO access_logs (student_id, nisn, ip_address, user_agent, accessed_at)
         VALUES (:student_id, :nisn, :ip_address, :user_agent, NOW())'
    );
    $log->execute([
        'student_id' => $student['id'],
        'nisn' => $student['nisn'],
        'ip_address' => client_ip(),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 1000),
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response([
        'success' => false,
        'message' => 'Data ditemukan, tetapi sistem belum dapat mencatat akses. Silakan coba lagi.',
    ], 500);
}

json_response([
    'success' => true,
    'student' => [
        'nama' => $student['nama'],
        'nisn' => $student['nisn'],
        'tanggal_lahir' => format_date_id($student['tanggal_lahir']),
        'kelas' => $student['kelas'],
        'status_kelulusan' => 'LULUS',
        'mode_drama' => normalize_drama_mode($student['mode_drama']),
        'pesan_khusus' => $student['pesan_khusus'] ?: 'Teruslah belajar, jaga akhlak, dan jadilah kebanggaan keluarga serta madrasah.',
    ],
]);
