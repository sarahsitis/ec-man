import { useTranslation } from '@/i18n/useTranslation';
import { Link, usePage } from '@inertiajs/react';

export interface AcademicYear { id: number; name: string; semester: string; is_active: boolean; memberships_count?: number; activities_count?: number }
export interface ManagedStudent { id: number; full_name: string; student_number: string; class_name: string | null; status: string }
export interface Membership { id: number; student_id: number; academic_year_id: number; class_name: string | null; status: string; student: ManagedStudent; academic_year?: AcademicYear }
export interface ActivityScheme { id: number; name: string; category: string; objectives: string; agenda: string; duration_minutes: number; activities_count?: number }
export interface Activity {
    id: number; academic_year_id: number; activity_scheme_id: number | null; title: string; category: string;
    description: string | null; objectives: string | null; agenda: string | null; activity_date: string;
    start_time: string; end_time: string | null; location: string | null; pic: string | null;
    target_audience: string | null; status: string; academic_year: AcademicYear; creator?: { name: string }; present_count?: number;
}
export interface Attendance { id: number; student_id: number; status: string; updated_at: string; recorder?: { name: string } }
export const studentStatuses: Record<string, string> = { active: 'Aktif', inactive: 'Nonaktif', left: 'Keluar', alumni: 'Alumni' };
export function yearLabel(year: AcademicYear, locale: 'id' | 'en' = 'id') { return `${year.name} · ${year.semester === 'ganjil' ? (locale === 'en' ? 'First semester' : 'Ganjil') : (locale === 'en' ? 'Second semester' : 'Genap')}${year.is_active ? (locale === 'en' ? ' (active)' : ' (aktif)') : ''}`; }
export function activityDate(value: string, locale: 'id' | 'en' = 'id') { return new Date(`${value.slice(0, 10)}T12:00:00+07:00`).toLocaleDateString(locale === 'en' ? 'en-GB' : 'id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'long', year: 'numeric' }); }
export function StatusBadge({ status, labels = studentStatuses }: { status: string; labels?: Record<string, string> }) {
    const { t, locale, dateLocale } = useTranslation();
    return <span className={`inline-block rounded-full px-2 py-1 text-xs ${['active', 'hadir', 'completed'].includes(status) ? 'bg-green-100 text-green-900' : ['left', 'cancelled', 'alpa'].includes(status) ? 'bg-red-100 text-red-900' : 'bg-gray-100 text-gray-800'}`}>{t(labels[status] || status)}</span>;
}
export function ManagementNav() {
    const { t, locale, dateLocale } = useTranslation();
    const user = usePage().props.auth.user;
    if (user.role !== 'pembina') { return null; }
    return <nav aria-label={t("Manajemen EC")} className="flex flex-wrap gap-2">{[
        ['students.index', t("Data siswa"), 'students.*'], ['memberships.index', t("Keanggotaan"), 'memberships.*'],
        ['academic-years.index', t("Tahun ajaran"), 'academic-years.*'], ['activities.index', t("Kegiatan & Presensi"), 'activities.*'],
        ['activity-schemes.index', t("Skema kegiatan"), 'activity-schemes.*'],
        ['committee-roles.index', t("Panitia EC"), 'committee-roles.*'],
        ['reports.interests', t("Laporan minat"), 'reports.interests'],
    ].map(([name, label, match]) => <Link key={name} href={route(name)} aria-current={route().current(match) ? 'page' : undefined} className="ec-action ec-action-secondary">{t(label)}</Link>)}</nav>;
}
