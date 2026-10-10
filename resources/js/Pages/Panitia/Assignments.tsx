import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Assignment, AssignmentTable, Paging, Paginator } from '@/Components/AssessmentUi';
import { EmptyState, Icon, PageHeading } from '@/Components/WorkspaceUi';
import { Head, Link } from '@inertiajs/react';

export default function Assignments({ assignments, status }: { assignments: Paginator<Assignment>; status: string }) {
    const history = status === 'history';
    const title = history ? 'Riwayat rekomendasi' : 'Penugasan saya';
    return <WorkspaceLayout><Head title={title} />
        <PageHeading eyebrow="Ruang panitia EC" title={title} description={history ? 'Lihat rekomendasi yang menunggu pembina, disahkan, atau ditolak.' : 'Catat pengamatan untuk penugasan baru, lanjutkan draf, dan perbaiki rekomendasi yang dikembalikan.'} />
        <div className="ec-notice ec-notice-success"><strong>Penilaian sesuai penugasan</strong>Daftar hanya mencakup penugasan Anda. Gunakan rubrik masing-masing aspek dan skor 1–4 sebelum mengirim rekomendasi.</div>
        <section className="ec-card"><div className="ec-toolbar"><nav className="ec-tabs" aria-label="Jenis daftar penugasan">
            <Link href={route('panitia.assignments', { per_page: assignments.per_page })} aria-current={!history ? 'page' : undefined}>Aktif / Revisi</Link>
            <Link href={route('panitia.assignments', { status: 'history', per_page: assignments.per_page })} aria-current={history ? 'page' : undefined}>Riwayat pengajuan</Link>
        </nav></div><div className="ec-card-header"><div><h2>{history ? 'Pengajuan dan keputusan pembina' : 'Siswa yang ditugaskan'}</h2><p>{assignments.total ?? assignments.data.length} penugasan dalam daftar ini.</p></div><Link href={route('panitia.dashboard')} className="ec-link">Dashboard<Icon name="arrow" /></Link></div>
            {assignments.data.length ? <AssignmentTable items={assignments.data} /> : <EmptyState title={history ? 'Belum ada pengajuan' : 'Tidak ada penugasan aktif'}><p>{history ? 'Rekomendasi akan tampil di sini setelah Anda mengirimnya kepada pembina.' : 'Penugasan baru dan permintaan revisi dari pembina akan muncul di sini.'}</p><Link href={route('panitia.assignments', { status: history ? 'active' : 'history', per_page: assignments.per_page })} className="ec-btn ec-btn-soft mt-4">{history ? 'Buka penugasan aktif' : 'Lihat riwayat rekomendasi'}</Link></EmptyState>}
            <Paging page={assignments} />
        </section>
    </WorkspaceLayout>;
}
