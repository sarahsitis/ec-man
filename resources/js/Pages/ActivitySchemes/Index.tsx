import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ActivityScheme, ManagementNav } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ schemes }: { schemes: ActivityScheme[] }) {
    const form = useForm({ name: '', category: '', objectives: '', agenda: '', duration_minutes: '60' });
    const [editing, setEditing] = useState<number | null>(null);
    const pageErrors = usePage().props.errors;
    const reset = () => { setEditing(null); form.reset(); form.clearErrors(); };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Skema Kegiatan</h2>}>
        <Head title="Skema Kegiatan" /><div className="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6"><ManagementNav />
            <div className={panel}><h3 className="text-lg font-semibold">{editing ? 'Edit skema' : 'Buat skema kegiatan'}</h3><p className="mt-2 text-sm text-gray-500">Skema merupakan template yang dapat diterapkan saat membuat kegiatan. Tujuan dan agenda disalin ke kegiatan agar catatannya tetap utuh ketika template berubah.</p>
                <form className="mt-4 grid gap-4 sm:grid-cols-2" onSubmit={e=>{e.preventDefault(); const options = { onSuccess: reset }; editing ? form.put(route('activity-schemes.update', editing), options) : form.post(route('activity-schemes.store'), options);}}>
                    <div><InputLabel htmlFor="scheme_name" value="Nama skema" /><input id="scheme_name" className={field} required maxLength={150} value={form.data.name} onChange={e=>form.setData('name', e.target.value)} placeholder="Latihan percakapan mingguan" /><InputError message={form.errors.name} /></div>
                    <div><InputLabel htmlFor="scheme_category" value="Kategori" /><input id="scheme_category" className={field} required maxLength={100} value={form.data.category} onChange={e=>form.setData('category', e.target.value)} placeholder="Percakapan / Debat / Storytelling" /><InputError message={form.errors.category} /></div>
                    <div className="sm:col-span-2"><InputLabel htmlFor="scheme_objectives" value="Tujuan kegiatan" /><textarea id="scheme_objectives" className={field} required rows={3} maxLength={5000} value={form.data.objectives} onChange={e=>form.setData('objectives', e.target.value)} /><InputError message={form.errors.objectives} /></div>
                    <div className="sm:col-span-2"><InputLabel htmlFor="scheme_agenda" value="Susunan agenda" /><textarea id="scheme_agenda" className={field} required rows={5} maxLength={10000} value={form.data.agenda} onChange={e=>form.setData('agenda', e.target.value)} placeholder={'10 menit: pemanasan\n35 menit: latihan berpasangan\n15 menit: refleksi dan umpan balik'} /><InputError message={form.errors.agenda} /></div>
                    <div><InputLabel htmlFor="scheme_duration" value="Durasi (menit)" /><input id="scheme_duration" className={field} required type="number" min={10} max={480} value={form.data.duration_minutes} onChange={e=>form.setData('duration_minutes', e.target.value)} /><InputError message={form.errors.duration_minutes} /></div>
                    <div className="flex items-end gap-3"><PrimaryButton disabled={form.processing}>{editing ? 'Simpan perubahan' : 'Tambah skema'}</PrimaryButton>{editing && <button type="button" disabled={form.processing} onClick={reset}>Batal</button>}</div>
                </form>
            </div>
            <InputError message={pageErrors.scheme} />
            <div className="grid gap-4 md:grid-cols-2">{schemes.map(scheme=><div key={scheme.id} className={panel}><h3 className="text-lg font-semibold">{scheme.name}</h3><p className="mt-1 text-sm text-gray-500">{scheme.category} · {scheme.duration_minutes} menit · {scheme.activities_count} kegiatan</p><h4 className="mt-4 font-medium">Tujuan</h4><p className="whitespace-pre-wrap">{scheme.objectives}</p><h4 className="mt-3 font-medium">Agenda</h4><p className="whitespace-pre-wrap">{scheme.agenda}</p><div className="mt-4 flex gap-4"><button className="text-indigo-600" onClick={()=>{setEditing(scheme.id); form.setData({ name: scheme.name, category: scheme.category, objectives: scheme.objectives, agenda: scheme.agenda, duration_minutes: String(scheme.duration_minutes) }); form.clearErrors(); window.scrollTo({ top: 0, behavior: 'smooth' });}}>Edit</button><button className="text-red-600" onClick={()=>{if(window.confirm(`Hapus skema ${scheme.name}? Agenda kegiatan yang sudah dibuat tetap tersimpan.`)) router.delete(route('activity-schemes.destroy', scheme.id), { onSuccess: ()=>{if(editing===scheme.id) reset();} });}}>Hapus</button></div></div>)}</div>
            {schemes.length===0 && <div className={panel}>Belum ada skema kegiatan. Buat skema pertama untuk menyiapkan agenda latihan.</div>}
        </div>
    </AuthenticatedLayout>;
}
