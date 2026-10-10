import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Assignment, AssignmentTable, Paging, Paginator, panel } from '@/Components/AssessmentUi';
import { Head, Link } from '@inertiajs/react';
export default function Assignments({ assignments, status }: { assignments: Paginator<Assignment>; status: string }) {
    const title = status === 'history' ? 'Riwayat Rekomendasi' : 'Penugasan Saya';
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{title}</h2>}>
        <Head title={title} /><div className="mx-auto max-w-7xl px-4 py-8 sm:px-6"><div className={panel}>
            <div className="mb-4 flex gap-4"><Link className="text-indigo-600" href={route('panitia.assignments')}>Aktif / Revisi</Link><Link className="text-indigo-600" href={route('panitia.assignments', {status:'history'})}>Riwayat pengajuan</Link></div>
            <AssignmentTable items={assignments.data} /><Paging page={assignments} />
        </div></div>
    </AuthenticatedLayout>;
}
