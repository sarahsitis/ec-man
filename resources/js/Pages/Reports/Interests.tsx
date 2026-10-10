import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { field, panel } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Distribution { id: number; name: string; selections_count: number; primary_count: number; percentage: number; primary_percentage: number }
export default function Interests({ distribution, counts, filters, classes, statuses }: {
    distribution: Distribution[]; counts: { total: number; respondents: number; pending: number; selections: number };
    filters: { status: string; class_name: string }; classes: string[]; statuses: Record<string, string>;
}) {
    const [status, setStatus] = useState(filters.status); const [className, setClassName] = useState(filters.class_name);
    const [mode, setMode] = useState<'all' | 'primary'>('all');
    const errors = usePage().props.errors;
    const sorted = [...distribution].sort((a, b) => (mode === 'all' ? b.selections_count - a.selections_count : b.primary_count - a.primary_count) || a.name.localeCompare(b.name, 'id-ID'));
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Distribusi Minat Siswa</h2>}>
        <Head title="Distribusi Minat Siswa" /><div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <div className={panel}><div className="flex flex-wrap justify-between gap-3"><h3 className="text-lg font-semibold">Pemetaan minat EC</h3><Link href={route('pretests.reports')} className="text-indigo-600">Lihat hasil pre-test</Link></div>
                <p className="mt-2 text-sm text-gray-500">Gunakan distribusi minat untuk merencanakan kelompok latihan dan kegiatan. Ringkasan mencakup seluruh siswa yang sesuai filter, termasuk siswa yang belum mencatat minat.</p>
                <form className="mt-5 flex flex-wrap items-end gap-3" onSubmit={e => { e.preventDefault(); router.get(route('reports.interests'), { status, class_name: className }); }}>
                    <div className="min-w-56"><label htmlFor="interest_class" className="text-sm font-medium">Kelas siswa saat ini</label><select id="interest_class" className={field} value={className} onChange={e => setClassName(e.target.value)}><option value="">Semua kelas</option>{classes.map(value => <option key={value} value={value}>{value}</option>)}</select><InputError message={errors.class_name} /></div>
                    <div><label htmlFor="interest_status" className="text-sm font-medium">Status siswa</label><select id="interest_status" className={field} value={status} onChange={e => setStatus(e.target.value)}><option value="all">Semua status</option>{Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select><InputError message={errors.status} /></div>
                    <button className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Terapkan filter</button><button type="button" className="px-3 py-2 text-sm text-indigo-600" onClick={() => router.get(route('reports.interests'))}>Reset</button>
                </form>
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{[[counts.total, 'Siswa sesuai filter'], [counts.respondents, 'Sudah mencatat minat'], [counts.pending, 'Belum mencatat minat'], [counts.selections, 'Total pilihan minat']].map(([value, label]) => <div key={label} className={panel}><p className="text-sm text-gray-500">{label}</p><p className="mt-2 text-3xl font-semibold">{value}</p></div>)}</div>
            <div className={panel}><div className="flex flex-wrap items-center justify-between gap-3"><h3 className="text-lg font-semibold">Grafik distribusi minat</h3><div role="group" aria-label="Pilihan distribusi minat" className="flex flex-wrap gap-2">{(['all', 'primary'] as const).map(value => <button key={value} type="button" aria-pressed={mode === value} className={`rounded px-3 py-2 text-sm ${mode === value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'}`} onClick={() => setMode(value)}>{value === 'all' ? 'Semua pilihan minat' : 'Minat utama'}</button>)}</div></div>
                <p className="mt-3 text-sm text-gray-500">Persentase dihitung dari {counts.respondents} siswa yang telah mencatat minat. {mode === 'all' ? 'Satu siswa dapat memilih beberapa minat, sehingga jumlah persentase dapat melebihi 100%.' : 'Setiap siswa memilih satu minat utama; pilihan pendukung tidak dihitung pada tampilan ini.'}</p>
                {!counts.respondents && <p className="my-5 rounded bg-gray-50 p-4 text-sm text-gray-600">Belum ada data minat untuk kelompok ini. Angka nol berarti belum ada siswa yang memilih, dan tidak menunjukkan bahwa siswa menolak kegiatan tersebut.</p>}
                <div className="my-6 space-y-5" role="img" aria-label={`Grafik bar ${mode === 'all' ? 'seluruh pilihan minat' : 'minat utama'} siswa`}>
                    {sorted.map(interest => { const count = mode === 'all' ? interest.selections_count : interest.primary_count; const percentage = mode === 'all' ? interest.percentage : interest.primary_percentage; return <div key={interest.id}>
                        <div className="mb-2 flex flex-wrap justify-between gap-2 text-sm"><span className="font-medium">{interest.name}</span><span>{count} siswa · {percentage.toLocaleString('id-ID')}%</span></div>
                        <div className="h-5 overflow-hidden rounded bg-gray-100 dark:bg-gray-700"><div className={`h-full rounded ${mode === 'all' ? 'bg-indigo-600' : 'bg-teal-600'}`} style={{ width: `${percentage}%` }} /></div>
                    </div>; })}
                </div>
                <div className="overflow-x-auto"><table className="w-full text-left text-sm"><caption className="sr-only">Rincian distribusi minat untuk seluruh kelompok yang dipilih</caption><thead><tr className="border-b">{['Minat', 'Jumlah peminat', '% peminat', 'Minat utama', '% utama'].map(label => <th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{sorted.map(interest => <tr key={interest.id} className="border-b"><th scope="row" className="p-3 font-medium">{interest.name}</th><td className="p-3">{interest.selections_count}</td><td className="p-3">{interest.percentage.toLocaleString('id-ID')}%</td><td className="p-3">{interest.primary_count}</td><td className="p-3">{interest.primary_percentage.toLocaleString('id-ID')}%</td></tr>)}{!sorted.length && <tr><td colSpan={5} className="p-6 text-center text-gray-500">Kategori minat belum tersedia.</td></tr>}</tbody></table></div>
            </div>
        </div>
    </AuthenticatedLayout>;
}
