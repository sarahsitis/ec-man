# Notifikasi Kegiatan

Pembina dan panitia EC dengan masa jabatan aktif dapat membuat pertemuan melalui **Kegiatan → Buat kegiatan**. Saat pertemuan berstatus **Terjadwal** disimpan, aplikasi membuat satu notifikasi untuk setiap siswa aktif dengan keanggotaan aktif pada semester kegiatan. Siswa yang menjadi panitia juga termasuk penerima jika terdaftar pada semester tersebut.

Notifikasi tersimpan di database dan tersedia setelah siswa masuk ke aplikasi. Ikon lonceng di bagian atas halaman menunjukkan jumlah notifikasi yang belum dibaca. Jumlah ini diperbarui setiap 30 detik saat tab terlihat, serta ketika pengguna kembali ke tab aplikasi.

Halaman notifikasi menyediakan jadwal dalam WIB, lokasi, pembuat kegiatan, status terkini, filter belum dibaca, dan tindakan tandai sudah dibaca. Memilih **Lihat kegiatan** menandai notifikasi sudah dibaca dan membuka detail kegiatan. Perubahan jadwal dan status tampil saat halaman notifikasi dimuat kembali. Kegiatan yang sudah dihapus atau kehilangan akses ditampilkan sebagai tidak tersedia.

Pembuatan kegiatan dan seluruh notifikasi berlangsung dalam satu transaksi. Jika pengiriman ke database gagal, keduanya dibatalkan. Kegiatan baru yang berstatus selesai atau dibatalkan tidak membuat notifikasi. Kegiatan yang sudah ada dan data demo tidak dikirim ulang. Perubahan atau penghapusan kegiatan tetap menjadi kewenangan pembina.

Notifikasi, jumlah belum dibaca, dan tindakan membaca dibatasi pada akun pemilik. Jalankan migrasi `2026_10_11_160000_create_notifications_table.php` pada lingkungan baru sebelum menggunakan fitur ini. Pengujian fitur tersedia pada `tests/Feature/ActivityNotificationsTest.php`.
