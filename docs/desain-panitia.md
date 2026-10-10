# Penerapan desain workspace EC-Man

Desain `ec-man-prototype/reference` diterapkan pada branch lokal `feat/desain-panitia`. Sidebar putih, latar abu kehijauan, warna utama `#1c5446`, hero hijau muda, kartu, tabel peserta, badge, progres, dan ringkasan penilaian menggunakan komponen React/TypeScript. CSS dibatasi dengan `.ec-workspace`.

## Perubahan

- Layout membaca identitas dan `is_panitia` dari akun login. Sidebar ponsel menggunakan dialog dengan fokus terjaga, Escape, tombol tutup, dan pengembalian fokus. Menu perkembangan, pre-test, kegiatan/presensi, profil, dan keluar tetap tersedia.
- Dashboard `/dashboard` pembina dan layout seluruh menu pembina juga memakai workspace baru. Dashboard menyediakan hero dan pintasan pengelolaan, memakai nama akun asli tanpa angka statistik contoh.
- Dashboard panitia menggunakan `counts`, `recent`, dan `committeeTerm` dari server. Kartu menghitung **penugasan**, bukan siswa unik. Progres = `(submitted + approved + rejected) / seluruh enam status`; revisi kembali menjadi pekerjaan aktif. Daftar terbaru tidak dipakai sebagai dasar jumlah keseluruhan.
- Daftar aktif/riwayat mempertahankan filter dan pagination server. Semua status backend, termasuk ditolak, memiliki label. Tabel dapat digeser tanpa membuat halaman ponsel melebar.
- Rekomendasi tetap memakai satu aspek dan rubrik **1–4**. Draf boleh belum lengkap; pengiriman membutuhkan skor serta dua catatan masing-masing 10–2.000 karakter. Label, pesan kesalahan, fokus pada input bermasalah, indikator proses, batas waktu, dan status terkunci tersedia.
- Pengiriman dan keputusan pembina memerlukan konfirmasi. Penyesuaian skor, revisi, dan penolakan membutuhkan alasan. Tombol serta pengunci request mencegah pengiriman ganda ketika proses berjalan.
- Halaman perkembangan mempertahankan grafik, filter periode, dan riwayat nilai resmi milik siswa. Observasi internal panitia tetap tidak dikirim ke siswa.
- Middleware, scope controller, transaksi, audit, schema, dan penerbitan `scores` menggunakan backend yang sudah ada. Tidak ada runtime prototipe, selector role, identitas contoh, kelompok hardcoded, atau penyimpanan penilaian di localStorage.

Peringatan perubahan belum disimpan berlaku pada navigasi Inertia GET, keluar akun, refresh, dan penutupan tab. Navigasi riwayat browser Back/Forward tidak dicegat oleh hook ini.

## File aplikasi dan tes

| Bagian | File |
| --- | --- |
| Layout | `resources/js/Layouts/WorkspaceLayout.tsx` (baru), `resources/js/Layouts/AuthenticatedLayout.tsx` |
| Visual dan data presentasi | `resources/js/Components/WorkspaceUi.tsx` (baru), `resources/js/Components/assessmentPresentation.ts` (baru), `resources/js/Components/AssessmentUi.tsx` |
| Dashboard dan daftar | `resources/js/Pages/Dashboard.tsx`, `resources/js/Pages/Panitia/Dashboard.tsx`, `resources/js/Pages/Panitia/Assignments.tsx` |
| Rekomendasi dan tinjauan | `resources/js/Pages/Assessments/Show.tsx`, `resources/js/Pages/Assessments/Queue.tsx` |
| Hasil siswa | `resources/js/Pages/Progress/Index.tsx` |
| CSS | `resources/css/workspace.css` (baru), `resources/css/app.css` |
| Perubahan belum disimpan | `resources/js/hooks/useUnsavedChanges.ts` (baru) |
| Tes frontend | `tests/frontend/assessmentPresentation.test.mjs` (baru) |

## Validasi lokal

```powershell
npm.cmd run build
& 'C:\xampp\php\php.exe' artisan test
node --test tests/frontend/assessmentPresentation.test.mjs
git diff --check
```

- TypeScript dan Vite berhasil dibangun.
- Backend: **144 tes lulus, 2.903 assertion**. `phpunit.xml` memakai SQLite `:memory:`; `Tests/TestCase.php` menolak database lain sebelum migrasi pengujian.
- Frontend: **6 tes lulus**, mencakup perhitungan progres seluruh status, daftar kosong, label ditolak, draf/pengiriman, skala dan panjang catatan, serta tanggal WIB.
- Pengujian browser memakai Chrome headless pada **1440px dan 375px**, server lokal terpisah, SQLite khusus QA, dan akun sintetis dari seeder. Database sekolah tidak digunakan.

Pemeriksaan browser menyeluruh **lulus**, tanpa exception JavaScript yang tertangkap. Alur draf → pengiriman → revisi → pengiriman ulang → pengesahan → nilai resmi siswa, konfirmasi/batal, alasan perubahan skor, klik pengesahan ganda, validasi kosong, dan tombol/input terkunci selama request telah diperiksa. Query read-only pada SQLite QA memastikan klik pengesahan ganda menghasilkan tepat satu nilai resmi 4/4 dan satu event pengesahan. Pemeriksaan akses mencakup URL penugasan panitia lain, URL detail bagi siswa, deadline lewat, masa tugas panitia berakhir, serta rekomendasi ditolak. Tampilan daftar aktif/riwayat, dashboard/riwayat kosong, halaman pre-test, kegiatan, presensi, dan perkembangan diuji pada ponsel; overflow presensi diperbaiki dengan `fieldset { min-width: 0 }` dalam scope workspace.

Hasil browser dan screenshot disimpan secara lokal di `storage/app/design-qa/`. Direktori tersebut diabaikan Git dan tidak menjadi fitur/data produksi. Fokus dialog, focus guard, latar inert, Escape, dan pengembalian fokus diperiksa melalui DOM browser; tombol keyboard fisik dan perangkat ponsel sungguhan belum diuji.

Pemeriksaan tambahan dashboard pembina lulus pada 1440px dan 375px, termasuk menu ponsel. Seluruh delapan menu selain dashboard dapat dibuka dengan sidebar baru, penanda menu aktif yang sesuai, dan tanpa overflow halaman pada 375px. Hasil tambahan ada di `storage/app/design-qa/pembina-results.json`; build dan enam tes frontend dijalankan ulang setelah perubahan pembina.

## Membuka alur pada aplikasi lokal

Gunakan server EC-Man lokal yang biasa digunakan dan akun sesuai perannya. Build di atas sudah menghasilkan aset aplikasi.

1. **Panitia aktif:** buka `/panitia/dashboard`, lalu `/panitia/penugasan`. Pilih penugasan sendiri, simpan draf, dan kirim setelah konfirmasi. Riwayat berada di `/panitia/penugasan?status=history`.
2. **Pembina:** buka `/dashboard` untuk tampilan pengelolaan baru, lalu `/pemeriksaan-rekomendasi`. Periksa pengajuan, minta revisi atau sahkan dengan konfirmasi. Jika mengubah skor, isi alasan.
3. **Siswa:** buka `/perkembangan-saya` untuk melihat nilai resmi miliknya setelah pengesahan. Pre-test dan kegiatan tetap melalui `/pre-test` dan `/kegiatan`.

Detail penugasan berada di `/penilaian/{id}`; pembatasan akses server tetap berlaku. Tidak ada push, merge, deploy, atau commit otomatis dalam pekerjaan ini.
