# Tahap 5: Pelaporan & Profil

## Menggunakan laporan

- Siswa dan panitia membuka **Perkembangan Saya** untuk melihat grafik radar/bar, minat, tujuan belajar, dan riwayat nilai resmi miliknya.
- Ringkasan kemampuan dan minat juga tersedia pada halaman **Profil**.
- Pembina membuka **Laporan** pada tabel siswa untuk melihat profil dan perkembangan siswa. Panitia yang masih aktif dapat membuka laporan siswa yang ditugaskan kepadanya melalui halaman hasil pre-test.
- Pembina membuka **Laporan Minat** untuk melihat seluruh pilihan minat atau hanya minat utama. Filter menggunakan kelas siswa saat ini dan status siswa; default mencakup siswa aktif di semua kelas.

## Perhitungan

Grafik kemampuan menggunakan tabel `scores`, yaitu nilai resmi yang diterbitkan setelah persetujuan pembina. Draf, rekomendasi yang diajukan, revisi, dan penolakan tidak menambah nilai pada grafik.

Rata-rata per aspek dihitung dari seluruh nilai resmi aspek tersebut pada periode yang dipilih, termasuk nilai di luar halaman riwayat yang sedang dibuka. Rata-rata keseluruhan dihitung dari seluruh nilai, sehingga setiap nilai memiliki bobot yang sama. Listening, speaking, reading, dan writing menggunakan skala 1–4.

Aspek tanpa nilai tetap `null` dan ditampilkan sebagai **Belum dinilai**. Grafik radar hanya menghubungkan bidang setelah keempat aspek memiliki nilai; sebelum itu grafik menampilkan titik yang tersedia. Penilaian diri saat pre-test dapat ditampilkan sebagai pembanding terpisah dan tidak mengubah nilai resmi.

Filter tanggal menggunakan tanggal persetujuan nilai dan batas hari inklusif dalam WIB. Nilai lama tanpa tanggal persetujuan muncul pada laporan tanpa filter, tetapi tidak masuk ketika batas tanggal digunakan. Filter periode tidak mengubah konteks minat atau hasil pre-test awal.

Distribusi minat menggunakan katalog `interests` dan pilihan siswa di `student_interests`. Persentase adalah jumlah siswa yang memilih minat tersebut dibagi jumlah siswa sesuai filter yang telah mencatat minat. Satu siswa dapat memilih beberapa minat, sehingga jumlah persentase semua pilihan dapat melebihi 100%. Tampilan minat utama hanya menghitung pilihan dengan `is_primary = true`.

Jumlah siswa belum mencatat minat ditampilkan terpisah. Kategori tanpa peminat tetap muncul dengan angka nol; kelompok kosong menghasilkan persentase nol.

## Akses dan migrasi

Laporan individu hanya tersedia bagi pemilik profil, pembina, atau panitia aktif yang memiliki penugasan untuk siswa tersebut. Panitia tidak menerima kontak pribadi, foto profil, jawaban pre-test, atau catatan internal penilaian melalui laporan. Laporan distribusi hanya tersedia bagi pembina.

Migrasi `2026_10_10_100000_rename_interest_categories_to_interests` mengganti nama tabel katalog lama tanpa mengubah ID atau data pilihan siswa. Kolom relasi `student_interests.interest_category_id` dipertahankan untuk kompatibilitas. Model `InterestCategory` tetap menjadi alias model `Interest` agar alur pre-test yang sudah ada berjalan.

Jalankan `php artisan migrate` untuk memperbarui database, `php artisan test` untuk memeriksa alur backend, dan `npm run build` untuk memeriksa TypeScript serta build frontend.

## Hasil pengujian

Pengujian tahap 5 mencakup publikasi empat aspek melalui alur delegasi, submit rekomendasi, dan persetujuan pembina; pengisian pre-test hingga tampil pada profil serta distribusi minat; akses setelah pencabutan hak panitia atau perubahan status siswa; filter tanggal WIB; agregasi lintas halaman; kelompok kosong; dan migrasi katalog tanpa kehilangan relasi.

- `php artisan test --filter=ReportingTest --stop-on-failure`: 12 tes lulus, 510 assertions.
- `php artisan test`: 136 tes lulus, 2.361 assertions, termasuk fitur tahap sebelumnya.
- `npm run build`: pemeriksaan TypeScript dan build produksi berhasil.

Tes backend menggunakan SQLite `:memory:` yang terisolasi. Pengujian klik dan pemeriksaan visual melalui browser belum dilakukan karena alat browser tidak tersedia di lingkungan kerja ini.
