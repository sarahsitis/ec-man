import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Assignment, Paging, Paginator } from '@/Components/AssessmentUi';
import { AssessmentBadge, Avatar, EmptyState, Icon, PageHeading } from '@/Components/WorkspaceUi';
import { timeLabel } from '@/Components/assessmentPresentation';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const tabs = { submitted: 'Menunggu pemeriksaan', approved: 'Disahkan', revision: 'Perlu revisi', rejected: 'Ditolak' };
export default function Queue({ assignments, counts, filters }: {
    assignments: Paginator<Assignment>; counts: Record<string, number>; filters: { status: string; search: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const errors = usePage().props.errors;
    return <WorkspaceLayout><Head title="Pemeriksaan Rekomendasi" />
        <PageHeading eyebrow="Ruang pembina" title="Pemeriksaan rekomendasi" description="Tinjau pengamatan panitia, berikan arahan revisi, atau sahkan nilai resmi siswa."
            extra={<Link href={route('assessments.index')} className="ec-btn"><Icon name="people" />Kelola penugasan</Link>} />
        <div className="ec-notice ec-notice-success"><strong>{counts.submitted || 0} rekomendasi menunggu pemeriksaan</strong>Antrean menampilkan pengajuan paling lama terlebih dahulu. Pengesahan menerbitkan nilai resmi; draf, pengajuan, revisi, dan penolakan belum menjadi nilai resmi.</div>
        <section className="ec-card"><div className="ec-toolbar"><nav aria-label="Status pemeriksaan" className="ec-tabs">{Object.entries(tabs).map(([status, label]) => <Link key={status} aria-current={filters.status === status ? 'page' : undefined} href={route('assessments.queue', { status, search: filters.search, per_page: assignments.per_page })}>{label} <span>({counts[status] || 0})</span></Link>)}</nav></div>
            <form className="ec-toolbar" onSubmit={event => { event.preventDefault(); router.get(route('assessments.queue'), { status: filters.status, search, per_page: assignments.per_page }); }}>
                <div className="min-w-0 flex-1"><label htmlFor="queue_search" className="ec-label">Cari siswa, NIS, judul tugas, atau panitia</label><input id="queue_search" type="search" maxLength={100} className="ec-input" value={search} onChange={event => setSearch(event.target.value)} placeholder="Cari rekomendasi…" aria-invalid={!!errors.search} aria-describedby={errors.search ? 'queue_search_error' : undefined} />{errors.search && <p id="queue_search_error" role="alert" className="ec-error">{errors.search}</p>}</div><button className="ec-btn ec-btn-primary">Cari</button>
            </form>
            {assignments.data.length ? <div className="ec-table-wrap" role="region" aria-label="Tabel rekomendasi siswa, geser untuk melihat seluruh kolom" tabIndex={0}><table className="ec-table"><caption className="sr-only">Antrean rekomendasi dan keputusan pembina</caption><thead><tr>{['Siswa', 'Tugas / Aspek', 'Panitia', 'Diajukan', 'Skor', 'Status', 'Aksi'].map(label => <th scope="col" key={label}>{label}</th>)}</tr></thead>
                <tbody>{assignments.data.map(item => <tr key={item.id}><td><div className="ec-person"><Avatar name={item.student.full_name} /><div><strong>{item.student.full_name}</strong><small>{item.student.student_number} · {item.student.class_name || 'Kelas belum diisi'}</small></div></div></td>
                    <td>{item.title}<span className="ec-table-detail capitalize">{item.aspect}</span></td><td>{item.assessor?.name}</td><td>{timeLabel(item.submitted_at)}</td>
                    <td>Usulan: {item.proposed_score ?? '—'}/4{item.official_score && <span className="ec-table-detail font-semibold">Resmi: {item.official_score.value}/4</span>}</td>
                    <td><AssessmentBadge status={item.status} />{item.reviewed_at && <span className="ec-table-detail">{item.reviewer?.name} · {timeLabel(item.reviewed_at)}</span>}</td>
                    <td><Link className="ec-btn ec-btn-soft ec-btn-small" href={route('assessments.show', { assignment: item.id, from: 'queue' })} aria-label={`${item.status === 'submitted' ? 'Periksa rekomendasi' : 'Lihat keputusan'} untuk ${item.student.full_name}`}>{item.status === 'submitted' ? 'Periksa' : 'Lihat keputusan'}<Icon name="arrow" /></Link></td>
                </tr>)}</tbody></table></div> : <EmptyState title="Tidak ada rekomendasi yang sesuai"><p>Ubah pencarian atau pilih status lain untuk melihat rekomendasi.</p><Link href={route('assessments.queue')} className="ec-btn ec-btn-soft mt-4">Buka antrean menunggu</Link></EmptyState>}
            <Paging page={assignments} />
        </section>
    </WorkspaceLayout>;
}
