# Penilaian dan rekomendasi EC

## Alur penggunaan

1. Pembina membuka **Penugasan**, memilih panitia dengan hak aktif, lalu memilih satu atau beberapa siswa aktif. Judul, aspek kemampuan, instruksi opsional, dan batas waktu menentukan tugas penilaian.
2. Panitia membuka **Penugasan Saya**. Setiap siswa dinilai secara terpisah menggunakan rubrik internal 1–4 untuk listening, speaking, reading, atau writing.
3. **Simpan draf** boleh digunakan sebelum pengamatan lengkap. **Kirim ke pembina** membutuhkan skor usulan, bukti pengamatan, dan umpan balik masing-masing minimal 10 karakter. Rekomendasi terkunci setelah dikirim.
4. Pembina membuka **Pemeriksaan**. Antrean menampilkan pengajuan paling lama lebih dahulu dan dapat dicari menurut siswa, NIS, tugas, atau panitia.
5. **Approved / Sahkan nilai** menerbitkan nilai resmi. Pembina boleh menyesuaikan skor dengan alasan. **Revision / Kembalikan untuk revisi** membuka kembali rekomendasi untuk panitia. **Rejected / Tolak rekomendasi** mengakhiri penugasan tanpa nilai resmi. Revisi dan penolakan wajib disertai catatan.
6. Siswa melihat hasil yang telah disahkan melalui **Perkembangan Saya**, termasuk umpan balik serta catatan pengesahan. Skor usulan, bukti internal, dan riwayat pemeriksaan tidak ditampilkan kepada siswa.

Satu panitia dapat menilai banyak siswa. Panitia hanya dapat membuka siswa yang ditugaskan kepadanya, tidak dapat menilai diri sendiri, dan tidak dapat mengesahkan nilai. Hak panitia harus masih berlaku ketika membuka atau mengirim rekomendasi. Batas waktu penugasan berlaku sampai akhir hari dalam WIB.

Gunakan judul berbeda untuk tugas atau pertemuan berikutnya. Kombinasi siswa, judul, dan aspek yang sudah ditugaskan tidak dapat dibuat lagi. Jika satu siswa dalam pilihan tidak valid atau tugasnya sudah ada, seluruh pengajuan dibatalkan tanpa membuat penugasan parsial.

## Penyimpanan

- `assessments`: definisi tugas bersama, rubrik, instruksi, dan pembina pembuat.
- `assessment_assignments`: hubungan tugas, panitia, dan siswa; draf, rekomendasi, batas waktu, dan keputusan. Metadata tugas lama dipertahankan sebagai snapshot untuk kompatibilitas.
- `assessment_events`: riwayat delegasi, penyimpanan draf, pengajuan, perubahan batas waktu, serta keputusan. Keputusan Approved mencatat ID nilai resmi yang dihasilkan.
- `scores`: satu nilai resmi per penugasan dan siswa dalam tugas tersebut, beserta umpan balik, pembina pengesah, dan waktu pengesahan.

Pengesahan, pembuatan nilai resmi, dan pencatatan riwayat dilakukan dalam satu transaksi. Kegagalan salah satu penulisan membatalkan seluruh keputusan. Pengesahan ulang ditolak, dan constraint unik mencegah nilai resmi ganda. Draf, Submitted, Revision, dan Rejected tidak menghasilkan baris `scores`.

## Migrasi data tahap sebelumnya

Migrasi menambahkan definisi tugas untuk setiap penugasan lama karena data lama tidak menyimpan identitas kelompok tugas. ID penugasan, rekomendasi, status, dan riwayatnya tetap tersimpan. Nilai lama dengan status Approved dan skor sah 1–4 dimasukkan ke `scores` tanpa mengubah skor, umpan balik, atau pembina pengesah. Data pengesah atau tanggal pengesahan yang belum tersedia tetap kosong. Penugasan baru untuk beberapa siswa menggunakan satu definisi tugas bersama.

Untuk memasang perubahan pada lingkungan lain:

```sh
php artisan migrate
npm run build
```

Pengujian:

```sh
php artisan test
```
