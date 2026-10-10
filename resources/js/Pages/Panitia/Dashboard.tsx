import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Assignment, AssignmentTable, panel, statuses } from '@/Components/AssessmentUi';
import { Head, Link } from '@inertiajs/react';
import { activityDate } from '@/Components/ManagementUi';
export default function Dashboard({ counts, recent, committeeTerm }: { counts: Record<string, number>; recent: Assignment[]; committeeTerm: { starts_on: string; ends_on: string } }) {
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard Panitia EC</h2>}>
        <Head title="Dashboard Panitia EC" />
        <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <div className={panel}><h3 className="text-lg font-semibold">Asisten penilai pembina</h3><p className="mt-2">Nilai siswa sesuai penugasan dan rubrik. Kirim rekomendasi kepada pembina untuk ditinjau sebelum menjadi nilai resmi.</p>
                <p className="mt-2 text-sm text-gray-500">Masa tugas: {activityDate(committeeTerm.starts_on)} s.d. {activityDate(committeeTerm.ends_on)} (WIB).</p>
                <div className="mt-4 flex flex-wrap gap-4"><Link className="rounded bg-indigo-600 px-4 py-2 text-white" href={route('panitia.assignments')}>Buka penugasan saya</Link><Link className="px-4 py-2 text-indigo-600" href={route('panitia.assignments', { status: 'history' })}>Riwayat rekomendasi</Link></div>
            </div>
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-3">{['assigned','draft','revision','submitted','approved','rejected'].map(s => <div key={s} className={panel}><div className="text-sm">{statuses[s]}</div><div className="mt-2 text-3xl font-semibold">{counts[s] || 0}</div></div>)}</div>
            <div className={panel}><h3 className="mb-4 text-lg font-semibold">Penugasan terbaru</h3><AssignmentTable items={recent} /></div>
        </div>
    </AuthenticatedLayout>;
}
