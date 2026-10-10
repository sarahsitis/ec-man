# Dummy data siswa dan jadwal

Branch: `dummy-data-siswa-jadwal`.

Jalankan setelah migrasi:

```powershell
php artisan db:seed --class=DemoDataSeeder
```

Seeder menambahkan 50 siswa contoh, termasuk 15 panitia. Data siswa sebelumnya tetap ada, sehingga jumlah siswa keseluruhan dapat lebih dari 50. Akun, nama, kegiatan, dan skema contoh menggunakan penanda `demo` atau `[Demo]`.

| Akun | Peran | Pre-test |
| --- | --- | --- |
| `demo001`–`demo015` | Panitia dan siswa | Selesai |
| `demo016`–`demo035` | Siswa | Selesai |
| `demo036`–`demo050` | Siswa | Belum mengisi |

Password seluruh akun contoh: `demo12345`. Seeder menggunakan pembina yang sudah ada. Jika belum ada, akun `demo_pembina` dibuat dengan password yang sama.

Semua siswa contoh memiliki kelas dari katalog sekolah, status aktif, dan keanggotaan semester sesuai bulan berjalan. Panitia berada di kelas XI/XII dengan masa aktif dari tanggal pertama hingga terakhir bulan tersebut.

Sebanyak 35 hasil pre-test memiliki jawaban lengkap, tingkat kemampuan yang bervariasi, penilaian diri empat aspek, tujuan belajar, dan dua atau tiga pilihan minat dengan satu minat utama. Nilai dihitung menggunakan `PreTestService` yang sama dengan pengisian dari aplikasi. Hasil pre-test tidak diterbitkan sebagai nilai resmi.

Jadwal dibuat selama satu bulan kalender berjalan menurut WIB, setiap Selasa dan Kamis pukul **14.30–16.00**. Pada Oktober 2026 terdapat **9 pertemuan**, dari 1 sampai 29 Oktober. Materi bergilir antara percakapan, storytelling, mendengarkan, debat, menulis kreatif, dan permainan bahasa. Jadwal berstatus terjadwal dan belum memiliki presensi.

Semester mengikuti tahun ajaran dan semester kalender bulan berjalan. Bila semester aktif sebelumnya berbeda, semester aktif tersebut dipertahankan; pilih semester yang sesuai pada filter Keanggotaan untuk melihat siswa contoh. Bila belum ada semester aktif, semester contoh diaktifkan.

Menjalankan seeder kembali pada bulan yang sama tidak menggandakan akun, siswa, keanggotaan, pengangkatan panitia, hasil pre-test, skema, atau jadwal yang sama. Perubahan manual pada data yang sudah dibuat dipertahankan. Menjalankannya pada bulan berbeda menggunakan akun yang sama dan menambahkan jadwal serta masa panitia untuk bulan baru.

Validasi: 8 pengujian seeder lulus dengan 542 assertions, termasuk login, akses presensi, laporan pre-test/minat, pengulangan tanpa duplikasi, perubahan manual, pergantian semester, tahun kabisat, dan rollback seluruh batch saat terjadi benturan NIS. Seluruh suite aplikasi lulus: 144 tes, 2.903 assertions. Setelah seeding database lokal, jumlah data terverifikasi: 50 siswa contoh, 15 panitia aktif, 35 pre-test selesai, dan 9 jadwal Oktober 2026.
