import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginator, panel } from '@/Components/AssessmentUi';
import { StatusBadge } from '@/Components/ManagementUi';
import StudentReportSummary, { StudentReportData } from '@/Components/StudentReportSummary';
import StudentGradeList, { Grade } from '@/Components/StudentGradeList';
import ReportPeriodFilter, { ReportPeriod } from '@/Components/ReportPeriodFilter';
import { Head, Link, usePage } from '@inertiajs/react';

export default function StudentReport({ student, report, grades, filters }: {
    student: { id: number; full_name: string; student_number: string; class_name: string | null; status: string; joined_year: number; profile_photo_url: string | null };
    report: StudentReportData; grades: Paginator<Grade>; filters: ReportPeriod;
}) {
    const user = usePage().props.auth.user;
    const back = user.role === 'pembina' ? route('students.index') : user.is_panitia ? route('panitia.assignments') : route('progress.index');
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Laporan & Profil Siswa</h2>}>
        <Head title={`Laporan ${student.full_name}`} /><div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
            <Link className="text-indigo-600" href={back}>Kembali ke daftar</Link>
            <div className={panel}><div className="flex flex-wrap items-center gap-4">{student.profile_photo_url && <img src={student.profile_photo_url} alt={`Foto ${student.full_name}`} className="h-16 w-16 rounded-full object-cover" />}
                <div className="flex-1"><h3 className="text-lg font-semibold">{student.full_name}</h3><p className="mt-1 text-sm text-gray-500">{student.student_number} · {student.class_name || 'Kelas belum diisi'} · Bergabung {student.joined_year}</p></div><StatusBadge status={student.status} /></div>
                <Link href={route('pretests.show', student.id)} className="mt-4 inline-block text-sm text-indigo-600">Lihat hasil pre-test</Link>
            </div>
            <ReportPeriodFilter filters={filters} url={route('reports.student', student.id)} />
            <StudentReportSummary report={report} /><StudentGradeList grades={grades} />
        </div>
    </AuthenticatedLayout>;
}
