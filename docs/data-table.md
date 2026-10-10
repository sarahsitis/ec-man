# Pagination tabel EC-Man

Setiap daftar utama menampilkan 10 baris secara default, dengan pilihan 10, 25, atau 50. Kontrol di bawah tabel menunjukkan rentang data, jumlah total, halaman saat ini, dan tombol Sebelumnya/Berikutnya. Mengubah jumlah baris mengembalikan daftar ke halaman pertama.

## Daftar yang memakai pagination server

- Siswa, keanggotaan, kegiatan, tahun ajaran.
- Penugasan pembina/panitia dan antrean pemeriksaan rekomendasi.
- Hasil pre-test dan riwayat nilai resmi siswa.

Parameter `per_page` divalidasi oleh `App\Support\TablePagination`; nilai selain 10/25/50 ditolak. Query mengambil data sesuai halaman dan menggunakan urutan deterministik. Link halaman mempertahankan parameter filter. Filter pencarian/status/periode mempertahankan jumlah baris dan memulai lagi dari halaman pertama. Pilihan massal siswa berlaku pada baris halaman yang ditampilkan dan direset saat berpindah halaman.

## Daftar yang memakai pagination tampilan

Presensi mempertahankan roster dan draf lengkap di formulir. Pagination hanya membatasi baris yang ditampilkan, sehingga perubahan dan pilihan peserta lintas halaman tidak hilang sebelum disimpan. Pencarian mereset halaman; tombol pilih semua hanya memilih baris yang terlihat. Jumlah perubahan/peserta dipilih mencakup seluruh draf.

Riwayat panitia menggunakan pagination setelah filter status lokal agar status yang diturunkan dari masa tugas dan kelayakan siswa tetap konsisten. Distribusi minat membatasi tabel rincian; grafik, persentase dan total tetap memakai keseluruhan kelompok hasil filter. Ringkasan empat aspek dan lima penugasan terakhir pada dashboard tetap merupakan ringkasan tetap.

Tidak ada migration atau perubahan pada data sekolah. Pengujian backend menggunakan SQLite in-memory; pengujian browser menggunakan database SQLite terpisah di `storage/app/design-qa`.

## Validasi

- Build TypeScript/Vite.
- Suite backend: 148 tes lulus, 3.148 assertions, termasuk `TablePaginationTest` untuk ukuran halaman, data antarhalaman, filter, hasil kosong, validasi batas, dan pembatasan akses.
- Tes frontend: 6 tes lulus.
- Uji browser lulus untuk ukuran 10/25/50, perubahan halaman, reset pilihan siswa, filter panitia, draf presensi lintas halaman dan tampilan mobile 375px.

Diff perubahan lokal: `storage/app/design-qa/desain-panitia.diff` (mencakup juga perubahan desain sebelumnya yang belum di-commit).
