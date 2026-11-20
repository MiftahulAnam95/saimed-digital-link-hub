# SAIMED Digital Link Hub

## Konfigurasi SSO

Login admin menggunakan cookie `jwt_token_test` dan database pengguna SSO. Akun harus berperan **Super Admin**, memiliki akses ke website ini, tidak diblokir, dan memiliki token serta status perangkat yang valid. Tidak ada login password admin lokal.

Aplikasi mengirim URL tujuan yang dienkripsi AES-256-GCM ke `logout.php` pada server `test-sso`. Formatnya adalah base64url dari nonce 12 byte, tag 16 byte, dan ciphertext, sesuai fungsi `decrypt()` di server tersebut.

Pastikan PHP memiliki ekstensi `openssl` dan `pdo_mysql` serta dependensi Composer tersedia. Atur variabel berikut di environment server aplikasi:

- `SSO_AES_SECRET_KEY` **wajib**: nilai yang sama persis dengan `AES_SECRET_KEY` di `.env` server `test-sso`, sepanjang 32 byte. `URL_KEY` tidak digunakan oleh implementasi `decrypt()` tersebut.
- `SSO_BASE_URL`: URL server SSO, bawaan `http://localhost/test-sso`.
- `SSO_APP_URL`: URL aplikasi yang terdaftar di `website_management.url_website`, tanpa garis miring di akhir; bawaan `http://localhost/saimed-digital-link-hub`.
- `SSO_DB_HOST`, `SSO_DB_PORT`, `SSO_DB_DATABASE`, `SSO_DB_USERNAME`, `SSO_DB_PASSWORD`: koneksi database pengguna SSO. Bawaan lokalnya `127.0.0.1`, `3306`, `test_user`, `root`, dan password kosong.

Atur key di konfigurasi environment server yang aman, bukan di repository atau chat, dan restart Apache setelah perubahan. Jika key tidak diatur atau panjangnya tidak sesuai, tombol masuk dinonaktifkan. Key yang salah tetap akan menyebabkan kegagalan dekripsi di server SSO. Karena key dipakai bersama, batasi akses ke konfigurasi kedua server.

Daftarkan URL aplikasi dan hak akses akun pada SSO; pastikan cookie SSO dikirim ke origin dan path aplikasi. Saat admin dibuka tanpa sesi yang valid, aplikasi langsung mengarahkan ke SSO dan menyimpan tujuan login selama 10 menit. Setelah browser kembali ke halaman utama, aplikasi meneruskannya ke admin. Jika token ditolak atau cookie tidak terkirim, aplikasi menampilkan kesalahan dan tombol coba lagi tanpa redirect berulang. Sesi aplikasi memakai cookie terpisah agar tidak terhapus saat logout SSO; token dan hak akses tetap diverifikasi sebelum dashboard dibuka. Logout menggunakan endpoint SSO dan mengakhiri sesi SSO bersama. Untuk produksi gunakan HTTPS dan akses database privat yang aman. Alur penuh perlu diuji dengan server SSO yang sebenarnya.

## Diagnostik login SSO

Jika kembali ke halaman admin dengan pesan sesi tidak valid, cari baris terbaru `SAIMED SSO: validasi ditolak [kode]` di error log PHP/Apache setelah mencoba login. Kode tersebut menunjukkan tahap yang gagal tanpa mencatat JWT, cookie, key, atau identitas pengguna. Misalnya `token_atau_akses_website_tidak_ditemukan` berarti tidak ditemukan baris yang cocok untuk cookie dan URL pada database SSO yang dikonfigurasi; `tanda_tangan_jwt_tidak_valid` berarti verifikasi tanda tangan gagal; dan `id_user_tidak_cocok` berarti klaim identitas berbeda. Data akses yang ditampilkan untuk akun belum tentu terkait dengan cookie yang sedang digunakan. Jangan membagikan token, key, atau password saat meminta bantuan.

## Pengujian

Jalankan `php tests/smoke.php`, `php tests/sso.php`, `php tests/sso-diagnostics.php`, `php tests/sso-url.php`, `php tests/sso-return.php`, serta `php tests/sso-controller.php <skenario>` untuk setiap skenario `start`, `retry`, `returned`, dan `config`; periksa juga sintaks PHP. Tes SSO memerlukan dependensi Composer; tes URL memerlukan ekstensi OpenSSL.
