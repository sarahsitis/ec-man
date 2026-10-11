import { useTranslation } from '@/i18n/useTranslation';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { timeLabel } from './assessmentPresentation';

type AttendanceStatus = 'hadir' | 'izin' | 'sakit' | 'alpa' | 'belum_diisi';
export interface AttendanceSummary {
    counts: Record<AttendanceStatus, number>; total: number; recorded: number; attendanceRate: number | null;
}
export interface DashboardGrade {
    id: number; title: string; aspect: string; value: number; approvedAt: string | null; approver: string | null;
}
const statuses: { key: AttendanceStatus; label: string; color: string }[] = [
    { key: 'hadir', label: 'Hadir', color: '#7c3aed' },
    { key: 'izin', label: 'Izin', color: '#0284c7' },
    { key: 'sakit', label: 'Sakit', color: '#d97706' },
    { key: 'alpa', label: 'Alpa', color: '#db2777' },
    { key: 'belum_diisi', label: 'Belum dicatat', color: '#94a3b8' },
];

export default function StudentDashboardInsights({ attendance, grades, gradeCount }: {
    attendance: AttendanceSummary; grades: DashboardGrade[]; gradeCount: number;
}) {
    const { t, locale, dateLocale } = useTranslation();
    const [selected, setSelected] = useState<AttendanceStatus | null>(null);
    const number = (value: number) => new Intl.NumberFormat(dateLocale, { maximumFractionDigits: 1 }).format(value);
    const percentage = (count: number) => attendance.total ? count / attendance.total * 100 : 0;
    let offset = 0;
    const segments = statuses.map(status => {
        const length = percentage(attendance.counts[status.key]);
        const segment = { ...status, length, offset };
        offset += length;
        return segment;
    });
    const selection = statuses.find(status => status.key === selected);
    return <div className="my-6 grid items-start gap-6 xl:grid-cols-2">
        <section className="ec-card" aria-labelledby="dashboard-attendance-heading">
            <div className="ec-card-header"><div><h2 id="dashboard-attendance-heading">{t('Ringkasan presensi saya')}</h2><p>{t('Kegiatan selesai pada semua semester yang dapat Anda akses.')}</p></div><Link href={route('activities.history', { period: 'completed' })} className="ec-action ec-action-secondary">{t('Histori Kegiatan')}</Link></div>
            <div className="ec-card-body">
                {!attendance.total ? <div className="rounded-lg bg-purple-50 p-4 text-sm"><p className="font-semibold">{t('Belum ada kegiatan selesai untuk ditampilkan.')}</p><p className="mt-2 text-gray-500">{t('Chart presensi akan tersedia setelah ada kegiatan yang selesai.')}</p></div> : <>
                    <div className="grid items-center gap-5 sm:grid-cols-2" data-attendance-chart>
                        <div className="relative mx-auto w-48">
                            <svg viewBox="0 0 120 120" className="w-full" role="img" aria-label={t('Distribusi presensi dari :count kegiatan selesai.', { count: attendance.total })}>
                                <circle cx="60" cy="60" r="48" fill="none" stroke="#f1f5f9" strokeWidth="14" />
                                {segments.filter(segment => segment.length > 0).map(segment => <circle key={segment.key} cx="60" cy="60" r="48" pathLength="100" fill="none" stroke={segment.color} strokeWidth={selected === segment.key ? 17 : 14} strokeDasharray={`${segment.length} ${100 - segment.length}`} strokeDashoffset={-segment.offset} transform="rotate(-90 60 60)" opacity={selected && selected !== segment.key ? 0.25 : 1} />)}
                            </svg>
                            <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center" aria-live="polite">
                                <strong className="text-3xl text-purple-900">{selected ? attendance.counts[selected] : attendance.total}</strong>
                                <span className="mt-1 text-xs text-gray-500">{selection ? t(selection.label) : t('Kegiatan selesai')}</span>
                                {selected && <span className="mt-1 text-xs text-gray-500">{number(percentage(attendance.counts[selected]))}%</span>}
                            </div>
                        </div>
                        <div className="space-y-1" role="group" aria-label={t('Rincian status presensi')}>
                            {statuses.map(status => <button key={status.key} type="button" aria-pressed={selected === status.key} onClick={() => setSelected(selected === status.key ? null : status.key)} className={`flex w-full items-center justify-between gap-3 rounded-lg border px-3 py-2 text-left text-sm ${selected === status.key ? 'border-purple-300 bg-purple-50' : 'border-transparent hover:bg-gray-50'}`} data-attendance-status={status.key}>
                                <span className="flex items-center gap-2"><span className="h-2.5 w-2.5 rounded-full" style={{ background: status.color }} aria-hidden="true" />{t(status.label)}</span><strong>{attendance.counts[status.key]}</strong>
                            </button>)}
                        </div>
                    </div>
                    <div className="mt-5 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm"><p className="font-semibold">{t('Persentase hadir')}: {attendance.attendanceRate === null ? '—' : `${number(attendance.attendanceRate)}%`}</p><p className="mt-1 text-xs text-gray-500">{t('Dihitung dari :count presensi yang sudah dicatat (hadir, izin, sakit, alpa).', { count: attendance.recorded })}</p></div>
                </>}
                <p className="mt-4 text-xs leading-5 text-gray-500">{t('Presensi kosong atau belum diisi dihitung sebagai Belum dicatat, bukan alpa. Kegiatan mendatang dan dibatalkan tidak masuk chart.')}</p>
            </div>
        </section>
        <section className="ec-card" aria-labelledby="dashboard-grades-heading">
            <div className="ec-card-header"><div><h2 id="dashboard-grades-heading">{t('Histori penilaian saya')}</h2><p>{t('Lima nilai resmi terbaru yang telah disahkan pembina.')}</p></div><Link href={route('progress.index')} className="ec-action ec-action-secondary">{t('Lihat semua nilai')}</Link></div>
            <div className="ec-card-body">
                {!grades.length ? <div className="rounded-lg bg-purple-50 p-4 text-sm"><p className="font-semibold">{t('Belum ada nilai resmi.')}</p><p className="mt-2 text-gray-500">{t('Histori penilaian akan tampil setelah pembina mengesahkan nilai Anda.')}</p></div> : <ol className="space-y-3" aria-label={t('Histori penilaian saya')}>
                    {grades.map(grade => <li key={grade.id} className="rounded-lg border border-purple-100 p-4" data-dashboard-grade-id={grade.id}>
                        <div className="flex items-start justify-between gap-3"><div className="min-w-0"><h3 className="break-words text-sm font-semibold">{grade.title}</h3><p className="mt-1 text-xs capitalize text-purple-700">{grade.aspect}</p></div><span className="shrink-0 rounded-lg bg-purple-50 px-3 py-2 font-semibold text-purple-900">{grade.value}<span className="text-xs font-normal"> / 4</span></span></div>
                        <p className="mt-3 break-words text-xs text-gray-500">{t('Disahkan oleh')} {grade.approver || t('Belum tercatat')} · {timeLabel(grade.approvedAt, locale)}</p>
                    </li>)}
                </ol>}
                {gradeCount > grades.length && <p className="mt-4 text-xs text-gray-500">{t('Menampilkan :shown dari :total nilai resmi.', { shown: grades.length, total: gradeCount })}</p>}
            </div>
        </section>
    </div>;
}
