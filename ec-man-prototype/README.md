# EC-Man: lanjutkan desain melalui Codex di VS Code

Paket ini membawa source prototipe dan konteks penerapannya ke komputer Anda. Ini paket referensi, belum merupakan patch aplikasi Laravel dan belum terhubung langsung ke ekstensi Codex Anda.

## Cara menggunakan

1. Ekstrak ZIP. Salin folder `ec-man-prototype` ke folder utama proyek EC-Man Anda, sejajar dengan `composer.json`, `package.json`, `app`, dan `resources`.
2. Di VS Code, pilih File > Open Folder dan buka folder utama proyek EC-Man tersebut.
3. Buka panel Codex melalui ikon Codex. Jika ikonnya tidak terlihat, tekan Ctrl+Shift+P dan jalankan `Codex: Open Codex Sidebar`.
4. Pilih pekerjaan lokal pada folder proyek itu. Login jika ekstensi memintanya.
5. Buka `ec-man-prototype/EC-MAN-CODEX-TASK.md` di editor, kemudian kirim pesan berikut pada Codex:

   ```text
   Baca ec-man-prototype/EC-MAN-CODEX-TASK.md dan source desain di ec-man-prototype/reference. Terapkan desain pada proyek EC-Man ini sesuai instruksi. Kerjakan perubahan secara lokal, validasi build dan tes, lalu tampilkan ringkasan diff. Jangan push, merge, deploy, atau mengubah sistem eksternal tanpa persetujuan saya.
   ```

6. Periksa perubahan yang dihasilkan di VS Code, jalankan aplikasi lokal, dan coba sebagai panitia, pembina, serta siswa.

Source prototipe ada dalam `reference/`. HTML tersebut memuat modul JavaScript; bila ingin menjalankan prototipe tersendiri, sajikan folder tersebut melalui server HTTP lokal. Codex dapat membaca file sumbernya sebagai referensi desain tanpa membuka URL privat.

## Isi paket

- `EC-MAN-CODEX-TASK.md`: tugas penerapan, pemetaan file, aturan backend, dan kriteria selesai.
- `reference/index.html`, `styles.css`, `app.mjs`, `model.mjs`: source prototipe statis yang dibuat dalam percakapan.

Paket tidak menyertakan repository Git, database, file lingkungan, maupun kredensial. Data pada prototipe adalah contoh dan pilihan role adalah simulasi. Saat penerapan ke Laravel, data dan hak akses menggunakan backend aplikasi.

Dokumentasi ekstensi: https://learn.chatgpt.com/docs/codex/ide
Repository sasaran: https://github.com/sarahsitis/ec-man
Prototipe privat: https://ec-man-panitia-demo.happytick9.chatgpt.site
