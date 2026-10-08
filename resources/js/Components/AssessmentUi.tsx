import { Link } from '@inertiajs/react';
export interface Assignment {
    id: number; title: string; aspect: string; status: string; due_date: string | null;
    student: { id: number; user_id?: number; full_name: string; student_number: string; class_name?: string };
    assessor?: { id: number; name: string }; reviewer?: { name: string } | null;
    proposed_score: number | null; observations: string | null; feedback: string | null;
    final_score: number | null; review_note: string | null; reviewed_at: string | null;
    rubric: { version: string; levels: Record<string, string> };
    events?: { id: number; action: string; details: Record<string, unknown> | null; created_at: string }[];
}
export interface Paginator<T> { data: T[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }
export const statuses: Record<string,string> = {
    assigned: 'Belum dinilai', draft: 'Draf', submitted: 'Menunggu pembina', revision: 'Perlu revisi', approved: 'Disahkan', rejected: 'Ditolak', cancelled: 'Dibatalkan',
};
export function Paging<T>({ page }: { page: Paginator<T> }) {
    return <div className="mt-4 flex flex-wrap items-center gap-4 text-sm">
        {page.prev_page_url && <Link href={page.prev_page_url} className="text-indigo-600">Sebelumnya</Link>}
        <span>Halaman {page.current_page} / {page.last_page}</span>
        {page.next_page_url && <Link href={page.next_page_url} className="text-indigo-600">Berikutnya</Link>}
    </div>;
}
export function AssignmentTable({ items }: { items: Assignment[] }) {
    return <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b">
        <th className="p-3">Siswa</th><th className="p-3">Tugas / Aspek</th><th className="p-3">Panitia</th><th className="p-3">Batas waktu</th><th className="p-3">Status</th><th className="p-3">Aksi</th>
    </tr></thead><tbody>{items.map(item => <tr key={item.id} className="border-b">
        <td className="p-3">{item.student.full_name}<div className="text-xs text-gray-500">{item.student.student_number} · {item.student.class_name || 'Kelas belum diisi'}</div></td>
        <td className="p-3">{item.title}<div className="capitalize text-gray-500">{item.aspect}</div></td>
        <td className="p-3">{item.assessor?.name || '—'}</td><td className="p-3">{item.due_date || 'Tanpa batas'}</td>
        <td className="p-3">{statuses[item.status] || item.status}</td><td className="p-3"><Link href={route('assessments.show', item.id)} className="font-medium text-indigo-600">Buka</Link></td>
    </tr>)}{items.length === 0 && <tr><td colSpan={6} className="p-6 text-center text-gray-500">Belum ada penugasan pada daftar ini.</td></tr>}</tbody></table></div>;
}
export const panel = 'rounded-lg bg-white p-6 shadow dark:bg-gray-800 dark:text-gray-100';
export const field = 'mt-1 block w-full rounded-md border-gray-300 text-gray-900';
