import { useTranslation } from '@/i18n/useTranslation';
import DataTransferActions from '@/Components/DataTransferActions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ManagedStudent } from '@/Components/ManagementUi';
import { panel, field, Paging, Paginator } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StudentStatusDropdown from '@/Components/StudentStatusDropdown';

interface StudentRow extends ManagedStudent { joined_year: string | number; profile_photo_url: string | null; user: { role: string; is_panitia: boolean } }
export default function Index({ students, statuses, filters }: { students: Paginator<StudentRow>; statuses: Record<string, string>; filters: { search: string; status: string } }) {
    const { t, locale, dateLocale } = useTranslation();
    const form = useForm({ student_ids: [] as number[], status: 'active' });
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const pageErrors = usePage().props.errors;
    const allSelected = students.data.length > 0 && students.data.every(s=>form.data.student_ids.includes(s.id));
    const toggle = (id: number) => form.setData('student_ids', form.data.student_ids.includes(id) ? form.data.student_ids.filter(value=>value!==id) : [...form.data.student_ids, id]);
    return <AuthenticatedLayout header={<div className="flex flex-wrap items-center justify-between gap-3"><h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{t("Kelola Siswa")}</h2><Link href={route('students.create')} className="ec-action ec-action-primary">{t("Tambah siswa")}</Link></div>}>
        <Head title={t("Kelola Siswa")} /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6"><DataTransferActions dataset="students" />
            <div className={panel}>
                <form className="flex flex-wrap items-end gap-3" onSubmit={e=>{e.preventDefault(); router.get(route('students.index'), { search, status, per_page: students.per_page });}}><div className="flex-1"><InputLabel htmlFor="student_search" value={t("Cari nama, NIS, atau kelas")} /><input id="student_search" className={field} type="search" maxLength={100} value={search} onChange={e=>setSearch(e.target.value)} /></div><div><InputLabel htmlFor="student_filter_status" value={t("Status siswa")} /><select id="student_filter_status" className={field} value={status} onChange={e=>setStatus(e.target.value)}><option value="all">{t("Semua")}</option>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{t(label)}</option>)}</select></div><PrimaryButton>{t("Terapkan")}</PrimaryButton></form>
                <form className="my-5 flex flex-wrap items-end gap-3 rounded bg-gray-50 p-4 text-gray-800" onSubmit={e=>{e.preventDefault(); form.post(route('students.bulk-status'), { onSuccess: ()=>form.reset('student_ids') });}}><div><InputLabel htmlFor="bulk_student_status" value={t('Ubah status :count siswa dipilih', { count: form.data.student_ids.length })} /><select id="bulk_student_status" className={field} value={form.data.status} onChange={e=>form.setData('status', e.target.value)}>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{t(label)}</option>)}</select></div><PrimaryButton disabled={form.processing || !form.data.student_ids.length}>{t("Simpan status massal")}</PrimaryButton><InputError message={form.errors.student_ids} /><InputError message={form.errors.status} /></form>
                <InputError message={pageErrors.student} />
                <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b"><th className="p-3"><input type="checkbox" aria-label={t("Pilih semua siswa ditampilkan")} className="rounded text-indigo-600" checked={allSelected} onChange={()=>form.setData('student_ids', allSelected ? [] : students.data.map(s=>s.id))} /></th>{[t("Foto"), t("Siswa"), t("Peran"), t("Kelas"), t("Status"), t("Tahun gabung"), t("Aksi")].map(label=><th key={label} className="p-3">{t(label)}</th>)}</tr></thead><tbody>{students.data.map(student=><tr key={student.id} className="border-b"><td className="p-3"><input type="checkbox" aria-label={t('Pilih :name', { name: student.full_name })} className="rounded text-indigo-600" checked={form.data.student_ids.includes(student.id)} onChange={()=>toggle(student.id)} /></td><td className="p-3">{student.profile_photo_url ? <img src={student.profile_photo_url} alt={t("Foto ") + student.full_name} className="h-10 w-10 rounded-full object-cover" /> : '—'}</td><td className="p-3">{student.full_name}<p className="text-xs text-gray-500">{student.student_number}</p></td><td className="p-3">{student.user.is_panitia ? t("Panitia EC") : t("Siswa")}</td><td className="p-3">{student.class_name || t('Belum diisi')}</td><td className="p-3"><StudentStatusDropdown student={student} statuses={statuses} disabled={form.processing} /></td><td className="p-3">{student.joined_year}</td><td className="p-3"><div className="flex flex-wrap gap-3"><Link href={route('reports.student', student.id)} className="ec-action ec-action-info">{t("Laporan")}</Link><Link href={route('students.edit', student.id)} className="ec-action ec-action-edit">{t("Edit")}</Link><button className="ec-action ec-action-danger" onClick={()=>{if(window.confirm(t('Hapus siswa :name beserta akun loginnya? Siswa dengan riwayat perlu diubah statusnya.', { name: student.full_name }))) router.delete(route('students.destroy', student.id));}}>{t("Hapus")}</button></div></td></tr>)}{students.data.length===0 && <tr><td colSpan={8} className="p-6 text-center text-gray-500">{t("Tidak ada siswa yang sesuai.")}</td></tr>}</tbody></table></div><Paging page={students} />
            </div>
        </div>
    </AuthenticatedLayout>;
}
