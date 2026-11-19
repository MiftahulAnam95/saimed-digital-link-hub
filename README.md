# SAIMED Digital Link Hub

Aplikasi link hub dan dashboard analytics berbasis **PHP 8.1+** dan PostgreSQL. Tidak membutuhkan Node.js atau Composer. Kode aplikasi berada di direktori `saimed-digital-link-hub/`.

## Persiapan

1. Siapkan PHP 8.1 atau lebih baru dengan ekstensi `pdo_pgsql` dan database PostgreSQL.
2. Jalankan `psql "$DATABASE_URL" -f saimed-digital-link-hub/schema.sql` sekali. Skema kompatibel dengan tabel `links` dan `analytics_events` versi sebelumnya; data yang sudah ada tidak dihapus. Tautan bawaan hanya dimasukkan jika tabel masih kosong.
3. Atur variabel lingkungan `DATABASE_URL` (contoh: `postgresql://user:password@localhost:5432/saimed`) dan `ADMIN_PASSWORD` di lingkungan server. Jangan menggunakan kata sandi contoh di produksi. Tanpa konfigurasi tersebut login admin tidak dapat digunakan. Gunakan HTTPS untuk produksi.
4. Jalankan untuk pengembangan:

   ```sh
   cd saimed-digital-link-hub
   php -S 0.0.0.0:8000 router.php
   ```

Buka `/` untuk halaman publik dan `/?page=admin` untuk dashboard. Halaman resmi tersedia di `/?page=official`. Rute lama `/admin` dan `/official` juga tersedia melalui router PHP atau Apache dengan `mod_rewrite` dan `.htaccess`. Pada server lain, arahkan permintaan yang bukan aset statis ke `index.php` dan larang akses langsung ke direktori `includes/`, `templates/`, serta `tests/`. Pastikan file SQL dan konfigurasi server tidak dapat diunduh publik.

Halaman publik menggunakan tautan bawaan ketika database tidak tersedia; perubahan tautan dan pencatatan analytics memerlukan database. Admin dapat menambah, mengubah, mengurutkan, mengaktifkan, menghapus tautan, melihat statistik dan log, serta memuat data contoh. Data contoh dapat diperbarui tanpa menghapus data nyata.

## Pengujian dan kompatibilitas

Jalankan `php tests/smoke.php` untuk pemeriksaan dasar, dan `php -l index.php` (serta file PHP lainnya) untuk memeriksa sintaks. Versi PHP memakai sesi PHP, sehingga sesi login dari versi Next.js tidak digunakan lagi. API admin Next.js digantikan oleh formulir PHP; endpoint tracking dan health tersedia di `?api=track` dan `?api=health` (juga `/api/track` dan `/api/health` dengan rewrite).

## Struktur kode

- `index.php`: pintu masuk, pengaturan sesi, dan pemilihan halaman/API.
- `app.php` dan `includes/`: konfigurasi serta fungsi database, tautan, keamanan, analytics, dan tampilan bersama.
- `admin.php` dan `official.php`: logika halaman administrator dan profil resmi.
- `templates/`: HTML halaman publik, login, dan dashboard, menggunakan sintaks PHP biasa.
- `assets/`: CSS, JavaScript untuk interaksi browser, logo, dan ikon SVG.

Aplikasi menggunakan PHP native dengan PDO, tanpa framework atau dependensi Composer. JavaScript hanya digunakan untuk interaksi di browser dan pencatatan aktivitas.
