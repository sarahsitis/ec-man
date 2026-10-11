# Tema Bold Hues

Catatan desain sebelumnya. Tema ini telah diganti oleh [tema violet dan pink](tema-violet-pink.md) sesuai arahan terbaru pengguna.

Tema EC-Man mengikuti palet gambar yang diberikan pengguna. Palet ini menggantikan warna hijau pada desain sebelumnya.

| Warna | Hex | Penggunaan |
|---|---|---|
| Pink | `#F72585` | Hapus/Cabut, aksen, grafik penilaian diri |
| Ungu | `#7209B7` | Edit dan ilustrasi |
| Ungu tua | `#3A0CA3` | Identitas aplikasi, menu aktif, judul dan bagian landing page |
| Biru | `#4361EE` | Simpan, Masuk, tombol utama, pagination dan grafik nilai resmi |
| Cyan | `#4CC9F0` | Laporan/Detail dan tombol ajakan landing page |

Token warna bersama berada di `resources/css/theme.css`. Landing, login, workspace pembina/panitia dan komponen tombol memakai token tersebut; warna Tailwind indigo disesuaikan melalui `tailwind.config.js`. Latar putih/lavender dan border lembut menjaga tabel serta formulir tetap terbaca.

Link aksi kini mempunyai latar, border, padding, sudut membulat, serta keadaan hover/fokus/nonaktif. Teks Hapus/Cabut memakai warna gelap untuk menjaga kontras pada pink. Pagination server maupun presensi memakai tombol biru; tombol yang belum bisa digunakan ditampilkan abu-abu. Konfirmasi hapus, hak akses, filter, pilihan 10/25/50 dan draf presensi tetap mengikuti alur yang sudah tersedia.

## Validasi

- Build TypeScript/Vite.
- 52 tes backend terkait autentikasi, keanggotaan, presensi, peran panitia, laporan dan pagination lulus (1.500 assertions), memakai SQLite in-memory.
- 6 tes frontend lulus.
- Pengujian browser pada database SQLite terpisah mencakup palet, kontras teks tombol, ukuran target, halaman desktop/mobile 375px, link Edit, pembatalan konfirmasi Hapus, pagination 10/25/50 dan tampilan panitia.

Diff lokal: `storage/app/design-qa/bold-hues.diff`. Hasil browser: `storage/app/design-qa/bold-hues-results.json`. Tidak ada perubahan backend, migration atau pengujian pada database sekolah.
