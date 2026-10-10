# Panitia EC dengan masa tugas

Pembina membuka menu **Panitia EC** (`/panitia-ec`) untuk mengelola peran tambahan siswa.

1. Pilih siswa berstatus aktif dari kelas XI atau XII.
2. Isi tanggal mulai, tanggal selesai, dan catatan tugas bila diperlukan.
3. Gunakan **Edit masa tugas** untuk menyesuaikan atau memperpanjang masa tugas.
4. Gunakan **Cabut hak**, lalu konfirmasi pencabutan dengan alasan opsional, untuk menghentikan penugasan lebih awal.

Masa tugas berlaku sejak pukul 00.00 tanggal mulai sampai pukul 23.59.59 tanggal selesai dalam WIB. Penugasan mendatang belum memberikan akses. Periode siswa yang sama tidak boleh bertumpang tindih; periode berikutnya boleh dimulai sehari setelah periode sebelumnya berakhir.

Menu, penilaian siswa yang ditugaskan, hasil pre-test siswa yang ditugaskan, dan pencatatan presensi memerlukan penugasan yang sedang berlaku. Middleware memeriksa masa tugas pada setiap permintaan ke menu panitia dan pengiriman rekomendasi. Fitur bersama juga memeriksa hak aktif di server. Tidak diperlukan cron atau proses terjadwal untuk mengakhiri akses.

Siswa tetap dapat menggunakan profil, pre-test pribadi, perkembangan pribadi, serta kegiatan yang menjadi haknya sebagai siswa ketika akses panitia berakhir. Status nonaktif, keluar, alumni, atau kelas yang tidak memenuhi syarat menangguhkan hak panitia. Mengaktifkan kembali siswa memulihkan hak hanya jika masa tugas masih berlaku dan syarat lainnya terpenuhi.

Pencabutan menyimpan tanggal, pembina yang mencabut, serta alasan dalam `committee_roles`; baris tidak dihapus. Penugasan yang telah dicabut tidak dapat dihidupkan kembali melalui edit. Buat penugasan baru untuk mengangkat kembali siswa. Jika siswa mempunyai periode mendatang lain, periode tersebut tetap mengikuti tanggal yang ditentukan.

Riwayat penilaian, presensi, dan identitas siswa tetap tersimpan. Akun siswa maupun pembina yang terkait riwayat penugasan tidak dapat dihapus. Kelas siswa dengan penugasan aktif atau mendatang hanya dapat diubah pembina. Pengangkatan panitia dilakukan melalui menu Panitia EC agar setiap pengangkatan memiliki masa tugas.

Migrasi mengubah panitia lama menjadi penugasan terbatas dari tanggal migrasi sampai 30 Juni atau 31 Desember sesuai semester kalender berjalan. Pembina perlu memeriksa tanggal selesai dan kelayakan siswa setelah migrasi. Migrasi tidak mengubah identitas, password, atau riwayat penilaian.

Untuk memasang perubahan pada lingkungan lain:

```sh
php artisan migrate
npm run build
```

Pengujian:

```sh
php artisan test
```
