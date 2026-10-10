import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AcademicYear, ManagementNav, yearLabel } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, router, useForm } from '@inertiajs/react';

export default function Index({ years, defaults }: { years: AcademicYear[]; defaults: { name: string; semester: string } }) {
    const form = useForm({ ...defaults, is_active: true });
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Tahun Ajaran</h2>}>
        <Head title="Tahun Ajaran" /><div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6"><ManagementNav />
            <div className={panel}><h3 className="text-lg font-semibold">Tambah tahun ajaran dan semester</h3><p className="mt-2 text-sm text-gray-500">Keanggotaan dan kegiatan dicatat per semester. Semester aktif menjadi pilihan awal pada formulir.</p>
                <form className="mt-4 space-y-4" onSubmit={e=>{e.preventDefault(); form.post(route('academic-years.store'), { onSuccess: ()=>form.clearErrors() });}}>
                    <div><InputLabel htmlFor="year_name" value="Tahun ajaran" /><input id="year_name" className={field} required pattern="[0-9]{4}/[0-9]{4}" placeholder="2026/2027" value={form.data.name} onChange={e=>form.setData('name', e.target.value)} /><InputError message={form.errors.name} /></div>
                    <div><InputLabel htmlFor="semester" value="Semester" /><select id="semester" className={field} value={form.data.semester} onChange={e=>form.setData('semester', e.target.value)}><option value="ganjil">Ganjil</option><option value="genap">Genap</option></select><InputError message={form.errors.semester} /></div>
                    <label className="flex items-center gap-2"><input type="checkbox" className="rounded text-indigo-600" checked={form.data.is_active} onChange={e=>form.setData('is_active', e.target.checked)} />Jadikan semester aktif</label><InputError message={form.errors.is_active} />
                    <PrimaryButton disabled={form.processing}>Tambah semester</PrimaryButton>
                </form>
            </div>
            <div className={panel}><h3 className="mb-4 text-lg font-semibold">Daftar semester</h3><div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b">{['Semester', 'Anggota', 'Kegiatan', 'Aksi'].map(label=><th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{years.map(year=><tr key={year.id} className="border-b"><td className="p-3">{yearLabel(year)}</td><td className="p-3">{year.memberships_count}</td><td className="p-3">{year.activities_count}</td><td className="p-3">{year.is_active ? 'Aktif' : <button className="text-indigo-600" onClick={()=>router.post(route('academic-years.activate', year.id))}>Aktifkan</button>}</td></tr>)}{years.length===0 && <tr><td colSpan={4} className="p-4 text-center text-gray-500">Belum ada tahun ajaran.</td></tr>}</tbody></table></div></div>
        </div>
    </AuthenticatedLayout>;
}
