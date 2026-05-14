<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? 'save_announcement');

    if ($action === 'save_announcement') {
        $date = trim((string) ($_POST['announcement_date'] ?? ''));
        $time = trim((string) ($_POST['announcement_time'] ?? ''));
        $combined = $date . ' ' . $time . ':00';
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $combined, new DateTimeZone(APP_TIMEZONE));

        if ($parsed && $parsed->format('Y-m-d H:i:s') === $combined) {
            save_setting('announcement_datetime', $parsed->format('Y-m-d H:i:s'));
            $message = 'Waktu pengumuman berhasil diperbarui.';
        } else {
            $error = 'Tanggal atau jam tidak valid.';
        }
    }

    if ($action === 'save_drama_durations') {
        $minutesInput = $_POST['drama_minutes'] ?? [];
        $secondsInput = $_POST['drama_seconds'] ?? [];
        $durationsToSave = [];
        $defaults = drama_duration_defaults();

        foreach (drama_modes() as $mode) {
            $fieldKey = drama_duration_setting_key($mode);
            $minuteRaw = trim((string) ($minutesInput[$fieldKey] ?? ''));
            $secondRaw = trim((string) ($secondsInput[$fieldKey] ?? ''));

            if ($minuteRaw === '' && $secondRaw === '') {
                $durationsToSave[$mode] = $defaults[$mode];
                continue;
            }

            if ($minuteRaw !== '' && !ctype_digit($minuteRaw)) {
                $error = 'Durasi menit untuk mode ' . $mode . ' harus berupa angka.';
                break;
            }

            if ($secondRaw !== '' && !ctype_digit($secondRaw)) {
                $error = 'Durasi detik untuk mode ' . $mode . ' harus berupa angka.';
                break;
            }

            $minutes = $minuteRaw === '' ? 0 : (int) $minuteRaw;
            $seconds = $secondRaw === '' ? 0 : (int) $secondRaw;
            $totalSeconds = ($minutes * 60) + $seconds;

            if ($minutes > 30 || $seconds > 59 || $totalSeconds > 1800) {
                $error = 'Durasi maksimal untuk setiap mode adalah 30 menit.';
                break;
            }

            if ($totalSeconds < 1) {
                $error = 'Durasi minimal untuk mode ' . $mode . ' adalah 1 detik.';
                break;
            }

            $durationsToSave[$mode] = $totalSeconds;
        }

        if ($error === '') {
            foreach ($durationsToSave as $mode => $totalSeconds) {
                save_setting(drama_duration_setting_key($mode), (string) $totalSeconds);
            }
            $message = 'Durasi mode drama berhasil diperbarui.';
        }
    }
}

$announcement = announcement_datetime();
$dramaDurations = drama_durations_seconds();

admin_header('Pengaturan', 'settings');
?>
<div class="page-heading">
    <div>
        <p class="admin-eyebrow">Pengaturan</p>
        <h1>Waktu Pengumuman</h1>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<section class="admin-card mb-4">
    <h2>Atur Jadwal Pengumuman</h2>
    <p class="text-muted">Zona waktu aplikasi menggunakan Asia/Makassar atau WITA. Siswa akan melihat countdown sampai jadwal ini tiba.</p>
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_announcement">
        <div class="col-md-4">
            <label class="form-label" for="announcement_date">Tanggal</label>
            <input class="form-control form-control-lg" type="date" name="announcement_date" id="announcement_date" required value="<?= h($announcement->format('Y-m-d')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="announcement_time">Jam WITA</label>
            <input class="form-control form-control-lg" type="time" name="announcement_time" id="announcement_time" required value="<?= h($announcement->format('H:i')) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-admin-primary btn-lg w-100" type="submit">Simpan Jadwal</button>
        </div>
    </form>
    <div class="current-time-box">
        <span>Jadwal aktif saat ini</span>
        <strong><?= h(format_date_id($announcement->format('Y-m-d'))) ?> pukul <?= h($announcement->format('H:i')) ?> WITA</strong>
    </div>
</section>

<section class="admin-card">
    <h2>Atur Durasi Mode Drama</h2>
    <p class="text-muted">Isi durasi loading untuk tiap mode dalam menit dan detik. Maksimal 30 menit per mode. Jika dikosongkan, sistem memakai durasi default.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_drama_durations">
        <div class="duration-settings">
            <?php foreach (drama_modes() as $mode): ?>
                <?php
                $totalSeconds = (int) ($dramaDurations[$mode] ?? drama_duration_defaults()[$mode]);
                $minutes = intdiv($totalSeconds, 60);
                $seconds = $totalSeconds % 60;
                $fieldKey = drama_duration_setting_key($mode);
                ?>
                <div class="duration-row">
                    <div class="duration-mode">
                        <strong><?= h($mode) ?></strong>
                        <span>Durasi saat ini: <?= (int) $minutes ?> menit <?= (int) $seconds ?> detik</span>
                    </div>
                    <div class="duration-inputs">
                        <label>
                            <span>Menit</span>
                            <input class="form-control" type="number" name="drama_minutes[<?= h($fieldKey) ?>]" min="0" max="30" value="<?= (int) $minutes ?>">
                        </label>
                        <label>
                            <span>Detik</span>
                            <input class="form-control" type="number" name="drama_seconds[<?= h($fieldKey) ?>]" min="0" max="59" value="<?= (int) $seconds ?>">
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3">
            <button class="btn btn-admin-primary btn-lg" type="submit">Simpan Durasi Drama</button>
        </div>
    </form>
</section>
<?php admin_footer(); ?>
