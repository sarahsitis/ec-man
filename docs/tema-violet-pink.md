# Tema violet dan pink

Tema terbaru mengikuti arahan pengguna: primary violet, secondary pink, tertiary abu tua, dan container putih. Tema ini menggantikan desain Bold Hues sebelumnya.

- Primary `#7C3AED`, hover `#5B21B6`: tombol utama, Edit, pagination, menu aktif dan grafik nilai resmi.
- Secondary `#EC4899`, teks aksen `#BE185D`: Hapus/Cabut, ilustrasi, label, tombol Laporan/Detail dan grafik penilaian diri.
- Tertiary `#374151`, hover `#1F2937`, latar lembut `#F3F4F6`: teks utama, label formulir, judul kolom tabel, menu yang tidak aktif, dan tombol kembali pada login.
- Container `#FFFFFF`: sidebar, kartu, hero dashboard, formulir login, ilustrasi landing, panel informasi dan dialog.
- Latar halaman lavender lembut, dengan aksen pink yang ringan.

Token bersama berada di `resources/css/theme.css`. `resources/css/playful.css` mengatur kartu dengan sudut membulat, bayangan ringan, garis aksen, badge, serta dekorasi hero. Alias warna lama dipertahankan agar komponen grafik tetap menggunakan token yang sama. Warna Tailwind indigo mengikuti violet, dan utilitas `tertiary`, `tertiary-strong`, serta `tertiary-soft` tersedia untuk elemen netral.

Dashboard memakai ilustrasi SVG buku, bintang, dan gelembung percakapan. Tiga kartu pintasan mendapatkan garis aksen dan ikon violet/pink. Landing page serta login menggunakan container putih dan ilustrasi dengan aksen serupa. Dekorasi tidak menambahkan data atau status bisnis, dan tidak masuk ke urutan fokus keyboard.

Tombol menggunakan ikon yang relevan sebelum tulisan: kunci untuk Masuk, rumah untuk Beranda, grid untuk Dashboard, buku untuk alur belajar/pre-test, clipboard untuk penugasan/penilaian, grafik untuk hasil siswa, jam untuk riwayat, serta simbol power untuk Keluar. Pintasan pengelolaan mengikuti ikon menu masing-masing. Ikon panah generik dan aturan rotasinya dihapus; ikon tetap terlihat pada layar ponsel. Label, route, handler, dan status processing tetap digunakan seperti sebelumnya. Pemeriksaan posisi ikon, navigasi, dan tampilan browser dicatat di `storage/app/design-qa/button-icon-results.json`.

## Validasi

Build TypeScript/Vite, tes frontend yang ada, tes render/autentikasi, dan pemeriksaan browser untuk warna/kontras tombol, container putih, halaman desktop/mobile, link Edit, konfirmasi Hapus, serta pagination 10/25/50. Pengujian menggunakan database SQLite terpisah; data sekolah tidak diakses untuk pengujian.

Diff lokal: `storage/app/design-qa/violet-pink.diff`. Hasil dan tangkapan layar browser berada di `storage/app/design-qa/violet-pink-*`.
