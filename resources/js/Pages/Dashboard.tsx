import { useTranslation } from '@/i18n/useTranslation';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { HeroArt, Icon, IconName, PageHeading } from '@/Components/WorkspaceUi';
import { Head, Link, usePage } from '@inertiajs/react';

function PembinaDashboard({ name }: { name: string }) {
    const { t, locale, dateLocale } = useTranslation();
    const shortcuts: { title: string; description: string; routeName: string; icon: IconName }[] = [
        { title: t("Kelola anggota"), description: t("Perbarui profil, kelas, dan status siswa yang mengikuti English Club."), routeName: 'students.index', icon: 'people' },
        { title: t("Kegiatan & presensi"), description: t("Atur pertemuan dan catat kehadiran siswa secara individual atau massal."), routeName: 'activities.index', icon: 'calendar' },
        { title: t("Pemeriksaan rekomendasi"), description: t("Tinjau pengamatan panitia, minta revisi, atau sahkan nilai resmi siswa."), routeName: 'assessments.queue', icon: 'check' },
    ];
    const tools: { title: string; description: string; routeName: string; icon: IconName }[] = [
        { title: t("Penugasan panitia"), description: t("Delegasikan siswa dan aspek kemampuan yang perlu dinilai."), routeName: 'assessments.index', icon: 'clipboard' },
        { title: t("Panitia EC"), description: t("Kelola hak panitia serta masa aktif tugasnya."), routeName: 'committee-roles.index', icon: 'shield' },
        { title: t("Keanggotaan semester"), description: t("Kelola anggota pada setiap tahun ajaran dan semester."), routeName: 'memberships.index', icon: 'people' },
        { title: t("Hasil pre-test"), description: t("Kenali kemampuan awal dan tujuan belajar siswa."), routeName: 'pretests.reports', icon: 'book' },
        { title: t("Laporan minat"), description: t("Lihat distribusi minat sebagai acuan merencanakan kegiatan."), routeName: 'reports.interests', icon: 'grid' },
        { title: t("Skema kegiatan"), description: t("Siapkan tujuan dan agenda untuk pertemuan English Club."), routeName: 'activity-schemes.index', icon: 'calendar' },
    ];
    return <WorkspaceLayout><Head title={t("Dashboard Pembina")} />
        <PageHeading eyebrow={t("Ruang pembina")} title={t("Selamat datang, :value1.", { value1: name })} description={t("Kelola English Club dan arahkan setiap siswa untuk mengembangkan potensinya.")} />
        <section className="ec-hero"><div className="ec-hero-content"><p className="ec-eyebrow">{t("Pendamping perkembangan siswa")}</p><h2>{t("Rencanakan kegiatan, tumbuhkan kemampuan.")}</h2><p>{t("Gunakan kemampuan awal dan minat siswa untuk menyusun latihan. Delegasikan pengamatan kepada panitia, lalu tinjau rekomendasi sebelum menerbitkan nilai resmi.")}</p><Link href={route('assessments.queue')} className="ec-btn ec-btn-primary"><Icon name="clipboard" />{t("Periksa rekomendasi")}</Link></div><HeroArt /></section>
        <div className="my-6 grid gap-4 md:grid-cols-3">{shortcuts.map(shortcut => <section key={shortcut.routeName} className="ec-card ec-card-body ec-shortcut"><div className="ec-stat-icon mb-4"><Icon name={shortcut.icon} /></div><h2>{t(shortcut.title)}</h2><p className="ec-hint">{t(shortcut.description)}</p><Link className="ec-link mt-4" href={route(shortcut.routeName)}><Icon name={shortcut.icon} />{t("Buka")} {shortcut.title.toLowerCase()}</Link></section>)}</div>
        <section className="ec-card"><div className="ec-card-header"><div><h2>{t("Ruang pengelolaan")}</h2><p>{t("Siapkan penugasan, kelola keanggotaan, dan gunakan laporan untuk merencanakan latihan.")}</p></div></div><div className="ec-card-body grid gap-4 sm:grid-cols-2 xl:grid-cols-3">{tools.map(tool => <Link key={tool.routeName} href={route(tool.routeName)} className="ec-info block"><div className="mb-3 flex items-center gap-3"><Icon name={tool.icon} /><h3 className="font-semibold">{t(tool.title)}</h3></div><p>{t(tool.description)}</p><span className="ec-link mt-3"><Icon name={tool.icon} />{t("Buka menu")}</span></Link>)}</div></section>
    </WorkspaceLayout>;
}

export default function Dashboard() {
    const { t, locale, dateLocale } = useTranslation();
    const user = usePage().props.auth.user;
    if (user.role === 'pembina') { return <PembinaDashboard name={user.name} />; }
    const roleLabel = user.is_panitia ? t("Panitia EC — Asisten Penilai") : t("Siswa");
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {t("Dashboard")}{t(roleLabel)}
                </h2>
            }
        >
            <Head title={t("Dashboard")} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            {t("Selamat datang,")}{user.name}{t("! Anda masuk sebagai")} {t(roleLabel)}.
                            <p className="mt-3 text-sm text-gray-500">{t("Isi pre-test untuk mengenali kemampuan awal dan menentukan minat belajar Anda.")}</p>
                            <Link className="mt-4 inline-block rounded bg-indigo-600 px-4 py-2 text-white" href={route('pretests.index')}>{t("Buka pre-test saya")}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
