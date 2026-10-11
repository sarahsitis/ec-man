# Animasi landing page

Landing page memakai `resources/css/landing-motion.css` dan hook `resources/js/hooks/useLandingMotion.ts` tanpa dependensi tambahan.

- Smooth scroll hanya aktif saat landing page terbuka. Offset anchor mengikuti header sticky desktop/mobile.
- Fade-in dan slide-up muncul sekali ketika hero, kartu, alur belajar, FAQ, serta CTA masuk viewport. IntersectionObserver memicu transisi CSS, dengan jeda singkat antar kartu. Konten tetap terlihat jika API observer tidak tersedia; fokus keyboard juga membuka konten.
- Tombol mendapat efek angkat, bayangan, kilau, dan pergeseran ikon. Efek hover dibatasi pada perangkat dengan pointer yang mendukung hover.
- Dekorasi hero memakai dua lapisan dengan pergeseran scroll berbeda. Pembaruan memakai requestAnimationFrame dan listener passive; gerakan dibatasi agar tetap ringan.
- Preloader berupa spinner violet/pink yang tidak menghalangi interaksi. Loader hilang setelah halaman dimuat, dengan batas waktu 1,5 detik jika resource lambat.

Sesuai instruksi terbaru pengguna, animasi berjalan otomatis saat landing page dibuka, termasuk ketika perangkat melaporkan `prefers-reduced-motion: reduce`. Tombol pengaturan animasi dihapus. Pilihan `ec-landing-motion` dari versi sebelumnya tidak lagi dibaca atau ditulis. Observer, event listener, timer, frame, dan class document dibersihkan saat navigasi Inertia meninggalkan landing page.

Validasi menggunakan build TypeScript/Vite, enam tes render/autentikasi (10 assertion) dengan SQLite in-memory, serta Chrome headless pada desktop 1440px dan ponsel 375/320px. Browser memeriksa tujuan dan offset anchor, CSS smooth scroll, hover tombol, perubahan posisi parallax, reveal ketika scroll, penghapusan loader, reduced motion, fallback observer, navigasi ke login, tidak ada overflow, dan tidak ada exception JavaScript. Sistem operasi dapat menonaktifkan interpolasi smooth scroll meskipun CSS aktif.

Hasil browser: `storage/app/design-qa/landing-motion-results.json`. Screenshot: `storage/app/design-qa/landing-motion-*.png`. Diff untuk perubahan animasi: `storage/app/design-qa/landing-motion.diff`.

Pemeriksaan versi otomatis dilakukan langsung pada `http://localhost:8000/`: animasi tetap aktif di bawah preferensi `reduce`, pilihan minimal lama diabaikan, tombol tidak muncul, transisi CSS dan parallax aktif, serta cleanup navigasi tetap berlaku. Hasil: `storage/app/design-qa/automatic-motion-results.json`.
