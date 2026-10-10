import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Paginator, panel } from '@/Components/AssessmentUi';
import StudentReportSummary, { StudentReportData } from '@/Components/StudentReportSummary';
import StudentGradeList, { Grade } from '@/Components/StudentGradeList';
import ReportPeriodFilter, { ReportPeriod } from '@/Components/ReportPeriodFilter';
import { Head, Link } from '@inertiajs/react';
import { Icon, PageHeading } from '@/Components/WorkspaceUi';

export default function Progress({ grades, report, student, filters }: {
    grades: Paginator<Grade>; report: StudentReportData; student: { id: number; full_name: string; class_name: string | null } | null; filters: ReportPeriod;
}) {
    return <WorkspaceLayout>
        <Head title="Perkembangan Saya" /><PageHeading eyebrow="Hasil belajar EC" title="Perkembangan saya" description="Kenali kemampuan dan minat Anda melalui nilai resmi yang sudah disahkan pembina." />
        {student && <div className="ec-notice ec-notice-success"><strong>{student.full_name}</strong>{student.class_name || 'Kelas belum diisi'} · Hasil pada halaman ini merupakan nilai resmi milik Anda.</div>}
        <div className="space-y-6">
            <p className="ec-info flex items-start gap-3"><Icon name="shield" />Draf dan rekomendasi yang menunggu tinjauan akan muncul setelah disahkan pembina.</p>
            {!student && <div className={panel}>Profil siswa belum tersedia. <Link href={route('profile.edit')} className="text-indigo-600 underline">Lengkapi profil</Link> untuk mulai mengisi pre-test.</div>}
            <ReportPeriodFilter filters={filters} url={route('progress.index')} />
            <StudentReportSummary report={report} />
            <StudentGradeList grades={grades} />
        </div>
    </WorkspaceLayout>;
}
