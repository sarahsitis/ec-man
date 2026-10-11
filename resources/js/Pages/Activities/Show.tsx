import { useTranslation } from '@/i18n/useTranslation';
import ActivityAttachments, { ActivityAttachment, AttachmentOptions } from '@/Components/ActivityAttachments';
import ClientTablePaging from '@/Components/ClientTablePaging';
import useTablePagination from '@/hooks/useTablePagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Activity, Attendance, ManagedStudent, StatusBadge, activityDate, yearLabel } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ activity, students, attendances, myAttendance, attendanceStatuses, activityStatuses, attachments, attachmentOptions }: {
    activity: Activity; students: ManagedStudent[]; attendances: Attendance[]; myAttendance: Attendance | null;
    attendanceStatuses: Record<string, string>; activityStatuses: Record<string, string>;
    attachments: ActivityAttachment[]; attachmentOptions: AttachmentOptions;
}) {
    const { t, locale, dateLocale } = useTranslation();
    const page = usePage(); const user = page.props.auth.user;
    const pembina = user.role === 'pembina'; const staff = pembina || user.is_panitia;
    const [search, setSearch] = useState(''); const [selected, setSelected] = useState<number[]>([]); const [bulkStatus, setBulkStatus] = useState('hadir');
    const form = useForm({ entries: {} as Record<string, string> });
    const saved = new Map(attendances.map(a=>[a.student_id,a]));
    const filtered = students.filter(s=>`${s.full_name} ${s.student_number} ${s.class_name || ''}`.toLocaleLowerCase('id-ID').includes(search.toLocaleLowerCase('id-ID')));
    const pagination = useTablePagination(filtered, search);
    const visible = pagination.items;
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
        <Head title={activity.title} /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6"><Link className="ec-action ec-action-secondary" href={route('activities.index')}>{t("Kembali ke kegiatan")}</Link>
            <div className={panel}><div className="flex flex-wrap items-start justify-between gap-3"><div><h3 className="text-lg font-semibold">{activity.title}</h3><p className="mt-1 text-sm text-gray-500">{activity.category} · {yearLabel(activity.academic_year, locale)}</p></div><StatusBadge status={activity.status} labels={activityStatuses} /></div>
                <div className="mt-4 grid gap-2 sm:grid-cols-2"><p>{activityDate(activity.activity_date, locale)} · {activity.start_time.slice(0,5)}{activity.end_time && `–${activity.end_time.slice(0,5)}`} WIB</p><p>{t("Lokasi:")} {activity.location || t('Belum diisi')}</p><p>{t("Penanggung jawab:")} {activity.pic || t('Belum diisi')}</p><p>{t("Sasaran:")} {activity.target_audience || 'Anggota semester kegiatan'}</p></div>
                {activity.description && <p className="mt-4 whitespace-pre-wrap">{activity.description}</p>}
                {(activity.objectives || activity.agenda) && <div className="mt-4 grid gap-4 sm:grid-cols-2">{activity.objectives && <div><h4 className="font-semibold">{t("Tujuan")}</h4><p className="mt-1 whitespace-pre-wrap">{activity.objectives}</p></div>}{activity.agenda && <div><h4 className="font-semibold">{t("Agenda")}</h4><p className="mt-1 whitespace-pre-wrap">{activity.agenda}</p></div>}</div>}
                {pembina && <div className="mt-5 flex gap-4"><Link className="ec-action ec-action-edit" href={route('activities.edit',activity.id)}>{t("Edit kegiatan")}</Link><button className="ec-action ec-action-danger" onClick={()=>{if(window.confirm(t("Hapus kegiatan :value1? Kegiatan yang memiliki riwayat presensi perlu dibatalkan.", { value1: activity.title }))) router.delete(route('activities.destroy',activity.id));}}>{t("Hapus kegiatan")}</button></div>}
                <InputError message={page.props.errors.activity} />
            </div>
            <ActivityAttachments activityId={activity.id} attachments={attachments} options={attachmentOptions} canUpload={staff} />
            {!staff ? <div className={panel}><h3 className="font-semibold">{t("Presensi saya")}</h3><div className="mt-3"><StatusBadge status={myAttendance?.status || 'belum_diisi'} labels={attendanceStatuses} /></div></div> : <div className={panel}>
                <h3 className="text-lg font-semibold">{t("Presensi peserta")}</h3><p className="mt-2 text-sm text-gray-500">{t("Daftar berasal dari anggota aktif semester kegiatan. Siswa yang sudah memiliki catatan tetap ditampilkan untuk koreksi riwayat. Siswa yang belum dicatat tidak otomatis dianggap alpa.")}</p>
                <div className="my-4 flex flex-wrap gap-3">{counts.map(c=><span key={c.status} className="rounded bg-gray-100 px-3 py-2 text-sm text-gray-800">{attendanceStatuses[c.status]}: {c.total}</span>)}</div>
                {activity.status==='cancelled' && <p className="mb-4 rounded bg-amber-100 p-3 text-amber-900">{t("Kegiatan dibatalkan. Catatan presensi tetap tersedia; pencatatan dinonaktifkan.")}</p>}
                {Object.entries(errors).map(([key,message])=><InputError key={key} message={message} />)}
                <fieldset disabled={form.processing || activity.status==='cancelled'}>
                    <div><InputLabel htmlFor="attendance_search" value={t("Cari nama, NIS, atau kelas")} /><input id="attendance_search" type="search" className={field} value={search} onChange={e=>setSearch(e.target.value)} /></div>
                    <div className="my-4 flex flex-wrap items-end gap-3 rounded bg-gray-50 p-4 text-gray-800"><div><InputLabel htmlFor="attendance_bulk_status" value={t("Status untuk :value1 peserta dipilih", { value1: selected.length })} /><select id="attendance_bulk_status" className={field} value={bulkStatus} onChange={e=>setBulkStatus(e.target.value)}>{Object.entries(attendanceStatuses).map(([key,label])=><option key={key} value={key}>{t(label)}</option>)}</select></div><button type="button" disabled={!selected.length} className="ec-action ec-action-secondary" onClick={()=>setStatuses(selected,bulkStatus)}>{t("Terapkan ke pilihan")}</button>{selected.length>0 && <button type="button" className="ec-action ec-action-secondary" onClick={()=>setSelected([])}>{t("Hapus pilihan")}</button>}</div>
                    <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b"><th className="p-3"><input type="checkbox" className="rounded text-indigo-600" aria-label={t("Pilih semua peserta ditampilkan")} checked={allSelected} onChange={()=>{const ids=visible.map(s=>s.id);setSelected(allSelected ? selected.filter(id=>!ids.includes(id)) : [...new Set([...selected,...ids])]);}} /></th>{[t("Peserta"),t("Status siswa"),t("Presensi"),t("Pencatatan terakhir")].map(label=><th key={label} className="p-3">{t(label)}</th>)}</tr></thead><tbody>{visible.map(student=><tr key={student.id} className={`border-b ${form.data.entries[String(student.id)] ? 'bg-indigo-50 dark:bg-indigo-950' : ''}`}><td className="p-3"><input type="checkbox" className="rounded text-indigo-600" aria-label={t("Pilih :value1", { value1: student.full_name })} checked={selected.includes(student.id)} onChange={()=>setSelected(selected.includes(student.id) ? selected.filter(id=>id!==student.id) : [...selected,student.id])} /></td><td className="p-3">{student.full_name}<p className="text-xs text-gray-500">{student.student_number} · {student.class_name || 'Kelas belum diisi'}</p></td><td className="p-3"><StatusBadge status={student.status} /></td><td className="p-3"><select className={field} aria-label={`Presensi ${student.full_name}`} value={form.data.entries[String(student.id)] ?? saved.get(student.id)?.status ?? 'belum_diisi'} onChange={e=>setStatuses([student.id],e.target.value)}>{Object.entries(attendanceStatuses).map(([key,label])=><option key={key} value={key}>{t(label)}</option>)}</select></td><td className="p-3">{saved.get(student.id)?.recorder?.name || '—'}{saved.get(student.id) && <p className="text-xs text-gray-500">{new Date(saved.get(student.id)!.updated_at).toLocaleString(dateLocale,{timeZone:'Asia/Jakarta'})} WIB</p>}</td></tr>)}{visible.length===0 && <tr><td colSpan={5} className="p-6 text-center text-gray-500">{t("Belum ada peserta yang sesuai.")} {pembina ? <Link className="ec-action ec-action-edit" href={route('memberships.index',{academic_year_id:activity.academic_year_id})}>{t("Kelola keanggotaan semester ini")}</Link> : t("Hubungi pembina untuk memeriksa keanggotaan semester.")}</td></tr>}</tbody></table></div><ClientTablePaging pagination={pagination} />
                    <div className="mt-5 flex flex-wrap items-center gap-4"><PrimaryButton type="button" disabled={!changed || form.processing} onClick={submit}>{t("Simpan")} {changed}  {t("perubahan presensi")}</PrimaryButton>{changed>0 && <button type="button" className="ec-action ec-action-edit" onClick={()=>form.reset()}>{t("Batalkan perubahan")}</button>}</div>
                </fieldset>
            </div>}
        </div>
    </AuthenticatedLayout>;
}
