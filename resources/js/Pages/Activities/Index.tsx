import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AcademicYear, Activity, ManagementNav, StatusBadge, activityDate, yearLabel } from '@/Components/ManagementUi';
import { panel, field, Paging, Paginator } from '@/Components/AssessmentUi';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ activities, years, statuses, filters }: {
    activities: Paginator<Activity>; years: AcademicYear[]; statuses: Record<string, string>;
    filters: { academic_year_id: number | null; status: string; search: string };
}) {
    const user = usePage().props.auth.user;
    const pembina = user.role==='pembina';
    const staff = user.role!=='siswa';
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [year, setYear] = useState(String(filters.academic_year_id || ''));
    return <AuthenticatedLayout header={<div className="flex flex-wrap items-center justify-between gap-3"><h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Kegiatan {staff ? '& Presensi' : 'Saya'}</h2>{pembina && <Link className="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" href={route('activities.create')}>Buat kegiatan</Link>}</div>}>
        <Head title="Kegiatan" /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6"><ManagementNav />
            <div className={panel}>
                <form className="mb-5 flex flex-wrap items-end gap-3" onSubmit={e=>{e.preventDefault(); router.get(route('activities.index'), { search, status, academic_year_id: year });}}>
                    <div className="flex-1"><InputLabel htmlFor="activity_search" value="Cari judul atau kategori" /><input id="activity_search" className={field} type="search" maxLength={100} value={search} onChange={e=>setSearch(e.target.value)} /></div>
                    <div><InputLabel htmlFor="activity_year" value="Semester" /><select id="activity_year" className={field} value={year} onChange={e=>setYear(e.target.value)}><option value="">Semua semester</option>{years.map(y=><option key={y.id} value={y.id}>{yearLabel(y)}</option>)}</select></div>
                    <div><InputLabel htmlFor="activity_status" value="Status" /><select id="activity_status" className={field} value={status} onChange={e=>setStatus(e.target.value)}><option value="all">Semua</option>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></div><PrimaryButton>Terapkan</PrimaryButton>
                </form>
                <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b">{['Kegiatan', 'Tanggal / Waktu (WIB)', 'Semester', 'Status', 'Hadir', 'Aksi'].map(label=><th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{activities.data.map(a=><tr key={a.id} className="border-b"><td className="p-3">{a.title}<p className="text-xs text-gray-500">{a.category} · {a.location || 'Lokasi belum diisi'}</p></td><td className="p-3">{activityDate(a.activity_date)}<p className="text-xs text-gray-500">{a.start_time.slice(0,5)}{a.end_time && `–${a.end_time.slice(0,5)}`}</p></td><td className="p-3">{yearLabel(a.academic_year)}</td><td className="p-3"><StatusBadge status={a.status} labels={statuses} /></td><td className="p-3">{a.present_count}</td><td className="p-3"><Link href={route('activities.show', a.id)} className="text-indigo-600">{staff ? 'Buka / Presensi' : 'Detail'}</Link></td></tr>)}{activities.data.length===0 && <tr><td colSpan={6} className="p-6 text-center text-gray-500">Belum ada kegiatan yang sesuai.{!staff && ' Kegiatan muncul setelah Anda terdaftar sebagai anggota semester terkait.'}</td></tr>}</tbody></table></div><Paging page={activities} />
            </div>
        </div>
    </AuthenticatedLayout>;
}
