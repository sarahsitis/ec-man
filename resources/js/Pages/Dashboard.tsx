import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { HeroArt, Icon, IconName, PageHeading } from '@/Components/WorkspaceUi';
import { Head, Link, usePage } from '@inertiajs/react';

function PembinaDashboard({ name }: { name: string }) {
    const shortcuts: { title: string; description: string; routeName: string; icon: IconName }[] = [
        { title: 'Kelola anggota', description: 'Perbarui profil, kelas, dan status siswa yang mengikuti English Club.', routeName: 'students.index', icon: 'people' },
        { title: 'Kegiatan & presensi', description: 'Atur pertemuan dan catat kehadiran siswa secara individual atau massal.', routeName: 'activities.index', icon: 'calendar' },
        { title: 'Pemeriksaan rekomendasi', description: 'Tinjau pengamatan panitia, minta revisi, atau sahkan nilai resmi siswa.', routeName: 'assessments.queue', icon: 'check' },
    ];
    const tools: { title: string; description: string; routeName: string; icon: IconName }[] = [
        { title: 'Penugasan panitia', description: 'Delegasikan siswa dan aspek kemampuan yang perlu dinilai.', routeName: 'assessments.index', icon: 'clipboard' },
        { title: 'Panitia EC', description: 'Kelola hak panitia serta masa aktif tugasnya.', routeName: 'committee-roles.index', icon: 'shield' },
        { title: 'Keanggotaan semester', description: 'Kelola anggota pada setiap tahun ajaran dan semester.', routeName: 'memberships.index', icon: 'people' },
        { title: 'Hasil pre-test', description: 'Kenali kemampuan awal dan tujuan belajar siswa.', routeName: 'pretests.reports', icon: 'book' },
        { title: 'Laporan minat', description: 'Lihat distribusi minat sebagai acuan merencanakan kegiatan.', routeName: 'reports.interests', icon: 'grid' },
        { title: 'Skema kegiatan', description: 'Siapkan tujuan dan agenda untuk pertemuan English Club.', routeName: 'activity-schemes.index', icon: 'calendar' },
    ];
    return <WorkspaceLayout><Head title="Dashboard Pembina" />
        <PageHeading eyebrow="Ruang pembina" title={`Selamat datang, ${name}.`} description="Kelola English Club dan arahkan setiap siswa untuk mengembangkan potensinya." />
        <section className="ec-hero"><div className="ec-hero-content"><p className="ec-eyebrow">Pendamping perkembangan siswa</p><h2>Rencanakan kegiatan, tumbuhkan kemampuan.</h2><p>Gunakan kemampuan awal dan minat siswa untuk menyusun latihan. Delegasikan pengamatan kepada panitia, lalu tinjau rekomendasi sebelum menerbitkan nilai resmi.</p><Link href={route('assessments.queue')} className="ec-btn ec-btn-primary">Periksa rekomendasi<Icon name="arrow" /></Link></div><HeroArt /></section>
        <div className="my-6 grid gap-4 md:grid-cols-3">{shortcuts.map(shortcut => <section key={shortcut.routeName} className="ec-card ec-card-body"><div className="ec-stat-icon mb-4"><Icon name={shortcut.icon} /></div><h2>{shortcut.title}</h2><p className="ec-hint">{shortcut.description}</p><Link className="ec-link mt-4" href={route(shortcut.routeName)}>Buka {shortcut.title.toLowerCase()}<Icon name="arrow" /></Link></section>)}</div>
        <section className="ec-card"><div className="ec-card-header"><div><h2>Ruang pengelolaan</h2><p>Siapkan penugasan, kelola keanggotaan, dan gunakan laporan untuk merencanakan latihan.</p></div></div><div className="ec-card-body grid gap-4 sm:grid-cols-2 xl:grid-cols-3">{tools.map(tool => <Link key={tool.routeName} href={route(tool.routeName)} className="ec-info block"><div className="mb-3 flex items-center gap-3"><Icon name={tool.icon} /><h3 className="font-semibold">{tool.title}</h3></div><p>{tool.description}</p><span className="ec-link mt-3">Buka menu<Icon name="arrow" /></span></Link>)}</div></section>
    </WorkspaceLayout>;
}

export default function Dashboard() {
    const user = usePage().props.auth.user;
    if (user.role === 'pembina') { return <PembinaDashboard name={user.name} />; }
    const roleLabel = user.is_panitia ? 'Panitia EC — Asisten Penilai' : 'Siswa';
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard {roleLabel}
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            Selamat datang, {user.name}! Anda masuk sebagai {roleLabel}.
                            <p className="mt-3 text-sm text-gray-500">Isi pre-test untuk mengenali kemampuan awal dan menentukan minat belajar Anda.</p>
                            <Link className="mt-4 inline-block rounded bg-indigo-600 px-4 py-2 text-white" href={route('pretests.index')}>Buka pre-test saya</Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
