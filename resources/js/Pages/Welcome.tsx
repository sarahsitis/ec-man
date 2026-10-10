import { Brand, LearningArt } from '@/Components/PublicBranding';
import { Icon, IconName } from '@/Components/WorkspaceUi';
import { User } from '@/types';
import { Head, Link } from '@inertiajs/react';

const features: { icon: IconName; title: string; description: string }[] = [
    { icon: 'book', title: 'Mulai dengan mengenal diri', description: 'Pre-test membantu mengenali kemampuan awal, minat, dan tujuan belajar sebelum memulai latihan.' },
    { icon: 'calendar', title: 'Kegiatan lebih terarah', description: 'Jadwal, tujuan pertemuan, dan presensi tersimpan dalam satu tempat agar setiap latihan punya arah.' },
    { icon: 'people', title: 'Keanggotaan tertata', description: 'Pembina mengelola anggota, kelas, status siswa, dan keanggotaan untuk setiap semester.' },
    { icon: 'clipboard', title: 'Panitia ikut mendampingi', description: 'Panitia yang ditugaskan mencatat pengamatan dan memberi saran latihan sesuai aspek kemampuan siswa.' },
    { icon: 'check', title: 'Penilaian yang jelas', description: 'Rekomendasi menggunakan rubrik 1–4 dan diperiksa pembina sebelum diterbitkan sebagai nilai resmi.' },
    { icon: 'grid', title: 'Perkembangan terlihat', description: 'Grafik kemampuan, riwayat nilai resmi, dan laporan minat membantu menentukan langkah belajar berikutnya.' },
];
const roles: { icon: IconName; name: string; title: string; description: string }[] = [
    { icon: 'book', name: 'Siswa', title: 'Temukan potensi Anda.', description: 'Kenali kemampuan awal, ikuti kegiatan, dan lihat perkembangan melalui hasil yang sudah disahkan.' },
    { icon: 'clipboard', name: 'Panitia EC', title: 'Bantu teman bertumbuh.', description: 'Amati siswa yang ditugaskan, simpan draf, dan kirim rekomendasi serta saran latihan kepada pembina.' },
    { icon: 'shield', name: 'Pembina', title: 'Arahkan setiap langkah.', description: 'Kelola anggota dan kegiatan, delegasikan penilaian, lalu tinjau rekomendasi sebelum mengesahkan nilai.' },
];
const steps = [
    { title: 'Kenali', description: 'Isi pre-test untuk mengenali kemampuan awal dan minat belajar.' },
    { title: 'Ikuti', description: 'Ikuti kegiatan English Club dan berlatih bersama teman.' },
    { title: 'Amati', description: 'Panitia mencatat pengamatan; pembina meninjau rekomendasinya.' },
    { title: 'Kembangkan', description: 'Lihat hasil resmi dan gunakan saran latihan untuk terus berkembang.' },
];
const questions = [
    { question: 'Bagaimana cara mendapatkan akun?', answer: 'Akun siswa dan panitia dikelola oleh pembina. Hubungi pembina English Club untuk mendapatkan akun, lalu masuk menggunakan username atau NIS dan password Anda.' },
    { question: 'Apa yang dilakukan saat pre-test?', answer: 'Siswa menjawab soal kemampuan awal, mengisi penilaian diri, serta memilih minat dan tujuan belajar. Hasilnya menjadi acuan untuk pendampingan dan latihan.' },
    { question: 'Apakah rekomendasi panitia langsung menjadi nilai?', answer: 'Pembina perlu memeriksa dan mengesahkan rekomendasi terlebih dahulu. Rekomendasi yang masih berupa draf, menunggu pemeriksaan, perlu revisi, atau ditolak belum menjadi nilai resmi.' },
    { question: 'Kemampuan apa saja yang dapat dinilai?', answer: 'Listening, speaking, reading, dan writing. Setiap penugasan menilai satu aspek menggunakan rubrik 1–4; siswa dapat melihat nilai resmi miliknya pada halaman perkembangan.' },
];

export default function Welcome({ auth }: { auth: { user: User | null } }) {
    const destination = route(auth.user ? 'dashboard' : 'login');
    const action = auth.user ? 'Buka dashboard' : 'Masuk ke EC-Man';
    return <div className="ec-landing" lang="id">
        <Head title="EC-Man — Ruang Tumbuh English Club"><meta name="description" content="Kenali kemampuan dan minat, ikuti kegiatan English Club, serta pantau perkembangan belajar bersama siswa, panitia, dan pembina melalui EC-Man." /></Head>
        <a href="#landing-main" className="el-skip">Lewati ke konten</a>
        <header className="el-header"><div className="el-container el-header-inner"><Brand /><nav aria-label="Navigasi beranda"><a href="#fitur">Fitur</a><a href="#alur">Alur belajar</a><a href="#peran">Untuk siapa</a></nav><Link className="el-button el-button-primary el-header-action" href={destination}>{auth.user ? 'Dashboard' : 'Masuk'}<Icon name="arrow" /></Link></div></header>
        <main id="landing-main" tabIndex={-1}>
            <section className="el-container el-hero" aria-labelledby="hero-title"><div className="el-hero-copy"><p className="el-eyebrow"><span />RUANG TUMBUH ENGLISH CLUB</p><h1 id="hero-title">Berani berbahasa.<br /><em>Siap berkembang.</em></h1><p className="el-lead">Setiap siswa punya titik awal dan potensi yang berbeda. EC-Man membantu mengenali kemampuan, menemukan minat, dan membuat setiap latihan lebih bermakna.</p><div className="el-actions"><Link href={destination} className="el-button el-button-primary">{action}<Icon name="arrow" /></Link><a href="#alur" className="el-button el-button-text">Kenali alurnya<Icon name="back" className="el-arrow-down" /></a></div><p className="el-account-note"><Icon name="shield" />Akun siswa dan panitia dikelola oleh pembina.</p></div>
                <div className="el-hero-visual"><div className="el-visual-heading"><span>English Club</span><span className="el-visual-tag">BELAJAR BERSAMA</span></div><LearningArt /><div className="el-visual-caption"><p>Small steps.<br /><em>Real progress.</em></p><span>Belajar hari ini.<br />Lebih percaya diri esok hari.</span></div><div className="el-skill-list" aria-label="Empat aspek kemampuan">{['Listening', 'Speaking', 'Reading', 'Writing'].map(skill => <span key={skill}>{skill}</span>)}</div></div>
            </section>
            <div className="el-principles"><div className="el-container">{([{ icon: 'book', text: 'Kenali kemampuan & minat' }, { icon: 'people', text: 'Berlatih dan tumbuh bersama' }, { icon: 'check', text: 'Pantau perkembangan belajar' }] as const).map(item => <p key={item.text}><Icon name={item.icon} />{item.text}</p>)}</div></div>
            <section id="fitur" className="el-container el-section" aria-labelledby="features-title"><div className="el-section-heading"><div><p className="el-eyebrow">SATU TEMPAT, SETIAP LANGKAH</p><h2 id="features-title">Kegiatan tertata.<br />Potensi lebih terlihat.</h2></div><p>Mulai dari mengenali diri hingga melihat hasil belajar, EC-Man mendukung perjalanan English Club yang terarah.</p></div><div className="el-feature-grid">{features.map((feature, index) => <article key={feature.title} className="el-feature"><span className={`el-icon-tile el-icon-tone-${index % 3}`}><Icon name={feature.icon} /></span><h3>{feature.title}</h3><p>{feature.description}</p></article>)}</div></section>
            <section id="peran" className="el-roles" aria-labelledby="roles-title"><div className="el-container"><div className="el-section-heading"><div><p className="el-eyebrow">BERKEMBANG MELALUI KOLABORASI</p><h2 id="roles-title">Satu ruang.<br />Tiga peran yang saling mendukung.</h2></div><p>Siswa berlatih, panitia mendampingi, dan pembina mengarahkan. Setiap peran memiliki ruang dan tanggung jawabnya.</p></div><div className="el-role-grid">{roles.map(role => <article key={role.name} className="el-role"><div className="el-role-label"><Icon name={role.icon} /><span>{role.name}</span></div><h3>{role.title}</h3><p>{role.description}</p></article>)}</div></div></section>
            <section id="alur" className="el-container el-section" aria-labelledby="steps-title"><div className="el-section-heading"><div><p className="el-eyebrow">LANGKAH KECIL, ARAH YANG JELAS</p><h2 id="steps-title">Perjalanan belajar<br />dimulai dari sini.</h2></div><p>Latihan dan pendampingan berjalan bersama agar siswa memahami kemampuan serta langkah berikutnya.</p></div><ol className="el-steps">{steps.map((step, index) => <li key={step.title}><span className="el-step-number">0{index + 1}</span><h3>{step.title}</h3><p>{step.description}</p></li>)}</ol></section>
            <section className="el-container el-faq" aria-labelledby="faq-title"><div><p className="el-eyebrow">SEBELUM MEMULAI</p><h2 id="faq-title">Ada yang ingin<br />Anda ketahui?</h2><p>Beberapa hal tentang akun, pre-test, dan penilaian di EC-Man.</p></div><div className="el-faq-list">{questions.map(question => <details key={question.question}><summary>{question.question}<span aria-hidden="true">+</span></summary><p>{question.answer}</p></details>)}</div></section>
            <section className="el-container el-cta" aria-labelledby="cta-title"><div><p className="el-eyebrow">JADIKAN SETIAP LATIHAN BERARTI</p><h2 id="cta-title">Langkah berikutnya<br />dimulai bersama.</h2><p>Masuk ke ruang English Club Anda dan lanjutkan perjalanan belajar.</p></div><Link href={destination} className="el-button el-button-primary">{action}<Icon name="arrow" /></Link></section>
        </main>
        <footer className="el-container el-footer"><Brand /><p>English Club · Belajar, berlatih, berkembang.</p><span>© {new Date().getFullYear()} EC-Man</span></footer>
    </div>;
}
