import { useTranslation } from '@/i18n/useTranslation';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Assignment, AssignmentTable, statuses } from '@/Components/AssessmentUi';
import { AssessmentBadge, EmptyState, HeroArt, Icon, IconName, PageHeading } from '@/Components/WorkspaceUi';
import { committeeProgress, dateLabel } from '@/Components/assessmentPresentation';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({ counts, recent, committeeTerm }: {
    counts: Record<string, number>; recent: Assignment[]; committeeTerm: { starts_on: string; ends_on: string };
}) {
    const { t, locale, dateLocale } = useTranslation();
    const user = usePage().props.auth.user;
    const progress = committeeProgress(counts);
    const active = (counts.assigned || 0) + (counts.draft || 0) + (counts.revision || 0);
    const stats: { label: string; value: number; hint: string; icon: IconName }[] = [
        { label: t("Penugasan saya"), value: progress.total, hint: t("Seluruh penugasan dari pembina"), icon: 'people' },
        { label: t("Menunggu pembina"), value: counts.submitted || 0, hint: t("Rekomendasi sudah dikirim"), icon: 'clock' },
        { label: t("Nilai disahkan"), value: counts.approved || 0, hint: t("Nilai resmi diterbitkan"), icon: 'check' },
    ];
    return <WorkspaceLayout><Head title={t("Dashboard Panitia EC")} />
        <PageHeading eyebrow={t("Ruang panitia EC")} title={t("Selamat datang, :value1.", { value1: user.name })} description={t("Setiap pengamatan membantu siswa mengenali kemampuan dan langkah belajarnya.")}
            extra={<div className="ec-term"><Icon name="calendar" />{t("Masa tugas:")} {dateLabel(committeeTerm.starts_on, locale)}–{dateLabel(committeeTerm.ends_on, locale)} (WIB)</div>} />
        <section className="ec-hero"><div className="ec-hero-content"><p className="ec-eyebrow">{t("Asisten penilai pembina")}</p><h2>{t("Bantu setiap potensi tumbuh.")}</h2>
            <p>{progress.total ? t(':count penugasan masih membutuhkan pengamatan atau revisi Anda. Simpan draf, lalu kirim rekomendasi kepada pembina.', { count: active }) : t("Penugasan Anda akan muncul setelah pembina mendelegasikan penilaian. Sementara itu, lihat pre-test dan jadwal kegiatan Anda.")}</p>
            <Link href={route('panitia.assignments')} className="ec-btn ec-btn-primary"><Icon name="clipboard" />{t("Buka penugasan saya")}</Link>
        </div><HeroArt /></section>
        <div className="ec-stats">{stats.map(stat => <section key={stat.label} className="ec-stat"><div className="ec-stat-icon"><Icon name={stat.icon} /></div><div><h2 className="ec-stat-label">{t(stat.label)}</h2><p className="ec-stat-value">{stat.value}</p><p className="ec-stat-hint">{t(stat.hint)}</p></div></section>)}</div>
        <div className="ec-dashboard-grid"><section className="ec-card"><div className="ec-card-header"><div><h2>{t("Penugasan terbaru")}</h2><p>{t("Pengamatan dan rekomendasi untuk siswa yang ditugaskan.")}</p></div><Link className="ec-link" href={route('panitia.assignments')}><Icon name="clipboard" />{t("Lihat penugasan")}</Link></div>
            {recent.length ? <AssignmentTable items={recent} compact /> : <EmptyState title={t("Belum ada penugasan")}><p>{t("Pembina akan menentukan siswa dan aspek yang perlu Anda amati.")}</p></EmptyState>}
        </section>
            <section className="ec-card ec-card-body ec-progress"><h2>{t("Progres penugasan")}</h2><p className="ec-hint">{t("Sudah dikirim atau diputuskan")}</p>
                <div className="ec-progress-ring" role="progressbar" aria-label={t("Progres penugasan yang sudah dikirim atau diputuskan")} aria-valuemin={0} aria-valuemax={100} aria-valuenow={progress.percent} aria-valuetext={`${progress.sent} dari ${progress.total} penugasan`} style={{ background: `conic-gradient(var(--ec-blue) ${progress.percent}%, #eee8fb 0)` }}><div><strong>{progress.percent}%</strong><span>{progress.sent}  {t("dari")} {progress.total}  {t("penugasan")}</span></div></div>
                <div className="ec-legend"><span><i aria-hidden="true" />{t("Dikirim / diputuskan")}</span><strong>{progress.sent}</strong></div><div className="ec-legend"><span><i aria-hidden="true" />{t("Draf / revisi")}</span><strong>{progress.working}</strong></div><div className="ec-legend"><span><i aria-hidden="true" />{t("Belum dinilai")}</span><strong>{progress.unstarted}</strong></div>
                <p className="ec-hint">{t("Dihitung dari status menunggu pembina, disahkan, dan ditolak. Revisi kembali menjadi pekerjaan aktif.")}</p>
                <div className="ec-info"><strong>{t("Anda adalah asisten penilai")}</strong>{t("Rekomendasi baru menjadi nilai resmi setelah disahkan pembina.")}</div>
            </section>
        </div>
        <section className="ec-card mt-6"><div className="ec-card-header"><div><h2>{t("Ringkasan status")}</h2><p>{t("Jumlah penugasan pada setiap tahap.")}</p></div><Link className="ec-link" href={route('panitia.assignments', { status: 'history' })}><Icon name="clock" />{t("Riwayat rekomendasi")}</Link></div>
            <div className="ec-card-body ec-status-list">{['assigned', 'draft', 'revision', 'submitted', 'approved', 'rejected'].map(status => <div className="ec-status-item" key={status}><AssessmentBadge status={status} /><strong aria-label={`${counts[status] || 0} ${t(statuses[status])}`}>{counts[status] || 0}</strong></div>)}</div>
        </section>
    </WorkspaceLayout>;
}
