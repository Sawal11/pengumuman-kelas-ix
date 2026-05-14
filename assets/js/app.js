(function () {
    const pageLoader = document.getElementById('pageLoader');
    const hidePageLoader = () => {
        if (pageLoader) {
            pageLoader.classList.add('is-hidden');
        }
    };

    if (document.readyState === 'complete') {
        window.setTimeout(hidePageLoader, 250);
    } else {
        window.addEventListener('load', () => window.setTimeout(hidePageLoader, 250));
    }
    window.setTimeout(hidePageLoader, 2500);

    const config = window.APP_CONFIG || {};
    const mainCard = document.querySelector('.main-card');
    const countdownPanel = document.getElementById('countdownPanel');
    const formPanel = document.getElementById('formPanel');
    const loadingPanel = document.getElementById('loadingPanel');
    const resultPanel = document.getElementById('resultPanel');
    const resultForm = document.getElementById('resultForm');
    const alertBox = document.getElementById('alertBox');
    const loadingText = document.getElementById('loadingText');
    const loadingProgress = document.getElementById('loadingProgress');
    const musicToggle = document.getElementById('musicToggle');
    const bgMusic = document.getElementById('bgMusic');
    const applauseAudio = document.getElementById('applauseAudio');

    const baseSteps = [
        'Mencocokkan data peserta didik...',
        'Memverifikasi nilai akhir...',
        'Memeriksa status kelulusan...',
        'Data Anda masuk pemeriksaan khusus. Mohon tunggu proses verifikasi...',
        'Menyiapkan hasil pengumuman...'
    ];

    const dramaSteps = {
        'Normal': [],
        'Drama Ringan': [
            'Mohon tunggu, data Anda sedang diverifikasi.'
        ],
        'Drama Sedang': [
            'Sistem menemukan catatan khusus pada data Anda.',
            'Mohon jangan panik, data sedang diverifikasi ulang.'
        ],
        'Drama Lucu': [
            'Waduh... sistem sempat bingung membaca data Anda.',
            'Ternyata bukan karena bermasalah.',
            'Tapi karena Anda terlalu spesial untuk diumumkan biasa-biasa saja.'
        ],
        'Drama Super Tegang': [
            'Data Anda masuk pemeriksaan khusus.',
            'Mohon tunggu, sistem sedang melakukan verifikasi akhir.',
            'Status belum dapat ditampilkan sebelum proses validasi selesai.',
            'Validasi selesai.',
            'Catatan khusus ditemukan: Anda adalah bagian dari generasi hebat MTsN 1 Pohuwato.'
        ]
    };

    const defaultDramaTotalDuration = {
        'Normal': 10000,
        'Drama Ringan': 30000,
        'Drama Sedang': 60000,
        'Drama Lucu': 90000,
        'Drama Super Tegang': 180000
    };
    const dramaTotalDuration = Object.assign({}, defaultDramaTotalDuration, config.dramaDurations || {});

    function showPanel(panel) {
        [countdownPanel, formPanel, loadingPanel, resultPanel].forEach((item) => {
            if (item) {
                item.classList.add('d-none');
            }
        });

        if (panel) {
            panel.classList.remove('d-none');
        }
    }

    function updateCountdown() {
        if (!countdownPanel) {
            return;
        }

        const target = new Date(config.announcementTime);
        const now = new Date();
        const diff = target.getTime() - now.getTime();

        if (diff <= 0) {
            showPanel(formPanel);
            if (mainCard) {
                mainCard.dataset.open = '1';
            }
            return;
        }

        const secondsTotal = Math.floor(diff / 1000);
        const days = Math.floor(secondsTotal / 86400);
        const hours = Math.floor((secondsTotal % 86400) / 3600);
        const minutes = Math.floor((secondsTotal % 3600) / 60);
        const seconds = secondsTotal % 60;

        setText('days', days);
        setText('hours', hours);
        setText('minutes', minutes);
        setText('seconds', seconds);
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = String(value).padStart(2, '0');
        }
    }

    function showAlert(message) {
        if (!alertBox) {
            return;
        }

        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }
    }

    async function runDrama(mode) {
        const safeMode = dramaSteps[mode] ? mode : 'Normal';
        const steps = [
            baseSteps[0],
            baseSteps[1],
            baseSteps[2],
            ...(dramaSteps[safeMode] || []),
            baseSteps[4]
        ];
        const totalDuration = dramaTotalDuration[safeMode] || dramaTotalDuration.Normal;

        showPanel(loadingPanel);
        loadingPanel.dataset.mode = safeMode;
        loadingProgress.style.width = '0%';
        loadingProgress.style.transition = 'none';
        loadingText.textContent = steps[0];
        loadingText.classList.remove('is-changing');

        return new Promise((resolve) => {
            const startedAt = performance.now();
            let currentStep = 0;

            function setStep(index) {
                if (index === currentStep) {
                    return;
                }

                currentStep = index;
                loadingText.classList.remove('is-changing');
                void loadingText.offsetWidth;
                loadingText.textContent = steps[index];
                loadingText.classList.add('is-changing');
            }

            function frame(now) {
                const elapsed = now - startedAt;
                const progress = Math.min(elapsed / totalDuration, 1);
                const stepIndex = Math.min(Math.floor(progress * steps.length), steps.length - 1);

                setStep(stepIndex);
                loadingProgress.style.width = `${Math.round(progress * 100)}%`;

                if (progress < 1) {
                    window.requestAnimationFrame(frame);
                    return;
                }

                loadingProgress.style.width = '100%';
                window.setTimeout(resolve, 650);
            }

            window.requestAnimationFrame(frame);
        });
    }

    function fillResult(student) {
        document.getElementById('resultNama').textContent = student.nama || '-';
        document.getElementById('resultNisn').textContent = student.nisn || '-';
        document.getElementById('resultTanggalLahir').textContent = student.tanggal_lahir || '-';
        document.getElementById('resultKelas').textContent = student.kelas || '-';
        document.getElementById('resultStatus').textContent = 'LULUS';
        document.getElementById('resultPesan').textContent = student.pesan_khusus || 'Teruslah belajar dan berprestasi.';

        const shareText = `Alhamdulillah, saya ${student.nama} dari kelas ${student.kelas} dinyatakan LULUS dari MTs Negeri 1 Pohuwato Tahun Pelajaran ${config.academicYear}. Terima kasih kepada Bapak/Ibu Guru dan orang tua atas doa dan bimbingannya.`;
        document.getElementById('waShare').href = `https://wa.me/?text=${encodeURIComponent(shareText)}`;
    }

    function celebrate() {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 140,
                spread: 78,
                origin: { y: 0.68 }
            });
            window.setTimeout(() => {
                confetti({
                    particleCount: 90,
                    spread: 100,
                    origin: { y: 0.62 }
                });
            }, 450);
        }

        if (applauseAudio) {
            applauseAudio.currentTime = 0;
            applauseAudio.play().catch(() => {});
        }
    }

    if (resultForm) {
        resultForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideAlert();

            const formData = new FormData(resultForm);

            try {
                const response = await fetch('check_result.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const payload = await response.json();

                if (!payload.success) {
                    showAlert(payload.message || 'Data tidak ditemukan. Pastikan NISN dan tanggal lahir sesuai data madrasah.');
                    return;
                }

                await runDrama(payload.student.mode_drama);
                fillResult(payload.student);
                showPanel(resultPanel);
                celebrate();
            } catch (error) {
                showAlert('Sistem belum dapat memproses permintaan. Silakan coba beberapa saat lagi.');
            }
        });
    }

    const checkAgain = document.getElementById('checkAgain');
    if (checkAgain) {
        checkAgain.addEventListener('click', () => {
            resultForm.reset();
            hideAlert();
            showPanel(formPanel);
        });
    }

    if (musicToggle && bgMusic) {
        musicToggle.addEventListener('click', async () => {
            if (bgMusic.paused) {
                try {
                    await bgMusic.play();
                    musicToggle.textContent = 'Nonaktifkan Musik Latar';
                } catch (error) {
                    musicToggle.textContent = 'Musik Tidak Tersedia';
                }
            } else {
                bgMusic.pause();
                musicToggle.textContent = 'Aktifkan Musik Latar';
            }
        });
    }

    if (!config.isOpen) {
        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    }
})();
