import TablePagination from './TablePagination';
import useTablePagination from '@/hooks/useTablePagination';

export default function ClientTablePaging<T>({ pagination }: { pagination: ReturnType<typeof useTablePagination<T>> }) {
    return <TablePagination {...pagination}
        previous={<button type="button" disabled={pagination.currentPage <= 1} onClick={() => pagination.go(pagination.currentPage - 1)} className="text-indigo-600 disabled:text-gray-400">Sebelumnya</button>}
        next={<button type="button" disabled={pagination.currentPage >= pagination.lastPage} onClick={() => pagination.go(pagination.currentPage + 1)} className="text-indigo-600 disabled:text-gray-400">Berikutnya</button>} />;
}
