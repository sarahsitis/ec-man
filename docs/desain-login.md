# Desain halaman login EC-Man

Halaman `/login` memakai identitas, warna hijau, dan ilustrasi yang sama dengan landing page. Desktop menampilkan panel pengenalan dan formulir berdampingan; ponsel mengutamakan formulir dalam kartu tunggal. Tautan beranda, judul, bantuan akun, dan label form menggunakan bahasa Indonesia.

Form mempertahankan autentikasi Laravel/Inertia menggunakan username/NIS, password, dan pilihan ingat saya. Tombol tampilkan/sembunyikan password memiliki label aksesibel. Field memakai autocomplete yang sesuai, validasi wajib, hubungan pesan kesalahan dengan input, serta fokus pada field yang bermasalah. Password dikosongkan dan kembali disembunyikan setelah request selesai. Selama request, form terkunci dan tombol menampilkan indikator proses; pengunci request mencegah klik ganda mengirim lebih dari sekali.

## File

- `resources/js/Pages/Auth/Login.tsx`: tampilan dan interaksi form login.
- `resources/css/login.css`: CSS dengan scope `.ec-login`, breakpoint, fokus, dan indikator proses.
- `resources/js/Components/PublicBranding.tsx`: logo dan ilustrasi SVG bersama untuk beranda/login.
- `resources/js/Pages/Welcome.tsx`: menggunakan komponen branding bersama.
- `resources/css/app.css`: impor stylesheet login.

## Validasi

`npm.cmd run build` berhasil. `php artisan test --filter=AuthenticationTest` menghasilkan **4 tes lulus, 8 assertion**, menggunakan SQLite `:memory:` dan guard database yang sudah tersedia.

Chrome headless memeriksa 1440, 768, 375, dan 320px tanpa overflow. Pemeriksaan mencakup field wajib, tampil/sembunyi password, ingat saya, input/tombol terkunci saat request, klik ganda dengan tepat satu POST login, pesan kesalahan server, reset password, fokus pada field bermasalah, login pembina, pengalihan panitia ke URL yang dituju, dan tautan kembali ke beranda. Tidak ada exception JavaScript yang tertangkap. Login browser memakai SQLite QA dan akun sintetis; database sekolah tidak digunakan.

Bukti lokal berada di `storage/app/design-qa/login-results.json`, `login-1440.png`, `login-375.png`, serta `login-error-mobile.png`. Direktori QA diabaikan Git. Keyboard fisik dan ponsel sungguhan belum diuji.

Buka `http://127.0.0.1:8000/login`. Jika sudah login, keluar akun terlebih dahulu karena middleware guest mengarahkan akun yang masih login ke dashboard. Muat ulang dengan Ctrl+F5 bila perlu. Pekerjaan tetap lokal tanpa commit, push, merge, atau deploy.
