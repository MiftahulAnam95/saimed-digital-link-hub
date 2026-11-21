# SAIMED Digital Link Hub

## Konfigurasi SSO

Login admin menggunakan cookie `jwt_token_test` dan database pengguna SSO. Akun harus berperan **Super Admin**, memiliki akses ke website ini, tidak diblokir, dan memiliki token serta status perangkat yang valid. Tidak ada login password admin lokal.

Aplikasi mengirim URL tujuan yang dienkripsi AES-256-GCM ke `logout.php` pada server `test-sso`. Formatnya adalah base64url dari nonce 12 byte, tag 16 byte, dan ciphertext, sesuai fungsi `decrypt()` di server tersebut.

Pastikan PHP memiliki ekstensi `openssl` dan `pdo_mysql` serta dependensi Composer tersedia. Atur variabel berikut di environment server aplikasi:

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

Jalankan `php tests/smoke.php`, `php tests/dashboard.php`, `php tests/dashboard-chart.php`, `php tests/dashboard-data.php`, `php tests/dashboard-stats.php`, `php tests/dashboard-export.php`, `php tests/sso.php`, `php tests/sso-profile.php`, `php tests/sso-diagnostics.php`, `php tests/sso-url.php`, `php tests/sso-return.php`, serta `php tests/sso-controller.php <skenario>` untuk setiap skenario `start`, `retry`, `returned`, dan `config`; periksa juga sintaks PHP. Tes SSO memerlukan dependensi Composer; tes URL memerlukan ekstensi OpenSSL.

## Laporan analytics

Dashboard menggunakan data dari MySQL, bukan data localStorage atau data contoh otomatis dari prototipe. Pilihan periode 7, 14, 30, 90 hari atau semua waktu dihitung berdasarkan hari kalender UTC. Perbandingan kunjungan dan klik memakai periode sebelumnya dengan panjang yang sama; jika periode sebelumnya nol atau memilih semua waktu, persentase tidak ditampilkan. Pada pilihan semua waktu, ringkasan dan ekspor mencakup seluruh data, sedangkan grafik harian menampilkan 30 hari terakhir agar tetap terbaca. Perangkat menghitung interaksi, bukan pengunjung unik. Kontribusi tautan membagi jumlah klik tautan dengan semua klik tautan dan sosial pada periode yang dipilih. Data contoh yang sudah ada tetap dihitung dan ditandai.

Tombol **Perbarui** memuat ulang data dari server. **Ekspor CSV** hanya tersedia bagi Super Admin yang terautentikasi dan mengikuti periode terpilih. File menggunakan zona waktu UTC dan memuat waktu, jenis aktivitas, label, perangkat, dan penanda data contoh; nilai dibatasi agar tidak dieksekusi sebagai formula spreadsheet. Ekspor tidak menyertakan ID pengunjung atau user agent.
