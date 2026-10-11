import { useTranslation } from '@/i18n/useTranslation';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { HeroArt, Icon, IconName, PageHeading } from '@/Components/WorkspaceUi';
import { AcademicYear, activityDate, StatusBadge, yearLabel } from '@/Components/ManagementUi';
import StudentDashboardInsights, { AttendanceSummary, DashboardGrade } from '@/Components/StudentDashboardInsights';
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

interface UpcomingActivity {
    id: number; title: string; category: string; activity_date: string; start_time: string; end_time: string | null;
    location: string | null; pic: string | null; academic_year: AcademicYear; phase: 'upcoming' | 'ongoing';
}

export default function Dashboard({ upcomingActivities = [], upcomingActivityCount = 0, attendanceSummary, recentGrades = [], gradeCount = 0 }: {
    upcomingActivities?: UpcomingActivity[]; upcomingActivityCount?: number;
    attendanceSummary?: AttendanceSummary; recentGrades?: DashboardGrade[]; gradeCount?: number;
}) {
    const { t, locale, dateLocale } = useTranslation();
    const user = usePage().props.auth.user;
    if (user.role === 'pembina') { return <PembinaDashboard name={user.name} />; }
    return <AuthenticatedLayout>
        <Head title={t('Dashboard')} />
        <PageHeading eyebrow={t('Ruang siswa')} title={t('Selamat datang, :value1.', { value1: user.name })} description={t('Lihat jadwal English Club dan siapkan diri untuk kegiatan berikutnya.')} />
        {attendanceSummary && <StudentDashboardInsights attendance={attendanceSummary} grades={recentGrades} gradeCount={gradeCount} />}
        <section className="ec-card" aria-labelledby="dashboard-activities-heading">
            <div className="ec-card-header flex flex-wrap items-center justify-between gap-3">
                <div><h2 id="dashboard-activities-heading">{t('Kegiatan mendatang')} <span className="ec-badge ec-badge-neutral">{upcomingActivityCount}</span></h2><p>{t('Jadwal terdekat dari pembina dan panitia EC, termasuk kegiatan yang sedang berlangsung.')}</p></div>
                <Link className="ec-action ec-action-secondary" href={route('activities.history', { period: 'upcoming' })}>{t('Lihat semua kegiatan')}</Link>
            </div>
            {!upcomingActivities.length ? <div className="ec-card-body"><p className="font-semibold">{t('Belum ada kegiatan yang akan atau sedang berlangsung.')}</p><p className="ec-hint mt-2">{t('Kegiatan baru akan tampil di sini saat dijadwalkan pada semester keanggotaan Anda.')}</p></div> : <ol className="ec-card-body grid gap-4 lg:grid-cols-2" aria-label={t('Kegiatan mendatang')}>
                {upcomingActivities.map(activity => <li key={activity.id} className="ec-info min-w-0" data-dashboard-activity-id={activity.id}>
                    <div className="flex flex-col items-start justify-between gap-3 sm:flex-row"><div className="min-w-0 sm:flex-1"><p className="text-xs font-medium text-purple-700">{activity.category}</p><h3 className="mt-1 break-words text-lg font-semibold">{activity.title}</h3></div><StatusBadge status={activity.phase} labels={{ upcoming: 'Akan berlangsung', ongoing: 'Sedang berlangsung' }} /></div>
                    <p className="mt-2 text-xs text-gray-500">{yearLabel(activity.academic_year, locale)}</p>
                    <dl className="mt-4 space-y-3 text-sm">
                        <div><dt className="text-xs text-gray-500">{t('Tanggal / Waktu (WIB)')}</dt><dd className="mt-1">{activityDate(activity.activity_date, locale)} · {activity.start_time.slice(0, 5)}{activity.end_time && `–${activity.end_time.slice(0, 5)}`} WIB</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Lokasi')}</dt><dd className="mt-1 break-words">{activity.location || t('Belum diisi')}</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Penanggung jawab')}</dt><dd className="mt-1 break-words">{activity.pic || t('Belum diisi')}</dd></div>
                    </dl>
                    <Link className="ec-action ec-action-primary mt-4" href={route('activities.show', activity.id)}><Icon name="calendar" />{t('Detail kegiatan')}</Link>
                </li>)}
            </ol>}
            {upcomingActivityCount > upcomingActivities.length && <p className="px-6 pb-5 text-xs text-gray-500">{t('Menampilkan :shown dari :total kegiatan. Buka semua kegiatan untuk melihat jadwal lainnya.', { shown: upcomingActivities.length, total: upcomingActivityCount })}</p>}
        </section>
        <section className="ec-card ec-card-body mt-6">
            <h2 className="font-semibold">{t('Pre-test Saya')}</h2><p className="ec-hint mt-2">{t('Isi pre-test untuk mengenali kemampuan awal dan menentukan minat belajar Anda.')}</p>
            <Link className="ec-action ec-action-secondary mt-4" href={route('pretests.index')}><Icon name="book" />{t('Buka pre-test saya')}</Link>
        </section>
    </AuthenticatedLayout>;
}
