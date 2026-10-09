import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Assignment, AssignmentTable, Paging, Paginator, panel, field } from '@/Components/AssessmentUi';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
export default function Index({ assignments, students, assessors, aspects, pendingCount }: {
    assignments: Paginator<Assignment>; students: { id:number; user_id:number; full_name:string; student_number:string; class_name:string | null }[];
    assessors: {id:number; name:string}[]; aspects:string[]; status:string; pendingCount:number;
}) {
    const form = useForm({student_ids: [] as number[],assessor_id:'',title:'',aspect:'speaking',due_date:''});
    const [search, setSearch] = useState('');
    const eligibleStudents = students.filter(s => String(s.user_id) !== form.data.assessor_id);
    const visibleStudents = eligibleStudents.filter(s => `${s.full_name} ${s.student_number} ${s.class_name || ''}`.toLocaleLowerCase('id-ID').includes(search.toLocaleLowerCase('id-ID')));
    const allVisibleSelected = visibleStudents.length > 0 && visibleStudents.every(s => form.data.student_ids.includes(s.id));
    const errors: Record<string, string | undefined> = form.errors;
    const selectAssessor = (assessorId: string) => {
        form.setData(data => ({ ...data, assessor_id: assessorId, student_ids: data.student_ids.filter(id => students.some(s => s.id === id && String(s.user_id) !== assessorId)) }));
        form.clearErrors();
    };
    const toggleStudent = (id: number) => {
        form.setData('student_ids', form.data.student_ids.includes(id) ? form.data.student_ids.filter(selected => selected !== id) : [...form.data.student_ids, id]);
        form.clearErrors();
    };
    const toggleVisibleStudents = () => {
        const visibleIds = visibleStudents.map(s => s.id);
        form.setData('student_ids', allVisibleSelected ? form.data.student_ids.filter(id => !visibleIds.includes(id)) : [...new Set([...form.data.student_ids, ...visibleIds])]);
        form.clearErrors();
    };
    const submit: FormEventHandler = e => {e.preventDefault(); form.post(route('assessments.store'), {onSuccess:()=>{form.reset(); setSearch('');}});};
    const pending = pendingCount;
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Penugasan dan Penilaian</h2>}>
        <Head title="Penugasan dan Penilaian" /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <div className={panel}><h3 className="text-lg font-semibold">Buat penugasan panitia</h3><p className="mt-1 text-sm text-gray-500">Pilih satu panitia untuk menilai beberapa siswa sekaligus. Skor dan catatan penilaian disimpan per siswa. Gunakan judul berbeda untuk tugas atau pertemuan yang berbeda.</p>
                {assessors.length === 0 && <p className="mt-3 text-amber-700">Belum ada panitia. Tetapkan role panitia dan kelas XI/XII melalui Edit Anggota.</p>}
                <form onSubmit={submit} className="mt-4 grid gap-4 md:grid-cols-2">
                    <div><InputLabel htmlFor="assessor_id" value="Panitia penilai" /><select id="assessor_id" className={field} required value={form.data.assessor_id} onChange={e=>selectAssessor(e.target.value)}><option value="">Pilih panitia</option>{assessors.map(a=><option key={a.id} value={a.id}>{a.name}</option>)}</select><InputError message={form.errors.assessor_id}/></div>
                    <fieldset className="md:col-span-2 md:row-start-2">
                        <legend className="text-sm font-medium">Siswa peserta ({form.data.student_ids.length} dipilih)</legend>
                        <InputLabel htmlFor="student_search" value="Cari nama, nomor induk, atau kelas" className="mt-2" />
                        <input id="student_search" type="search" className={field} value={search} onChange={e=>setSearch(e.target.value)} placeholder="Cari siswa..." />
                        <div className="my-2 flex flex-wrap gap-4 text-sm">
                            <button type="button" className="text-indigo-600" disabled={!visibleStudents.length} onClick={toggleVisibleStudents}>{allVisibleSelected ? 'Batalkan pilihan yang ditampilkan' : 'Pilih semua yang ditampilkan'}</button>
                            {form.data.student_ids.length > 0 && <button type="button" className="text-indigo-600" onClick={()=>{form.setData('student_ids', []); form.clearErrors();}}>Hapus semua pilihan</button>}
                        </div>
                        <div className="max-h-64 overflow-y-auto rounded-md border border-gray-300">
                            {visibleStudents.map(s => <div key={s.id} className="border-b border-gray-200 px-3 py-2 last:border-b-0">
                                <label className="flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked={form.data.student_ids.includes(s.id)} onChange={()=>toggleStudent(s.id)} />
                                    <span>{s.full_name}<span className="block text-xs text-gray-500">{s.student_number} · {s.class_name || 'Kelas belum diisi'}</span></span>
                                </label>
                            </div>)}
                            {!visibleStudents.length && <p className="p-3 text-sm text-gray-500">Tidak ada siswa yang sesuai.</p>}
                        </div>
                        <InputError message={form.errors.student_ids} />
                        {form.data.student_ids.map((id, index) => errors[`student_ids.${index}`] && <InputError key={id} message={`${students.find(s=>s.id===id)?.full_name || 'Siswa'}: ${errors[`student_ids.${index}`]}`} />)}
                    </fieldset>
                    <div><InputLabel value="Judul tugas / Pertemuan" /><input className={field} required maxLength={150} value={form.data.title} onChange={e=>form.setData('title',e.target.value)} placeholder="Storytelling — Pertemuan 1"/><InputError message={form.errors.title}/></div>
                    <div><InputLabel value="Aspek kemampuan" /><select className={field} value={form.data.aspect} onChange={e=>form.setData('aspect',e.target.value)}>{aspects.map(a=><option key={a} value={a}>{a}</option>)}</select><InputError message={form.errors.aspect}/></div>
                    <div><InputLabel value="Batas waktu (opsional, WIB)" /><input className={field} type="date" value={form.data.due_date} onChange={e=>form.setData('due_date',e.target.value)}/><InputError message={form.errors.due_date}/></div>
                    <div className="flex items-end"><PrimaryButton disabled={form.processing || !assessors.length || !form.data.student_ids.length}>Buat penugasan untuk {form.data.student_ids.length} siswa</PrimaryButton></div>
                </form>
            </div>
            <div className={panel}><h3 className="mb-2 text-lg font-semibold">Daftar penilaian</h3><p className="mb-4 text-sm text-gray-500">{pending} rekomendasi menunggu keputusan. Buka penugasan untuk meninjau skor dan memberi keputusan.</p><div className="mb-4 flex flex-wrap gap-4">{[['all','Semua'],['submitted','Menunggu keputusan'],['revision','Perlu revisi'],['approved','Disahkan']].map(([status,label])=><Link key={status} className="text-indigo-600" href={route('assessments.index',{status})}>{label}</Link>)}</div><AssignmentTable items={assignments.data}/><Paging page={assignments}/></div>
        </div>
    </AuthenticatedLayout>;
}
