<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$announcement = announcement_datetime();
$isOpen = announcement_is_open();
$announcementIso = $announcement->format(DateTimeInterface::ATOM);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(APP_NAME) ?></title>
    <meta name="description" content="Portal pengumuman kelulusan Kelas IX MTs Negeri 1 Pohuwato.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) (@filemtime(__DIR__ . '/assets/css/style.css') ?: time()) ?>">
</head>
<body>
<div id="pageLoader" class="page-loader" aria-hidden="true">
    <div class="page-loader-card">
        <img src="assets/img/logo-kemenag.png" alt="">
        <div class="page-loader-ring"></div>
        <span>Memuat halaman...</span>
    </div>
</div>

<div class="site-bg">
    <span class="glow glow-a"></span>
    <span class="glow glow-b"></span>
</div>

<main class="student-shell">
    <section class="brand-panel">
        <img class="school-mark" src="assets/img/logo-kemenag.png" alt="Logo Kementerian Agama">
        <p class="eyebrow">Pengumuman Kelulusan Kelas IX</p>
        <h1>Portal Kelulusan MTsN 1 Pohuwato</h1>
        <p class="lead-copy">
            Tahun Pelajaran <?= h(APP_YEAR) ?>. Siapkan NISN dan tanggal lahir Anda untuk membuka hasil pengumuman resmi.
        </p>
    </section>

    <section class="main-card" data-announcement="<?= h($announcementIso) ?>" data-open="<?= $isOpen ? '1' : '0' ?>">
        <?php if (!$isOpen): ?>
            <div id="countdownPanel" class="countdown-panel">
                <p class="section-label">Mohon Menunggu</p>
                <h2>Pengumuman akan dibuka dalam:</h2>
                <div class="countdown-grid" aria-live="polite">
                    <div class="count-box">
                        <strong id="days">0</strong>
                        <span>Hari</span>
                    </div>
                    <div class="count-box">
                        <strong id="hours">0</strong>
                        <span>Jam</span>
                    </div>
                    <div class="count-box">
                        <strong id="minutes">0</strong>
                        <span>Menit</span>
                    </div>
                    <div class="count-box">
                        <strong id="seconds">0</strong>
                        <span>Detik</span>
                    </div>
                </div>
                <p class="helper-text">
                    Pengumuman Kelulusan Kelas IX MTs Negeri 1 Pohuwato akan dibuka pada
                    <strong><?= h(format_date_id($announcement->format('Y-m-d'))) ?> pukul <?= h($announcement->format('H:i')) ?> WITA</strong>.
                </p>
                <p class="helper-text muted">Siapkan NISN dan tanggal lahir Anda. Mohon menunggu hingga waktu pengumuman resmi dibuka.</p>
            </div>
        <?php endif; ?>

        <div id="formPanel" class="form-panel <?= $isOpen ? '' : 'd-none' ?>">
            <p class="section-label">Cek Hasil Kelulusan</p>
            <h2>Buka Hasil Anda</h2>
            <p class="helper-text">Masukkan NISN dan tanggal lahir sesuai data madrasah.</p>

            <div id="alertBox" class="alert alert-danger d-none" role="alert"></div>

            <form id="resultForm" novalidate>
                <div class="mb-3">
                    <label for="nisn" class="form-label">NISN</label>
                    <input type="text" inputmode="numeric" class="form-control form-control-lg" id="nisn" name="nisn" required placeholder="Contoh: 1234567890">
                </div>
                <div class="mb-4">
                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control form-control-lg" id="tanggal_lahir" name="tanggal_lahir" required>
                </div>
                <button type="submit" class="btn btn-gold w-100 btn-lg">
                    Lihat Hasil Kelulusan
                </button>
            </form>

            <div class="audio-toggle">
                <button type="button" id="musicToggle" class="btn btn-outline-light btn-sm">
                    Aktifkan Musik Latar
                </button>
            </div>
        </div>

        <div id="loadingPanel" class="loading-panel d-none" aria-live="polite">
            <div class="spinner-ring"></div>
            <h2>Memproses Data</h2>
            <div class="progress shimmer-progress" role="progressbar" aria-label="Proses verifikasi">
                <div id="loadingProgress" class="progress-bar"></div>
            </div>
            <p id="loadingText" class="loading-text">Mencocokkan data peserta didik...</p>
        </div>

        <div id="resultPanel" class="result-panel d-none">
            <div class="result-ribbon">SELAMAT!</div>
            <h2>Anda Dinyatakan LULUS</h2>
            <p class="result-message">
                Kelulusan ini bukan akhir, melainkan awal dari perjalanan baru. Teruslah belajar, jaga akhlak, dan jadilah kebanggaan orang tua, madrasah, agama, bangsa, dan negara.
            </p>

            <div class="student-result-card" id="printCard">
                <div class="print-header">
                    <img class="print-logo" src="assets/img/logo-kemenag.png" alt="Logo Kementerian Agama">
                    <div>
                        <strong>MTs Negeri 1 Pohuwato</strong>
                        <span>Kartu Hasil Kelulusan Tahun Pelajaran <?= h(APP_YEAR) ?></span>
                    </div>
                </div>
                <dl class="result-data">
                    <div>
                        <dt>Nama</dt>
                        <dd id="resultNama">-</dd>
                    </div>
                    <div>
                        <dt>NISN</dt>
                        <dd id="resultNisn">-</dd>
                    </div>
                    <div>
                        <dt>Tanggal Lahir</dt>
                        <dd id="resultTanggalLahir">-</dd>
                    </div>
                    <div>
                        <dt>Kelas</dt>
                        <dd id="resultKelas">-</dd>
                    </div>
                    <div class="status-row">
                        <dt>Status Kelulusan</dt>
                        <dd id="resultStatus">LULUS</dd>
                    </div>
                </dl>
                <div class="special-message" id="resultPesan">Teruslah belajar dan berprestasi.</div>
            </div>

            <div class="result-actions">
                <a id="waShare" class="btn btn-success btn-lg" target="_blank" rel="noopener">Bagikan ke WhatsApp</a>
                <button type="button" class="btn btn-outline-light btn-lg" onclick="window.print()">Unduh PDF</button>
                <button type="button" id="checkAgain" class="btn btn-link text-white">Cek Data Lain</button>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <span>Portal Kelulusan MTsN 1 Pohuwato</span>
    <span>&copy; 2026 MTs Negeri 1 Pohuwato</span>
</footer>

<audio id="bgMusic" loop preload="none">
    <source src="assets/audio/graduation-music.mp3" type="audio/mpeg">
</audio>
<audio id="applauseAudio" preload="none">
    <source src="assets/audio/applause.mp3" type="audio/mpeg">
</audio>

<script>
    window.APP_CONFIG = {
        announcementTime: <?= json_encode($announcementIso) ?>,
        isOpen: <?= $isOpen ? 'true' : 'false' ?>,
        academicYear: <?= json_encode(APP_YEAR) ?>,
        dramaDurations: <?= json_encode(drama_durations_milliseconds(), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="assets/js/app.js?v=<?= (int) (@filemtime(__DIR__ . '/assets/js/app.js') ?: time()) ?>"></script>
</body>
</html>
