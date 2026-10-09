import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paging, Paginator, panel, field } from '@/Components/AssessmentUi';
import { PreTestResult, PreTestStudent } from '@/Components/PreTestResultView';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface ReportStudent extends PreTestStudent { pre_test_result: PreTestResult | null }
export default function Reports({ students, filters, counts }: {
    students: Paginator<ReportStudent>;
    filters: { search: string; status: string };
    counts: { total: number; completed: number; pending: number };
}) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Hasil Pre-test</h2>}>
        <Head title="Hasil Pre-test" />
        <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <div className="grid gap-4 sm:grid-cols-3">{[[counts.total, 'Total siswa'], [counts.completed, 'Sudah mengisi'], [counts.pending, 'Belum mengisi']].map(([value, label]) => <div key={label} className={panel}><p className="text-sm text-gray-500">{label}</p><p className="mt-2 text-3xl font-semibold">{value}</p></div>)}</div>
            <div className={panel}>
                <h3 className="text-lg font-semibold">Pemetaan kemampuan dan minat siswa</h3>
                <p className="mt-2 text-sm text-gray-500">Gunakan hasil awal untuk merencanakan latihan dan penugasan. Skor pilihan ganda mengukur bacaan dan penggunaan bahasa. Listening, speaking, reading, dan writing juga dicatat melalui penilaian diri siswa.</p>
                <form className="my-5 flex flex-wrap items-end gap-3" onSubmit={e=>{e.preventDefault(); router.get(route('pretests.reports'), { search, status }, { preserveState: true });}}>
                    <div className="min-w-48 flex-1"><InputLabel htmlFor="report_search" value="Cari nama, nomor induk, atau kelas" /><input id="report_search" className={field} type="search" maxLength={100} value={search} onChange={e=>setSearch(e.target.value)} /></div>
                    <div><InputLabel htmlFor="report_status" value="Status pre-test" /><select id="report_status" className={field} value={status} onChange={e=>setStatus(e.target.value)}><option value="all">Semua siswa</option><option value="completed">Sudah mengisi</option><option value="pending">Belum mengisi</option></select></div>
                    <PrimaryButton>Terapkan</PrimaryButton>
                </form>
                <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b">
                    {['Siswa', 'Kemampuan awal', 'Minat utama', 'Dikirim', 'Aksi'].map(label=><th key={label} className="p-3">{label}</th>)}
                </tr></thead><tbody>{students.data.map(student => <tr key={student.id} className="border-b">
                    <td className="p-3">{student.full_name}<p className="text-xs text-gray-500">{student.student_number} · {student.class_name || 'Kelas belum diisi'}</p></td>
                    <td className="p-3">{student.pre_test_result ? <>{student.pre_test_result.score}/{student.pre_test_result.max_score}<p className="text-xs text-gray-500">{student.pre_test_result.level}</p></> : 'Belum mengisi'}</td>
                    <td className="p-3">{student.pre_test_result?.interests.find(i=>i.is_primary)?.name || '—'}</td>
                    <td className="p-3">{student.pre_test_result ? new Date(student.pre_test_result.submitted_at).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta' }) : '—'}</td>
                    <td className="p-3"><Link href={route('pretests.show', student.id)} className="font-medium text-indigo-600">Lihat hasil</Link></td>
                </tr>)}{students.data.length === 0 && <tr><td colSpan={5} className="p-6 text-center text-gray-500">Tidak ada siswa yang sesuai.</td></tr>}</tbody></table></div>
                <Paging page={students} />
            </div>
        </div>
    </AuthenticatedLayout>;
}
