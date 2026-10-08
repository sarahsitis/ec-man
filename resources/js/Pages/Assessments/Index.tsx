import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Assignment, AssignmentTable, Paging, Paginator, panel, field } from '@/Components/AssessmentUi';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
export default function Index({ assignments, students, assessors, aspects, pendingCount }: {
    assignments: Paginator<Assignment>; students: { id:number; user_id:number; full_name:string; student_number:string }[];
    assessors: {id:number; name:string}[]; aspects:string[]; status:string; pendingCount:number;
}) {
    const form = useForm({student_id:'',assessor_id:'',title:'',aspect:'speaking',due_date:''});
    const submit: FormEventHandler = e => {e.preventDefault(); form.post(route('assessments.store'), {onSuccess:()=>form.reset()});};
    const pending = pendingCount;
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Penugasan dan Penilaian</h2>}>
        <Head title="Penugasan dan Penilaian" /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <div className={panel}><h3 className="text-lg font-semibold">Buat penugasan panitia</h3><p className="mt-1 text-sm text-gray-500">Satu penugasan untuk satu siswa dan satu aspek. Gunakan judul berbeda untuk tugas atau pertemuan yang berbeda.</p>
                {assessors.length === 0 && <p className="mt-3 text-amber-700">Belum ada panitia. Tetapkan role panitia dan kelas XI/XII melalui Edit Anggota.</p>}
                <form onSubmit={submit} className="mt-4 grid gap-4 md:grid-cols-2">
                    <div><InputLabel value="Panitia penilai" /><select className={field} required value={form.data.assessor_id} onChange={e=>form.setData('assessor_id',e.target.value)}><option value="">Pilih panitia</option>{assessors.map(a=><option key={a.id} value={a.id}>{a.name}</option>)}</select><InputError message={form.errors.assessor_id}/></div>
                    <div><InputLabel value="Siswa peserta" /><select className={field} required value={form.data.student_id} onChange={e=>form.setData('student_id',e.target.value)}><option value="">Pilih siswa</option>{students.filter(s=>String(s.user_id)!==form.data.assessor_id).map(s=><option key={s.id} value={s.id}>{s.full_name} ({s.student_number})</option>)}</select><InputError message={form.errors.student_id}/></div>
                    <div><InputLabel value="Judul tugas / Pertemuan" /><input className={field} required maxLength={150} value={form.data.title} onChange={e=>form.setData('title',e.target.value)} placeholder="Storytelling — Pertemuan 1"/><InputError message={form.errors.title}/></div>
                    <div><InputLabel value="Aspek kemampuan" /><select className={field} value={form.data.aspect} onChange={e=>form.setData('aspect',e.target.value)}>{aspects.map(a=><option key={a} value={a}>{a}</option>)}</select><InputError message={form.errors.aspect}/></div>
                    <div><InputLabel value="Batas waktu (opsional, WIB)" /><input className={field} type="date" value={form.data.due_date} onChange={e=>form.setData('due_date',e.target.value)}/><InputError message={form.errors.due_date}/></div>
                    <div className="flex items-end"><PrimaryButton disabled={form.processing || !assessors.length}>Buat penugasan</PrimaryButton></div>
                </form>
            </div>
            <div className={panel}><h3 className="mb-2 text-lg font-semibold">Daftar penilaian</h3><p className="mb-4 text-sm text-gray-500">{pending} rekomendasi menunggu keputusan. Buka penugasan untuk meninjau skor dan memberi keputusan.</p><div className="mb-4 flex flex-wrap gap-4">{[['all','Semua'],['submitted','Menunggu keputusan'],['revision','Perlu revisi'],['approved','Disahkan']].map(([status,label])=><Link key={status} className="text-indigo-600" href={route('assessments.index',{status})}>{label}</Link>)}</div><AssignmentTable items={assignments.data}/><Paging page={assignments}/></div>
        </div>
    </AuthenticatedLayout>;
}
