# Tugas Codex: terapkan desain prototipe EC-Man pada proyek lokal

## Tujuan dan batas pekerjaan

Terapkan tampilan prototipe pada aplikasi EC-Man Laravel yang terbuka di VS Code. Pengguna ingin dashboard serta menu khusus panitia EC sebagai asisten penilai pembina, dengan alur melihat peserta yang ditugaskan, menyimpan draf rekomendasi, mengirim ke pembina, dan melihat hasil tinjauan.

Kerjakan source aplikasi secara lokal sampai implementasi dan validasi selesai. Jangan melakukan push, merge, deployment, perubahan hak akses hosting, atau mutasi sistem eksternal tanpa persetujuan pengguna. Jangan mengakses database sekolah untuk pengujian. Jaga perubahan pengguna yang sudah ada. Jangan mengubah atau membatalkan pekerjaan lain.

Paket ini adalah konteks untuk tugas tertentu, bukan AGENTS.md atau skill. Baca AGENTS.md yang berlaku di proyek dan periksa kondisi Git sebelum mengedit. Bila perlu, buat branch baru atau checkout terpisah dengan cara yang mempertahankan perubahan lokal pengguna. Tidak perlu membuat plugin atau MCP baru.

## Sumber desain

Baca file `reference/index.html`, `reference/styles.css`, `reference/app.mjs`, dan `reference/model.mjs` relatif terhadap direktori dokumen ini.

Pertahankan arah visual: sidebar putih, latar abu kehijauan, hijau tua sebagai warna utama (#1c5446), kartu putih dengan garis tipis dan sudut membulat, hero hijau muda, tabel peserta yang terbaca, badge status, indikator progres, serta formulir dengan ringkasan di samping. Buat responsif untuk ponsel dan desktop. Gunakan navigasi keyboard, label input, pesan validasi, menu aktif, keadaan kosong, serta tombol yang jelas.

Source prototipe statis adalah referensi tampilan dan alur. Port ke komponen React/TypeScript; jangan menjalankan app.mjs bersamaan dengan React atau menyisipkan seluruh aplikasi statis ke halaman produksi. Jangan membawa selector simulasi role, identitas dummy, data contoh, atau localStorage penilaian ke fitur produksi.

## Konteks repository yang telah diperiksa

Repository: https://github.com/sarahsitis/ec-man.git
Branch yang diperiksa: main
Commit referensi saat pemeriksaan: 71010be72476732833b463ba2ababeb2d7143c6a

Kode lokal pengguna adalah sumber utama. Bila sudah berbeda dari commit referensi, sesuaikan dengan kode lokal terkini; jangan memaksa kembali ke snapshot ini.

Stack yang diperiksa: Laravel 12, PHP ^8.2, React 18, TypeScript, Inertia 2, Tailwind, dan Vite. Backend penilaian sudah tersedia sehingga fokus awalnya adalah penerapan desain frontend.

### Pemetaan file

| Bagian | File proyek yang diperiksa |
|---|---|
| Layout dan menu berdasarkan role | resources/js/Layouts/AuthenticatedLayout.tsx |
| Dashboard khusus panitia | resources/js/Pages/Panitia/Dashboard.tsx |
| Daftar penugasan panitia | resources/js/Pages/Panitia/Assignments.tsx |
| Form rekomendasi dan tinjauan | resources/js/Pages/Assessments/Show.tsx |
| Antrean pemeriksaan pembina | resources/js/Pages/Assessments/Queue.tsx |
| Hasil resmi siswa | resources/js/Pages/Progress/Index.tsx |
| Komponen tabel, status, dan pagination | resources/js/Components/AssessmentUi.tsx |
| Stylesheet utama | resources/css/app.css |
| Data halaman dan validasi request | app/Http/Controllers/AssessmentController.php |
| Transisi status dan penerbitan nilai resmi | app/Services/AssessmentService.php |

Gunakan layout khusus panitia bila itu memudahkan isolasi desain. Halaman Assessments/Show dipakai pembina dan panitia; pertahankan perbedaan kontrol dan pilih layout sesuai akun. Menu dan halaman lain milik panitia yang sudah ada (perkembangan sendiri, pre-test, kegiatan/presensi) harus tetap dapat dibuka, termasuk pada ponsel.

## Integrasi data yang sudah tersedia

- Dashboard panitia menerima `counts`, `recent`, dan `committeeTerm` dari Laravel.
- Daftar penugasan menerima `assignments` berupa paginator dan `status`.
- Detail penilaian menerima `assignment`, `expired`, dan `nextPendingId`.
- Akun tersedia melalui `usePage().props.auth.user`; `is_panitia` mencerminkan masa tugas dan kelayakan panitia, bukan sekadar string role.
- Gunakan Inertia Link dan useForm, serta route yang sudah ada. Pertahankan pagination dan feedback server.

Route penting yang diperiksa:

| Nama | Perilaku |
|---|---|
| panitia.dashboard | Dashboard panitia |
| panitia.assignments | Penugasan aktif/revisi atau riwayat dengan status=history |
| assessments.show | Detail satu penugasan |
| assessments.recommend | POST draf/kirim rekomendasi panitia |
| assessments.queue | Antrean tinjauan pembina |
| assessments.review | POST keputusan pembina |
| progress.index | Nilai resmi milik siswa |

Form rekomendasi menggunakan `action` = draft/submit, `proposed_score`, `observations`, dan `feedback`. Form tinjauan menggunakan `decision` = approve/revise/reject, `final_score`, serta `review_note`. Pertahankan pesan kesalahan untuk seluruh field dan deadline, status processing, serta penguncian setelah kirim.

## Perbedaan prototipe dan backend yang wajib diakomodasi

1. Prototipe memakai empat kriteria berbobot pada skala 0–100 sebagai contoh. Backend EC-Man menggunakan aspek listening, speaking, reading, writing dan skor internal 1–4 per penugasan. Pertahankan rubrik backend. Jangan mengubah skema atau skala penilaian untuk meniru angka contoh desain.
2. Status backend: assigned (belum dinilai), draft, submitted (menunggu pembina), revision (perlu revisi), approved (disahkan/final), dan rejected (ditolak). Tampilkan semuanya dengan jelas, termasuk rejected yang belum ditunjukkan alur utama prototipe.
3. Pembatasan kelompok A/B pada prototipe adalah contoh; backend membatasi penugasan lewat assessor_id. Jangan menghardcode kelompok.
4. `counts` adalah jumlah penugasan, bukan jumlah peserta unik. Labeli kartu "Penugasan saya" bila memakai jumlah tersebut. Bila memakai label "Peserta ditugaskan", hitung distinct student_id dari seluruh penugasan panitia di controller, bukan dari daftar recent atau satu halaman paginator.
5. Jangan membentuk skor gabungan atau persentase nilai dari rubrik 1–4 tanpa aturan bisnis. Persentase progres dapat dihitung dari status penugasan dengan definisi yang ditampilkan jelas.
6. Catatan observasi dan umpan balik saat kirim wajib masing-masing minimal 10 karakter, maksimal 2.000 menurut validasi yang diperiksa. Gunakan ketentuan backend, bukan batas catatan prototipe.

## Hak akses dan alur

- Panitia aktif hanya membuka dan menilai penugasannya sendiri, serta tidak boleh menilai diri sendiri.
- Draf/revisi/assigned dapat diedit; submitted terkunci sampai pembina meminta revisi. Masa tugas dan deadline tetap berlaku.
- Pembina meninjau rekomendasi submitted; dapat menyetujui, meminta revisi, atau menolak. Nilai resmi diterbitkan oleh proses backend saat approved.
- Siswa hanya melihat nilai resmi miliknya di halaman perkembangan. Data rekomendasi internal tidak dikirim ke halaman siswa.
- Pertahankan EnsureActiveCommittee, EnsurePembina, pemeriksaan scope controller, transaksi, audit, dan aturan AssessmentService yang sudah ada. Menyembunyikan tombol tidak menggantikan pemeriksaan server.

## Urutan implementasi

1. Periksa proyek lokal, instruksi, perubahan Git, komponen frontend, props, routes, dan dependensi yang tersedia.
2. Terapkan layout, warna, sidebar responsif, serta identitas dari akun login. Hindari CSS global prototipe yang menimpa halaman lain; gunakan scope atau utility Tailwind.
3. Terapkan dashboard panitia memakai data asli, progres dengan definisi jelas, tautan penugasan, dan keadaan kosong.
4. Terapkan tabel penugasan serta detail peserta/form rekomendasi. Pertahankan backend 1–4, catatan, deadline, riwayat, draft/submit, pesan error, dan status terkunci.
5. Pastikan tinjauan pembina dan hasil siswa tetap selaras. Tambahkan konfirmasi pengiriman/pengesahan serta penjagaan perubahan belum disimpan bila cocok dengan struktur aplikasi.
6. Validasi build dan tes relevan, perbaiki kegagalan akibat perubahan, lalu tampilkan hasil dan diff untuk pengguna.

## Validasi dan kriteria selesai

- Jalankan `npm run build` dan `php artisan test` bila lingkungan lokal mendukung. Gunakan konfigurasi testing yang tersedia (snapshot phpunit.xml menggunakan SQLite :memory:); jangan mengalihkan tes ke database sekolah. Jika dependency atau layanan tidak tersedia, laporkan batas validasi secara spesifik.
- Uji manual/brower lokal jika fasilitas tersedia: panitia aktif membuka daftar dan detail; simpan draf; kirim; pembina meminta revisi; panitia mengirim ulang; pembina mengesahkan; siswa melihat nilai resmi miliknya.
- Periksa akses URL langsung milik panitia lain/siswa, deadline, masa tugas habis, double submit, serta tidak ada kontrol tinjauan untuk panitia/siswa. Manfaatkan tes yang sudah ada (PanitiaRoleTest, AssessmentWorkflowTest, OfficialAssessmentTest, CommitteeRoleWorkflowTest) dan hindari duplikasi tes.
- Uji tampilan sekitar lebar 375px dan desktop, menu mobile, label/fokus keyboard, tabel overflow, error validasi, status kosong, dan loading tombol.
- Jangan mengklaim pengujian browser atau tes yang tidak dijalankan. Prototype sumber ini telah lolos pemeriksaan logika dan controller event pada Node, tetapi belum melalui QA visual browser.
- Selesai ketika desain sudah diterapkan, data asli terhubung, alur dan akses tetap benar, validasi relevan telah dijalankan atau batasnya dilaporkan, dan perubahan siap ditinjau tanpa push/merge/deploy otomatis.

## Hasil yang dilaporkan

Tampilkan ringkasan perubahan, daftar file yang diubah, hasil build/tes, batas verifikasi, dan cara membuka alur lokal untuk ketiga akun. Gunakan bahasa Indonesia yang mudah dipahami.
