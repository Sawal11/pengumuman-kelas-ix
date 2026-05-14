# Portal Kelulusan MTsN 1 Pohuwato

Aplikasi web PHP Native + MySQL/MariaDB untuk pengumuman kelulusan Kelas IX MTs Negeri 1 Pohuwato. Aplikasi siap diunggah ke hosting cPanel atau subdomain seperti `pengumuman.mtssapo.sch.id`.

## Fitur Utama

- Halaman siswa dengan countdown sebelum waktu pengumuman dibuka.
- Form cek hasil menggunakan NISN dan tanggal lahir.
- Hasil akhir selalu menampilkan status `LULUS`.
- Mode drama aman: Normal, Drama Ringan, Drama Sedang, Drama Lucu, Drama Super Tegang.
- Durasi mode drama: Normal 10 detik, Drama Ringan 30 detik, Drama Sedang 60 detik, Drama Lucu 90 detik, Drama Super Tegang 180 detik.
- Durasi mode drama dapat diubah dari menu **Pengaturan** admin dengan input menit + detik, maksimal 30 menit per mode.
- Animasi loading, confetti, tombol WhatsApp, musik latar, suara tepuk tangan, dan cetak/unduh PDF via browser print.
- Panel admin untuk dashboard, data siswa, import CSV/XLSX fallback, export CSV, pengaturan jadwal, log akses, dan ganti password.
- Tampilan clean premium terang dengan dominan putih, hijau Kemenag, aksen gold tipis, dan animasi ringan.
- Keamanan dasar dengan session admin, PDO prepared statement, CSRF form admin, dan escaping output.

## Struktur Folder

```text
.
├── .htaccess
├── admin/
│   ├── change_password.php
│   ├── dashboard.php
│   ├── import.php
│   ├── login.php
│   ├── logs.php
│   ├── logout.php
│   ├── settings.php
│   └── students.php
├── assets/
│   ├── audio/
│   │   ├── applause.mp3
│   │   ├── graduation-music.mp3
│   │   └── README.md
│   ├── css/
│   │   ├── admin.css
│   │   └── style.css
│   ├── img/
│   │   └── README.md
│   └── js/
│       ├── admin.js
│       └── app.js
├── includes/
│   ├── admin_auth.php
│   ├── admin_layout.php
│   └── helpers.php
├── check_result.php
├── config.php
├── database.sql
├── index.php
└── README.md
```

## Persyaratan Hosting

- PHP 8.0 atau lebih baru.
- MySQL/MariaDB.
- Ekstensi PHP PDO MySQL aktif.
- Untuk import XLSX, aplikasi mencoba memakai PhpSpreadsheet jika tersedia. Jika belum ada, aplikasi memakai pembaca XLSX sederhana sebagai fallback untuk template standar. Jika hosting tidak mendukung ZIP/SimpleXML, gunakan Template CSV.

## Cara Membuat Database di cPanel

1. Masuk ke cPanel.
2. Buka menu **MySQL Databases**.
3. Buat database baru, misalnya `username_portal_kelulusan`.
4. Buat user database dan password.
5. Tambahkan user ke database dengan hak akses **All Privileges**.
6. Catat nama database, username database, dan password.

## Cara Import `database.sql`

1. Masuk ke cPanel.
2. Buka **phpMyAdmin**.
3. Pilih database yang sudah dibuat.
4. Klik tab **Import**.
5. Pilih file `database.sql`.
6. Klik **Go/Kirim**.

Catatan: file `database.sql` versi ini memang tidak memakai perintah `CREATE DATABASE` dan `USE`, karena pada cPanel database biasanya sudah dibuat lebih dulu dan dipilih langsung di phpMyAdmin. Ini mencegah error `#1044 - Access denied` saat import.

## Cara Mengatur `config.php`

Buka file `config.php`, lalu ubah bagian berikut sesuai data cPanel:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nama_database_cpanel');
define('DB_USER', 'username_database_cpanel');
define('DB_PASS', 'password_database_cpanel');
```

Jika aplikasi dipasang di subdomain, `APP_BASE_URL` boleh dikosongkan. Jika ingin URL absolut, isi seperti:

```php
define('APP_BASE_URL', 'https://pengumuman.mtssapo.sch.id');
```

Zona waktu sudah diatur ke `Asia/Makassar` atau WITA.

## Cara Upload ke Subdomain cPanel

1. Buat subdomain di cPanel, misalnya `pengumuman.mtssapo.sch.id`.
2. Buka **File Manager**.
3. Masuk ke folder document root subdomain tersebut.
4. Upload file ZIP aplikasi.
5. Extract ZIP di folder subdomain.
6. Pastikan `index.php`, `config.php`, folder `admin`, `assets`, dan `includes` berada langsung di document root subdomain.
7. Atur `config.php`.
8. Import `database.sql` ke database.
9. Buka URL subdomain di browser.

## Login Admin Default

- URL: `/admin/login.php`
- Username: `admin`
- Password: `admin123`

Segera ganti password melalui menu **Password** setelah login pertama.

## Mengatur Waktu Pengumuman

1. Login admin.
2. Buka menu **Pengaturan**.
3. Pilih tanggal dan jam pengumuman.
4. Klik **Simpan Jadwal**.

Sebelum waktu tersebut tiba, siswa hanya melihat countdown. Setelah waktu tiba, form cek hasil akan tampil.

## Mengatur Durasi Mode Drama

1. Login admin.
2. Buka menu **Pengaturan**.
3. Pada bagian **Atur Durasi Mode Drama**, isi menit dan detik untuk tiap mode.
4. Klik **Simpan Durasi Drama**.

Durasi maksimal setiap mode adalah 30 menit. Jika menit dan detik dikosongkan, sistem memakai default: Normal 10 detik, Drama Ringan 30 detik, Drama Sedang 60 detik, Drama Lucu 90 detik, Drama Super Tegang 180 detik.

## Cara Import Data Siswa

1. Login admin.
2. Buka menu **Import**.
3. Klik **Template Excel** atau **Template CSV** untuk mengunduh format pengisian.
4. Isi data siswa pada template. Untuk Excel, gunakan sheet `data_siswa`; sheet `mode_drama` hanya berisi daftar mode yang valid.
5. Upload file CSV atau XLSX.
6. Centang opsi **Perbarui data jika NISN sudah ada** jika ingin update otomatis.
7. Klik **Import Data**.

Format CSV:

```csv
nama,nisn,tanggal_lahir,kelas,status_kelulusan,mode_drama,pesan_khusus
Ahmad Fulan,1234567890,2011-05-14,IX A,LULUS,Drama Lucu,"Selamat, teruslah berprestasi."
```

Ketentuan:

- `nama` wajib diisi.
- `nisn` wajib diisi dan unik.
- `tanggal_lahir` memakai format `YYYY-MM-DD`.
- `status_kelulusan` boleh kosong, sistem tetap mengisi `LULUS`.
- Mode drama yang valid: `Normal`, `Drama Ringan`, `Drama Sedang`, `Drama Lucu`, `Drama Super Tegang`.

## Audio

Folder `assets/audio/` berisi placeholder:

- `graduation-music.mp3`
- `applause.mp3`

Ganti kedua file tersebut dengan file MP3 asli jika ingin musik latar dan tepuk tangan terdengar. Jika file audio tidak valid atau belum diganti, aplikasi tetap berjalan normal tanpa error.

## Catatan Keamanan

- Jangan gunakan password admin default setelah aplikasi dipublikasikan.
- Jangan tampilkan file backup database di folder publik.
- Pastikan permission file/folder mengikuti standar cPanel.
- Gunakan HTTPS pada subdomain pengumuman.
