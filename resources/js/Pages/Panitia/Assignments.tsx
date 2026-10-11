import { useTranslation } from '@/i18n/useTranslation';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Assignment, AssignmentTable, Paging, Paginator } from '@/Components/AssessmentUi';
import { EmptyState, Icon, PageHeading } from '@/Components/WorkspaceUi';
import { Head, Link } from '@inertiajs/react';

export default function Assignments({ assignments, status }: { assignments: Paginator<Assignment>; status: string }) {
    const { t, locale, dateLocale } = useTranslation();
    const history = status === 'history';
    const title = history ? t("Riwayat rekomendasi") : t("Penugasan saya");
    return <WorkspaceLayout><Head title={title} />
        <PageHeading eyebrow={t("Ruang panitia EC")} title={title} description={history ? t("Lihat rekomendasi yang menunggu pembina, disahkan, atau ditolak.") : t("Catat pengamatan untuk penugasan baru, lanjutkan draf, dan perbaiki rekomendasi yang dikembalikan.")} />
        <div className="ec-notice ec-notice-success"><strong>{t("Penilaian sesuai penugasan")}</strong>{t("Daftar hanya mencakup penugasan Anda. Gunakan rubrik masing-masing aspek dan skor 1–4 sebelum mengirim rekomendasi.")}</div>
        <section className="ec-card"><div className="ec-toolbar"><nav className="ec-tabs" aria-label={t("Jenis daftar penugasan")}>
            <Link href={route('panitia.assignments', { per_page: assignments.per_page })} aria-current={!history ? 'page' : undefined}>{t("Aktif / Revisi")}</Link>
            <Link href={route('panitia.assignments', { status: 'history', per_page: assignments.per_page })} aria-current={history ? 'page' : undefined}>{t("Riwayat pengajuan")}</Link>
        </nav></div><div className="ec-card-header"><div><h2>{history ? t("Pengajuan dan keputusan pembina") : t("Siswa yang ditugaskan")}</h2><p>{assignments.total ?? assignments.data.length}  {t("penugasan dalam daftar ini.")}</p></div><Link href={route('panitia.dashboard')} className="ec-link"><Icon name="grid" />{t("Dashboard")}</Link></div>
            {assignments.data.length ? <AssignmentTable items={assignments.data} /> : <EmptyState title={history ? t("Belum ada pengajuan") : t("Tidak ada penugasan aktif")}><p>{history ? t("Rekomendasi akan tampil di sini setelah Anda mengirimnya kepada pembina.") : t("Penugasan baru dan permintaan revisi dari pembina akan muncul di sini.")}</p><Link href={route('panitia.assignments', { status: history ? 'active' : 'history', per_page: assignments.per_page })} className="ec-btn ec-btn-soft mt-4">{history ? t("Buka penugasan aktif") : t("Lihat riwayat rekomendasi")}</Link></EmptyState>}
            <Paging page={assignments} />
        </section>
    </WorkspaceLayout>;
}
