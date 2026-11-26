# SAIMED Digital Link Hub

## Struktur proyek dan audit terhadap test-system

Struktur mengikuti pola `test-system` pada bagian yang sepadan, tanpa mengganti fungsi Link Hub:

- `akses.php` di root merangkum logika autentikasi dan otorisasi SSO, status kembali login, timeout, URL login/logout dan informasi profil. `index.php` memuatnya untuk menangani kembali dari SSO, sedangkan `admin.php` memeriksa akses sebelum memproses tindakan, ekspor atau dashboard.
- `config/koneksi_user.php` menangani database pengguna SSO (nama mengikuti referensi); `config/koneksi.php` tetap khusus untuk database Link Hub dan analytics. Semua konfigurasi environment `SSO_DB_*` tetap sama.
- `assets/css/`, `assets/js/`, `assets/images/` dan `favicon.ico` di root mengelompokkan aset berdasarkan jenis seperti referensi. URL aset versi lama tetap didukung oleh `.htaccess` dan `router.php` untuk HTML yang masih berada di cache.
- `index.php` tetap menjadi pintu masuk publik dan routing, karena Link Hub memiliki beranda publik dan endpoint analytics yang tidak ada di test-system. `admin.php` adalah controller dashboard; `app.php` memuat fungsi umum. `includes/` dan `templates/` dipertahankan untuk fungsi analytics, tindakan dan tampilan yang memang khusus aplikasi ini; folder `template/` test-system berisi aset tema pihak ketiga, bukan template PHP yang setara.
- `api/`, `tests/`, `schema.sql`, dan `vendor/` tetap pada tempatnya karena memiliki fungsi yang berbeda dari kode akses SSO.

Implementasi SSO tidak disalin mentah dari `test-system`: validasi Super Admin, prepared statements, verifikasi token, URL AES-GCM, serta perlindungan timeout tetap dipertahankan. Saat deployment, unggah seluruh struktur baru dan aturan `.htaccess` sekaligus; hapus file lama yang tidak lagi dipakai setelah mencadangkan aplikasi. Pastikan server selain Apache menerapkan pembatasan folder internal dan kompatibilitas URL aset yang setara. Struktur ulang ini tidak membutuhkan migrasi database atau perubahan variabel environment.

## Database Link Hub dan analytics

Database aplikasi bawaan adalah **`test_saimed_link`** di MySQL lokal (`127.0.0.1:3306`, pengguna `root`, password kosong). Database ini menyimpan tautan dan analytics, terpisah dari database pengguna SSO `test_user`.

Untuk instalasi baru, buat database `test_saimed_link` di phpMyAdmin, pilih database tersebut, lalu impor `schema.sql`. Jika sebelumnya memakai database `saimed` dan ingin mempertahankan data, cadangkan database lama terlebih dahulu. Hentikan sementara pencatatan/perubahan data, ekspor struktur dan data dari database lama, lalu impor ke `test_saimed_link`. Pastikan file ekspor tidak memaksa `CREATE DATABASE` atau `USE` ke nama lama. Verifikasi tabel, relasi dan jumlah data sebelum kembali menjalankan aplikasi. Jangan hapus database lama sampai hasilnya dipastikan benar; perubahan kode ini tidak memindahkan atau menghapus data secara otomatis. Untuk database baru yang sudah diimpor dari database lama, tidak perlu mengimpor `schema.sql` lagi.

Koneksi Link Hub dan SSO menggunakan MySQLi, bukan PDO. Nilai localhost ditulis di `config/koneksi.php` dan `config/koneksi_user.php`; koneksi baru dibuka saat diperlukan agar halaman publik masih dapat menampilkan fallback ketika database bermasalah. Kesalahan tidak ditampilkan secara rinci kepada pengunjung. Query yang menerima input menggunakan prepared statement MySQLi dengan placeholder `?`.

Nama dan koneksi dapat diatur melalui `DB_DATABASE`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, dan `DB_PASSWORD`. Jika `DATABASE_URL` disetel, nilai URL tersebut **menggantikan** konfigurasi `DB_*`, termasuk nama database. Bila sebelumnya ada `DB_DATABASE=saimed` atau `DATABASE_URL` yang menunjuk ke `saimed`, perbarui pengaturan server tersebut dan restart Apache. Koneksi SSO `SSO_DB_*` tidak berubah.

## Konfigurasi SSO

Login admin menggunakan cookie `jwt_token_test` dan database pengguna SSO. Akun harus berperan **Super Admin**, memiliki akses ke website ini, tidak diblokir, dan memiliki token serta status perangkat yang valid. Tidak ada login password admin lokal.

Aplikasi mengirim URL tujuan yang dienkripsi AES-256-GCM ke `logout.php` pada server `test-sso`. Formatnya adalah base64url dari nonce 12 byte, tag 16 byte, dan ciphertext, sesuai fungsi `decrypt()` di server tersebut.

Pastikan PHP 8.1 atau lebih baru memiliki ekstensi `openssl` dan `mysqli` dengan driver mysqlnd (untuk `get_result()`), serta dependensi Composer tersedia. XAMPP umumnya sudah menyertakan mysqlnd. Vendor bawaan repository memiliki pemeriksaan PHP 8.2; untuk PHP 8.1 jalankan `composer install` sesuai lingkungan atau gunakan PHP 8.2+. Atur variabel berikut di environment server aplikasi:

- `SSO_JWT_SECRET_KEY` **wajib**: sama persis dengan `$jwt_key` yang dipakai `JWT::encode()` di test-sso (pada kode yang diberikan, `JWT_SECRET_KEY` di `.env`).
- `SSO_AES_SECRET_KEY` **wajib**: nilai yang sama persis dengan `AES_SECRET_KEY` di `.env` server `test-sso`, sepanjang 32 byte. `URL_KEY` tidak digunakan oleh implementasi `decrypt()` tersebut.
- `SSO_BASE_URL`: URL server SSO, bawaan `http://localhost/test-sso`.
- `SSO_APP_URL`: URL aplikasi yang terdaftar di `website_management.url_website`, tanpa garis miring di akhir; bawaan `http://localhost/saimed-digital-link-hub`.
- `SSO_PROFILE_PHOTO_BASE_URL`: URL folder foto profil, bawaan `https://test-user-account.mandirialkesindo.co.id/profile%20user` seperti di test-system. Sesuaikan jika penyimpanan foto berbeda. Nama file diambil dari tabel SSO `photo_profile`; jika tabel, foto, atau server foto tidak tersedia, dashboard menggunakan ikon profil bawaan.
- `SSO_DB_HOST`, `SSO_DB_PORT`, `SSO_DB_DATABASE`, `SSO_DB_USERNAME`, `SSO_DB_PASSWORD`: koneksi database pengguna SSO. Bawaan lokalnya `127.0.0.1`, `3306`, `test_user`, `root`, dan password kosong.

Atur kedua key di konfigurasi environment server yang aman, bukan di repository atau chat, dan restart Apache setelah perubahan. Contohnya, pada blok Apache `<Directory>` yang sudah digunakan, tambahkan `SetEnv SSO_JWT_SECRET_KEY "nilai_JWT_SECRET_KEY_dari_test_sso"`; ini adalah placeholder dan harus diganti dengan nilai sebenarnya tanpa menyertakan tanda kutip `.env` sebagai bagian dari nilai. Jangan menimpa `SSO_AES_SECRET_KEY` yang sudah ada. Perubahan ini memerlukan konfigurasi tambahan sebelum login dapat berfungsi; tanpa JWT key, validasi ditolak (`jwt_secret_belum_diatur`).

JWT pada kode `query_proses_login.php` yang diberikan ditandatangani dengan `$jwt_key` menggunakan HS256, sementara `key_token` berisi nilai acak yang berbeda dan JWT tidak memiliki `id_user`. SAIMED memverifikasi tanda tangan dengan `SSO_JWT_SECRET_KEY`, mencocokkan token secara persis dan memastikan `user_token.id_user` sama dengan pemilik akses di database; jika klaim `id_user` ada, nilainya juga harus cocok. Token, status, role, blokir, dan masa berlaku tetap divalidasi.

**Risiko keamanan:** HS256 menggunakan key yang sama untuk menandatangani dan memverifikasi. Memberikan JWT secret ke aplikasi ini juga memberinya kemampuan membuat JWT yang bisa dipercaya sistem SSO lain. Gunakan hanya pada server yang tepercaya, batasi akses konfigurasi, dan pertimbangkan endpoint verifikasi SSO atau tanda tangan asimetris untuk produksi. Pengguna database SAIMED sebaiknya hanya diberi izin baca tabel SSO. Key AES yang salah tetap akan menyebabkan kegagalan dekripsi URL di server SSO.

Daftarkan URL aplikasi dan hak akses akun pada SSO; pastikan cookie SSO dikirim ke origin dan path aplikasi. Saat admin dibuka tanpa sesi yang valid, aplikasi langsung mengarahkan ke SSO dan menyimpan tujuan login selama 10 menit. Setelah browser kembali ke halaman utama, aplikasi meneruskannya ke admin. Jika token ditolak atau cookie tidak terkirim, aplikasi menampilkan kesalahan dan tombol coba lagi tanpa redirect berulang. Sesi aplikasi memakai cookie terpisah agar tidak terhapus saat logout SSO; token dan hak akses tetap diverifikasi sebelum dashboard dibuka. Logout menggunakan endpoint SSO dan mengakhiri sesi SSO bersama. Untuk produksi gunakan HTTPS dan akses database privat yang aman. Alur penuh perlu diuji dengan server SSO yang sebenarnya.

Dashboard menampilkan nama akun SSO dan hitung mundur batas terdekat antara 30 menit sejak request admin terakhir, kedaluwarsa JWT, dan kedaluwarsa token di database. Hitungan ini hanya perkiraan pada tab tersebut: aktivitas di tab lain atau pencabutan akses di SSO dapat mengubah status sebenarnya. Ketika hitungan mencapai nol, gunakan **Periksa sesi**; validasi dan timeout tetap ditegakkan di server pada request berikutnya, bukan oleh timer browser.

## Diagnostik login SSO

Jika kembali ke halaman admin dengan pesan sesi tidak valid, cari baris terbaru `SAIMED SSO: validasi ditolak [kode]` di error log PHP/Apache setelah mencoba login. Kode tersebut menunjukkan tahap yang gagal tanpa mencatat JWT, cookie, key, atau identitas pengguna. Misalnya `token_atau_akses_website_tidak_ditemukan` berarti tidak ditemukan baris yang cocok untuk cookie dan URL pada database SSO yang dikonfigurasi; `jwt_secret_belum_diatur` berarti JWT secret belum dikonfigurasi; `tanda_tangan_jwt_tidak_valid` berarti verifikasi tanda tangan gagal; dan `id_user_tidak_cocok` berarti klaim identitas berbeda. Data akses yang ditampilkan untuk akun belum tentu terkait dengan cookie yang sedang digunakan. Jangan membagikan token, key, atau password saat meminta bantuan.

## Pengujian

Jalankan `php tests/smoke.php`, `php tests/db-config.php`, `php tests/dashboard.php`, `php tests/dashboard-chart.php`, `php tests/dashboard-data.php`, `php tests/dashboard-stats.php`, `php tests/dashboard-export.php`, `php tests/admin-no-demo.php`, `php tests/admin-actions-mysqli.php`, `php tests/uploaded-icons.php`, `node tests/dashboard-links.js` (jika Node.js tersedia), `php tests/analytics-track.php <skenario>` untuk `valid`, `null`, `invalid`, dan `method`, `php tests/sso.php`, `php tests/sso-access.php`, `php tests/sso-logout.php`, `php tests/sso-profile.php`, `php tests/sso-diagnostics.php`, `php tests/sso-url.php`, `php tests/sso-return.php`, serta `php tests/sso-controller.php <skenario>` untuk setiap skenario `start`, `retry`, `returned`, dan `config`; periksa juga sintaks PHP. Tes SSO memerlukan dependensi Composer; tes URL memerlukan ekstensi OpenSSL.

## Laporan analytics

Dashboard menggunakan data dari MySQL, bukan data localStorage atau data contoh otomatis dari prototipe. Pilihan periode 7, 14, 30, 90 hari atau semua waktu dihitung berdasarkan hari kalender WIB (Asia/Jakarta). Waktu pada database tetap disimpan dalam UTC dan dikonversi untuk tampilan serta ekspor. Perbandingan kunjungan dan klik memakai periode sebelumnya dengan panjang yang sama; jika periode sebelumnya nol atau memilih semua waktu, persentase tidak ditampilkan. Pada pilihan semua waktu, ringkasan dan ekspor mencakup seluruh data, sedangkan grafik harian menampilkan 30 hari terakhir agar tetap terbaca. Perangkat menghitung interaksi, bukan pengunjung unik. Kontribusi tautan membagi jumlah klik tautan dengan semua klik tautan dan sosial pada periode yang dipilih. Data yang ditandai sebagai data contoh dari versi lama tidak ditampilkan, dihitung, atau diekspor. Data tersebut tetap tersimpan di database; kolom `is_demo` dipertahankan untuk kompatibilitas tanpa migrasi atau penghapusan otomatis.

Tombol **Perbarui** memuat ulang data dari server. **Ekspor CSV** hanya tersedia bagi Super Admin yang terautentikasi dan mengikuti periode terpilih. File menggunakan zona waktu WIB dan memuat waktu, jenis aktivitas, label, dan perangkat; nilai dibatasi agar tidak dieksekusi sebagai formula spreadsheet. Ekspor tidak menyertakan ID pengunjung atau user agent.

Grafik Aktivitas Harian menampilkan nilai kunjungan dan klik saat diarahkan kursor atau disentuh. Pengguna keyboard dapat memfokuskan grafik lalu memakai tombol panah, Home, dan End; tabel rincian harian tetap tersedia. Garis melengkung melewati titik data yang tercatat dan tidak menambahkan nilai harian baru.

## Logo tautan

Saat menambah tautan, Super Admin mengunggah logo PNG atau JPG (maksimal 2 MB, 4096 piksel per sisi dan 16 megapiksel). File diverifikasi di server, disimpan dengan nama acak pada `config/uploads/`, dan ditampilkan di dalam kotak berukuran sama dengan `object-fit: contain` agar tidak terdistorsi; ukuran file asli tidak diubah. Logo lama dapat dipertahankan atau diganti saat menyunting. Ikon bawaan untuk tautan yang sudah ada tetap berfungsi. Tidak perlu mengubah struktur database.

Urutan adalah posisi unik dari semua tautan, termasuk yang nonaktif; halaman publik hanya menampilkan tautan aktif sesuai urutan tersebut. Memilih posisi yang ditempati akan menggeser tautan lain, tautan baru masuk di akhir, dan penghapusan menutup celah. Data lama dengan angka duplikat atau bercelah ditampilkan menurut `sort_order, id` dan dirapikan saat perubahan tautan berhasil. Jika urutan berubah sejak halaman dibuka, simpan ditolak dan administrator perlu memperbarui halaman. Perubahan memakai transaksi InnoDB dan MySQL advisory lock per database; semua penulis lain yang mengubah urutan perlu mengikuti mekanisme lock yang sama agar aman dari konflik.

Folder `config/uploads/` berisi data pengguna dan tidak masuk Git. Proses PHP harus dapat membuat dan menulis folder ini. Backup folder tersebut bersama database sebelum deployment atau migrasi, dan gunakan penyimpanan persisten bersama jika menjalankan lebih dari satu instance. Folder `config/` harus diblokir dari akses HTTP langsung (aturan Apache `.htaccess` tersedia; konfigurasi setara wajib untuk server lain). Logo ditayangkan melalui endpoint aplikasi `?api=icon&file=...`. Pastikan batas PHP `upload_max_filesize` minimal 2M dan `post_max_size` lebih besar dari 2M. File lama yang diganti atau dihapus dibersihkan setelah perubahan database berhasil.

## Kelola tautan

Daftar tautan publik memakai DataTables untuk pencarian, pengurutan, dan pagination. Tombol Edit membuka modal yang mempertahankan nama, URL, logo, urutan, status aktif, dan status unggulan; logo baru hanya diperlukan jika ingin mengganti logo lama. SweetAlert2 menampilkan hasil penyimpanan atau penghapusan, serta konfirmasi sebelum menghapus. Jika DataTables atau SweetAlert2 tidak tersedia, pesan teks dan tabel dasar tetap ditampilkan; tanpa JavaScript formulir tambah/edit dan konfirmasi hapus tetap dapat digunakan.

Pustaka browser disimpan secara lokal dalam `assets/vendor/` agar tidak memerlukan CDN: jQuery 3.7.1, DataTables 2.3.5, dan SweetAlert2 11.26.10. Lisensi MIT masing-masing disertakan. Untuk tes interaksi DOM berbasis Node.js, jalankan `npm ci` kemudian `npm run test:dashboard-links`; dependensi Node hanya diperlukan untuk pengujian, bukan saat menjalankan PHP.

Halaman **Aktivitas** menampilkan maksimal 50 event terbaru dalam periode yang dipilih, dengan pencarian, pengurutan, dan pagination DataTables. Pencarian dan pagination hanya mencakup 50 event tersebut; ekspor CSV tetap mengikuti periode terpilih. Waktu ditampilkan dalam WIB, sementara pengurutan memakai timestamp UTC agar tetap kronologis. Tanpa JavaScript atau pustaka DataTables, tabel dasar tetap tersedia. Jalankan `npm run test:dashboard-activity` setelah `npm ci` untuk pengujian DOM tabel aktivitas.
