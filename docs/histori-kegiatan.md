# Histori Kegiatan

Histori dapat dibuka dari tombol **Histori Kegiatan** pada daftar kegiatan. Pembina dan panitia juga memiliki submenu histori di sidebar; siswa memiliki menu histori pada navigasi utama.

- **Akan & sedang berlangsung**: kegiatan terjadwal yang belum melewati waktu selesai, diurutkan dari jadwal terdekat. Kegiatan yang sudah dimulai ditandai sedang berlangsung.
- **Telah dilaksanakan**: kegiatan dengan status selesai, diurutkan dari jadwal terbaru.
- **Menunggu konfirmasi**: kegiatan terjadwal yang waktunya telah lewat. Pembina dapat membuka **Konfirmasi pelaksanaan** untuk memperbarui status melalui formulir edit yang sudah tersedia.
- **Dibatalkan**: kegiatan berstatus dibatalkan.

Waktu dihitung berdasarkan Asia/Jakarta (WIB) saat halaman dimuat. Kegiatan tanpa jam selesai tetap berada pada daftar mendatang/berlangsung hingga akhir tanggal kegiatan. Jadwal yang lewat tidak otomatis dianggap telah dilaksanakan.

Filter semester, judul/kategori, dan rentang tanggal berlaku pada daftar serta jumlah setiap kategori. Perpindahan kategori dan paginasi mempertahankan filter. Kartu kegiatan menyediakan jadwal, lokasi, penanggung jawab, presensi, jumlah materi/dokumentasi, dan tautan detail berkas. Siswa hanya menerima presensi miliknya; pembina dan panitia melihat jumlah hadir.

Akses histori mengikuti akses detail kegiatan yang sudah berlaku: pembina/panitia aktif melihat seluruh kegiatan, siswa melihat semester keanggotaannya atau kegiatan dengan riwayat presensinya. Tidak ada migrasi database baru. Pengujian tersedia pada `tests/Feature/ActivityHistoryTest.php`.

Untuk siswa, halaman histori membuka **Akan & sedang berlangsung** jika ada jadwal mendatang/berlangsung yang dapat diakses dalam filter saat ini. Jika tidak ada, halaman membuka **Telah dilaksanakan**. Pilihan kategori secara eksplisit selalu diikuti; tautan filter keikutsertaan tetap membuka kegiatan selesai. Kategori lain juga menampilkan pengingat dan tautan jadwal mendatang bila tersedia.

Dashboard siswa menampilkan **Kegiatan mendatang**: maksimal lima jadwal terdekat, termasuk kegiatan yang sedang berlangsung, dengan tanggal/waktu WIB, semester, lokasi, penanggung jawab, dan tombol detail. Jumlah kegiatan menunjukkan seluruh jadwal yang dapat diakses siswa. **Lihat semua kegiatan** membuka kategori mendatang pada histori. Kegiatan baru tampil saat dashboard dimuat kembali; kegiatan selesai, dibatalkan, atau yang telah melewati waktu selesai tidak tampil di bagian ini. Aturan akses dan batas waktu sama dengan histori.

Dashboard juga menyediakan chart **Ringkasan presensi saya** untuk kegiatan selesai pada seluruh semester yang dapat diakses siswa. Chart membagi kegiatan menjadi Hadir, Izin, Sakit, Alpa, dan Belum dicatat. Tidak ada catatan presensi dan status belum diisi masuk Belum dicatat. Persentase hadir memakai pembagi hanya presensi yang telah dicatat (hadir + izin + sakit + alpa); bila belum ada catatan, persentase ditampilkan sebagai tanda pisah. Klik atau pilih dengan keyboard pada legenda untuk melihat jumlah dan persentase kategori dari seluruh kegiatan selesai; klik kembali untuk mengembalikan total. Kegiatan terjadwal dan dibatalkan tidak dihitung.

**Histori penilaian saya** menampilkan lima nilai resmi terbaru milik siswa, diurutkan berdasarkan waktu pengesahan dan ID, dengan judul penilaian, aspek, nilai skala 1–4, pembina yang mengesahkan, dan waktu WIB. Nilai arsip resmi yang tidak memiliki waktu pengesahan tetap ditampilkan dengan keterangan Belum tercatat. Draf dan rekomendasi belum disahkan tidak masuk histori. **Lihat semua nilai** membuka Perkembangan Saya untuk riwayat lengkap dan umpan balik. Jika belum ada kegiatan selesai atau nilai resmi, dashboard menampilkan keterangan kosong tanpa membuat data contoh.

Pada **Telah dilaksanakan**, siswa memiliki filter **Keikutsertaan saya**:

- **Diikuti**: presensi milik siswa berstatus hadir.
- **Dilewatkan**: presensi milik siswa berstatus izin, sakit, atau alpa; alasan tetap ditampilkan pada kartu kegiatan.
- **Belum dicatat**: presensi belum diisi atau belum ada catatan untuk siswa. Kegiatan ini tidak dianggap alpa atau dilewatkan secara otomatis.

Jumlah keikutsertaan mengikuti filter semester, pencarian, dan tanggal pada kegiatan selesai. Filter keikutsertaan serta paginasi mempertahankan filter lainnya. Perpindahan kategori kegiatan mengembalikan keikutsertaan ke Semua. Jumlah kategori kegiatan tetap menunjukkan seluruh kegiatan dalam filter semester/pencarian/tanggal. Pembaruan presensi langsung mengubah klasifikasi pada pemuatan berikutnya; parameter siswa lain tidak dapat mengganti pemilik presensi yang diperiksa.
