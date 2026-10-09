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
export function yearLabel(year: AcademicYear) { return `${year.name} · ${year.semester === 'ganjil' ? 'Ganjil' : 'Genap'}${year.is_active ? ' (aktif)' : ''}`; }
export function activityDate(value: string) { return new Date(`${value.slice(0, 10)}T12:00:00+07:00`).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'long', year: 'numeric' }); }
export function StatusBadge({ status, labels = studentStatuses }: { status: string; labels?: Record<string, string> }) {
    return <span className={`inline-block rounded-full px-2 py-1 text-xs ${['active', 'hadir', 'completed'].includes(status) ? 'bg-green-100 text-green-900' : ['left', 'cancelled', 'alpa'].includes(status) ? 'bg-red-100 text-red-900' : 'bg-gray-100 text-gray-800'}`}>{labels[status] || status}</span>;
}
export function ManagementNav() {
    const user = usePage().props.auth.user;
    if (user.role !== 'pembina') { return null; }
    return <nav aria-label="Manajemen EC" className="flex flex-wrap gap-2">{[
        ['students.index', 'Data siswa', 'students.*'], ['memberships.index', 'Keanggotaan', 'memberships.*'],
        ['academic-years.index', 'Tahun ajaran', 'academic-years.*'], ['activities.index', 'Kegiatan & Presensi', 'activities.*'],
        ['activity-schemes.index', 'Skema kegiatan', 'activity-schemes.*'],
    ].map(([name, label, match]) => <Link key={name} href={route(name)} className={`rounded px-3 py-2 text-sm ${route().current(match) ? 'bg-indigo-100 font-semibold text-indigo-900' : 'bg-white text-indigo-600 dark:bg-gray-800'}`}>{label}</Link>)}</nav>;
}
