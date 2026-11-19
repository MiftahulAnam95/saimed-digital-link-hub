# SAIMED Digital Link Hub

Aplikasi link hub dan dashboard analytics menggunakan **PHP native 8.1+** dan **MySQL/MariaDB**. Tidak membutuhkan Node.js, Composer, atau framework. Kode aplikasi berada di direktori `saimed-digital-link-hub/`.

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
   SetEnv ADMIN_PASSWORD "ganti-password-admin"
   ```

   Sesuaikan dengan pengaturan MySQL Anda; **password admin dan password MySQL adalah dua hal yang berbeda**. Jangan simpan password produksi di repositori. `SetEnv` memerlukan `mod_env` dan pengaturan Apache yang mengizinkan `.htaccess`. Restart Apache setelah mengubah konfigurasi.
4. Buka `http://localhost/saimed-digital-link-hub/` (sesuaikan nama folder Anda). Halaman admin ada di `?page=admin`; login menggunakan `ADMIN_PASSWORD` yang Anda tentukan. Tanpa konfigurasi itu tidak ada password admin bawaan.

Sebagai alternatif, atur `DATABASE_URL` dalam bentuk `mysql://user:password@127.0.0.1:3306/saimed`. Jika disetel, URL ini menggantikan variabel `DB_*`; hapus atau perbarui URL PostgreSQL lama. URL-encode karakter khusus dalam user dan password. Untuk koneksi terenkripsi ke database jarak jauh, konfigurasi SSL PDO belum disediakan; gunakan koneksi lokal atau jaringan privat yang aman.

Untuk server pengembangan bawaan PHP:

```sh
cd saimed-digital-link-hub
php -S 0.0.0.0:8000 router.php
```

Atur variabel lingkungan pada proses PHP sebelum menjalankan perintah tersebut; `.htaccess` hanya dibaca Apache, bukan server bawaan PHP. Halaman resmi tersedia di `?page=official`. Rute `/admin` dan `/official` juga tersedia melalui router PHP atau Apache dengan `mod_rewrite`. Pada server lain, arahkan permintaan yang bukan aset statis ke `index.php` dan larang akses langsung ke `includes/`, `templates/`, `tests/`, file SQL, serta konfigurasi server.

## Data dan migrasi

`schema.sql` membuat tabel MySQL jika belum ada dan memasukkan tautan bawaan hanya ketika tabel tautan kosong. Halaman publik menggunakan tautan bawaan jika database tidak tersedia; pengelolaan tautan dan analytics memerlukan database. Data contoh hanya menggantikan data contoh sebelumnya, bukan data nyata. Waktu analytics disimpan dalam UTC.

**Data PostgreSQL lama tidak dipindahkan otomatis.** Database PostgreSQL lama tidak dihapus atau diubah. Cadangkan dan migrasikan data tautan serta analytics secara terpisah sebelum menggunakan database MySQL baru; ekspor SQL PostgreSQL tidak dapat langsung diimpor sebagai SQL MySQL.

## Pengujian

Jalankan `php tests/smoke.php` dan `php -l` pada file PHP. Setelah database MySQL pengujian disiapkan dengan `schema.sql`, `php tests/mysql.php` memeriksa koneksi, penulisan dan pembacaan dalam transaksi yang dibatalkan setelah tes.

## Struktur kode

- `index.php`: pintu masuk, pengaturan sesi, dan pemilihan halaman/API.
- `app.php` dan `includes/`: konfigurasi serta fungsi database, tautan, keamanan, analytics, dan tampilan bersama.
- `admin.php` dan `official.php`: logika halaman administrator dan profil resmi.
- `templates/`: HTML halaman publik, login, dan dashboard.
- `assets/`: CSS, JavaScript untuk interaksi browser, logo, dan ikon SVG.
