import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Activity, Attendance, ManagedStudent, ManagementNav, StatusBadge, activityDate, yearLabel } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ activity, students, attendances, myAttendance, attendanceStatuses, activityStatuses }: {
    activity: Activity; students: ManagedStudent[]; attendances: Attendance[]; myAttendance: Attendance | null;
    attendanceStatuses: Record<string, string>; activityStatuses: Record<string, string>;
}) {
    const page = usePage(); const user = page.props.auth.user;
    const pembina = user.role === 'pembina'; const staff = pembina || user.is_panitia;
    const [search, setSearch] = useState(''); const [selected, setSelected] = useState<number[]>([]); const [bulkStatus, setBulkStatus] = useState('hadir');
    const form = useForm({ entries: {} as Record<string, string> });
    const saved = new Map(attendances.map(a=>[a.student_id,a]));
    const visible = students.filter(s=>`${s.full_name} ${s.student_number} ${s.class_name || ''}`.toLocaleLowerCase('id-ID').includes(search.toLocaleLowerCase('id-ID')));
    const allSelected = visible.length>0 && visible.every(s=>selected.includes(s.id));
    const changed = Object.keys(form.data.entries).length;
    const setStatuses = (ids: number[], status: string) => {
        const entries = { ...form.data.entries };
        ids.forEach(id=>{if(status===(saved.get(id)?.status || 'belum_diisi')) delete entries[String(id)]; else entries[String(id)]=status;});
        form.setData('entries',entries);
    };
    const submit = () => {
        form.transform(data=>({ attendances: Object.entries(data.entries).map(([id,status])=>({ student_id:Number(id),status })) }));
        form.post(route('activities.attendance',activity.id), { onSuccess:()=>{form.reset();setSelected([]);} });
    };
    const counts = Object.keys(attendanceStatuses).map(status=>({status,total:students.filter(s=>(saved.get(s.id)?.status || 'belum_diisi')===status).length}));
    const errors: Record<string,string | undefined> = form.errors;
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{activity.title}</h2>}>
        <Head title={activity.title} /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6"><ManagementNav /><Link className="inline-block text-indigo-600" href={route('activities.index')}>Kembali ke kegiatan</Link>
            <div className={panel}><div className="flex flex-wrap items-start justify-between gap-3"><div><h3 className="text-lg font-semibold">{activity.title}</h3><p className="mt-1 text-sm text-gray-500">{activity.category} · {yearLabel(activity.academic_year)}</p></div><StatusBadge status={activity.status} labels={activityStatuses} /></div>
                <div className="mt-4 grid gap-2 sm:grid-cols-2"><p>{activityDate(activity.activity_date)} · {activity.start_time.slice(0,5)}{activity.end_time && `–${activity.end_time.slice(0,5)}`} WIB</p><p>Lokasi: {activity.location || 'Belum diisi'}</p><p>Penanggung jawab: {activity.pic || 'Belum diisi'}</p><p>Sasaran: {activity.target_audience || 'Anggota semester kegiatan'}</p></div>
                {activity.description && <p className="mt-4 whitespace-pre-wrap">{activity.description}</p>}
                {(activity.objectives || activity.agenda) && <div className="mt-4 grid gap-4 sm:grid-cols-2">{activity.objectives && <div><h4 className="font-semibold">Tujuan</h4><p className="mt-1 whitespace-pre-wrap">{activity.objectives}</p></div>}{activity.agenda && <div><h4 className="font-semibold">Agenda</h4><p className="mt-1 whitespace-pre-wrap">{activity.agenda}</p></div>}</div>}
                {pembina && <div className="mt-5 flex gap-4"><Link className="text-indigo-600" href={route('activities.edit',activity.id)}>Edit kegiatan</Link><button className="text-red-600" onClick={()=>{if(window.confirm(`Hapus kegiatan ${activity.title}? Kegiatan yang memiliki riwayat presensi perlu dibatalkan.`)) router.delete(route('activities.destroy',activity.id));}}>Hapus kegiatan</button></div>}
                <InputError message={page.props.errors.activity} />
            </div>
            {!staff ? <div className={panel}><h3 className="font-semibold">Presensi saya</h3><div className="mt-3"><StatusBadge status={myAttendance?.status || 'belum_diisi'} labels={attendanceStatuses} /></div></div> : <div className={panel}>
                <h3 className="text-lg font-semibold">Presensi peserta</h3><p className="mt-2 text-sm text-gray-500">Daftar berasal dari anggota aktif semester kegiatan. Siswa yang sudah memiliki catatan tetap ditampilkan untuk koreksi riwayat. Siswa yang belum dicatat tidak otomatis dianggap alpa.</p>
                <div className="my-4 flex flex-wrap gap-3">{counts.map(c=><span key={c.status} className="rounded bg-gray-100 px-3 py-2 text-sm text-gray-800">{attendanceStatuses[c.status]}: {c.total}</span>)}</div>
                {activity.status==='cancelled' && <p className="mb-4 rounded bg-amber-100 p-3 text-amber-900">Kegiatan dibatalkan. Catatan presensi tetap tersedia; pencatatan dinonaktifkan.</p>}
                {Object.entries(errors).map(([key,message])=><InputError key={key} message={message} />)}
                <fieldset disabled={form.processing || activity.status==='cancelled'}>
                    <div><InputLabel htmlFor="attendance_search" value="Cari nama, NIS, atau kelas" /><input id="attendance_search" type="search" className={field} value={search} onChange={e=>setSearch(e.target.value)} /></div>
                    <div className="my-4 flex flex-wrap items-end gap-3 rounded bg-gray-50 p-4 text-gray-800"><div><InputLabel htmlFor="attendance_bulk_status" value={`Status untuk ${selected.length} peserta dipilih`} /><select id="attendance_bulk_status" className={field} value={bulkStatus} onChange={e=>setBulkStatus(e.target.value)}>{Object.entries(attendanceStatuses).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></div><button type="button" disabled={!selected.length} className="rounded border border-indigo-300 px-3 py-2 text-sm text-indigo-700 disabled:opacity-50" onClick={()=>setStatuses(selected,bulkStatus)}>Terapkan ke pilihan</button>{selected.length>0 && <button type="button" className="px-3 py-2 text-sm text-indigo-600" onClick={()=>setSelected([])}>Hapus pilihan</button>}</div>
                    <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b"><th className="p-3"><input type="checkbox" className="rounded text-indigo-600" aria-label="Pilih semua peserta ditampilkan" checked={allSelected} onChange={()=>{const ids=visible.map(s=>s.id);setSelected(allSelected ? selected.filter(id=>!ids.includes(id)) : [...new Set([...selected,...ids])]);}} /></th>{['Peserta','Status siswa','Presensi','Pencatatan terakhir'].map(label=><th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{visible.map(student=><tr key={student.id} className={`border-b ${form.data.entries[String(student.id)] ? 'bg-indigo-50 dark:bg-indigo-950' : ''}`}><td className="p-3"><input type="checkbox" className="rounded text-indigo-600" aria-label={`Pilih ${student.full_name}`} checked={selected.includes(student.id)} onChange={()=>setSelected(selected.includes(student.id) ? selected.filter(id=>id!==student.id) : [...selected,student.id])} /></td><td className="p-3">{student.full_name}<p className="text-xs text-gray-500">{student.student_number} · {student.class_name || 'Kelas belum diisi'}</p></td><td className="p-3"><StatusBadge status={student.status} /></td><td className="p-3"><select className={field} aria-label={`Presensi ${student.full_name}`} value={form.data.entries[String(student.id)] ?? saved.get(student.id)?.status ?? 'belum_diisi'} onChange={e=>setStatuses([student.id],e.target.value)}>{Object.entries(attendanceStatuses).map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></td><td className="p-3">{saved.get(student.id)?.recorder?.name || '—'}{saved.get(student.id) && <p className="text-xs text-gray-500">{new Date(saved.get(student.id)!.updated_at).toLocaleString('id-ID',{timeZone:'Asia/Jakarta'})} WIB</p>}</td></tr>)}{visible.length===0 && <tr><td colSpan={5} className="p-6 text-center text-gray-500">Belum ada peserta yang sesuai. {pembina ? <Link className="text-indigo-600" href={route('memberships.index',{academic_year_id:activity.academic_year_id})}>Kelola keanggotaan semester ini</Link> : 'Hubungi pembina untuk memeriksa keanggotaan semester.'}</td></tr>}</tbody></table></div>
                    <div className="mt-5 flex flex-wrap items-center gap-4"><PrimaryButton type="button" disabled={!changed || form.processing} onClick={submit}>Simpan {changed} perubahan presensi</PrimaryButton>{changed>0 && <button type="button" className="text-indigo-600" onClick={()=>form.reset()}>Batalkan perubahan</button>}</div>
                </fieldset>
            </div>}
        </div>
    </AuthenticatedLayout>;
}
