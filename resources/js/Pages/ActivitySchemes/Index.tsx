import { useTranslation } from '@/i18n/useTranslation';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ActivityScheme } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ schemes }: { schemes: ActivityScheme[] }) {
    const { t, locale, dateLocale } = useTranslation();
    const form = useForm({ name: '', category: '', objectives: '', agenda: '', duration_minutes: '60' });
    const [editing, setEditing] = useState<number | null>(null);
    const pageErrors = usePage().props.errors;
    const reset = () => { setEditing(null); form.reset(); form.clearErrors(); };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{t("Skema Kegiatan")}</h2>}>
        <Head title={t("Skema Kegiatan")} /><div className="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
            <div className={panel}><h3 className="text-lg font-semibold">{editing ? t("Edit skema") : t("Buat skema kegiatan")}</h3><p className="mt-2 text-sm text-gray-500">{t("Skema merupakan template yang dapat diterapkan saat membuat kegiatan. Tujuan dan agenda disalin ke kegiatan agar catatannya tetap utuh ketika template berubah.")}</p>
                <form className="mt-4 grid gap-4 sm:grid-cols-2" onSubmit={e=>{e.preventDefault(); const options = { onSuccess: reset }; editing ? form.put(route('activity-schemes.update', editing), options) : form.post(route('activity-schemes.store'), options);}}>
                    <div><InputLabel htmlFor="scheme_name" value={t("Nama skema")} /><input id="scheme_name" className={field} required maxLength={150} value={form.data.name} onChange={e=>form.setData('name', e.target.value)} placeholder={t("Latihan percakapan mingguan")} /><InputError message={form.errors.name} /></div>
                    <div><InputLabel htmlFor="scheme_category" value={t("Kategori")} /><input id="scheme_category" className={field} required maxLength={100} value={form.data.category} onChange={e=>form.setData('category', e.target.value)} placeholder={t("Percakapan / Debat / Storytelling")} /><InputError message={form.errors.category} /></div>
                    <div className="sm:col-span-2"><InputLabel htmlFor="scheme_objectives" value={t("Tujuan kegiatan")} /><textarea id="scheme_objectives" className={field} required rows={3} maxLength={5000} value={form.data.objectives} onChange={e=>form.setData('objectives', e.target.value)} /><InputError message={form.errors.objectives} /></div>
                    <div className="sm:col-span-2"><InputLabel htmlFor="scheme_agenda" value={t("Susunan agenda")} /><textarea id="scheme_agenda" className={field} required rows={5} maxLength={10000} value={form.data.agenda} onChange={e=>form.setData('agenda', e.target.value)} placeholder={t("10 menit: pemanasan\n35 menit: latihan berpasangan\n15 menit: refleksi dan umpan balik")} /><InputError message={form.errors.agenda} /></div>
                    <div><InputLabel htmlFor="scheme_duration" value={t("Durasi (menit)")} /><input id="scheme_duration" className={field} required type="number" min={10} max={480} value={form.data.duration_minutes} onChange={e=>form.setData('duration_minutes', e.target.value)} /><InputError message={form.errors.duration_minutes} /></div>
                    <div className="flex items-end gap-3"><PrimaryButton disabled={form.processing}>{editing ? t("Simpan perubahan") : t("Tambah skema")}</PrimaryButton>{editing && <button type="button" disabled={form.processing} onClick={reset}>{t("Batal")}</button>}</div>
                </form>
            </div>
            <InputError message={pageErrors.scheme} />
            <div className="grid gap-4 md:grid-cols-2">{schemes.map(scheme=><div key={scheme.id} className={panel}><h3 className="text-lg font-semibold">{scheme.name}</h3><p className="mt-1 text-sm text-gray-500">{scheme.category} · {scheme.duration_minutes}  {t("menit ·")} {scheme.activities_count}  {t("kegiatan")}</p><h4 className="mt-4 font-medium">{t("Tujuan")}</h4><p className="whitespace-pre-wrap">{scheme.objectives}</p><h4 className="mt-3 font-medium">{t("Agenda")}</h4><p className="whitespace-pre-wrap">{scheme.agenda}</p><div className="mt-4 flex gap-4"><button className="ec-action ec-action-edit" onClick={()=>{setEditing(scheme.id); form.setData({ name: scheme.name, category: scheme.category, objectives: scheme.objectives, agenda: scheme.agenda, duration_minutes: String(scheme.duration_minutes) }); form.clearErrors(); window.scrollTo({ top: 0, behavior: 'smooth' });}}>{t("Edit")}</button><button className="ec-action ec-action-danger" onClick={()=>{if(window.confirm(t("Hapus skema :value1? Agenda kegiatan yang sudah dibuat tetap tersimpan.", { value1: scheme.name }))) router.delete(route('activity-schemes.destroy', scheme.id), { onSuccess: ()=>{if(editing===scheme.id) reset();} });}}>{t("Hapus")}</button></div></div>)}</div>
            {schemes.length===0 && <div className={panel}>{t("Belum ada skema kegiatan. Buat skema pertama untuk menyiapkan agenda latihan.")}</div>}
        </div>
    </AuthenticatedLayout>;
}
