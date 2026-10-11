import { useTranslation } from '@/i18n/useTranslation';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TablePagination from './TablePagination';
import { AssessmentBadge, Avatar, Icon } from './WorkspaceUi';
import { assessmentStatuses, dateLabel } from './assessmentPresentation';
export interface Assignment {
    id: number; assessment_id: number; title: string; aspect: string; status: string; due_date: string | null;
    assessment?: { instructions: string | null };
    official_score?: { id: number; value: number; approved_at?: string | null; approver?: { name: string } | null } | null;
    submitted_at: string | null;
    student: { id: number; user_id?: number; full_name: string; student_number: string; class_name?: string };
    assessor?: { id: number; name: string }; reviewer?: { name: string } | null;
    proposed_score: number | null; observations: string | null; feedback: string | null;
    final_score: number | null; review_note: string | null; reviewed_at: string | null;
    rubric: { version: string; levels: Record<string, string> };
    events?: { id: number; action: string; details: Record<string, unknown> | null; created_at: string }[];
}
export interface Paginator<T> { data: T[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; total: number; per_page: number; from: number | null; to: number | null }
export const statuses = assessmentStatuses;
export function Paging<T>({ page }: { page: Paginator<T> }) {
    const { t, locale, dateLocale } = useTranslation();
    const { url } = usePage();
    const [busy, setBusy] = useState(false);
    const changeSize = (size: number) => {
        const destination = new URL(url, window.location.origin);
        destination.searchParams.set('per_page', String(size));
        destination.searchParams.delete('page');
        router.get(destination.pathname + destination.search, {}, {
            preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false),
        });
    };
    return <TablePagination perPage={page.per_page} currentPage={page.current_page} lastPage={page.last_page}
        total={page.total} from={page.from} to={page.to} onSizeChange={changeSize} busy={busy}
        previous={page.prev_page_url && !busy ? <Link href={page.prev_page_url} className="ec-action ec-action-primary" rel="prev" preserveScroll>{t("Sebelumnya")}</Link> : <span className="ec-action ec-action-secondary" aria-disabled="true">{t("Sebelumnya")}</span>}
        next={page.next_page_url && !busy ? <Link href={page.next_page_url} className="ec-action ec-action-primary" rel="next" preserveScroll>{t("Berikutnya")}</Link> : <span className="ec-action ec-action-secondary" aria-disabled="true">{t("Berikutnya")}</span>} />;
}
export function AssignmentTable({ items, compact = false }: { items: Assignment[]; compact?: boolean }) {
    const { t, locale, dateLocale } = useTranslation();
    return <div className="ec-table-wrap overflow-x-auto" role="region" aria-label={t("Tabel penugasan siswa, geser untuk melihat seluruh kolom")} tabIndex={0}><table className={`ec-table ${compact ? 'ec-table-compact' : ''} w-full text-left text-sm`}><caption className="sr-only">{t("Penugasan siswa dan status rekomendasi")}</caption><thead><tr className="border-b">
        <th scope="col" className="p-3">{t("Siswa")}</th><th scope="col" className="p-3">{t("Tugas / Aspek")}</th>{!compact && <th scope="col" className="p-3">{t("Panitia")}</th>}<th scope="col" className="p-3">{t("Batas waktu")}</th><th scope="col" className="p-3">{t("Status")}</th><th scope="col" className="p-3">{t("Aksi")}</th>
    </tr></thead><tbody>{items.map(item => <tr key={item.id} className="border-b">
        <td className="p-3"><div className="ec-person"><Avatar name={item.student.full_name} /><div><strong>{item.student.full_name}</strong><small>{item.student.student_number} · {item.student.class_name || 'Kelas belum diisi'}</small></div></div></td>
        <td className="p-3">{item.title}<div className="ec-table-detail capitalize text-gray-500">{item.aspect}</div></td>
        {!compact && <td className="p-3">{item.assessor?.name || '—'}</td>}<td className="p-3">{dateLabel(item.due_date, locale)}<span className="ec-table-detail">WIB</span></td>
        <td className="p-3"><AssessmentBadge status={item.status} />{item.official_score && <span className="ec-table-detail">{t("Nilai resmi")} {item.official_score.value}/4</span>}</td><td className="p-3"><Link href={route('assessments.show', item.id)} className="ec-btn ec-btn-soft ec-btn-small font-medium text-indigo-600" aria-label={t("Buka penugasan :value1 untuk :value2", { value1: item.title, value2: item.student.full_name })}><Icon name={['assigned', 'draft', 'revision'].includes(item.status) ? 'clipboard' : 'chart'} />{['assigned', 'draft', 'revision'].includes(item.status) ? t("Buka penilaian") : t("Lihat hasil")}</Link></td>
    </tr>)}{items.length === 0 && <tr><td colSpan={compact ? 5 : 6} className="p-6 text-center text-gray-500">{t("Belum ada penugasan pada daftar ini.")}</td></tr>}</tbody></table></div>;
}
export const panel = 'ec-standard-panel rounded-lg bg-white p-6 shadow dark:bg-gray-800 dark:text-gray-100';
export const field = 'ec-standard-input mt-1 block w-full rounded-md border-gray-300 text-gray-900';
