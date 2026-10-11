import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AcademicYear, Activity, activityDate, StatusBadge, yearLabel } from '@/Components/ManagementUi';
import { field, panel, Paging, Paginator } from '@/Components/AssessmentUi';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Icon } from '@/Components/WorkspaceUi';
import { useTranslation } from '@/i18n/useTranslation';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Period = 'upcoming' | 'completed' | 'pending' | 'cancelled';
type Participation = 'all' | 'attended' | 'missed' | 'unrecorded';
interface HistoryActivity extends Activity {
    phase: Period | 'ongoing'; material_count: number; documentation_count: number; my_attendance: string | null;
    my_participation: Exclude<Participation, 'all'> | null;
}
interface Filters { period: Period; participation: Participation; academic_year_id: number | null; search: string; date_from: string; date_to: string }

export default function History({ activities, years, filters, counts, periods, attendanceStatuses, participationOptions, participationCounts, asOf }: {
    activities: Paginator<HistoryActivity>; years: AcademicYear[]; filters: Filters;
    counts: Record<Period, number>; periods: Record<Period, string>; attendanceStatuses: Record<string, string>; asOf: string;
    participationOptions: Record<Participation, string>; participationCounts: Record<Participation, number> | null;
}) {
    const { t, locale, dateLocale } = useTranslation();
    const user = usePage().props.auth.user;
    const staff = user.role === 'pembina' || user.is_panitia;
    const [search, setSearch] = useState(filters.search);
    const [year, setYear] = useState(String(filters.academic_year_id || ''));
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const phases = { upcoming: 'Akan berlangsung', ongoing: 'Sedang berlangsung', completed: 'Telah dilaksanakan', pending: 'Menunggu konfirmasi', cancelled: 'Dibatalkan' };
    const periodLink = (period: Period) => route('activities.history', { ...filters, period, participation: 'all', per_page: activities.per_page });
    const participationEmpty = { all: 'Belum ada kegiatan yang telah dilaksanakan.', attended: 'Belum ada kegiatan yang tercatat Anda ikuti.', missed: 'Tidak ada kegiatan yang tercatat Anda lewatkan.', unrecorded: 'Tidak ada kegiatan dengan presensi Anda yang belum dicatat.' };
    const empty: Record<Period, string> = {
        upcoming: 'Belum ada kegiatan yang akan atau sedang berlangsung.',
        completed: 'Belum ada kegiatan yang telah dilaksanakan.',
        pending: 'Tidak ada kegiatan yang menunggu konfirmasi pelaksanaan.',
        cancelled: 'Tidak ada kegiatan yang dibatalkan.',
    };
    return <AuthenticatedLayout header={<div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{t('Histori Kegiatan')}</h2>
        <div className="flex flex-wrap gap-2"><Link className="ec-action ec-action-secondary" href={route('activities.index')}>{t('Daftar kegiatan')}</Link>{staff && <Link className="ec-action ec-action-primary" href={route('activities.create')}>{t('Buat kegiatan')}</Link>}</div>
    </div>}>
        <Head title={t('Histori Kegiatan')} />
        <div className="mx-auto max-w-6xl space-y-5 px-4 py-8 sm:px-6">
            <div className={panel}>
                <p className="text-sm leading-6 text-gray-500">{t(staff ? 'Pantau pertemuan mendatang dan telusuri riwayat pelaksanaan English Club.' : 'Lihat kegiatan yang Anda ikuti atau lewatkan berdasarkan presensi, serta jadwal pertemuan mendatang.')}</p>
                <form className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" onSubmit={e => {
                    e.preventDefault();
                    router.get(route('activities.history'), { period: filters.period, participation: filters.participation, search, academic_year_id: year, date_from: dateFrom, date_to: dateTo, per_page: activities.per_page });
                }}>
                    <div className="sm:col-span-2"><InputLabel htmlFor="history_search" value={t('Cari judul atau kategori')} /><input id="history_search" type="search" maxLength={100} className={field} value={search} onChange={e => setSearch(e.target.value)} /></div>
                    <div className="sm:col-span-2"><InputLabel htmlFor="history_year" value={t('Semester')} /><select id="history_year" className={field} value={year} onChange={e => setYear(e.target.value)}><option value="">{t('Semua semester')}</option>{years.map(y => <option key={y.id} value={y.id}>{yearLabel(y, locale)}</option>)}</select></div>
                    <div><InputLabel htmlFor="history_from" value={t('Dari tanggal')} /><input id="history_from" type="date" className={field} value={dateFrom} onChange={e => setDateFrom(e.target.value)} /></div>
                    <div><InputLabel htmlFor="history_to" value={t('Sampai tanggal')} /><input id="history_to" type="date" min={dateFrom || undefined} className={field} value={dateTo} onChange={e => setDateTo(e.target.value)} /></div>
                    <div className="flex flex-wrap items-end gap-2 sm:col-span-2"><PrimaryButton>{t('Terapkan')}</PrimaryButton><Link href={route('activities.history', { period: filters.period })} className="ec-action ec-action-secondary">{t('Atur ulang filter')}</Link></div>
                </form>
            </div>
            <nav className="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label={t('Filter histori kegiatan')}>
                {(Object.keys(periods) as Period[]).map(period => <Link key={period} href={periodLink(period)} aria-current={filters.period === period ? 'page' : undefined} className={`ec-history-filter ${filters.period === period ? 'ec-history-filter-active' : ''}`}>
                    <span>{t(periods[period])}</span><strong>{counts[period]}</strong>
                </Link>)}
            </nav>
            <div className="flex flex-wrap items-center justify-between gap-2"><h3 className="text-lg font-semibold">{t(periods[filters.period])}</h3><p className="text-xs text-gray-500">{t('Jadwal berdasarkan WIB')} · {new Intl.DateTimeFormat(dateLocale, { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' }).format(new Date(asOf))}</p></div>
            {!staff && filters.period !== 'upcoming' && counts.upcoming > 0 && <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-purple-200 bg-purple-50 p-4 text-sm">
                <p>{t('Ada :count kegiatan yang akan atau sedang berlangsung.', { count: counts.upcoming })}</p>
                <Link href={periodLink('upcoming')} className="ec-action ec-action-primary">{t('Lihat jadwal mendatang')}</Link>
            </div>}
            {!staff && filters.period === 'completed' && participationCounts && <section className={panel} aria-labelledby="participation-heading">
                <h4 id="participation-heading" className="font-semibold">{t('Keikutsertaan saya')}</h4>
                <p className="mt-2 text-sm leading-6 text-gray-500">{t('Diikuti: hadir. Dilewatkan: izin, sakit, atau alpa. Presensi yang belum diisi ditampilkan sebagai Belum dicatat.')}</p>
                <nav className="mt-4 flex flex-wrap gap-2" aria-label={t('Filter keikutsertaan saya')}>
                    {(Object.keys(participationOptions) as Participation[]).map(participation => <Link key={participation} href={route('activities.history', { ...filters, participation, per_page: activities.per_page })} className={`ec-action ${filters.participation === participation ? 'ec-action-primary' : 'ec-action-secondary'}`} aria-current={filters.participation === participation ? 'page' : undefined}>{t(participationOptions[participation])} ({participationCounts[participation]})</Link>)}
                </nav>
            </section>}
            {filters.period === 'pending' && <p className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">{t('Jadwal telah lewat, tetapi status pelaksanaan belum dikonfirmasi. Pembina dapat memperbarui status melalui Edit kegiatan.')}</p>}
            {!activities.data.length ? <div className={panel}><p className="font-semibold">{t(!staff && filters.period === 'completed' ? participationEmpty[filters.participation] : empty[filters.period])}</p><p className="mt-2 text-sm text-gray-500">{t('Sesuaikan filter untuk melihat kegiatan lainnya. Siswa hanya melihat kegiatan yang dapat diakses melalui keanggotaan atau riwayat presensi.')}</p></div> : <ol className="space-y-4" aria-label={t('Daftar histori kegiatan')}>
                {activities.data.map(activity => <li key={activity.id} className={panel} data-history-id={activity.id}>
                    <div className="flex flex-wrap items-start justify-between gap-3"><div className="min-w-0 flex-1"><p className="text-xs font-medium text-purple-700">{activity.category}</p><h4 className="mt-1 break-words text-lg font-semibold">{activity.title}</h4><p className="mt-1 text-xs text-gray-500">{yearLabel(activity.academic_year, locale)}</p></div><StatusBadge status={activity.phase} labels={phases} /></div>
                    {activity.my_participation && <p className="mt-3 text-sm font-semibold" data-participation={activity.my_participation}>{t(participationOptions[activity.my_participation])} · {t(attendanceStatuses[activity.my_attendance || 'belum_diisi'] || 'Belum diisi')}</p>}
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt className="text-xs text-gray-500">{t('Tanggal / Waktu (WIB)')}</dt><dd className="mt-1">{activityDate(activity.activity_date, locale)} · {activity.start_time.slice(0, 5)}{activity.end_time && `–${activity.end_time.slice(0, 5)}`} WIB</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Lokasi')}</dt><dd className="mt-1 break-words">{activity.location || t('Belum diisi')}</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Penanggung jawab')}</dt><dd className="mt-1 break-words">{activity.pic || activity.creator?.name || t('Belum diisi')}</dd></div>
                        <div><dt className="text-xs text-gray-500">{t(staff ? 'Hadir' : 'Presensi saya')}</dt><dd className="mt-1">{staff ? t(':count siswa', { count: activity.present_count ?? 0 }) : t(attendanceStatuses[activity.my_attendance || 'belum_diisi'] || 'Belum diisi')}</dd></div>
                    </dl>
                    <div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500"><span>{t('Materi Kegiatan')}: {activity.material_count}</span><span>{t('Dokumentasi Kegiatan')}: {activity.documentation_count}</span></div>
                    <div className="mt-4 flex flex-wrap gap-2"><Link href={route('activities.show', activity.id)} className="ec-action ec-action-primary"><Icon name="calendar" />{t('Detail kegiatan')}</Link><Link href={`${route('activities.show', activity.id)}#activity-files-heading`} className="ec-action ec-action-secondary">{t('Materi & Dokumentasi')}</Link>{user.role === 'pembina' && activity.phase === 'pending' && <Link href={route('activities.edit', activity.id)} className="ec-action ec-action-edit">{t('Konfirmasi pelaksanaan')}</Link>}</div>
                </li>)}
            </ol>}
            <Paging page={activities} />
        </div>
    </AuthenticatedLayout>;
}
