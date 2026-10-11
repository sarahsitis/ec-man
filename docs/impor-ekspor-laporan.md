# Ekspor, impor, dan laporan administrasi English Club

Fitur tersedia untuk pembina melalui menu **Laporan & Arsip**. Siswa dan panitia tidak memiliki akses ke pertukaran data administrasi seluruh siswa.

Tombol **Impor Data** dan **Ekspor Data** tersedia di bagian atas halaman **Kelola Siswa**, **Keanggotaan Siswa**, **Kegiatan & Presensi**, serta **Penugasan dan Penilaian**. **Impor Data** membuka dialog di halaman yang sama: unduh template, unggah CSV, periksa pratinjau, lalu konfirmasi penyimpanan. Jenis data otomatis mengikuti halaman. Setelah berhasil, daftar pada halaman asal diperbarui dan hasil impor ditampilkan di dialog.

**Ekspor Data** langsung mengunduh CSV tanpa membuka halaman laporan. Ekspor siswa mencakup seluruh data induk dan dapat dilakukan meskipun belum ada tahun ajaran. Ekspor keanggotaan dan presensi mengikuti semester yang dipilih; jika tidak ada pilihan semester, ekspor memakai semester aktif atau terbaru. Ekspor nilai memakai periode semester aktif atau terbaru berdasarkan tanggal pengesahan. Cakupan ekspor ditampilkan di bawah tombol. Gunakan **Laporan & Arsip** untuk memilih periode mingguan/bulanan, filter kelas, atau mencetak laporan formal/PDF. Pada halaman tersebut, tombol **Ekspor CSV** tetap tersedia di bagian atas.

## Identitas dan format laporan

Kop laporan memakai **SMKN 1 CIANJUR**. Penandatangan bawaan adalah **Sarah Siti Sumaerah, ST.**, **Pembina Ekstrakurikuler English Club**, **NIP 199306302022212017**. Nama sekolah, penandatangan, jabatan, dan NIP dapat disesuaikan melalui pengaturan laporan. Nilai bawaan dikonfigurasi di `config/reports.php`, dengan variabel lingkungan `REPORT_SCHOOL_NAME`, `REPORT_SIGNATORY`, `REPORT_SIGNATORY_ROLE`, dan `REPORT_SIGNATORY_NIP`.

Laporan cetak berukuran A4 lanskap, dengan kop, nomor dokumen, tahun ajaran, semester, periode, kelas, tanggal penyusunan WIB, ringkasan, tabel bernomor, dan ruang tanda tangan. Kepala tabel diulang pada setiap halaman. Pilih **Cetak / Simpan PDF**, kemudian **Simpan sebagai PDF** pada dialog cetak peramban. Pilihan **Cetak laporan lengkap** menyertakan keempat jenis data, masing-masing pada bagian terpisah. Ruang tanda tangan diisi setelah laporan diperiksa.

## Periode dan cakupan

| Pilihan | Rentang |
| --- | --- |
| Mingguan | Senin sampai Minggu pada tanggal yang dipilih |
| Bulanan | Hari pertama sampai hari terakhir bulan pada tanggal yang dipilih |
| Semester ganjil | 1 Juli sampai 31 Desember tahun pertama tahun ajaran |
| Semester genap | 1 Januari sampai 30 Juni tahun kedua tahun ajaran |

Rentang mingguan dan bulanan dibatasi pada semester terpilih. Tanggal di luar semester ditolak. Tahun ajaran harus berurutan, misalnya `2026/2027`.

- Data siswa adalah data induk **saat ini**, termasuk siswa yang belum terdaftar dalam keanggotaan semester. Filter kelas memakai kelas siswa saat ini.
- Keanggotaan menggunakan semester dan kelas yang tersimpan pada keanggotaan. Ini adalah kondisi data saat laporan disusun, bukan rekonstruksi status siswa pada masa lampau.
- Presensi mengikuti tanggal kegiatan dan semester kegiatan. Kegiatan dibatalkan tidak disertakan. Filter kelas memakai keanggotaan semester. Hanya presensi tersimpan yang ditampilkan; siswa yang belum dicatat tidak otomatis dianggap alpa.
- Nilai hanya mengambil nilai resmi pada tabel `scores`, berdasarkan tanggal pengesahan dalam WIB. Nilai draf/usulan tidak disertakan. Karena nilai tidak memiliki relasi semester, cakupan semester ditentukan oleh tanggal pengesahan. Filter kelas memakai keanggotaan semester; tanpa filter kelas, arsip nilai tetap disertakan meskipun keanggotaan siswa kemudian dihapus.
- Rata-rata nilai memakai skala 1–4 dan hanya nilai yang tersedia. Aspek tanpa nilai ditampilkan sebagai `—`. Persentase kehadiran adalah jumlah hadir dibagi jumlah presensi terisi; `belum_diisi` tidak masuk penyebut.

Pratinjau laporan menampilkan maksimal 20 baris. CSV dan laporan cetak menyertakan seluruh data sesuai filter. Terapkan pengaturan baru sebelum mengunduh.

## Ekspor CSV

CSV memakai UTF-8 dengan BOM, pemisah koma, dan baris CRLF agar sesuai untuk pertukaran data dan dapat dibaca Excel. CSV adalah tabel data untuk impor kembali; kop dan tanda tangan tersedia dalam laporan cetak/PDF.

NIS numerik diberi awalan apostrof untuk mempertahankan angka nol di depan pada spreadsheet. Isi yang dapat dibaca sebagai formula juga diberi apostrof. Impor EC-Man menghapus awalan perlindungan ini secara otomatis. Saat membuka CSV melalui menu impor Excel, pilih tipe **Text** untuk kolom NIS. Tahun bergabung dan nilai tetap berupa angka. Kata sandi dan peran akun tidak diekspor.

## Alur impor

1. Pilih jenis data, lalu unduh template CSV.
2. Isi baris setelah judul kolom. Jangan menambah, menghapus, atau menggandakan kolom. Urutan kolom boleh berubah.
3. Pilih **Tambahkan saja** atau **Perbarui sesuai isi file**. Nilai resmi hanya dapat ditambahkan.
4. Unggah CSV UTF-8; pemisah koma dan titik koma didukung. Maksimal 5 MB dan 2.000 baris data per file.
5. Pilih **Periksa & Pratinjau**. Periksa jumlah data baru, diperbarui, dilewati, serta kesalahan per baris. Pratinjau isi menampilkan maksimal 50 baris valid; konfirmasi tetap memproses seluruh file.
6. Jika ada kesalahan, koreksi file lalu unggah ulang. Tidak ada data yang disimpan pada tahap pratinjau.
7. Pilih **Konfirmasi & Simpan Impor**. Untuk nilai, pembina wajib menyatakan pengesahan arsip sebagai nilai resmi.

Data identik dilewati. Pada mode tambahkan saja, data yang sudah ada dengan isi berbeda ditolak. Pada mode perbarui, semua kolom dalam file menjadi isi terbaru, termasuk pengosongan informasi kontak jika kolom kosong. Penyimpanan dilakukan dalam satu transaksi; kegagalan membatalkan seluruh perubahan. Pratinjau berlaku 30 menit, terikat kepada pengguna yang membuatnya, dan tidak dapat dipakai ulang setelah berhasil. Data diperiksa ulang sebelum penyimpanan; perubahan sejak pratinjau mengharuskan pratinjau baru.

### Format seluruh template impor

Seluruh template mengikuti format daftar peserta yang diberikan: judul pada baris ke-1, nama sekolah pada baris ke-2, baris kosong ke-3 sebagai pemisah kop, dan nama kolom huruf besar pada baris ke-4. Pemisah CSV memakai titik koma. Contoh template siswa:

```csv
DAFTAR PESERTA EKTRAKULIKULER ENGLISH CLUB;;;;;;;
SMKN 1 CIANJUR;;;;;;;
;;;;;;;
NIS;NAMA_LENGKAP;TAHUN_BERGABUNG;KELAS;STATUS;TELEPON;EMAIL;ALAMAT
```

Judul menyesuaikan jenis data:

| Template | Judul kop |
| --- | --- |
| Siswa | DAFTAR PESERTA EKTRAKULIKULER ENGLISH CLUB |
| Keanggotaan | DAFTAR KEANGGOTAAN EKSTRAKURIKULER ENGLISH CLUB |
| Presensi | REKAP PRESENSI EKSTRAKURIKULER ENGLISH CLUB |
| Nilai resmi | DAFTAR NILAI RESMI EKSTRAKURIKULER ENGLISH CLUB |

Nama sekolah mengikuti konfigurasi laporan. Setiap baris kop dan baris kosong memiliki jumlah sel yang sama dengan kolom data: 6 sel untuk keanggotaan, 8 sel untuk jenis lainnya. Isi data mulai **baris ke-5**, pertahankan kop serta nama kolom, lalu simpan sebagai CSV UTF-8. Baris kosong diabaikan; nomor baris pada pratinjau dan kesalahan mengikuti baris spreadsheet, termasuk kop. Isi sel yang mengandung pemisah atau baris baru harus diapit tanda kutip sesuai format CSV. File lama dengan judul kolom langsung pada baris pertama tetap dapat diimpor untuk keempat jenis data. Pilih jenis data sesuai judul template; kop milik jenis lain ditolak.

## Kolom dan aturan

| Jenis data | Kolom CSV |
| --- | --- |
| Siswa | `nis,nama_lengkap,tahun_bergabung,kelas,status,telepon,email,alamat` |
| Keanggotaan | `nis,nama_lengkap,tahun_ajaran,semester,kelas,status` |
| Presensi | `nis,nama_lengkap,kegiatan_id,tanggal_kegiatan,judul_kegiatan,tahun_ajaran,semester,status` |
| Nilai resmi | `nis,nama_lengkap,judul_penilaian,aspek,nilai,tanggal_pengesahan,umpan_balik,catatan_pembina` |

**Siswa:** NIS harus unik dan tidak boleh mengambil username akun lain. Status: `active`, `inactive`, `left`, `alumni`. Tahun bergabung antara 2000 dan tahun sekarang. Kelas harus sesuai daftar kelas; kelas lama boleh dipertahankan saat pembaruan. Kontak boleh kosong. Akun baru mengikuti aturan aplikasi: username dan kata sandi awal sesuai NIS, peran `siswa`. Pembaruan mempertahankan kata sandi, foto, peran, dan masa jabatan panitia. Kelas siswa yang masih memiliki masa jabatan panitia harus XI atau XII.

**Keanggotaan:** siswa, tahun ajaran, dan semester harus sudah ada. Semester: `ganjil` atau `genap`. Satu siswa hanya memiliki satu keanggotaan per semester. Keanggotaan aktif baru/pembaruan membutuhkan siswa aktif. Kelas adalah kelas keanggotaan semester, bukan perubahan otomatis pada data induk siswa.

**Presensi:** gunakan ID kegiatan dan identitas yang diperoleh dari ekspor. Tanggal, judul, tahun ajaran, dan semester wajib sesuai dengan kegiatan tersebut. Status: `hadir`, `izin`, `sakit`, `alpa`, `belum_diisi`. Siswa harus termasuk roster kegiatan. Koreksi presensi lama tetap diperbolehkan untuk siswa yang kemudian tidak aktif, selama presensinya telah tersimpan. Kegiatan dibatalkan ditolak. Impor tidak membuat kegiatan baru.

**Nilai resmi:** aspek: `listening`, `speaking`, `reading`, `writing`; nilai bilangan bulat 1–4. Tanggal pengesahan: `YYYY-MM-DD HH:mm:ss` dalam WIB dan tidak boleh di masa mendatang. Umpan balik dan catatan pembina boleh kosong. NIS + judul penilaian + aspek menjadi identitas arsip. Data identik dilewati; nilai resmi berbeda dan penugasan yang sedang berjalan ditolak. Impor membuat penilaian arsip yang telah disahkan, menyimpan identitas pembina serta tanggal pengesahan, dan menambahkan peristiwa audit `imported` dengan waktu impor. Impor nilai tidak menggantikan pemeriksaan rekomendasi panitia.

Untuk keanggotaan, presensi, dan nilai, `nama_lengkap` boleh kosong. Jika diisi, nama harus sesuai dengan NIS. Impor siswa dilakukan sebelum data yang bergantung pada siswa; keanggotaan dilakukan sebelum presensi siswa baru.
