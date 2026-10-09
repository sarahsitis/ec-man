import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PreTestResultView, { PreTestMetadata, PreTestResult, PreTestStudent } from '@/Components/PreTestResultView';
import { panel } from '@/Components/AssessmentUi';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Show({ student, result, skills, scales }: PreTestMetadata & { student: PreTestStudent; result: PreTestResult | null }) {
    const user = usePage().props.auth.user;
    const back = user.role === 'pembina' ? route('pretests.reports') : user.role === 'panitia' ? route('panitia.assignments') : route('pretests.index');
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Hasil Pre-test Siswa</h2>}>
        <Head title={`Pre-test ${student.full_name}`} />
        <div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
            <Link href={back} className="text-indigo-600">Kembali ke daftar</Link>
            <div className={panel}><h3 className="text-lg font-semibold">{student.full_name}</h3><p className="mt-1">{student.student_number} · {student.class_name || 'Kelas belum diisi'}</p></div>
            {result ? <PreTestResultView result={result} skills={skills} scales={scales} /> : <div className={panel}>Siswa belum mengisi pre-test.</div>}
        </div>
    </AuthenticatedLayout>;
}
