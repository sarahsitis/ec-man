import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ManagedStudent, ManagementNav } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StudentStatusDropdown from '@/Components/StudentStatusDropdown';

interface StudentRow extends ManagedStudent { joined_year: string | number; profile_photo_url: string | null; user: { role: string } }
export default function Index({ students, statuses, filters }: { students: StudentRow[]; statuses: Record<string, string>; filters: { search: string; status: string } }) {
    const form = useForm({ student_ids: [] as number[], status: 'active' });
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const pageErrors = usePage().props.errors;
    const allSelected = students.length > 0 && students.every(s=>form.data.student_ids.includes(s.id));
    const toggle = (id: number) => form.setData('student_ids', form.data.student_ids.includes(id) ? form.data.student_ids.filter(value=>value!==id) : [...form.data.student_ids, id]);
    return <AuthenticatedLayout header={<div className="flex flex-wrap items-center justify-between gap-3"><h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Kelola Siswa</h2><Link href={route('students.create')} className="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tambah siswa</Link></div>}>
        <Head title="Kelola Siswa" /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6"><ManagementNav />
            <div className={panel}>
                <form className="flex flex-wrap items-end gap-3" onSubmit={e=>{e.preventDefault(); router.get(route('students.index'), { search, status });}}><div className="flex-1"><InputLabel htmlFor="student_search" value="Cari nama, NIS, atau kelas" /><input id="student_search" className={field} type="search" maxLength={100} value={search} onChange={e=>setSearch(e.target.value)} /></div><div><InputLabel htmlFor="student_filter_status" value="Status siswa" /><select id="student_filter_status" className={field} value={status} onChange={e=>setStatus(e.target.value)}><option value="all">Semua</option>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></div><PrimaryButton>Terapkan</PrimaryButton></form>
                <form className="my-5 flex flex-wrap items-end gap-3 rounded bg-gray-50 p-4 text-gray-800" onSubmit={e=>{e.preventDefault(); form.post(route('students.bulk-status'), { onSuccess: ()=>form.reset('student_ids') });}}><div><InputLabel htmlFor="bulk_student_status" value={'Ubah status ' + form.data.student_ids.length + ' siswa dipilih'} /><select id="bulk_student_status" className={field} value={form.data.status} onChange={e=>form.setData('status', e.target.value)}>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></div><PrimaryButton disabled={form.processing || !form.data.student_ids.length}>Simpan status massal</PrimaryButton><InputError message={form.errors.student_ids} /><InputError message={form.errors.status} /></form>
                <InputError message={pageErrors.student} />
                <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b"><th className="p-3"><input type="checkbox" aria-label="Pilih semua siswa ditampilkan" className="rounded text-indigo-600" checked={allSelected} onChange={()=>form.setData('student_ids', allSelected ? [] : students.map(s=>s.id))} /></th>{['Foto', 'Siswa', 'Peran', 'Kelas', 'Status', 'Tahun gabung', 'Aksi'].map(label=><th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{students.map(student=><tr key={student.id} className="border-b"><td className="p-3"><input type="checkbox" aria-label={'Pilih ' + student.full_name} className="rounded text-indigo-600" checked={form.data.student_ids.includes(student.id)} onChange={()=>toggle(student.id)} /></td><td className="p-3">{student.profile_photo_url ? <img src={student.profile_photo_url} alt={'Foto ' + student.full_name} className="h-10 w-10 rounded-full object-cover" /> : '—'}</td><td className="p-3">{student.full_name}<p className="text-xs text-gray-500">{student.student_number}</p></td><td className="p-3">{student.user.role === 'panitia' ? 'Panitia EC' : 'Siswa'}</td><td className="p-3">{student.class_name || 'Belum diisi'}</td><td className="p-3"><StudentStatusDropdown student={student} statuses={statuses} disabled={form.processing} /></td><td className="p-3">{student.joined_year}</td><td className="p-3"><div className="flex gap-3"><Link href={route('students.edit', student.id)} className="text-indigo-600">Edit</Link><button className="text-red-600" onClick={()=>{if(window.confirm('Hapus siswa ' + student.full_name + ' beserta akun loginnya? Siswa dengan riwayat perlu diubah statusnya.')) router.delete(route('students.destroy', student.id));}}>Hapus</button></div></td></tr>)}{students.length===0 && <tr><td colSpan={8} className="p-6 text-center text-gray-500">Tidak ada siswa yang sesuai.</td></tr>}</tbody></table></div>
            </div>
        </div>
    </AuthenticatedLayout>;
}