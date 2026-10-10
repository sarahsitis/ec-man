import { useId } from 'react';

export const pageSizes = [10, 25, 50] as const;

export default function TablePagination({ perPage, currentPage, lastPage, total, from, to, onSizeChange, previous, next, busy = false }: {
    perPage: number; currentPage: number; lastPage: number; total: number; from: number | null; to: number | null;
    onSizeChange: (size: number) => void; previous: React.ReactNode; next: React.ReactNode; busy?: boolean;
}) {
    const id = useId();
    return <nav aria-label="Halaman daftar" aria-busy={busy} className="ec-pagination mt-4 flex flex-wrap items-center justify-between gap-4 text-sm">
        <div className="flex flex-wrap items-center gap-3">
            <label htmlFor={id}>Baris per halaman</label>
            <select id={id} value={perPage} disabled={busy} onChange={event => onSizeChange(Number(event.target.value))} className="rounded-md border-gray-300 bg-white text-sm text-gray-900 disabled:opacity-50">
                {pageSizes.map(size => <option key={size} value={size}>{size}</option>)}
            </select>
            <span role="status">Menampilkan {from ?? 0}–{to ?? 0} dari {total} data</span>
        </div>
        <div className="flex flex-wrap items-center gap-4">{previous}<span>Halaman {currentPage} / {lastPage}</span>{next}</div>
    </nav>;
}
