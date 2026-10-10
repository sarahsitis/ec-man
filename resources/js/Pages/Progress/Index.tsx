import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginator, panel } from '@/Components/AssessmentUi';
import StudentReportSummary, { StudentReportData } from '@/Components/StudentReportSummary';
import StudentGradeList, { Grade } from '@/Components/StudentGradeList';
import ReportPeriodFilter, { ReportPeriod } from '@/Components/ReportPeriodFilter';
import { Head, Link } from '@inertiajs/react';

export default function Progress({ grades, report, student, filters }: {
    grades: Paginator<Grade>; report: StudentReportData; student: { id: number; full_name: string; class_name: string | null } | null; filters: ReportPeriod;
}) {
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Perkembangan Saya</h2>}>
        <Head title="Perkembangan Saya" /><div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
            {!student && <div className={panel}>Profil siswa belum tersedia. <Link href={route('profile.edit')} className="text-indigo-600 underline">Lengkapi profil</Link> untuk mulai mengisi pre-test.</div>}
            <ReportPeriodFilter filters={filters} url={route('progress.index')} />
            <StudentReportSummary report={report} />
            <StudentGradeList grades={grades} />
        </div>
    </AuthenticatedLayout>;
}
