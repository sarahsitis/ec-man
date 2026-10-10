# Landing page EC-Man

Beranda `/` menggantikan halaman bawaan Laravel dengan pengenalan English Club dalam bahasa Indonesia, warna hijau yang selaras dengan workspace, dan ilustrasi SVG lokal.

Halaman menyediakan hero, enam fitur utama, penjelasan tiga peran, empat langkah belajar, FAQ, serta tombol masuk. Pengunjung diarahkan ke `/login`; pengguna yang sudah login mendapat tombol ke `/dashboard`, dengan pengalihan peran tetap mengikuti backend. Akun dikelola pembina; halaman tidak menawarkan pendaftaran mandiri.

## File

- `resources/js/Pages/Welcome.tsx`: konten dan navigasi beranda, metadata, ilustrasi, dan CTA berdasarkan akun login.
- `resources/css/landing.css`: CSS dengan scope `.ec-landing`, breakpoint desktop/ponsel, fokus terlihat, serta dukungan reduced motion.
- `resources/css/app.css`: impor stylesheet landing page.

## Validasi

`npm.cmd run build` berhasil. `php artisan test --filter=ExampleTest` menghasilkan dua tes bawaan lulus, termasuk akses beranda; konfigurasi tes menggunakan SQLite `:memory:`.

Chrome headless memeriksa lebar 1440, 768, 375, dan 320px, navigasi anchor tanpa tertutup header, buka/tutup FAQ, metadata, serta CTA sebelum dan sesudah login. Tidak ada overflow halaman atau exception JavaScript yang tertangkap. Login browser menggunakan SQLite QA dan akun sintetis, tanpa database sekolah. Keyboard fisik dan perangkat ponsel sungguhan belum diuji.

Bukti lokal tersimpan di `storage/app/design-qa/landing-results.json`, `landing-desktop.png`, `landing-full-desktop.png`, dan `landing-375.png`. Direktori QA diabaikan Git.

Buka `http://127.0.0.1:8000/` pada server aplikasi yang sedang berjalan. Tekan Ctrl+F5 untuk memuat ulang aset jika diperlukan. Pekerjaan tetap lokal tanpa commit, push, merge, atau deploy.
