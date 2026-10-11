# Materi dan Dokumentasi Kegiatan

Buka **Kegiatan → Materi & Dokumentasi** pada kegiatan yang dituju. Bagian ini juga tersedia pada detail kegiatan, sebelum presensi.

- Pembina dan panitia dengan masa jabatan aktif dapat mengunggah beberapa berkas sekaligus beserta keterangan opsional.
- Siswa dapat melihat dan mengunduh berkas pada kegiatan semester keanggotaannya atau kegiatan dengan riwayat presensinya.
- Pembina dapat menghapus seluruh berkas. Panitia aktif hanya dapat menghapus berkas yang diunggahnya sendiri. Penghapusan memerlukan konfirmasi.
- Foto JPG, JPEG, PNG, dan WebP ditampilkan sebagai pratinjau. Dokumen dan video tersedia melalui tombol unduh.

Materi mendukung PDF, Word, PowerPoint, Excel, TXT, CSV, JPG/JPEG, PNG, WebP, MP3, MP4, dan WebM. Dokumentasi mendukung JPG/JPEG, PNG, WebP, PDF, MP4, MOV, dan WebM. Maksimal 10 berkas setiap unggahan, 20 MB per berkas, dengan total maksimal 30 MB. Batas konfigurasi PHP/web server juga berlaku.

Berkas tersimpan pada disk privat `local`, dengan nama penyimpanan acak. Unduhan dan pratinjau memeriksa autentikasi serta akses kegiatan. Nama berkas asli, keterangan, pengunggah, ukuran, dan waktu unggah ditampilkan pada detail kegiatan. Berkas tidak ditempatkan pada direktori publik dan tidak memerlukan `storage:link`.

Jika satu berkas dalam unggahan tidak valid, seluruh unggahan ditolak. Jika penyimpanan gagal, transaksi metadata dibatalkan dan berkas yang sudah tersimpan dibersihkan. Menghapus kegiatan melalui aplikasi juga menghapus berkasnya; kegiatan dengan riwayat presensi tetap tidak dapat dihapus.

Jalankan migrasi `2026_10_11_120000_create_activity_attachments_table.php` sebelum menggunakan fitur ini pada lingkungan baru. Pengujian fitur tersedia pada `tests/Feature/ActivityAttachmentsTest.php`.
