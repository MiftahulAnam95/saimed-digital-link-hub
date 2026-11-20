# SAIMED Digital Link Hub

Aplikasi link hub dan dashboard analytics menggunakan **PHP native 8.1+** dan **MySQL/MariaDB**. Tidak membutuhkan Node.js atau framework; integrasi SSO menggunakan Composer untuk pustaka JWT. Kode aplikasi berada di direktori `saimed-digital-link-hub/`.

## Menjalankan di localhost dengan XAMPP

1. Salin folder `saimed-digital-link-hub` ke folder `htdocs` XAMPP, lalu jalankan **Apache** dan **MySQL** dari XAMPP Control Panel. Pastikan ekstensi PHP `pdo_mysql` aktif.
2. Buka `http://localhost/phpmyadmin`, buat database bernama **`saimed`** dengan karakter `utf8mb4`, pilih database tersebut, lalu gunakan menu **Import** untuk mengimpor `saimed-digital-link-hub/schema.sql`.
3. Pengaturan bawaan aplikasi adalah host `127.0.0.1`, port `3306`, database `saimed`, user `root`, dan password MySQL kosong, sesuai konfigurasi umum XAMPP. Jika berbeda, atur variabel lingkungan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di Apache. Contoh untuk localhost di file `.htaccess` aplikasi:

   ```apache
   SetEnv DB_HOST "127.0.0.1"
   SetEnv DB_PORT "3306"
   SetEnv DB_DATABASE "saimed"
   SetEnv DB_USERNAME "root"
   SetEnv DB_PASSWORD ""
   ```

   Sesuaikan dengan pengaturan MySQL Anda. Jangan simpan password produksi di repositori. `SetEnv` memerlukan `mod_env` dan pengaturan Apache yang mengizinkan `.htaccess`. Restart Apache setelah mengubah konfigurasi.
4. Jalankan `composer install --no-dev` di folder `saimed-digital-link-hub`, lalu siapkan SSO seperti di bawah. Buka `http://localhost/saimed-digital-link-hub/`; halaman admin ada di `?page=admin`. Login admin hanya melalui SSO.

Sebagai alternatif, atur `DATABASE_URL` dalam bentuk `mysql://user:password@127.0.0.1:3306/saimed`. Jika disetel, URL ini menggantikan variabel `DB_*`; hapus atau perbarui URL PostgreSQL lama. URL-encode karakter khusus dalam user dan password. Untuk koneksi terenkripsi ke database jarak jauh, konfigurasi SSL PDO belum disediakan; gunakan koneksi lokal atau jaringan privat yang aman.

Untuk server pengembangan bawaan PHP:

```sh
cd saimed-digital-link-hub
php -S 0.0.0.0:8000 router.php
```

Atur variabel lingkungan pada proses PHP sebelum menjalankan perintah tersebut; `.htaccess` hanya dibaca Apache, bukan server bawaan PHP. Halaman resmi tersedia di `?page=official`. Rute `/admin` dan `/official` juga tersedia melalui router PHP atau Apache dengan `mod_rewrite`. Pada server lain, arahkan permintaan yang bukan aset statis ke `index.php` dan larang akses langsung ke `config/`, `includes/`, `templates/`, `tests/`, `vendor/`, file SQL, serta konfigurasi server.

## Integrasi SSO

Alur mengikuti `test-system`: cookie `jwt_token_test` dibaca dari browser, token HS256 diverifikasi dengan key pada database SSO, lalu akses dicek pada setiap permintaan admin. Hanya role **Super Admin**, perangkat `Online`, akses yang tidak diblokir, token yang belum kedaluwarsa, dan website yang terdaftar dapat masuk. Sesi aplikasi kedaluwarsa setelah 30 menit tanpa aktivitas admin. Tombol masuk dan keluar menggunakan `logout.php?url=...` milik server SSO seperti pada proyek referensi. Logout mengakhiri sesi SSO bersama, bukan hanya sesi aplikasi ini.

Install [Composer](https://getcomposer.org/) dan jalankan `composer install --no-dev` di direktori aplikasi. `vendor/` tidak disertakan di repository. Pastikan PHP memiliki ekstensi `pdo_mysql` dan dependensi Composer tersedia pada server web.

Bawaan lokal memakai `http://localhost/test-sso`, database `test_user` di `127.0.0.1:3306` dengan user `root` dan password kosong, serta website `http://localhost/saimed-digital-link-hub`. Konfigurasikan melalui environment server bila berbeda:

```apache
SetEnv SSO_BASE_URL "http://localhost/test-sso"
SetEnv SSO_APP_URL "http://localhost/saimed-digital-link-hub"
SetEnv SSO_DB_HOST "127.0.0.1"
SetEnv SSO_DB_PORT "3306"
SetEnv SSO_DB_DATABASE "test_user"
SetEnv SSO_DB_USERNAME "root"
SetEnv SSO_DB_PASSWORD ""
```

`SSO_APP_URL` harus sama dengan `website_management.url_website` pada database SSO (tanpa garis miring di akhir); daftarkan aplikasi dan berikan hak akses akun pada SSO terlebih dahulu. Server SSO harus mengirim cookie `jwt_token_test` ke origin/path aplikasi (untuk subdomain, atur domain cookie dengan benar). Untuk produksi gunakan HTTPS, akun database dengan izin baca minimum pada tabel SSO, dan koneksi database privat yang aman; koneksi SSL PDO untuk database jarak jauh belum dikonfigurasi. Jangan menyimpan password atau key di repository. SSO ini mengandalkan skema dan perilaku server pada `test-system`; aplikasi tidak membuat akun atau database SSO. Periksa `logout.php` dan pengaturan cookie di server SSO jika pengalihan login tidak berjalan.

## Data dan migrasi

`schema.sql` membuat tabel MySQL jika belum ada dan memasukkan tautan bawaan hanya ketika tabel tautan kosong. Halaman publik menggunakan tautan bawaan jika database tidak tersedia; pengelolaan tautan dan analytics memerlukan database. Data contoh hanya menggantikan data contoh sebelumnya, bukan data nyata. Waktu analytics disimpan dalam UTC.

**Data PostgreSQL lama tidak dipindahkan otomatis.** Database PostgreSQL lama tidak dihapus atau diubah. Cadangkan dan migrasikan data tautan serta analytics secara terpisah sebelum menggunakan database MySQL baru; ekspor SQL PostgreSQL tidak dapat langsung diimpor sebagai SQL MySQL.

## Pengujian

Jalankan `php tests/smoke.php`, `php tests/sso.php` (setelah Composer install), dan `php -l` pada file PHP. Setelah database MySQL pengujian disiapkan dengan `schema.sql`, `php tests/mysql.php` memeriksa koneksi, penulisan dan pembacaan dalam transaksi yang dibatalkan setelah tes.

## Struktur kode

- `index.php`: pintu masuk, pengaturan sesi, dan pemilihan halaman/API.
- `config/`: konfigurasi aplikasi dan koneksi MySQL melalui PDO.
- `api/`: endpoint pencatatan aktivitas dan pemeriksaan database.
- `includes/`: fungsi bersama serta header dan footer yang disertakan di halaman.
- `admin.php` dan `official.php`: logika halaman administrator dan profil resmi.
- `templates/`: HTML halaman publik, login, dan dashboard.
- `assets/`: CSS, JavaScript untuk interaksi browser, logo, dan ikon SVG.

Struktur PHP prosedural mengikuti pola konfigurasi dan `include` pada proyek referensi. Folder `vendor` dibuat oleh Composer; aset template eksternal tidak diperlukan.
