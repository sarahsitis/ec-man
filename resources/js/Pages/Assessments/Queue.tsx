import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Assignment, Paging, Paginator, field, panel, statuses } from '@/Components/AssessmentUi';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const tabs = { submitted: 'Menunggu pemeriksaan', approved: 'Approved · Disahkan', revision: 'Revision · Perlu revisi', rejected: 'Rejected · Ditolak' };
function timestamp(value: string | null) { return value ? new Date(value).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) + ' WIB' : '—'; }

export default function Queue({ assignments, counts, filters }: {
    assignments: Paginator<Assignment>; counts: Record<string, number>; filters: { status: string; search: string };
}) {
    const [search, setSearch] = useState(filters.search);
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Pemeriksaan Rekomendasi</h2>}>
        <Head title="Pemeriksaan Rekomendasi" />
        <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <div className={panel}>
                <div className="flex flex-wrap items-center justify-between gap-4"><div>
                    <h3 className="text-lg font-semibold">{counts.submitted || 0} rekomendasi menunggu pembina</h3>
                    <p className="mt-2 text-sm text-gray-500">Antrean menampilkan pengajuan paling lama terlebih dahulu. Buka rekomendasi untuk memeriksa rubrik dan bukti, lalu sahkan, kembalikan untuk revisi, atau tolak.</p>
                </div><Link href={route('assessments.index')} className="text-indigo-600">Kelola penugasan</Link></div>
                <p className="mt-3 text-sm text-gray-500">Pengesahan menerbitkan nilai resmi yang dapat dilihat siswa. Draf, pengajuan, revisi, dan penolakan belum menjadi nilai resmi.</p>
            </div>
            <div className={panel}>
                <nav aria-label="Status pemeriksaan" className="mb-5 flex flex-wrap gap-3">
                    {Object.entries(tabs).map(([status, label]) => <Link key={status} aria-current={filters.status === status ? 'page' : undefined}
                        href={route('assessments.queue', { status, search: filters.search })}
                        className={`rounded px-3 py-2 text-sm ${filters.status === status ? 'bg-indigo-100 font-semibold text-indigo-900' : 'bg-gray-100 text-gray-700'}`}>
                        {label} ({counts[status] || 0})
                    </Link>)}
                </nav>
                <form className="mb-5 flex flex-wrap items-end gap-3" onSubmit={e => { e.preventDefault(); router.get(route('assessments.queue'), { status: filters.status, search }); }}>
                    <div className="min-w-64 flex-1"><label htmlFor="queue_search" className="text-sm font-medium">Cari siswa, NIS, judul tugas, atau panitia</label>
                        <input id="queue_search" type="search" maxLength={100} className={field} value={search} onChange={e => setSearch(e.target.value)} placeholder="Cari rekomendasi..." /></div>
                    <button className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Cari</button>
                </form>
                <div className="overflow-x-auto"><table className="w-full text-left text-sm">
                    <thead><tr className="border-b">{['Siswa', 'Tugas / Aspek', 'Panitia', 'Diajukan', 'Skor', 'Status', 'Aksi'].map(label => <th key={label} className="p-3">{label}</th>)}</tr></thead>
                    <tbody>{assignments.data.map(item => <tr key={item.id} className="border-b align-top">
                        <td className="p-3 font-medium">{item.student.full_name}<p className="text-xs font-normal text-gray-500">{item.student.student_number} · {item.student.class_name || 'Belum diisi'}</p></td>
                        <td className="p-3">{item.title}<p className="capitalize text-gray-500">{item.aspect}</p></td>
                        <td className="p-3">{item.assessor?.name}</td>
                        <td className="p-3">{timestamp(item.submitted_at)}</td>
                        <td className="p-3">Usulan: {item.proposed_score ?? '—'}/4{item.official_score && <p className="mt-1 font-semibold text-green-700">Resmi: {item.official_score.value}/4</p>}</td>
                        <td className="p-3">{statuses[item.status]}{item.reviewed_at && <p className="mt-1 text-xs text-gray-500">{item.reviewer?.name} · {timestamp(item.reviewed_at)}</p>}</td>
                        <td className="p-3"><Link className="font-medium text-indigo-600" href={route('assessments.show', { assignment: item.id, from: 'queue' })}>{item.status === 'submitted' ? 'Periksa rekomendasi' : 'Lihat keputusan'}</Link></td>
                    </tr>)}{!assignments.data.length && <tr><td colSpan={7} className="p-6 text-center text-gray-500">Tidak ada rekomendasi yang sesuai.</td></tr>}</tbody>
                </table></div>
                <Paging page={assignments} />
            </div>
        </div>
    </AuthenticatedLayout>;
}
