import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AcademicYear, ManagedStudent, Membership, ManagementNav, StatusBadge, yearLabel } from '@/Components/ManagementUi';
import { panel, field, Paging, Paginator } from '@/Components/AssessmentUi';
import StudentPicker from '@/Components/StudentPicker';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import ClassSelect from '@/Components/ClassSelect';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ memberships, years, students, statuses, filters }: {
    memberships: Paginator<Membership>; years: AcademicYear[]; students: ManagedStudent[]; statuses: Record<string, string>;
    filters: { academic_year_id: number | null; status: string; search: string };
}) {
    const form = useForm({ academic_year_id: String(filters.academic_year_id || ''), student_ids: [] as number[], status: 'active' });
    const edit = useForm({ status: 'active', class_name: '' });
    const [editing, setEditing] = useState<Membership | null>(null);
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const errors: Record<string, string | undefined> = form.errors;
    const filter = (year = filters.academic_year_id) => router.get(route('memberships.index'), { academic_year_id: year, search, status });
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Keanggotaan Siswa</h2>}>
        <Head title="Keanggotaan Siswa" /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6"><ManagementNav />
            {years.length===0 ? <div className={panel}>Tambahkan tahun ajaran dan semester sebelum mendaftarkan anggota. <Link className="text-indigo-600" href={route('academic-years.index')}>Kelola tahun ajaran</Link></div> : <>
                <div className={panel}><InputLabel htmlFor="membership_year" value="Tahun ajaran dan semester" /><select id="membership_year" className={field} value={filters.academic_year_id || ''} onChange={e=>filter(Number(e.target.value))}>{years.map(year=><option key={year.id} value={year.id}>{yearLabel(year)}</option>)}</select><p className="mt-2 text-sm text-gray-500">Status keanggotaan berlaku untuk semester ini. Status siswa pada profil tetap ditampilkan untuk membedakan riwayat semester dan kondisi siswa saat ini.</p></div>
                <div className={panel}><h3 className="text-lg font-semibold">Daftarkan anggota sekaligus</h3>
                    <form className="mt-4 space-y-4" onSubmit={e=>{e.preventDefault(); form.post(route('memberships.store'), { onSuccess: ()=>form.reset('student_ids') });}}>
                        <StudentPicker students={students} selected={form.data.student_ids} onChange={ids=>form.setData('student_ids', ids)} disabled={form.processing} /><InputError message={form.errors.student_ids} />
                        {Object.entries(errors).filter(([key])=>key.startsWith('student_ids.')).map(([key, message])=><InputError key={key} message={message} />)}
                        <InputError message={form.errors.academic_year_id} />
                        <div><InputLabel htmlFor="new_membership_status" value="Status keanggotaan" /><select id="new_membership_status" className={field} value={form.data.status} onChange={e=>form.setData('status', e.target.value)}>{Object.entries(statuses).map(([key, label])=><option key={key} value={key}>{label}</option>)}</select><InputError message={form.errors.status} /></div>
                        <PrimaryButton disabled={form.processing || !form.data.student_ids.length}>Daftarkan {form.data.student_ids.length} siswa</PrimaryButton>
                    </form>
                </div>
                <div className={panel}><h3 className="text-lg font-semibold">Anggota semester ini</h3>
                    <form className="my-4 flex flex-wrap items-end gap-3" onSubmit={e=>{e.preventDefault(); filter();}}><div className="flex-1"><InputLabel htmlFor="membership_search" value="Cari nama, NIS, atau kelas" /><input id="membership_search" className={field} type="search" maxLength={100} value={search} onChange={e=>setSearch(e.target.value)} /></div><div><InputLabel htmlFor="membership_status" value="Status keanggotaan" /><select id="membership_status" className={field} value={status} onChange={e=>setStatus(e.target.value)}><option value="all">Semua</option>{Object.entries(statuses).map(([key, label])=><option key={key} value={key}>{label}</option>)}</select></div><PrimaryButton>Terapkan</PrimaryButton></form>
                    <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b">{['Siswa', 'Kelas semester', 'Keanggotaan', 'Status siswa', 'Aksi'].map(label=><th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{memberships.data.map(m=><tr key={m.id} className="border-b"><td className="p-3">{m.student.full_name}<p className="text-xs text-gray-500">{m.student.student_number}</p></td><td className="p-3">{m.class_name || 'Belum diisi'}</td><td className="p-3"><StatusBadge status={m.status} labels={statuses} /></td><td className="p-3"><StatusBadge status={m.student.status} labels={statuses} /></td><td className="p-3"><div className="flex gap-3"><button className="text-indigo-600" onClick={()=>{setEditing(m); edit.setData({ status: m.status, class_name: m.class_name || '' }); edit.clearErrors();}}>Edit</button><button className="text-red-600" onClick={()=>{if(window.confirm(`Hapus keanggotaan semester ${m.student.full_name}? Profil dan riwayat presensi tetap tersimpan.`)) router.delete(route('memberships.destroy', m.id));}}>Hapus</button></div></td></tr>)}{memberships.data.length===0 && <tr><td colSpan={5} className="p-6 text-center text-gray-500">Belum ada anggota yang sesuai.</td></tr>}</tbody></table></div><Paging page={memberships} />
                </div>
            </>}
            <Modal show={editing!==null} onClose={()=>{if(!edit.processing) setEditing(null);}}><div className="p-6 dark:text-gray-100"><h3 className="text-lg font-semibold">Edit keanggotaan {editing?.student.full_name}</h3><form className="mt-4 space-y-4" onSubmit={e=>{e.preventDefault(); if(editing) edit.put(route('memberships.update', editing.id), { onSuccess: ()=>setEditing(null) });}}><div><InputLabel htmlFor="edit_membership_class" value="Kelas pada semester ini" /><ClassSelect id="edit_membership_class" value={edit.data.class_name} onChange={e=>edit.setData('class_name', e.target.value)} /><InputError message={edit.errors.class_name} /></div><div><InputLabel htmlFor="edit_membership_status" value="Status keanggotaan" /><select id="edit_membership_status" className={field} value={edit.data.status} onChange={e=>edit.setData('status', e.target.value)}>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select><InputError message={edit.errors.status} /></div><div className="flex gap-3"><PrimaryButton disabled={edit.processing}>Simpan</PrimaryButton><button type="button" disabled={edit.processing} onClick={()=>setEditing(null)}>Batal</button></div></form></div></Modal>
        </div>
    </AuthenticatedLayout>;
}
